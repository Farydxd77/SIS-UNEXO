<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'apellidos',
    'documento',
    'email',
    'correo_institucional',
    'telefono',
    'password',
    'activo',
    'google_id',
    'moodle_user_id',
    'crm_id',
    'debe_cambiar_password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROL_ADMINISTRADOR = 'administrador';

    public const ROL_DOCENTE = 'docente';

    public const ROL_ESTUDIANTE = 'estudiante';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Encripta la contrasena automaticamente al asignarla (RN 1.7).
            'password' => 'hashed',
            'activo' => 'boolean',
            'debe_cambiar_password' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /** N:M con roles a traves de rol_user. Un usuario puede ser docente Y estudiante. */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_user', 'user_id', 'rol_id');
    }

    /**
     * PRIMER camino hacia grupos: como DOCENTE (uno por grupo).
     */
    public function gruposComoDocente(): HasMany
    {
        return $this->hasMany(Grupo::class, 'docente_id');
    }

    /**
     * SEGUNDO camino hacia grupos: como ESTUDIANTE, via matriculas.
     */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'user_id');
    }

    /** Los grupos en los que esta inscrito como alumno. */
    public function gruposComoEstudiante(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'matriculas', 'user_id', 'grupo_id')
            ->withPivot(['estado', 'nota_final', 'resultado'])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de rol
    |--------------------------------------------------------------------------
    */

    public function tieneRol(string $rol): bool
    {
        // relationLoaded evita una consulta extra si los roles ya vinieron con with().
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('nombre', $rol);
        }

        return $this->roles()->where('nombre', $rol)->exists();
    }

    public function tieneAlgunRol(array $roles): bool
    {
        foreach ($roles as $rol) {
            if ($this->tieneRol($rol)) {
                return true;
            }
        }

        return false;
    }

    public function esAdministrador(): bool
    {
        return $this->tieneRol(self::ROL_ADMINISTRADOR);
    }

    public function esDocente(): bool
    {
        return $this->tieneRol(self::ROL_DOCENTE);
    }

    public function esEstudiante(): bool
    {
        return $this->tieneRol(self::ROL_ESTUDIANTE);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors y scopes
    |--------------------------------------------------------------------------
    */

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->name} {$this->apellidos}");
    }

    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }

    public function scopeConRol(Builder $query, string $rol): void
    {
        $query->whereHas('roles', fn (Builder $q) => $q->where('nombre', $rol));
    }

    /**
     * RN 1.5: no se puede quitar el rol al ultimo administrador activo.
     * Cuenta cuantos admins activos hay ademas de este usuario.
     */
    public static function otrosAdministradoresActivos(?int $exceptoId = null): int
    {
        return self::query()
            ->activos()
            ->conRol(self::ROL_ADMINISTRADOR)
            ->when($exceptoId, fn (Builder $q) => $q->whereKeyNot($exceptoId))
            ->count();
    }
}
