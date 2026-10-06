<?php

namespace App\Http\Requests;

use App\Models\Grupo;

/**
 * Al editar, el modulo no se toca: junto con la version identifica al grupo.
 */
class UpdateGrupoRequest extends GrupoRequest
{
    protected function grupoActual(): ?Grupo
    {
        return $this->route('grupo');
    }
}
