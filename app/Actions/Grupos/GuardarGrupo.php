<?php

namespace App\Actions\Grupos;

use App\Models\Grupo;
use App\Models\Modulo;
use Illuminate\Support\Facades\DB;

/**
 * Crea o actualiza un grupo junto con sus horarios.
 *
 * Escribe en dos tablas (grupos y horarios), por eso va en transaccion:
 * nunca queda un grupo guardado con la mitad de su horario.
 */
class GuardarGrupo
{
    /**
     * @param  array  $horarios  Filas [dia_semana, hora_inicio, hora_fin]
     */
    public function crear(array $datos, array $horarios): Grupo
    {
        return DB::transaction(function () use ($datos, $horarios) {
            // RN 3.5: la version se calcula sola, max(version) + 1 del modulo.
            // PostgreSQL no permite FOR UPDATE junto a max(): se bloquea la fila
            // del modulo, asi dos grupos simultaneos no sacan la misma version.
            Modulo::whereKey($datos['modulo_id'])->lockForUpdate()->firstOrFail();

            $datos['version'] = (int) Grupo::where('modulo_id', $datos['modulo_id'])
                ->max('version') + 1;

            $grupo = Grupo::create($datos);
            $grupo->horarios()->createMany($horarios);

            return $grupo;
        });
    }

    public function actualizar(Grupo $grupo, array $datos, array $horarios): Grupo
    {
        return DB::transaction(function () use ($grupo, $datos, $horarios) {
            // El modulo y la version no cambian al editar: identifican al grupo.
            unset($datos['modulo_id'], $datos['version']);

            $grupo->update($datos);

            // Las filas dinamicas del formulario reemplazan a las anteriores.
            $grupo->horarios()->delete();
            $grupo->horarios()->createMany($horarios);

            return $grupo;
        });
    }
}
