<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MODULO 2 - Catalogo academico: modulos, programas y su estructura.
 */
class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([
            Rol::create(['nombre' => User::ROL_ADMINISTRADOR])->id,
        ]);

        $this->actingAs($this->admin);
    }

    /*
    |--------------------------------------------------------------------------
    | Modulos
    |--------------------------------------------------------------------------
    */

    public function test_se_puede_crear_un_modulo_y_nace_en_borrador(): void
    {
        $this->post(route('modulos.store'), [
            'codigo' => 'sql-01',
            'nombre' => 'SQL',
            'descripcion' => 'Consultas relacionales.',
            'requisitos' => 'Nociones de bases de datos',
            'temario' => "1. SELECT\n2. JOINs",
            'horas' => 32,
            'precio' => 850,
            'se_oferta_por_separado' => '1',
        ])->assertRedirect();

        // prepareForValidation pasa el codigo a mayusculas.
        $this->assertDatabaseHas('modulos', [
            'codigo' => 'SQL-01',
            'estado_comercial' => EstadoComercial::Borrador->value,
        ]);
    }

    /** RN 2.1: el codigo de modulo es unico. */
    public function test_el_codigo_de_modulo_es_unico(): void
    {
        Modulo::factory()->create(['codigo' => 'SQL-01']);

        $this->post(route('modulos.store'), $this->datosModulo(['codigo' => 'SQL-01']))
            ->assertSessionHasErrors('codigo');
    }

    /** RN 2.10: horas > 0 y precio >= 0. */
    public function test_rechaza_horas_cero_y_precio_negativo(): void
    {
        $this->post(route('modulos.store'), $this->datosModulo(['horas' => 0]))
            ->assertSessionHasErrors('horas');

        $this->post(route('modulos.store'), $this->datosModulo(['precio' => -1]))
            ->assertSessionHasErrors('precio');
    }

    /** RN 2.4: para abrir, el registro debe estar completo. */
    public function test_un_modulo_sin_descripcion_no_se_puede_abrir(): void
    {
        $modulo = Modulo::factory()->create(['descripcion' => null]);

        $this->patch(route('modulos.estado', $modulo), ['estado_comercial' => 'abierto'])
            ->assertSessionHas('error');

        $this->assertSame(EstadoComercial::Borrador, $modulo->fresh()->estado_comercial);
    }

    public function test_un_modulo_completo_si_se_puede_abrir(): void
    {
        $modulo = Modulo::factory()->create(['descripcion' => 'Tiene descripcion.']);

        $this->patch(route('modulos.estado', $modulo), ['estado_comercial' => 'abierto'])
            ->assertSessionHas('exito');

        $this->assertSame(EstadoComercial::Abierto, $modulo->fresh()->estado_comercial);
    }

    public function test_no_admite_una_transicion_de_estado_invalida(): void
    {
        $modulo = Modulo::factory()->create(['estado_comercial' => EstadoComercial::Borrador]);

        // borrador -> cerrado no existe en la maquina de estados.
        $this->patch(route('modulos.estado', $modulo), ['estado_comercial' => 'cerrado'])
            ->assertSessionHas('error');

        $this->assertSame(EstadoComercial::Borrador, $modulo->fresh()->estado_comercial);
    }

    /** RN 2.7: no se elimina un modulo con relaciones. */
    public function test_no_se_elimina_un_modulo_que_esta_en_un_programa(): void
    {
        $programa = Programa::factory()->create(['cantidad_modulos' => 1]);
        $modulo = Modulo::factory()->create();
        $programa->modulos()->attach($modulo->id, ['orden' => 1]);

        $this->delete(route('modulos.destroy', $modulo))->assertSessionHas('error');

        $this->assertDatabaseHas('modulos', ['id' => $modulo->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Programas y estructura
    |--------------------------------------------------------------------------
    */

    public function test_se_puede_crear_un_programa_y_va_a_su_estructura(): void
    {
        $this->post(route('programas.store'), [
            'codigo' => 'dae-2025',
            'nombre' => 'Data Analysis Expert',
            'descripcion' => 'Seis modulos de analisis de datos.',
            'cantidad_modulos' => 6,
            'precio_contado' => 4200,
        ])->assertRedirect(route('programas.estructura', Programa::where('codigo', 'DAE-2025')->first()));

        $this->assertDatabaseHas('programas', ['codigo' => 'DAE-2025', 'cantidad_modulos' => 6]);
    }

    public function test_se_agregan_modulos_al_programa_en_orden_creciente(): void
    {
        $programa = Programa::factory()->create(['cantidad_modulos' => 3]);
        $a = Modulo::factory()->create(['nombre' => 'Excel']);
        $b = Modulo::factory()->create(['nombre' => 'Power BI']);

        $this->post(route('programas.modulos.agregar', $programa), ['modulo_id' => $a->id]);
        $this->post(route('programas.modulos.agregar', $programa), ['modulo_id' => $b->id]);

        $this->assertDatabaseHas('programa_modulo', ['programa_id' => $programa->id, 'modulo_id' => $a->id, 'orden' => 1]);
        $this->assertDatabaseHas('programa_modulo', ['programa_id' => $programa->id, 'modulo_id' => $b->id, 'orden' => 2]);
    }

    /** RN 2.2: un modulo no se agrega dos veces al mismo programa. */
    public function test_no_se_agrega_el_mismo_modulo_dos_veces(): void
    {
        $programa = Programa::factory()->create(['cantidad_modulos' => 2]);
        $modulo = Modulo::factory()->create();

        $this->post(route('programas.modulos.agregar', $programa), ['modulo_id' => $modulo->id]);
        $this->post(route('programas.modulos.agregar', $programa), ['modulo_id' => $modulo->id])
            ->assertSessionHas('error');

        $this->assertSame(1, $programa->modulos()->count());
    }

    /** RN 2.3: dos modulos no ocupan la misma posicion. */
    public function test_mover_un_modulo_intercambia_el_orden(): void
    {
        $programa = Programa::factory()->create(['cantidad_modulos' => 3]);
        $a = Modulo::factory()->create(['nombre' => 'Primero']);
        $b = Modulo::factory()->create(['nombre' => 'Segundo']);

        $programa->modulos()->attach($a->id, ['orden' => 1]);
        $programa->modulos()->attach($b->id, ['orden' => 2]);

        $this->patch(route('programas.modulos.mover', [$programa, $b]), ['direccion' => 'arriba'])
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('programa_modulo', ['modulo_id' => $b->id, 'orden' => 1]);
        $this->assertDatabaseHas('programa_modulo', ['modulo_id' => $a->id, 'orden' => 2]);
    }

    public function test_quitar_un_modulo_recompacta_el_orden(): void
    {
        $programa = Programa::factory()->create(['cantidad_modulos' => 3]);
        $a = Modulo::factory()->create();
        $b = Modulo::factory()->create();
        $c = Modulo::factory()->create();

        $programa->modulos()->attach($a->id, ['orden' => 1]);
        $programa->modulos()->attach($b->id, ['orden' => 2]);
        $programa->modulos()->attach($c->id, ['orden' => 3]);

        $this->delete(route('programas.modulos.quitar', [$programa, $b]))->assertSessionHas('exito');

        // Sin huecos: el tercero pasa a ser el segundo.
        $this->assertDatabaseHas('programa_modulo', ['modulo_id' => $a->id, 'orden' => 1]);
        $this->assertDatabaseHas('programa_modulo', ['modulo_id' => $c->id, 'orden' => 2]);
        $this->assertDatabaseMissing('programa_modulo', ['modulo_id' => $b->id]);
    }

    /** RN 2.5: un programa NO puede abrirse con la estructura incompleta. */
    public function test_un_programa_con_estructura_incompleta_no_se_puede_abrir(): void
    {
        $programa = Programa::factory()->create([
            'cantidad_modulos' => 6,
            'descripcion' => 'Completa.',
        ]);
        $programa->modulos()->attach(Modulo::factory()->create()->id, ['orden' => 1]);

        $this->patch(route('programas.estado', $programa), ['estado_comercial' => 'abierto'])
            ->assertSessionHas('error');

        $this->assertSame(EstadoComercial::Borrador, $programa->fresh()->estado_comercial);
    }

    public function test_un_programa_con_estructura_completa_si_se_abre(): void
    {
        $programa = Programa::factory()->create([
            'cantidad_modulos' => 2,
            'descripcion' => 'Completa.',
        ]);
        $programa->modulos()->attach(Modulo::factory()->create()->id, ['orden' => 1]);
        $programa->modulos()->attach(Modulo::factory()->create()->id, ['orden' => 2]);

        $this->patch(route('programas.estado', $programa), ['estado_comercial' => 'abierto'])
            ->assertSessionHas('exito');

        $this->assertSame(EstadoComercial::Abierto, $programa->fresh()->estado_comercial);
    }

    /*
    |--------------------------------------------------------------------------
    | Las pantallas cargan
    |--------------------------------------------------------------------------
    */

    public function test_todas_las_pantallas_del_catalogo_cargan(): void
    {
        $modulo = Modulo::factory()->create(['nombre' => 'Modulo Visible']);
        $programa = Programa::factory()->create(['nombre' => 'Programa Visible', 'cantidad_modulos' => 2]);
        $programa->modulos()->attach($modulo->id, ['orden' => 1]);

        $this->get(route('modulos.index'))->assertOk()->assertSee('Modulo Visible');
        $this->get(route('modulos.create'))->assertOk()->assertSee('Nuevo modulo');
        $this->get(route('modulos.show', $modulo))->assertOk()->assertSee('Modulo Visible');
        $this->get(route('modulos.edit', $modulo))->assertOk()->assertSee('Modulo Visible');

        $this->get(route('programas.index'))->assertOk()->assertSee('Programa Visible');
        $this->get(route('programas.create'))->assertOk()->assertSee('Nuevo programa');
        $this->get(route('programas.show', $programa))->assertOk()->assertSee('Programa Visible');
        $this->get(route('programas.edit', $programa))->assertOk()->assertSee('Programa Visible');
        $this->get(route('programas.estructura', $programa))->assertOk()->assertSee('Estructura');
    }

    public function test_un_docente_no_entra_al_catalogo(): void
    {
        $docente = User::factory()->create();
        $docente->roles()->sync([Rol::create(['nombre' => User::ROL_DOCENTE])->id]);

        $this->actingAs($docente);

        $this->get(route('modulos.index'))->assertForbidden();
        $this->get(route('programas.index'))->assertForbidden();
    }

    private function datosModulo(array $cambios = []): array
    {
        return array_merge([
            'codigo' => 'NEW-01',
            'nombre' => 'Modulo nuevo',
            'descripcion' => 'Una descripcion.',
            'horas' => 24,
            'precio' => 700,
            'se_oferta_por_separado' => '1',
        ], $cambios);
    }
}
