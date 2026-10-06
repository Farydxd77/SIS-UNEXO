<?php

namespace Tests\Feature;

use App\Actions\Matriculas\InscribirEstudiante;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Enums\ResultadoMatricula;
use App\Exceptions\ReglaDeNegocioException;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MODULO 4: inscripcion, estados de la matricula y registro de notas.
 */
class MatriculaNotaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $docente;

    private Rol $rolEstudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['nombre' => User::ROL_ADMINISTRADOR]);
        $rolDocente = Rol::create(['nombre' => User::ROL_DOCENTE]);
        $this->rolEstudiante = Rol::create(['nombre' => User::ROL_ESTUDIANTE]);

        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([$rolAdmin->id]);

        $this->docente = User::factory()->create();
        $this->docente->roles()->sync([$rolDocente->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Inscripcion
    |--------------------------------------------------------------------------
    */

    public function test_inscribir_persona_nueva_crea_usuario_con_rol_estudiante(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->actingAs($this->admin)
            ->post(route('matriculas.store'), [
                'documento' => '99887766',
                'name' => 'Ana',
                'apellidos' => 'Rios',
                'email' => 'ana@example.com',
                'telefono' => '70000000',
                'tipo' => 'grupo',
                'grupo_id' => $grupo->id,
                'acepto_requisitos' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito');

        $ana = User::where('documento', '99887766')->firstOrFail();
        $this->assertTrue($ana->esEstudiante());
        $this->assertTrue($ana->debe_cambiar_password);

        $matricula = $ana->matriculas()->firstOrFail();
        $this->assertSame(EstadoMatricula::Activa, $matricula->estado);
        $this->assertTrue($matricula->acepto_requisitos);
        $this->assertNotNull($matricula->fecha_aceptacion);
    }

    public function test_persona_existente_se_reutiliza_por_documento_y_recibe_rol_estudiante(): void
    {
        // Un docente que ademas se inscribe como alumno.
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->actingAs($this->admin)
            ->post(route('matriculas.store'), [
                'documento' => $this->docente->documento,
                'tipo' => 'grupo',
                'grupo_id' => $grupo->id,
                'acepto_requisitos' => '1',
            ])
            ->assertSessionHas('exito');

        $this->assertSame(1, User::where('documento', $this->docente->documento)->count());
        $this->assertTrue($this->docente->fresh()->esEstudiante());
        $this->assertTrue($this->docente->fresh()->esDocente());
    }

    public function test_sin_aceptar_requisitos_no_se_inscribe(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->actingAs($this->admin)
            ->post(route('matriculas.store'), [
                'documento' => $this->docente->documento,
                'tipo' => 'grupo',
                'grupo_id' => $grupo->id,
            ])
            ->assertSessionHasErrors('acepto_requisitos');
    }

    public function test_solo_se_inscribe_en_grupos_en_convocatoria_o_habilitados(): void
    {
        $grupo = Grupo::factory()->create(); // planificado

        $this->inscribir($grupo)->assertSessionHas('error');

        $this->assertSame(0, Matricula::count());
    }

    public function test_no_se_matricula_dos_veces_en_el_mismo_grupo(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->inscribir($grupo)->assertSessionHas('exito');
        $this->inscribir($grupo)->assertSessionHas('error');

        $this->assertSame(1, Matricula::count());
    }

    public function test_no_se_supera_el_cupo_maximo(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create(['cupo_minimo' => 1, 'cupo_maximo' => 1]);
        Matricula::factory()->create(['grupo_id' => $grupo->id]);

        $this->inscribir($grupo)->assertSessionHas('error', fn ($m) => str_contains($m, 'cupo maximo'));
    }

    public function test_inscripcion_a_cohorte_es_todo_o_nada(): void
    {
        $cohorte = Cohorte::factory()->create();
        $abierto = Grupo::factory()->enConvocatoria()->create(['cohorte_id' => $cohorte->id]);
        // El segundo grupo sigue planificado: toda la inscripcion debe fallar.
        Grupo::factory()->create(['cohorte_id' => $cohorte->id]);

        $this->actingAs($this->admin)
            ->post(route('matriculas.store'), [
                'documento' => '55555555',
                'name' => 'Pedro',
                'apellidos' => 'Luna',
                'email' => 'pedro@example.com',
                'telefono' => '71111111',
                'tipo' => 'cohorte',
                'cohorte_id' => $cohorte->id,
                'acepto_requisitos' => '1',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, $abierto->matriculas()->count());
        // Tampoco quedo creada la persona: la transaccion lo deshizo todo.
        $this->assertNull(User::where('documento', '55555555')->first());
    }

    public function test_inscripcion_a_cohorte_crea_una_matricula_por_grupo(): void
    {
        $cohorte = Cohorte::factory()->create();
        Grupo::factory()->count(3)->enConvocatoria()->sequence(
            ['modulo_id' => Modulo::factory()],
            ['modulo_id' => Modulo::factory()],
            ['modulo_id' => Modulo::factory()],
        )->create(['cohorte_id' => $cohorte->id]);

        $this->actingAs($this->admin)
            ->post(route('matriculas.store'), [
                'documento' => $this->docente->documento,
                'tipo' => 'cohorte',
                'cohorte_id' => $cohorte->id,
                'acepto_requisitos' => '1',
            ])
            ->assertSessionHas('exito');

        $this->assertSame(3, Matricula::where('user_id', $this->docente->id)->count());

        $this->get(route('matriculas.index'))
            ->assertOk()
            ->assertSee('Programa completo')
            ->assertDontSee('Modulo suelto');
    }

    public function test_un_solo_grupo_de_una_cohorte_se_lista_como_modulo_suelto(): void
    {
        $cohorte = Cohorte::factory()->create();
        $grupos = Grupo::factory()->count(2)->enConvocatoria()->sequence(
            ['modulo_id' => Modulo::factory()],
            ['modulo_id' => Modulo::factory()],
        )->create(['cohorte_id' => $cohorte->id]);

        $this->inscribir($grupos->first())->assertSessionHas('exito');

        $this->get(route('matriculas.index'))
            ->assertOk()
            ->assertSee('Modulo suelto')
            ->assertDontSee('Programa completo');
    }

    public function test_el_mismo_lead_del_crm_no_se_procesa_dos_veces(): void
    {
        Matricula::factory()->create(['crm_lead_id' => 'LEAD-1']);
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->expectException(ReglaDeNegocioException::class);

        (new InscribirEstudiante)->ejecutar(
            ['documento' => $this->docente->documento],
            collect([$grupo]),
            OrigenMatricula::Api,
            'LEAD-1',
        );
    }

    public function test_por_api_del_crm_la_matricula_nace_activa(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $resultado = (new InscribirEstudiante)->ejecutar(
            ['documento' => $this->docente->documento],
            collect([$grupo]),
            OrigenMatricula::Api,
            'LEAD-PAGADO',
        );

        $this->assertSame(EstadoMatricula::Activa, $resultado['matriculas']->sole()->estado);
    }

    public function test_la_inscripcion_manual_tambien_nace_activa(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->inscribir($grupo)->assertSessionHas('exito');

        $this->assertSame(EstadoMatricula::Activa, Matricula::sole()->estado);
    }

    /*
    |--------------------------------------------------------------------------
    | Estados de la matricula
    |--------------------------------------------------------------------------
    */

    public function test_activar_y_retirar_con_motivo(): void
    {
        $matricula = Matricula::factory()->create();

        $this->actingAs($this->admin)->patch(route('matriculas.activar', $matricula))->assertSessionHas('exito');
        $this->assertSame(EstadoMatricula::Activa, $matricula->fresh()->estado);

        // RN 4.9: sin motivo no se retira.
        $this->patch(route('matriculas.retirar', $matricula), [])->assertSessionHasErrors('motivo_retiro');

        $this->patch(route('matriculas.retirar', $matricula), ['motivo_retiro' => 'Cambio de trabajo'])
            ->assertSessionHas('exito');

        $this->assertSame(EstadoMatricula::Retirada, $matricula->fresh()->estado);
        $this->assertSame('Cambio de trabajo', $matricula->fresh()->motivo_retiro);
    }

    public function test_una_reservada_no_se_puede_retirar(): void
    {
        $matricula = Matricula::factory()->create();

        $this->actingAs($this->admin)
            ->patch(route('matriculas.retirar', $matricula), ['motivo_retiro' => 'No aplica aqui'])
            ->assertSessionHas('error');
    }

    public function test_reubicar_alumno_de_grupo_cancelado_en_otro_del_mismo_modulo(): void
    {
        $modulo = Modulo::factory()->create();
        $cancelado = Grupo::factory()->create(['modulo_id' => $modulo->id, 'version' => 1, 'estado' => EstadoGrupo::Cancelado]);
        $destino = Grupo::factory()->enConvocatoria()->create(['modulo_id' => $modulo->id, 'version' => 2]);
        $matricula = Matricula::factory()->create(['grupo_id' => $cancelado->id, 'estado' => EstadoMatricula::Anulada]);

        $this->actingAs($this->admin)
            ->post(route('matriculas.reubicar', $matricula), ['grupo_id' => $destino->id])
            ->assertSessionHas('exito');

        $this->assertTrue($destino->matriculas()->where('user_id', $matricula->user_id)->exists());
        // La anulada se conserva (RN 4.8).
        $this->assertModelExists($matricula);
    }

    public function test_las_vistas_de_matriculas_cargan(): void
    {
        Matricula::factory()->create();

        $this->actingAs($this->admin);
        $this->get(route('matriculas.index'))->assertOk();
        $this->get(route('matriculas.create'))->assertOk();
        $this->get(route('matriculas.create', ['documento' => '000']))->assertOk()->assertSee('Completa sus datos');
        $this->get(route('matriculas.create', ['documento' => $this->docente->documento]))->assertOk()->assertSee('ya esta registrado');
    }

    public function test_el_docente_no_puede_matricular(): void
    {
        $this->actingAs($this->docente)->get(route('matriculas.create'))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Notas
    |--------------------------------------------------------------------------
    */

    public function test_el_docente_carga_notas_y_al_completar_todas_se_finalizan(): void
    {
        $grupo = Grupo::factory()->create(['docente_id' => $this->docente->id, 'estado' => EstadoGrupo::EnCurso]);
        $m1 = Matricula::factory()->activa()->create(['grupo_id' => $grupo->id]);
        $m2 = Matricula::factory()->activa()->create(['grupo_id' => $grupo->id]);

        $this->actingAs($this->docente)->get(route('grupos.notas.edit', $grupo))->assertOk();

        // Solo una nota: sigue activa, con resultado calculado.
        $this->put(route('grupos.notas.update', $grupo), ['notas' => [$m1->id => 80, $m2->id => null]]);

        $this->assertSame(EstadoMatricula::Activa, $m1->fresh()->estado);
        $this->assertSame(ResultadoMatricula::Aprobado, $m1->fresh()->resultado);

        // Con la segunda, todas pasan a finalizada.
        $this->put(route('grupos.notas.update', $grupo), ['notas' => [$m2->id => 30]]);

        $this->assertSame(EstadoMatricula::Finalizada, $m1->fresh()->estado);
        $this->assertSame(EstadoMatricula::Finalizada, $m2->fresh()->estado);
        $this->assertSame(ResultadoMatricula::Reprobado, $m2->fresh()->resultado);
    }

    public function test_la_nota_debe_estar_entre_0_y_100(): void
    {
        $grupo = Grupo::factory()->create(['docente_id' => $this->docente->id, 'estado' => EstadoGrupo::EnCurso]);
        $m = Matricula::factory()->activa()->create(['grupo_id' => $grupo->id]);

        $this->actingAs($this->docente)
            ->put(route('grupos.notas.update', $grupo), ['notas' => [$m->id => 150]])
            ->assertSessionHasErrors('notas.'.$m->id);
    }

    public function test_solo_el_docente_del_grupo_carga_sus_notas(): void
    {
        $otroDocente = User::factory()->create();
        $otroDocente->roles()->sync([Rol::where('nombre', User::ROL_DOCENTE)->value('id')]);

        $grupo = Grupo::factory()->create(['docente_id' => $this->docente->id, 'estado' => EstadoGrupo::EnCurso]);
        $m = Matricula::factory()->activa()->create(['grupo_id' => $grupo->id]);

        $this->actingAs($otroDocente);
        $this->get(route('grupos.notas.edit', $grupo))->assertForbidden();
        $this->put(route('grupos.notas.update', $grupo), ['notas' => [$m->id => 90]])->assertForbidden();

        $this->assertNull($m->fresh()->nota_final);
    }

    public function test_un_estudiante_no_ve_la_lista_de_alumnos_ni_el_meet_si_esta_reservado(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->roles()->sync([$this->rolEstudiante->id]);

        $grupo = Grupo::factory()->enConvocatoria()->create(['enlace_meet' => 'https://meet.google.com/sec-reto-xyz']);
        $matricula = Matricula::factory()->create(['grupo_id' => $grupo->id, 'user_id' => $estudiante->id]);

        $this->actingAs($estudiante)
            ->get(route('grupos.show', $grupo))
            ->assertOk()
            ->assertDontSee('sec-reto-xyz')
            ->assertDontSee('Alumnos (');

        // Con matricula activa, si ve el enlace (RN 3.9).
        $matricula->update(['estado' => EstadoMatricula::Activa]);

        $this->get(route('grupos.show', $grupo))->assertSee('sec-reto-xyz');
    }

    public function test_la_oferta_muestra_solo_lo_abierto(): void
    {
        $estudiante = User::factory()->create();
        $estudiante->roles()->sync([$this->rolEstudiante->id]);

        Modulo::factory()->abierto()->create(['nombre' => 'Modulo Abierto']);
        Modulo::factory()->create(['nombre' => 'Modulo Borrador']);

        $this->actingAs($estudiante)
            ->get(route('oferta.index'))
            ->assertOk()
            ->assertSee('Modulo Abierto')
            ->assertDontSee('Modulo Borrador');
    }

    private function inscribir(Grupo $grupo)
    {
        return $this->actingAs($this->admin)->post(route('matriculas.store'), [
            'documento' => $this->docente->documento,
            'tipo' => 'grupo',
            'grupo_id' => $grupo->id,
            'acepto_requisitos' => '1',
        ]);
    }
}
