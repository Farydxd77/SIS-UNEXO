<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Enums\ResultadoMatricula;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Clase asociativa entre un estudiante y un grupo, con atributos propios:
 * estado, origen, aceptacion de requisitos, nota y resultado.
 * Nunca se borra: se retira o se anula (RN 4.8).
 */
#[Fillable([
    'user_id', 'grupo_id', 'estado', 'origen', 'crm_lead_id',
    'acepto_requisitos', 'fecha_aceptacion', 'nota_final',
    'resultado', 'motivo_retiro',
])]
class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    protected function casts(): array
    {
        return [
            'estado' => EstadoMatricula::class,
            'origen' => OrigenMatricula::class,
            'resultado' => ResultadoMatricula::class,
            'acepto_requisitos' => 'boolean',
            'fecha_aceptacion' => 'datetime',
            'nota_final' => 'decimal:2',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Estado
    |--------------------------------------------------------------------------
    */

    public function esVigente(): bool
    {
        return in_array($this->estado, [EstadoMatricula::Reservada, EstadoMatricula::Activa], true);
    }

    /** Solo las matriculas activas pueden recibir nota. */
    public function puedeRecibirNota(): bool
    {
        return $this->estado === EstadoMatricula::Activa;
    }

    /**
     * Registra la nota y calcula el resultado. RN 4.11: nota entre 0 y 100.
     * No cambia el estado: pasa a finalizada cuando el grupo tiene todas
     * sus notas cargadas (ver App\Actions\Matriculas\RegistrarNotas).
     */
    public function registrarNota(float $nota): void
    {
        $this->update([
            'nota_final' => $nota,
            'resultado' => ResultadoMatricula::segunNota($nota),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeVigentes(Builder $query): void
    {
        $query->whereIn('estado', EstadoMatricula::vigentes());
    }

    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', EstadoMatricula::Activa);
    }

    public function scopeDelEstudiante(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }
}
