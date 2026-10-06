<?php

namespace App\Http\Resources;

use App\Models\Cohorte;
use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Edicion de un programa en el catalogo del CRM.
 *
 * @mixin Cohorte
 */
class CohorteCatalogoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'estado' => $this->estado->value,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            // La inscripcion a la cohorte es todo o nada: si un grupo no
            // acepta (fuera de convocatoria o lleno), la API la rechaza.
            'admite_inscripciones' => $this->grupos->isNotEmpty()
                && $this->grupos->every(fn (Grupo $grupo) => $grupo->aceptaInscripcionesAhora()),
            'grupos' => GrupoCatalogoResource::collection($this->grupos),
        ];
    }
}
