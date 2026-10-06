<?php

namespace App\Actions\Matriculas;

use App\Enums\EstadoMatricula;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Registro de notas de un grupo (Modulo 4).
 *
 * - El docente carga la nota de cada alumno con matricula activa.
 * - El resultado (aprobado / reprobado) lo calcula el sistema.
 * - Cuando TODAS las matriculas activas tienen nota, pasan a finalizada.
 * - El admin puede corregir notas de matriculas ya finalizadas.
 */
class RegistrarNotas
{
    /**
     * @param  array<int|string, mixed>  $notas  [matricula_id => nota]; vacio = sin cargar
     * @return array{guardadas: int, finalizadas: int}
     */
    public function ejecutar(Grupo $grupo, array $notas, User $usuario): array
    {
        return DB::transaction(function () use ($grupo, $notas, $usuario) {
            $guardadas = 0;

            $editables = [EstadoMatricula::Activa->value];
            if ($usuario->esAdministrador()) {
                $editables[] = EstadoMatricula::Finalizada->value;
            }

            // Solo matriculas de ESTE grupo: un id ajeno enviado a mano se ignora.
            $matriculas = $grupo->matriculas()
                ->whereIn('estado', $editables)
                ->whereIn('id', array_keys($notas))
                ->get();

            foreach ($matriculas as $matricula) {
                $nota = $notas[$matricula->id] ?? null;

                if ($nota === null || $nota === '') {
                    continue;
                }

                $matricula->registrarNota((float) $nota);
                $guardadas++;
            }

            // Al cargar todas las notas, las matriculas pasan a finalizada.
            $activas = $grupo->matriculas()->where('estado', EstadoMatricula::Activa);
            $finalizadas = 0;

            if ($activas->clone()->exists() && $activas->clone()->whereNull('nota_final')->doesntExist()) {
                $finalizadas = $activas->clone()->update(['estado' => EstadoMatricula::Finalizada->value]);
            }

            return ['guardadas' => $guardadas, 'finalizadas' => $finalizadas];
        });
    }
}
