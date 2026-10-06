<?php

namespace App\Http\Requests;

use App\Models\Grupo;

class StoreGrupoRequest extends GrupoRequest
{
    protected function grupoActual(): ?Grupo
    {
        return null;
    }

    public function rules(): array
    {
        return [
            'modulo_id' => ['required', 'exists:modulos,id'],
            ...parent::rules(),
        ];
    }
}
