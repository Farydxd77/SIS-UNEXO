<?php

namespace App\Http\Resources;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Modulo que se vende por separado, con sus grupos abiertos.
 *
 * @mixin Modulo
 */
class ModuloCatalogoResource extends JsonResource
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
            'requisitos' => $this->requisitos,
            'horas' => $this->horas,
            'precio' => (float) $this->precio,
            // Con el id de uno de estos se inscribe: tipo=grupo, grupo_id=id.
            'grupos' => GrupoCatalogoResource::collection($this->grupos),
        ];
    }
}
