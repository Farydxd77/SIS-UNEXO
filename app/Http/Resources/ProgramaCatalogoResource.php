<?php

namespace App\Http\Resources;

use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Programa abierto, tal como lo ve el CRM.
 *
 * @mixin Programa
 */
class ProgramaCatalogoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio_contado' => (float) $this->precio_contado,
            'cantidad_modulos' => $this->cantidad_modulos,
            'modulos' => $this->modulos->map(fn (Modulo $modulo) => [
                'orden' => $modulo->pivot->orden,
                'id' => $modulo->id,
                'codigo' => $modulo->codigo,
                'nombre' => $modulo->nombre,
                'horas' => $modulo->horas,
                'requisitos' => $modulo->requisitos,
            ])->values(),
            // Con el id de una de estas se inscribe: tipo=cohorte, cohorte_id=id.
            'cohortes' => CohorteCatalogoResource::collection($this->cohortes),
        ];
    }
}
