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
 * Agrupador del catalogo: N modulos en orden. Sin fechas ni docente.
 */
#[Fillable(['codigo', 'nombre', 'descripcion', 'cantidad_modulos', 'precio_contado', 'estado_comercial'])]
class Programa extends Model
{
    use HasFactory;

    protected $table = 'programas';

    protected function casts(): array
    {
        return [
            'cantidad_modulos' => 'integer',
            'precio_contado' => 'decimal:2',
            'estado_comercial' => EstadoComercial::class,
        ];
    }

    /**
     * N:M con modulos. withPivot('orden') expone el orden y orderByPivot
     * hace que los modulos lleguen SIEMPRE en su secuencia.
     */
    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'programa_modulo', 'programa_id', 'modulo_id')
            ->withPivot('orden')
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    public function cohortes(): HasMany
    {
        return $this->hasMany(Cohorte::class, 'programa_id');
    }

    /** RN 2.5: un programa no puede abrirse si le faltan modulos. */
    public function estructuraCompleta(): bool
    {
        return $this->modulos()->count() >= $this->cantidad_modulos;
    }

    public function modulosAsignados(): int
    {
        return $this->modulos()->count();
    }

    /** Ej: "4 de 6 modulos asignados" */
    public function indicadorEstructura(): string
    {
        return $this->modulosAsignados().' de '.$this->cantidad_modulos.' modulos asignados';
    }

    /** La siguiente posicion libre al agregar un modulo. */
    public function siguienteOrden(): int
    {
        return (int) $this->modulos()->max('orden') + 1;
    }

    /** RN 2.6: solo se vuelve a borrador si nunca tuvo grupos ni inscritos. */
    public function tieneActividad(): bool
    {
        return $this->cohortes()->exists()
            || Grupo::whereIn('cohorte_id', $this->cohortes()->select('id'))->exists();
    }

    /** RN 2.4: para pasar a abierto el registro debe estar completo. */
    public function datosCompletos(): bool
    {
        return filled($this->nombre)
            && filled($this->descripcion)
            && $this->precio_contado !== null;
    }

    public function scopeAbiertos(Builder $query): void
    {
        $query->where('estado_comercial', EstadoComercial::Abierto);
    }
}
