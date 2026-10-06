<?php

namespace App\Actions\Grupos;

use App\Enums\EstadoGrupo;
use App\Models\Grupo;
use Illuminate\Support\Collection;

/**
 * RN 3.4 - Validacion de choque de horario de un docente.
 *
 * Dos grupos del mismo docente chocan si se cumplen LAS TRES condiciones:
 *   1. Mismo docente_id
 *   2. Las fechas se solapan:  g1.inicio <= g2.fin  AND  g1.fin >= g2.inicio
 *   3. Coincide el dia y las horas se solapan:
 *      h1.dia = h2.dia  AND  h1.inicio < h2.fin  AND  h1.fin > h2.inicio
 *
 * Ejemplos:
 *   SQL lun/mie 19-21 (oct-nov) vs Python jue 19-21 (oct-nov) -> NO choca (dias distintos)
 *   SQL lun 19-21 (oct-nov)     vs Python lun 20-22 (oct-nov) -> SI choca
 *   SQL lun 19-21 (oct-nov)     vs Python lun 19-21 (feb-mar) -> NO choca (fechas distintas)
 */
class DetectarChoqueHorario
{
    /**
     * @param  array  $horarios  Filas [dia_semana, hora_inicio, hora_fin]
     * @param  int|null  $grupoIdExcluido  Al editar, el propio grupo se excluye
     *                                     para que no choque consigo mismo.
     * @return Collection<int, object> Choques encontrados, con el grupo y el horario.
     */
    public function ejecutar(
        int $docenteId,
        string $fechaInicio,
        string $fechaFin,
        array $horarios,
        ?int $grupoIdExcluido = null,
    ): Collection {
        if ($horarios === []) {
            return collect();
        }

        $choques = collect();

        foreach ($horarios as $horario) {
            $dia = (int) ($horario['dia_semana'] ?? 0);
            $desde = $horario['hora_inicio'] ?? null;
            $hasta = $horario['hora_fin'] ?? null;

            if ($dia === 0 || blank($desde) || blank($hasta)) {
                continue;
            }

            $encontrados = Grupo::query()
                ->select('grupos.*')
                ->join('horarios', 'horarios.grupo_id', '=', 'grupos.id')
                ->with(['modulo', 'horarios'])
                ->where('grupos.docente_id', $docenteId)
                // Un grupo cancelado o finalizado ya no ocupa la agenda.
                ->whereIn('grupos.estado', EstadoGrupo::vigentesParaChoque())
                ->when($grupoIdExcluido, fn ($q) => $q->where('grupos.id', '!=', $grupoIdExcluido))
                // 2) solapamiento de fechas
                ->where('grupos.fecha_inicio', '<=', $fechaFin)
                ->where('grupos.fecha_fin', '>=', $fechaInicio)
                // 3) mismo dia y solapamiento de horas
                ->where('horarios.dia_semana', $dia)
                ->where('horarios.hora_inicio', '<', $hasta)
                ->where('horarios.hora_fin', '>', $desde)
                ->distinct()
                ->get();

            foreach ($encontrados as $grupo) {
                $choques->push($grupo);
            }
        }

        return $choques->unique('id')->values();
    }

    /**
     * Mensaje descriptivo, como pide la spec:
     * "El docente ya dicta SQL - Version 4 los lunes de 19:00 a 21:00"
     */
    public function mensaje(Collection $choques): string
    {
        $detalles = $choques->map(function (Grupo $grupo) {
            $cuando = $grupo->horarios
                ->map(fn ($h) => $h->resumenLargo())
                ->join(' y ');

            return $grupo->nombre_completo.' '.$cuando;
        })->join('; ');

        return 'El docente ya dicta '.$detalles.'.';
    }

    /** Atajo booleano para cuando solo interesa si choca o no. */
    public function hayChoque(
        int $docenteId,
        string $fechaInicio,
        string $fechaFin,
        array $horarios,
        ?int $grupoIdExcluido = null,
    ): bool {
        return $this->ejecutar($docenteId, $fechaInicio, $fechaFin, $horarios, $grupoIdExcluido)->isNotEmpty();
    }
}
