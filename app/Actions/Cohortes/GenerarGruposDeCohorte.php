<?php

namespace App\Actions\Cohortes;

use App\Enums\EstadoGrupo;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use Illuminate\Support\Facades\DB;

/**
 * Al abrir una cohorte, el sistema genera automaticamente un grupo por cada
 * modulo del programa, respetando el orden de programa_modulo (Modulo 3).
 *
 * Si el programa tiene 6 modulos, se crean 6 grupos en estado planificado,
 * todos con el cohorte_id de esa cohorte.
 */
class GenerarGruposDeCohorte
{
    /**
     * @param  int|null  $semanasPorModulo  Si se pasa, encadena las fechas:
     *                                      el grupo 2 empieza cuando termina el 1.
     * @return int Cantidad de grupos creados.
     */
    public function ejecutar(Cohorte $cohorte, ?int $semanasPorModulo = 4): int
    {
        $programa = $cohorte->programa;
        $modulos = $programa->modulos; // ya vienen ordenados por pivot.orden

        if ($modulos->isEmpty()) {
            return 0;
        }

        // Transaccion: o se crean todos los grupos o ninguno. Una cohorte
        // con la mitad de sus grupos es un estado invalido del sistema.
        return DB::transaction(function () use ($cohorte, $modulos, $semanasPorModulo) {
            $creados = 0;
            $inicio = $cohorte->fecha_inicio->copy();

            foreach ($modulos as $modulo) {
                // Fechas tentativas encadenadas.
                $fin = $inicio->copy()->addWeeks($semanasPorModulo)->subDay();

                Grupo::create([
                    'modulo_id' => $modulo->id,
                    'cohorte_id' => $cohorte->id,
                    'docente_id' => null,          // lo asigna el admin despues
                    'version' => $this->siguienteVersion($modulo),
                    'fecha_inicio' => $inicio->toDateString(),
                    'fecha_fin' => $fin->toDateString(),
                    'cupo_minimo' => 20,
                    'estado' => EstadoGrupo::Planificado,
                ]);

                $creados++;
                $inicio = $fin->copy()->addDay();
            }

            // La cohorte termina cuando termina su ultimo grupo.
            $cohorte->update(['fecha_fin' => $inicio->copy()->subDay()->toDateString()]);

            return $creados;
        });
    }

    /**
     * RN 3.5: version = max(version) + 1 para ese modulo.
     * Se calcula dentro del bucle porque cada insercion cambia el maximo.
     */
    private function siguienteVersion(Modulo $modulo): int
    {
        return (int) Grupo::where('modulo_id', $modulo->id)->max('version') + 1;
    }
}
