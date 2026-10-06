<?php

namespace App\Http\Resources;

use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Matricula
 */
class MatriculaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado->value,
            'origen' => $this->origen->value,
            'crm_lead_id' => $this->crm_lead_id,
            'grupo' => [
                'id' => $this->grupo->id,
                'nombre' => $this->grupo->nombre_completo,
                'modulo' => $this->grupo->modulo->codigo,
                'cohorte_id' => $this->grupo->cohorte_id,
                'fecha_inicio' => $this->grupo->fecha_inicio->toDateString(),
                'fecha_fin' => $this->grupo->fecha_fin->toDateString(),
                'enlace_meet' => $this->grupo->enlace_meet,
            ],
        ];
    }
}
