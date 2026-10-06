<?php

namespace Tests\Feature;

use App\Actions\Cohortes\GenerarGruposDeCohorte;
use App\Actions\Grupos\DetectarChoqueHorario;
use App\Enums\DiaSemana;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
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
 * MODULO 3 - Planificacion operativa: cohortes, grupos, horarios
 * y la validacion de choque de horario del docente.
 */
class PlanificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;

    protected function setUp(): void
    {
        parent::setUp();

        $rolDocente = Rol::create(['nombre' => User::ROL_DOCENTE]);
        $this->docente = User::factory()->create();
        $this->docente->roles()->sync([$rolDocente->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generacion automatica de grupos al abrir una cohorte
    |--------------------------------------------------------------------------
    */

    public function test_abrir_una_cohorte_genera_un_grupo_por_cada_modulo_en_orden(): void
    {
        $programa = Programa::factory()->create(['cantidad_modulos' => 3]);

        $excel = Modulo::factory()->create(['nombre' => 'Excel']);
        $powerbi = Modulo::factory()->create(['nombre' => 'Power BI']);
        $sql = Modulo::factory()->create(['nombre' => 'SQL']);

        $programa->modulos()->attach($excel->id, ['orden' => 1]);
        $programa->modulos()->attach($powerbi->id, ['orden' => 2]);
        $programa->modulos()->attach($sql->id, ['orden' => 3]);

        $cohorte = Cohorte::factory()->create([
            'programa_id' => $programa->id,
            'fecha_inicio' => '2027-03-01',
        ]);

        $creados = (new GenerarGruposDeCohorte)->ejecutar($cohorte, semanasPorModulo: 4);

        $this->assertSame(3, $creados);
        $this->assertSame(3, $cohorte->grupos()->count());

        // Todos nacen planificados y sin docente: los completa el admin.
        foreach ($cohorte->grupos as $grupo) {
            $this->assertSame(EstadoGrupo::Planificado, $grupo->estado);
            $this->assertNull($grupo->docente_id);
            $this->assertSame($cohorte->id, $grupo->cohorte_id);
        }
    }

    public function test_las_fechas_de_los_grupos_se_encadenan(): void
    {
        $programa = Programa::factory()->create(['cantidad_modulos' => 2]);
        $programa->modulos()->attach(Modulo::factory()->create()->id, ['orden' => 1]);
        $programa->modulos()->attach(Modulo::factory()->create()->id, ['orden' => 2]);

        $cohorte = Cohorte::factory()->create([
            'programa_id' => $programa->id,
            'fecha_inicio' => '2027-03-01',
        ]);

        (new GenerarGruposDeCohorte)->ejecutar($cohorte, semanasPorModulo: 4);

        $grupos = $cohorte->grupos()->orderBy('fecha_inicio')->get();

        // 4 semanas: 01/03 al 28/03, y el siguiente arranca el 29/03.
        $this->assertSame('2027-03-01', $grupos[0]->fecha_inicio->toDateString());
        $this->assertSame('2027-03-28', $grupos[0]->fecha_fin->toDateString());
        $this->assertSame('2027-03-29', $grupos[1]->fecha_inicio->toDateString());

        // La cohorte termina cuando termina su ultimo grupo.
        $this->assertSame($grupos[1]->fecha_fin->toDateString(), $cohorte->fresh()->fecha_fin->toDateString());
    }

    /** RN 3.5: version = max(version) + 1 por modulo. */
    public function test_la_version_del_grupo_se_calcula_sola(): void
    {
        $modulo = Modulo::factory()->create();
        // Ya existen dos grupos de ese modulo.
        Grupo::factory()->create(['modulo_id' => $modulo->id, 'version' => 1]);
        Grupo::factory()->create(['modulo_id' => $modulo->id, 'version' => 2]);

        $programa = Programa::factory()->create(['cantidad_modulos' => 1]);
        $programa->modulos()->attach($modulo->id, ['orden' => 1]);

        $cohorte = Cohorte::factory()->create(['programa_id' => $programa->id]);
        (new GenerarGruposDeCohorte)->ejecutar($cohorte);

        $this->assertSame(3, $cohorte->grupos()->first()->version);
        $this->assertSame(3, $modulo->siguienteVersion() - 1);
    }

    public function test_una_cohorte_de_un_programa_sin_modulos_no_genera_nada(): void
    {
        $cohorte = Cohorte::factory()->create();

        $this->assertSame(0, (new GenerarGruposDeCohorte)->ejecutar($cohorte));
        $this->assertSame(0, $cohorte->grupos()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | RN 3.4 - Choque de horario del docente
    |--------------------------------------------------------------------------
    | Chocan si: mismo docente + fechas solapadas + mismo dia + horas solapadas.
    */

    public function test_no_choca_si_el_dia_es_distinto(): void
    {
        $this->grupoDelDocente(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30');

        // Jueves, mismas fechas y horas: NO choca.
        $this->assertFalse($this->hayChoque(DiaSemana::Jueves, '19:00', '21:00', '2027-10-01', '2027-11-30'));
    }

    public function test_si_choca_si_coincide_el_dia_y_las_horas_se_solapan(): void
    {
        $this->grupoDelDocente(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30');

        // Lunes 20-22 se solapa con 19-21.
        $this->assertTrue($this->hayChoque(DiaSemana::Lunes, '20:00', '22:00', '2027-10-01', '2027-11-30'));
    }

    public function test_no_choca_si_las_fechas_no_se_solapan(): void
    {
        $this->grupoDelDocente(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30');

        // Mismo dia y hora pero en otro periodo: NO choca.
        $this->assertFalse($this->hayChoque(DiaSemana::Lunes, '19:00', '21:00', '2028-02-01', '2028-03-15'));
    }

    public function test_no_choca_si_las_horas_solo_se_tocan(): void
    {
        $this->grupoDelDocente(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30');

        // 17-19 termina justo cuando el otro empieza: NO se solapan.
        $this->assertFalse($this->hayChoque(DiaSemana::Lunes, '17:00', '19:00', '2027-10-01', '2027-11-30'));
    }

    public function test_al_editar_el_grupo_no_choca_consigo_mismo(): void
    {
        $grupo = $this->grupoDelDocente(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30');

        $choques = (new DetectarChoqueHorario)->ejecutar(
            $this->docente->id,
            '2027-10-01',
            '2027-11-30',
            [['dia_semana' => DiaSemana::Lunes->value, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']],
            grupoIdExcluido: $grupo->id,
        );

        $this->assertTrue($choques->isEmpty());
    }

    public function test_un_grupo_cancelado_ya_no_ocupa_la_agenda(): void
    {
        $grupo = $this->grupoDelDocente(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30');
        $grupo->update(['estado' => EstadoGrupo::Cancelado]);

        $this->assertFalse($this->hayChoque(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30'));
    }

    public function test_otro_docente_no_choca(): void
    {
        $this->grupoDelDocente(DiaSemana::Lunes, '19:00', '21:00', '2027-10-01', '2027-11-30');

        $otro = User::factory()->create();

        $choques = (new DetectarChoqueHorario)->ejecutar(
            $otro->id,
            '2027-10-01',
            '2027-11-30',
            [['dia_semana' => DiaSemana::Lunes->value, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']],
        );

        $this->assertTrue($choques->isEmpty());
    }

    public function test_el_mensaje_de_choque_dice_con_que_grupo_choca(): void
    {
        $modulo = Modulo::factory()->create(['nombre' => 'SQL']);
        $grupo = Grupo::factory()->create([
            'modulo_id' => $modulo->id,
            'docente_id' => $this->docente->id,
            'version' => 4,
            'fecha_inicio' => '2027-10-01',
            'fecha_fin' => '2027-11-30',
            'estado' => EstadoGrupo::EnConvocatoria,
        ]);
        Horario::factory()->create([
            'grupo_id' => $grupo->id,
            'dia_semana' => DiaSemana::Lunes,
            'hora_inicio' => '19:00',
            'hora_fin' => '21:00',
        ]);

        $detector = new DetectarChoqueHorario;
        $choques = $detector->ejecutar(
            $this->docente->id,
            '2027-10-01',
            '2027-11-30',
            [['dia_semana' => DiaSemana::Lunes->value, 'hora_inicio' => '20:00', 'hora_fin' => '22:00']],
        );

        // El mensaje que pide la spec, literal.
        $this->assertSame(
            'El docente ya dicta SQL - Version 4 los lunes de 19:00 a 21:00.',
            $detector->mensaje($choques),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cupo y transiciones del grupo
    |--------------------------------------------------------------------------
    */

    public function test_solo_cuentan_para_el_cupo_las_matriculas_vigentes(): void
    {
        $grupo = Grupo::factory()->create(['cupo_minimo' => 3]);

        Matricula::factory()->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Reservada]);
        Matricula::factory()->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Activa]);
        // Estas NO cuentan.
        Matricula::factory()->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Anulada]);
        Matricula::factory()->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Retirada]);

        $this->assertSame(2, $grupo->inscritosVigentes());
        $this->assertFalse($grupo->alcanzoCupoMinimo());
    }

    /** RN 3.7: para habilitar hace falta cupo, docente y enlace de Meet. */
    public function test_no_se_habilita_un_grupo_al_que_le_falta_algo(): void
    {
        $grupo = Grupo::factory()->create(['cupo_minimo' => 2, 'docente_id' => null, 'enlace_meet' => null]);

        $this->assertFalse($grupo->puedeSerHabilitado());

        $faltantes = $grupo->faltantesParaHabilitar();
        $this->assertCount(3, $faltantes);
        $this->assertStringContainsString('inscritos', $faltantes[0]);
        $this->assertStringContainsString('docente', $faltantes[1]);
        $this->assertStringContainsString('Meet', $faltantes[2]);
    }

    public function test_se_habilita_cuando_esta_todo_listo(): void
    {
        $grupo = Grupo::factory()->conMeet()->create([
            'cupo_minimo' => 2,
            'docente_id' => $this->docente->id,
        ]);

        Matricula::factory()->count(2)->create(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Activa]);

        $this->assertTrue($grupo->fresh()->puedeSerHabilitado());
    }

    /** RN 3.6: para en_convocatoria hace falta fechas y al menos un horario. */
    public function test_no_pasa_a_convocatoria_sin_horario(): void
    {
        $grupo = Grupo::factory()->create();

        $this->assertFalse($grupo->puedeIrAConvocatoria());

        Horario::factory()->create(['grupo_id' => $grupo->id]);

        $this->assertTrue($grupo->fresh()->puedeIrAConvocatoria());
    }

    public function test_la_maquina_de_estados_del_grupo_respeta_el_orden(): void
    {
        $this->assertTrue(EstadoGrupo::Planificado->puedePasarA(EstadoGrupo::EnConvocatoria));
        $this->assertTrue(EstadoGrupo::EnConvocatoria->puedePasarA(EstadoGrupo::Habilitado));
        $this->assertTrue(EstadoGrupo::Habilitado->puedePasarA(EstadoGrupo::EnCurso));
        $this->assertTrue(EstadoGrupo::EnCurso->puedePasarA(EstadoGrupo::Finalizado));

        // Saltos no permitidos.
        $this->assertFalse(EstadoGrupo::Planificado->puedePasarA(EstadoGrupo::Habilitado));
        $this->assertFalse(EstadoGrupo::Finalizado->puedePasarA(EstadoGrupo::EnCurso));

        // Cancelar se puede desde cualquier estado vivo.
        $this->assertTrue(EstadoGrupo::EnConvocatoria->puedePasarA(EstadoGrupo::Cancelado));
    }

    /*
    |--------------------------------------------------------------------------
    | RN 3.9 - Quien ve el enlace de Meet
    |--------------------------------------------------------------------------
    */

    public function test_el_enlace_de_meet_solo_lo_ve_quien_debe(): void
    {
        $rolAdmin = Rol::create(['nombre' => User::ROL_ADMINISTRADOR]);
        $admin = User::factory()->create();
        $admin->roles()->sync([$rolAdmin->id]);

        $grupo = Grupo::factory()->conMeet()->create(['docente_id' => $this->docente->id]);

        $alumnoActivo = User::factory()->create();
        Matricula::factory()->create([
            'user_id' => $alumnoActivo->id,
            'grupo_id' => $grupo->id,
            'estado' => EstadoMatricula::Activa,
        ]);

        $alumnoReservado = User::factory()->create();
        Matricula::factory()->create([
            'user_id' => $alumnoReservado->id,
            'grupo_id' => $grupo->id,
            'estado' => EstadoMatricula::Reservada,
        ]);

        $ajeno = User::factory()->create();

        $this->assertTrue($grupo->puedeVerEnlaceMeet($admin), 'el admin si');
        $this->assertTrue($grupo->puedeVerEnlaceMeet($this->docente), 'el docente del grupo si');
        $this->assertTrue($grupo->puedeVerEnlaceMeet($alumnoActivo), 'con matricula activa si');
        $this->assertFalse($grupo->puedeVerEnlaceMeet($alumnoReservado), 'solo reservada NO');
        $this->assertFalse($grupo->puedeVerEnlaceMeet($ajeno), 'un ajeno NO');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function grupoDelDocente(
        DiaSemana $dia,
        string $desde,
        string $hasta,
        string $fechaInicio,
        string $fechaFin,
    ): Grupo {
        $grupo = Grupo::factory()->create([
            'docente_id' => $this->docente->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado' => EstadoGrupo::EnConvocatoria,
        ]);

        Horario::factory()->create([
            'grupo_id' => $grupo->id,
            'dia_semana' => $dia,
            'hora_inicio' => $desde,
            'hora_fin' => $hasta,
        ]);

        return $grupo;
    }

    private function hayChoque(
        DiaSemana $dia,
        string $desde,
        string $hasta,
        string $fechaInicio,
        string $fechaFin,
    ): bool {
        return (new DetectarChoqueHorario)->hayChoque(
            $this->docente->id,
            $fechaInicio,
            $fechaFin,
            [['dia_semana' => $dia->value, 'hora_inicio' => $desde, 'hora_fin' => $hasta]],
        );
    }
}
