<?php

namespace App\Models;

use App\Enums\EstadoComercial;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unidad minima de ensenanza del catalogo. Sin fechas ni docente.
 * No guarda material educativo: solo el temario en texto.
 */
#[Fillable([
    'codigo', 'nombre', 'descripcion', 'requisitos', 'temario',
    'horas', 'precio', 'se_oferta_por_separado', 'estado_comercial',
])]
class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';

    protected function casts(): array
    {
        return [
            'horas' => 'integer',
            'precio' => 'decimal:2',
            'se_oferta_por_separado' => 'boolean',
            'estado_comercial' => EstadoComercial::class,
        ];
    }

    public function programas(): BelongsToMany
    {
        return $this->belongsToMany(Programa::class, 'programa_modulo', 'modulo_id', 'programa_id')
            ->withPivot('orden')
            ->withTimestamps();
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class, 'modulo_id');
    }

    /**
     * RN 3.5: el version del grupo se calcula solo.
     * "SQL - Version 4" es el cuarto grupo que se abre de SQL.
     */
    public function siguienteVersion(): int
    {
        return (int) $this->grupos()->max('version') + 1;
    }

    public function tieneActividad(): bool
    {
        return $this->grupos()->exists() || $this->programas()->exists();
    }

    public function datosCompletos(): bool
    {
        return filled($this->nombre)
            && filled($this->descripcion)
            && $this->precio !== null;
    }

    public function scopeAbiertos(Builder $query): void
    {
        $query->where('estado_comercial', EstadoComercial::Abierto);
    }

    /** RN 3.2: solo se crean grupos de modulos que NO esten en borrador. */
    public function scopeDisponiblesParaGrupo(Builder $query): void
    {
        $query->whereIn('estado_comercial', [
            EstadoComercial::Abierto->value,
            EstadoComercial::Cerrado->value,
        ]);
    }
}
