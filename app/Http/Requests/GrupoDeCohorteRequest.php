<?php

namespace App\Http\Requests;

use App\Models\Grupo;

/**
 * Las mismas reglas de editar un grupo (GrupoRequest), aplicadas a cada fila
 * de la pantalla "Asignar docentes y Meet" de una cohorte. No llega por una
 * ruta propia: el controlador la arma por fila y le dice que grupo edita.
 */
class GrupoDeCohorteRequest extends GrupoRequest
{
    private ?Grupo $grupo = null;

    public function paraGrupo(Grupo $grupo): static
    {
        $this->grupo = $grupo;

        return $this;
    }

    protected function grupoActual(): ?Grupo
    {
        return $this->grupo;
    }
}
