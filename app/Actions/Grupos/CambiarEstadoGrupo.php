<?php

namespace App\Actions\Grupos;

use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Exceptions\ReglaDeNegocioException;
use App\Models\Grupo;
use Illuminate\Support\Facades\DB;

/**
 * Maquina de estados del grupo (Modulo 3):
 *
 *   planificado -> en_convocatoria -> habilitado -> en_curso -> finalizado
 *                        \-> cancelado
 */
class CambiarEstadoGrupo
{
    public function ejecutar(Grupo $grupo, EstadoGrupo $destino): void
    {
        if (! $grupo->estado->puedePasarA($destino)) {
            throw new ReglaDeNegocioException(
                "No se puede pasar el grupo de {$grupo->estado->etiqueta()} a {$destino->etiqueta()}."
            );
        }

        // RN 3.6
        if ($destino === EstadoGrupo::EnConvocatoria && ! $grupo->puedeIrAConvocatoria()) {
            throw new ReglaDeNegocioException(
                'Para pasar a convocatoria el grupo necesita fechas y al menos un horario.'
            );
        }

        // RN 3.7
        if ($destino === EstadoGrupo::Habilitado && ! $grupo->puedeSerHabilitado()) {
            throw new ReglaDeNegocioException(
                'No se puede habilitar el grupo: '.implode('; ', $grupo->faltantesParaHabilitar()).'.'
            );
        }

        DB::transaction(function () use ($grupo, $destino) {
            $grupo->update(['estado' => $destino]);

            // RN 3.11: al cancelar un grupo, sus matriculas vigentes pasan a anulada.
            if ($destino === EstadoGrupo::Cancelado) {
                $grupo->matriculas()
                    ->whereIn('estado', EstadoMatricula::vigentes())
                    ->update(['estado' => EstadoMatricula::Anulada->value]);
            }
        });
    }
}
