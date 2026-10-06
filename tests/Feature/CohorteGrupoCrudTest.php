<?php

namespace Tests\Feature;

use App\Actions\Cohortes\GenerarGruposDeCohorte;
use App\Enums\DiaSemana;
use App\Enums\EstadoCohorte;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Enums\ResultadoMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MODULO 3 por HTTP: CRUD de cohortes y grupos, estados y permisos.
 */
class CohorteGrupoCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $docente;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['nombre' => User::ROL_ADMINISTRADOR]);
        $rolDocente = Rol::create(['nombre' => User::ROL_DOCENTE]);
        Rol::create(['nombre' => User::ROL_ESTUDIANTE]);

        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([$rolAdmin->id]);

        $this->docente = User::factory()->create();
        $this->docente->roles()->sync([$rolDocente->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cohortes
    |--------------------------------------------------------------------------
    */

    public function test_crear_una_cohorte_genera_sus_grupos(): void
    {
        $programa = $this->programaAbiertoCon(3);

        $this->actingAs($this->admin)
            ->post(route('cohortes.store'), [
                'programa_id' => $programa->id,
                'nombre' => 'DAE - Version 3, Marzo 2027',
                'fecha_inicio' => '2027-03-01',
                'semanas_por_modulo' => 4,
            ])
            ->assertRedirect();

        $cohorte = Cohorte::firstOrFail();
        $this->assertSame(3, $cohorte->grupos()->count());

        $this->get(route('cohortes.show', $cohorte))
            ->assertOk()
            ->assertSee('Linea de tiempo de grupos');
    }

    public function test_no_se_crea_cohorte_de_un_programa_en_borrador(): void
    {
        $programa = $this->programaAbiertoCon(2);
        $programa->update(['estado_comercial' => 'borrador']);

        $this->actingAs($this->admin)
            ->post(route('cohortes.store'), [
                'programa_id' => $programa->id,
                'nombre' => 'X',
                'fecha_inicio' => '2027-03-01',
                'semanas_por_modulo' => 4,
            ])
            ->assertSessionHasErrors('programa_id');

        $this->assertSame(0, Cohorte::count());
    }

    public function test_cancelar_la_cohorte_cancela_grupos_y_anula_matriculas(): void
    {
        $programa = $this->programaAbiertoCon(2);
        $cohorte = Cohorte::factory()->create(['programa_id' => $programa->id]);
        (new GenerarGruposDeCohorte)->ejecutar($cohorte);

        $grupo = $cohorte->grupos()->first();
        $grupo->update(['estado' => EstadoGrupo::EnConvocatoria]);
        $matricula = Matricula::factory()->create(['grupo_id' => $grupo->id]);

        $this->actingAs($this->admin)
            ->patch(route('cohortes.estado', $cohorte), ['estado' => 'cancelado'])
            ->assertSessionHas('exito');

        $this->assertSame(EstadoGrupo::Cancelado, $grupo->fresh()->estado);
        $this->assertSame(EstadoMatricula::Anulada, $matricula->fresh()->estado);
    }

    public function test_las_vistas_de_cohortes_cargan(): void
    {
        $cohorte = Cohorte::factory()->create();

        $this->actingAs($this->admin);
        $this->get(route('cohortes.index'))->assertOk();
        $this->get(route('cohortes.create'))->assertOk();
        $this->get(route('cohortes.edit', $cohorte))->assertOk();
    }

    public function test_las_cohortes_cerradas_pasan_al_historial(): void
    {
        Cohorte::factory()->create(['nombre' => 'Cohorte Vigente', 'estado' => EstadoCohorte::EnCurso]);
        Cohorte::factory()->create(['nombre' => 'Cohorte Finalizada', 'estado' => EstadoCohorte::Finalizado]);
        Cohorte::factory()->create(['nombre' => 'Cohorte Cancelada', 'estado' => EstadoCohorte::Cancelado]);

        $this->actingAs($this->admin);

        $this->get(route('cohortes.index'))
            ->assertOk()
            ->assertSee('Cohorte Vigente')
            ->assertDontSee('Cohorte Finalizada')
            ->assertDontSee('Cohorte Cancelada');

        $this->get(route('cohortes.index', ['vista' => 'historial']))
            ->assertOk()
            ->assertSee('Cohorte Finalizada')
            ->assertSee('Cohorte Cancelada')
            ->assertDontSee('Cohorte Vigente');
    }

    public function test_el_historial_cuenta_matriculas_y_aprobadas_sin_anuladas(): void
    {
        $cohorte = Cohorte::factory()->create(['estado' => EstadoCohorte::Finalizado]);
        $grupo = Grupo::factory()->create(['cohorte_id' => $cohorte->id]);

        Matricula::factory()->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Finalizada, 'resultado' => ResultadoMatricula::Aprobado]);
        Matricula::factory()->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Finalizada, 'resultado' => ResultadoMatricula::Reprobado]);
        Matricula::factory()->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Anulada]);

        $this->actingAs($this->admin)
            ->get(route('cohortes.index', ['vista' => 'historial']))
            ->assertOk()
            ->assertViewHas('cohortes', fn ($cohortes) => $cohortes->first()->matriculas_count === 2
                && $cohortes->first()->aprobadas_count === 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Grupos: alta, edicion, reglas
    |--------------------------------------------------------------------------
    */

    public function test_crear_grupo_con_horarios_calcula_la_version(): void
    {
        $modulo = Modulo::factory()->abierto()->create();
        Grupo::factory()->create(['modulo_id' => $modulo->id, 'version' => 3]);

        $this->actingAs($this->admin)
            ->post(route('grupos.store'), $this->datosGrupo($modulo, [
                'horarios' => [
                    ['dia_semana' => 1, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'],
                    ['dia_semana' => 3, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'],
                ],
            ]))
            ->assertRedirect();

        $grupo = Grupo::where('modulo_id', $modulo->id)->where('version', 4)->firstOrFail();
        $this->assertSame(2, $grupo->horarios()->count());
        $this->assertSame(EstadoGrupo::Planificado, $grupo->estado);
    }

    public function test_no_se_crea_grupo_de_modulo_en_borrador(): void
    {
        $modulo = Modulo::factory()->create(); // borrador

        $this->actingAs($this->admin)
            ->post(route('grupos.store'), $this->datosGrupo($modulo))
            ->assertSessionHasErrors('modulo_id');
    }

    public function test_hora_fin_debe_ser_posterior_a_hora_inicio(): void
    {
        $modulo = Modulo::factory()->abierto()->create();

        $this->actingAs($this->admin)
            ->post(route('grupos.store'), $this->datosGrupo($modulo, [
                'horarios' => [['dia_semana' => 1, 'hora_inicio' => '21:00', 'hora_fin' => '19:00']],
            ]))
            ->assertSessionHasErrors('horarios.0.hora_fin');
    }

    public function test_el_choque_de_horario_se_rechaza_con_mensaje_descriptivo(): void
    {
        $sql = Modulo::factory()->abierto()->create(['nombre' => 'SQL']);
        $existente = Grupo::factory()->create([
            'modulo_id' => $sql->id,
            'version' => 4,
            'docente_id' => $this->docente->id,
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-11-30',
        ]);
        Horario::factory()->create(['grupo_id' => $existente->id, 'dia_semana' => DiaSemana::Lunes]);

        $python = Modulo::factory()->abierto()->create(['nombre' => 'Python']);

        $this->actingAs($this->admin)
            ->post(route('grupos.store'), $this->datosGrupo($python, [
                'docente_id' => $this->docente->id,
                'fecha_inicio' => '2026-10-15',
                'fecha_fin' => '2026-11-15',
                'horarios' => [['dia_semana' => 1, 'hora_inicio' => '20:00', 'hora_fin' => '22:00']],
            ]))
            ->assertSessionHasErrors(['horarios' => 'El docente ya dicta SQL - Version 4 los lunes de 19:00 a 21:00.']);
    }

    public function test_editar_un_grupo_no_choca_consigo_mismo(): void
    {
        $modulo = Modulo::factory()->abierto()->create();
        $grupo = Grupo::factory()->create(['modulo_id' => $modulo->id, 'docente_id' => $this->docente->id]);
        Horario::factory()->create(['grupo_id' => $grupo->id]);

        $this->actingAs($this->admin)
            ->put(route('grupos.update', $grupo), $this->datosGrupo($modulo, [
                'docente_id' => $this->docente->id,
                'horarios' => [['dia_semana' => 1, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('grupos.show', $grupo));
    }

    public function test_el_docente_debe_tener_rol_docente(): void
    {
        $modulo = Modulo::factory()->abierto()->create();
        $sinRol = User::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('grupos.store'), $this->datosGrupo($modulo, ['docente_id' => $sinRol->id]))
            ->assertSessionHasErrors('docente_id');
    }

    public function test_el_enlace_de_meet_no_se_repite_entre_grupos_activos(): void
    {
        $modulo = Modulo::factory()->abierto()->create();
        Grupo::factory()->create(['modulo_id' => $modulo->id, 'enlace_meet' => 'https://meet.google.com/aaa-bbbb-ccc']);

        $this->actingAs($this->admin)
            ->post(route('grupos.store'), $this->datosGrupo($modulo, ['enlace_meet' => 'https://meet.google.com/aaa-bbbb-ccc']))
            ->assertSessionHasErrors('enlace_meet');
    }

    /*
    |--------------------------------------------------------------------------
    | Grupos: maquina de estados
    |--------------------------------------------------------------------------
    */

    public function test_sin_horario_no_pasa_a_convocatoria(): void
    {
        $grupo = Grupo::factory()->create();

        $this->actingAs($this->admin)
            ->patch(route('grupos.estado', $grupo), ['estado' => 'en_convocatoria'])
            ->assertSessionHas('error');

        $this->assertSame(EstadoGrupo::Planificado, $grupo->fresh()->estado);
    }

    public function test_no_se_habilita_sin_cupo_minimo_y_el_mensaje_dice_que_falta(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create(['cupo_minimo' => 2]);

        $this->actingAs($this->admin)
            ->patch(route('grupos.estado', $grupo), ['estado' => 'habilitado'])
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'faltan 2 inscritos')
                && str_contains($msg, 'no tiene docente')
                && str_contains($msg, 'enlace de Meet'));
    }

    public function test_se_habilita_con_cupo_docente_y_meet(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->conMeet()->create([
            'cupo_minimo' => 2,
            'docente_id' => $this->docente->id,
        ]);
        Matricula::factory()->count(2)->create(['grupo_id' => $grupo->id]);

        $this->actingAs($this->admin)
            ->patch(route('grupos.estado', $grupo), ['estado' => 'habilitado'])
            ->assertSessionHas('exito');

        $this->assertSame(EstadoGrupo::Habilitado, $grupo->fresh()->estado);
    }

    public function test_no_se_elimina_un_grupo_con_inscritos(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->create();
        Matricula::factory()->create(['grupo_id' => $grupo->id]);

        $this->actingAs($this->admin)
            ->delete(route('grupos.destroy', $grupo))
            ->assertSessionHas('error');

        $this->assertModelExists($grupo);
    }

    /*
    |--------------------------------------------------------------------------
    | Permisos
    |--------------------------------------------------------------------------
    */

    public function test_un_docente_recibe_403_en_un_grupo_ajeno(): void
    {
        $ajeno = Grupo::factory()->create();

        $this->actingAs($this->docente)
            ->get(route('grupos.show', $ajeno))
            ->assertForbidden();
    }

    public function test_un_docente_ve_su_grupo_y_sus_alumnos(): void
    {
        $grupo = Grupo::factory()->create(['docente_id' => $this->docente->id]);
        $alumno = User::factory()->create(['name' => 'Alumnito']);
        Matricula::factory()->create(['grupo_id' => $grupo->id, 'user_id' => $alumno->id]);

        $this->actingAs($this->docente)
            ->get(route('grupos.show', $grupo))
            ->assertOk()
            ->assertSee('Alumnito');
    }

    public function test_un_docente_no_accede_al_abm_de_grupos_ni_cohortes(): void
    {
        $grupo = Grupo::factory()->create(['docente_id' => $this->docente->id]);

        $this->actingAs($this->docente);
        $this->get(route('grupos.index'))->assertForbidden();
        $this->get(route('grupos.edit', $grupo))->assertForbidden();
        $this->get(route('cohortes.index'))->assertForbidden();
    }

    public function test_las_vistas_de_grupos_cargan(): void
    {
        $grupo = Grupo::factory()->create();
        Horario::factory()->create(['grupo_id' => $grupo->id]);

        $this->actingAs($this->admin);
        $this->get(route('grupos.index'))->assertOk();
        $this->get(route('grupos.create'))->assertOk();
        $this->get(route('grupos.edit', $grupo))->assertOk()->assertSee('19:00');
        $this->get(route('grupos.show', $grupo))->assertOk();
    }

    private function programaAbiertoCon(int $modulos): Programa
    {
        $programa = Programa::factory()->abierto()->create(['cantidad_modulos' => $modulos]);

        for ($i = 1; $i <= $modulos; $i++) {
            $programa->modulos()->attach(Modulo::factory()->abierto()->create()->id, ['orden' => $i]);
        }

        return $programa;
    }

    private function datosGrupo(Modulo $modulo, array $extra = []): array
    {
        return [
            'modulo_id' => $modulo->id,
            'cohorte_id' => null,
            'docente_id' => null,
            'fecha_inicio' => '2027-03-01',
            'fecha_fin' => '2027-03-28',
            'cupo_minimo' => 20,
            'cupo_maximo' => 30,
            'enlace_meet' => null,
            'horarios' => [],
            ...$extra,
        ];
    }
}
