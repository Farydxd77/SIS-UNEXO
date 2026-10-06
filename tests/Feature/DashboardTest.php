<?php

namespace Tests\Feature;

use App\Enums\EstadoCohorte;
use App\Enums\EstadoMatricula;
use App\Enums\ResultadoMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada rol tiene su propio dashboard al iniciar sesion.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;

    private Rol $rolDocente;

    private Rol $rolEstudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::create(['nombre' => User::ROL_ADMINISTRADOR]);
        $this->rolDocente = Rol::create(['nombre' => User::ROL_DOCENTE]);
        $this->rolEstudiante = Rol::create(['nombre' => User::ROL_ESTUDIANTE]);
    }

    public function test_el_administrador_ve_su_panel_con_metricas(): void
    {
        $admin = $this->usuarioCon($this->rolAdmin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel del administrador')
            ->assertSee('Usuarios activos');
    }

    public function test_el_docente_ve_solo_sus_grupos(): void
    {
        $docente = $this->usuarioCon($this->rolDocente);
        $otroDocente = $this->usuarioCon($this->rolDocente);

        $mio = Modulo::factory()->create(['nombre' => 'Modulo Mio']);
        Grupo::factory()->create(['modulo_id' => $mio->id, 'docente_id' => $docente->id]);

        $ajeno = Modulo::factory()->create(['nombre' => 'Modulo Ajeno']);
        Grupo::factory()->create(['modulo_id' => $ajeno->id, 'docente_id' => $otroDocente->id]);

        $this->actingAs($docente)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mis grupos')
            ->assertSee('Modulo Mio')
            ->assertDontSee('Modulo Ajeno');
    }

    public function test_el_estudiante_ve_su_avance(): void
    {
        $alumno = $this->usuarioCon($this->rolEstudiante);

        $modulo = Modulo::factory()->create(['nombre' => 'Modulo Inscrito']);
        $grupo = Grupo::factory()->create(['modulo_id' => $modulo->id]);

        Matricula::factory()->create([
            'user_id' => $alumno->id,
            'grupo_id' => $grupo->id,
            'estado' => EstadoMatricula::Activa,
        ]);

        $this->actingAs($alumno)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mi avance academico')
            ->assertSee('Modulo Inscrito');
    }

    public function test_el_estudiante_ve_su_programa_con_los_modulos_que_le_faltan(): void
    {
        $alumno = $this->usuarioCon($this->rolEstudiante);

        $programa = Programa::factory()->create(['nombre' => 'Programa del Alumno']);
        $actual = Cohorte::factory()->create(['programa_id' => $programa->id, 'estado' => EstadoCohorte::EnCurso]);
        $cursado = Grupo::factory()->create([
            'cohorte_id' => $actual->id,
            'modulo_id' => Modulo::factory()->create(['nombre' => 'Modulo Cursado'])->id,
        ]);
        $siguiente = Grupo::factory()->create([
            'cohorte_id' => $actual->id,
            'modulo_id' => Modulo::factory()->create(['nombre' => 'Modulo Siguiente'])->id,
        ]);
        // Programa completo: una matricula en cada grupo de la cohorte.
        Matricula::factory()->create([
            'user_id' => $alumno->id,
            'grupo_id' => $cursado->id,
            'estado' => EstadoMatricula::Finalizada,
            'nota_final' => 80,
            'resultado' => ResultadoMatricula::Aprobado,
        ]);
        Matricula::factory()->create([
            'user_id' => $alumno->id,
            'grupo_id' => $siguiente->id,
            'estado' => EstadoMatricula::Reservada,
        ]);

        $anterior = Cohorte::factory()->create(['nombre' => 'Cohorte Pasada', 'estado' => EstadoCohorte::Finalizado]);
        Matricula::factory()->create([
            'user_id' => $alumno->id,
            'grupo_id' => Grupo::factory()->create(['cohorte_id' => $anterior->id])->id,
            'estado' => EstadoMatricula::Finalizada,
        ]);

        // Una cohorte ajena no debe aparecer.
        Cohorte::factory()->create(['nombre' => 'Cohorte Ajena', 'estado' => EstadoCohorte::EnCurso]);

        $this->actingAs($alumno)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Mis programas', 'Programa del Alumno', 'Modulo Cursado', 'Aprobado', 'Modulo Siguiente', 'Reservada'])
            ->assertSee('1 de 2 modulo(s) aprobado(s)')
            ->assertSeeInOrder(['Programas anteriores', 'Cohorte Pasada'])
            ->assertDontSee('Cohorte Ajena');
    }

    public function test_un_modulo_suelto_no_se_muestra_como_programa(): void
    {
        $alumno = $this->usuarioCon($this->rolEstudiante);

        // Grupo que pertenece a una cohorte, pero el alumno solo tomo ese modulo.
        $cohorte = Cohorte::factory()->create(['estado' => EstadoCohorte::EnCurso]);
        $grupo = Grupo::factory()->create([
            'cohorte_id' => $cohorte->id,
            'modulo_id' => Modulo::factory()->create(['nombre' => 'Modulo Tomado Suelto'])->id,
        ]);
        Grupo::factory()->create(['cohorte_id' => $cohorte->id]);

        Matricula::factory()->create([
            'user_id' => $alumno->id,
            'grupo_id' => $grupo->id,
            'estado' => EstadoMatricula::Activa,
        ]);

        $this->actingAs($alumno)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Mis modulos sueltos', 'Modulo Tomado Suelto'])
            ->assertDontSee('Mis programas')
            ->assertDontSee('Avance del programa');
    }

    public function test_quien_tiene_admin_y_docente_ve_el_de_admin(): void
    {
        $mixto = User::factory()->create();
        $mixto->roles()->sync([$this->rolAdmin->id, $this->rolDocente->id]);

        $this->actingAs($mixto)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel del administrador');
    }

    public function test_un_usuario_sin_roles_ve_el_aviso(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('no tiene rol asignado');
    }

    /** RN 1.7: con el cambio pendiente, cualquier ruta redirige al cambio. */
    public function test_con_cambio_de_password_pendiente_no_se_puede_navegar(): void
    {
        $usuario = User::factory()->debeCambiarPassword()->create();
        $usuario->roles()->sync([$this->rolAdmin->id]);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.cambio'));

        // La pantalla de cambio si es accesible.
        $this->actingAs($usuario)->get(route('password.cambio'))->assertOk();
    }

    public function test_al_cambiar_la_password_se_libera_la_navegacion(): void
    {
        $usuario = User::factory()->debeCambiarPassword()->create(['password' => 'temporal123']);
        $usuario->roles()->sync([$this->rolAdmin->id]);

        $this->actingAs($usuario)
            ->put(route('password.actualizar'), [
                'password_actual' => 'temporal123',
                'password' => 'definitiva123',
                'password_confirmation' => 'definitiva123',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertFalse($usuario->fresh()->debe_cambiar_password);
        $this->actingAs($usuario->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_mi_perfil_solo_deja_cambiar_el_telefono(): void
    {
        $usuario = $this->usuarioCon($this->rolEstudiante, [
            'name' => 'Juan',
            'apellidos' => 'Perez',
            'documento' => '12345678',
            'telefono' => '70000000',
        ]);

        $this->actingAs($usuario)->get(route('perfil.show'))
            ->assertOk()
            ->assertSee('Mi perfil')
            ->assertSee('12345678');

        // Intento cambiar el documento a la vez que el telefono.
        $this->actingAs($usuario)->put(route('perfil.actualizar'), [
            'telefono' => '71111111',
            'documento' => '99999999',
            'name' => 'Otro Nombre',
        ])->assertRedirect(route('perfil.show'));

        $usuario->refresh();

        $this->assertSame('71111111', $usuario->telefono);
        // Los datos oficiales no se tocan: el controlador ni los lee.
        $this->assertSame('12345678', $usuario->documento);
        $this->assertSame('Juan', $usuario->name);
    }

    private function usuarioCon(Rol $rol, array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos);
        $usuario->roles()->sync([$rol->id]);

        return $usuario;
    }
}
