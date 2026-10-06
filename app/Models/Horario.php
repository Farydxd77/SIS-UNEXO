<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un dia y tramo horario de clase de un grupo.
 * Es la tabla que hace posible validar el choque de horarios (RN 3.4).
 */
#[Fillable(['grupo_id', 'dia_semana', 'hora_inicio', 'hora_fin'])]
class Horario extends Model
{
    use HasFactory;

    protected $table = 'horarios';

    protected function casts(): array
    {
        return [
            'dia_semana' => DiaSemana::class,
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    /** Las horas llegan como '19:00:00'; para mostrar sobra el segundero. */
    public function horaInicioCorta(): string
    {
        return substr((string) $this->hora_inicio, 0, 5);
    }

    public function horaFinCorta(): string
    {
        return substr((string) $this->hora_fin, 0, 5);
    }

    /** Ej: "Lun 19:00-21:00" */
    public function resumenCorto(): string
    {
        return $this->dia_semana->abreviatura().' '.$this->horaInicioCorta().'-'.$this->horaFinCorta();
    }

    /** Ej: "los lunes de 19:00 a 21:00" */
    public function resumenLargo(): string
    {
        return 'los '.$this->dia_semana->plural().' de '
            .$this->horaInicioCorta().' a '.$this->horaFinCorta();
    }
}
