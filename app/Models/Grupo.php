<?php

namespace App\Models;

use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Edicion concreta de un MODULO: cuando se dicta, con que docente y donde.
 * Aqui SI hay fechas, docente, horarios y enlace de Meet.
 */
#[Fillable([
    'modulo_id', 'cohorte_id', 'docente_id', 'version',
    'fecha_inicio', 'fecha_fin', 'cupo_minimo', 'cupo_maximo',
    'enlace_meet', 'estado',
])]
class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'version' => 'integer',
            'cupo_minimo' => 'integer',
            'cupo_maximo' => 'integer',
            'estado' => EstadoGrupo::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'modulo_id');
    }

    /** Nullable: un grupo de modulo suelto no pertenece a ninguna cohorte. */
    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class, 'cohorte_id');
    }

    /** El docente es un User con rol docente. */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'grupo_id');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'grupo_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Cupo
    |--------------------------------------------------------------------------
    */

    /** Inscritos que cuentan para el cupo: reservada + activa. */
    public function inscritosVigentes(): int
    {
        return $this->matriculas()
            ->whereIn('estado', EstadoMatricula::vigentes())
            ->count();
    }

    public function alcanzoCupoMinimo(): bool
    {
        return $this->inscritosVigentes() >= $this->cupo_minimo;
    }

    /** RN 4.3: no se puede superar el cupo maximo si esta definido. */
    public function tieneEspacio(): bool
    {
        if ($this->cupo_maximo === null) {
            return true;
        }

        return $this->inscritosVigentes() < $this->cupo_maximo;
    }

    /** Ej: "14 / 20" */
    public function indicadorCupo(): string
    {
        return $this->inscritosVigentes().' / '.$this->cupo_minimo;
    }

    /*
    |--------------------------------------------------------------------------
    | Nombre y presentacion
    |--------------------------------------------------------------------------
    */

    /** Ej: "SQL - Version 4" */
    public function getNombreCompletoAttribute(): string
    {
        $modulo = $this->relationLoaded('modulo') ? $this->modulo : $this->modulo()->first();

        return ($modulo?->nombre ?? 'Modulo').' - Version '.$this->version;
    }

    /** Ej: "Lun 19:00-21:00, Mie 19:00-21:00" */
    public function resumenHorario(): string
    {
        return $this->horarios
            ->map(fn (Horario $h) => $h->resumenCorto())
            ->join(', ');
    }

    /*
    |--------------------------------------------------------------------------
    | Reglas de transicion de estado
    |--------------------------------------------------------------------------
    */

    /** RN 3.6: para en_convocatoria debe tener fechas y al menos un horario. */
    public function puedeIrAConvocatoria(): bool
    {
        return filled($this->fecha_inicio)
            && filled($this->fecha_fin)
            && $this->horarios()->exists();
    }

    /** RN 3.7: para habilitado necesita cupo minimo, docente y enlace de Meet. */
    public function puedeSerHabilitado(): bool
    {
        return $this->alcanzoCupoMinimo()
            && $this->docente_id !== null
            && filled($this->enlace_meet);
    }

    /**
     * Motivos por los que NO se puede habilitar, para un mensaje descriptivo.
     */
    public function faltantesParaHabilitar(): array
    {
        $faltan = [];

        if (! $this->alcanzoCupoMinimo()) {
            $faltan[] = 'faltan '.($this->cupo_minimo - $this->inscritosVigentes()).' inscritos para el cupo minimo';
        }
        if ($this->docente_id === null) {
            $faltan[] = 'no tiene docente asignado';
        }
        if (blank($this->enlace_meet)) {
            $faltan[] = 'no tiene enlace de Meet';
        }

        return $faltan;
    }

    /**
     * RN 3.9: el enlace de Meet solo lo ven el admin, el docente del grupo
     * y los alumnos con matricula activa.
     */
    public function puedeVerEnlaceMeet(User $usuario): bool
    {
        if ($usuario->esAdministrador()) {
            return true;
        }

        if ($this->docente_id === $usuario->id) {
            return true;
        }

        return $this->matriculas()
            ->where('user_id', $usuario->id)
            ->where('estado', EstadoMatricula::Activa)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDelDocente(Builder $query, int $docenteId): void
    {
        $query->where('docente_id', $docenteId);
    }

    public function scopeAdmiteInscripciones(Builder $query): void
    {
        $query->whereIn('estado', [
            EstadoGrupo::EnConvocatoria->value,
            EstadoGrupo::Habilitado->value,
        ]);
    }
}
