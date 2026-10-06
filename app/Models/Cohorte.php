<?php

namespace App\Models;

use App\Enums\EstadoCohorte;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

/**
 * Edicion concreta de un PROGRAMA. Ej: "DAE - Version 3, Marzo 2027".
 * Agrupa los grupos de cada uno de sus modulos.
 */
#[Fillable(['programa_id', 'nombre', 'fecha_inicio', 'fecha_fin', 'estado'])]
class Cohorte extends Model
{
    use HasFactory;

    protected $table = 'cohortes';

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => EstadoCohorte::class,
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class, 'programa_id');
    }

    /** Los grupos generados para esta cohorte, en el orden del programa. */
    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'cohorte_id');
    }

    /** Todas las matriculas de todos sus grupos. */
    public function matriculas(): HasManyThrough
    {
        return $this->hasManyThrough(Matricula::class, Grupo::class, 'cohorte_id', 'grupo_id');
    }

    public function totalInscritos(): int
    {
        return Matricula::whereIn('grupo_id', $this->grupos()->select('id'))
            ->whereIn('estado', EstadoMatricula::vigentes())
            ->count();
    }

    /**
     * Inscribirse a la cohorte crea una matricula en cada uno de sus grupos;
     * quien entro a un solo grupo tomo un modulo suelto. Como las matriculas
     * nunca se borran (RN 4.8), basta con que el alumno este en todos los
     * grupos. Los cancelados en los que no esta no cuentan: se cancelaron
     * antes de que se inscribiera.
     *
     * @param  Collection<int, int>  $gruposDelAlumno  ids de los grupos donde tiene matricula
     */
    public function esProgramaCompletoDe(Collection $gruposDelAlumno): bool
    {
        return $this->grupos->isNotEmpty() && $this->grupos->every(
            fn (Grupo $grupo) => $gruposDelAlumno->contains($grupo->id) || $grupo->estado === EstadoGrupo::Cancelado
        );
    }

    public function scopeVigentes(Builder $query): void
    {
        $query->whereNotIn('estado', EstadoCohorte::historicos());
    }

    /** Finalizadas o canceladas: las cohortes anteriores. */
    public function scopeHistoricas(Builder $query): void
    {
        $query->whereIn('estado', EstadoCohorte::historicos());
    }

    /** Cuantos de sus grupos ya tienen docente y horario listos. */
    public function gruposListos(): int
    {
        return $this->grupos()
            ->whereNotNull('docente_id')
            ->whereHas('horarios')
            ->count();
    }
}
