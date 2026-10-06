<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nombre', 'descripcion'])]
class Rol extends Model
{
    /** Sin esto Laravel buscaria la tabla 'rols'. */
    protected $table = 'roles';

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rol_user', 'rol_id', 'user_id');
    }

    public function etiqueta(): string
    {
        return ucfirst($this->nombre);
    }
}
