<?php

namespace App\Policies;

use App\Enums\EstadoMatricula;
use App\Models\Grupo;
use App\Models\User;

/**
 * Autorizacion de grupos (seccion 4 de la spec).
 *
 * La clave: un docente solo toca SUS grupos. Si escribe a mano la URL de
 * un grupo que no es suyo, esto devuelve false y el controlador aborta 403.
 */
class GrupoPolicy
{
    /** Ver el listado completo de grupos: solo admin. */
    public function viewAny(User $usuario): bool
    {
        return $usuario->esAdministrador();
    }

    /** Ver un grupo: admin siempre; docente si es suyo; estudiante si esta matriculado. */
    public function view(User $usuario, Grupo $grupo): bool
    {
        if ($usuario->esAdministrador()) {
            return true;
        }

        if ($usuario->esDocente() && $grupo->docente_id === $usuario->id) {
            return true;
        }

        return $grupo->matriculas()
            ->where('user_id', $usuario->id)
            ->whereIn('estado', EstadoMatricula::vigentes())
            ->exists();
    }

    public function create(User $usuario): bool
    {
        return $usuario->esAdministrador();
    }

    public function update(User $usuario, Grupo $grupo): bool
    {
        return $usuario->esAdministrador();
    }

    /** RN 3.10: no se elimina un grupo con inscritos; se cancela. */
    public function delete(User $usuario, Grupo $grupo): bool
    {
        return $usuario->esAdministrador() && $grupo->matriculas()->doesntExist();
    }

    /** Ver la lista de alumnos: admin, o el docente del grupo. */
    public function verAlumnos(User $usuario, Grupo $grupo): bool
    {
        return $usuario->esAdministrador()
            || ($usuario->esDocente() && $grupo->docente_id === $usuario->id);
    }

    /** RN 3.9: el enlace de Meet. La regla vive en el modelo, aqui se expone. */
    public function verEnlaceMeet(User $usuario, Grupo $grupo): bool
    {
        return $grupo->puedeVerEnlaceMeet($usuario);
    }

    /** RN 4.10: solo el docente del grupo carga sus notas. El admin puede corregir. */
    public function registrarNotas(User $usuario, Grupo $grupo): bool
    {
        if ($usuario->esAdministrador()) {
            return true;
        }

        return $usuario->esDocente() && $grupo->docente_id === $usuario->id;
    }

    /** Cambiar el estado del grupo: solo admin. */
    public function cambiarEstado(User $usuario, Grupo $grupo): bool
    {
        return $usuario->esAdministrador();
    }
}
