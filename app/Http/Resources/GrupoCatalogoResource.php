<?php

namespace App\Http\Resources;

use App\Models\Grupo;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Grupo en el catalogo del CRM. No incluye docente ni enlace de Meet
 * (RN 3.9: el enlace solo lo ven el admin, el docente y los inscritos).
 *
 * @mixin Grupo
 */
class GrupoCatalogoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre_completo,
            'modulo_codigo' => $this->modulo->codigo,
            'estado' => $this->estado->value,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            'cupo_maximo' => $this->cupo_maximo,
            'cupos_disponibles' => $this->cuposDisponibles(),
            'admite_inscripciones' => $this->aceptaInscripcionesAhora(),
            'horarios' => $this->horarios->map(fn (Horario $horario) => [
                'dia_semana' => $horario->dia_semana->value,
                'dia' => $horario->dia_semana->etiqueta(),
                'hora_inicio' => $horario->horaInicioCorta(),
                'hora_fin' => $horario->horaFinCorta(),
            ])->values(),
        ];
    }
}
