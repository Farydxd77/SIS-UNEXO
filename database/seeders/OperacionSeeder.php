<?php

namespace Database\Seeders;

use App\Actions\Cohortes\GenerarGruposDeCohorte;
use App\Enums\DiaSemana;
use App\Enums\EstadoCohorte;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Models\Cohorte;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Una cohorte del DAE con sus 6 grupos, docentes asignados, horarios y
 * alumnos matriculados: los datos para demostrar el sistema funcionando.
 */
class OperacionSeeder extends Seeder
{
    public function run(): void
    {
        $programa = Programa::where('codigo', 'DAE-2025')->firstOrFail();

        if ($programa->cohortes()->where('nombre', 'DAE - Version 1, Marzo 2027')->exists()) {
            return; // ya sembrado
        }

        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'DAE - Version 1, Marzo 2027',
            'fecha_inicio' => '2027-03-01',
            'estado' => EstadoCohorte::EnConvocatoria,
        ]);

        // Aqui se usa la MISMA Action que usara el controlador:
        // genera un grupo por cada modulo del programa, en orden.
        $creados = (new GenerarGruposDeCohorte)->ejecutar($cohorte, semanasPorModulo: 4);
        $this->command->info("  Generados {$creados} grupos para la cohorte.");

        $docente1 = User::where('email', 'docente@unexo.test')->firstOrFail();
        $docente2 = User::where('email', 'docente2@unexo.test')->firstOrFail();

        // Horarios distintos por docente para que NO choquen entre si:
        // el docente 1 da lunes y miercoles, el 2 martes y jueves.
        $agenda = [
            1 => ['docente' => $docente1, 'dias' => [DiaSemana::Lunes, DiaSemana::Miercoles]],
            2 => ['docente' => $docente2, 'dias' => [DiaSemana::Martes, DiaSemana::Jueves]],
            3 => ['docente' => $docente1, 'dias' => [DiaSemana::Lunes, DiaSemana::Miercoles]],
            4 => ['docente' => $docente2, 'dias' => [DiaSemana::Martes, DiaSemana::Jueves]],
            5 => ['docente' => $docente1, 'dias' => [DiaSemana::Lunes, DiaSemana::Miercoles]],
            6 => ['docente' => $docente2, 'dias' => [DiaSemana::Martes, DiaSemana::Jueves]],
        ];

        $grupos = $cohorte->grupos()->orderBy('fecha_inicio')->orderBy('id')->get();

        foreach ($grupos as $i => $grupo) {
            $config = $agenda[$i + 1];

            $grupo->update([
                'docente_id' => $config['docente']->id,
                'enlace_meet' => 'https://meet.google.com/dae-'.strtolower($grupo->modulo->codigo).'-v'.$grupo->version,
                'cupo_maximo' => 30,
                'estado' => EstadoGrupo::EnConvocatoria,
            ]);

            foreach ($config['dias'] as $dia) {
                Horario::create([
                    'grupo_id' => $grupo->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '19:00',
                    'hora_fin' => '21:00',
                ]);
            }
        }

        // Matriculas: el primer grupo con 22 alumnos (supera el cupo minimo de 20,
        // asi se puede habilitar); el segundo con 8 (todavia no llega).
        $alumnos = User::conRol(User::ROL_ESTUDIANTE)->activos()->orderBy('id')->take(22)->get();

        $primerGrupo = $grupos->first();
        $segundoGrupo = $grupos->get(1);

        foreach ($alumnos as $indice => $alumno) {
            Matricula::create([
                'user_id' => $alumno->id,
                'grupo_id' => $primerGrupo->id,
                // Por ahora solo se inscribe a quien ya pago.
                'estado' => EstadoMatricula::Activa,
                'origen' => $indice % 4 === 0 ? OrigenMatricula::Api : OrigenMatricula::Manual,
                'crm_lead_id' => $indice % 4 === 0 ? 'LEAD-'.(1000 + $indice) : null,
                'acepto_requisitos' => true,
                'fecha_aceptacion' => now(),
            ]);
        }

        foreach ($alumnos->take(8) as $alumno) {
            Matricula::create([
                'user_id' => $alumno->id,
                'grupo_id' => $segundoGrupo->id,
                'estado' => EstadoMatricula::Activa,
                'origen' => OrigenMatricula::Manual,
                'acepto_requisitos' => true,
                'fecha_aceptacion' => now(),
            ]);
        }

        $this->command->info('  Grupo 1: '.$primerGrupo->fresh()->indicadorCupo().' inscritos');
        $this->command->info('  Grupo 2: '.$segundoGrupo->fresh()->indicadorCupo().' inscritos');
    }
}
