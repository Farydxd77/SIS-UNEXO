<?php

namespace App\Policies;

use App\Enums\EstadoMatricula;
use App\Models\Matricula;
use App\Models\User;

class MatriculaPolicy
{
    /** Matricular alumnos: solo admin (seccion 4 de la spec). */
    public function create(User $usuario): bool
    {
        return $usuario->esAdministrador();
    }

    /** Ver una matricula: admin, el docente del grupo, o el propio alumno. */
    public function view(User $usuario, Matricula $matricula): bool
    {
        if ($usuario->esAdministrador()) {
            return true;
        }

        if ($matricula->user_id === $usuario->id) {
            return true;
        }

        return $usuario->esDocente() && $matricula->grupo->docente_id === $usuario->id;
    }

    /** Cambiar el estado (confirmar pago, anular): solo admin. */
    public function cambiarEstado(User $usuario, Matricula $matricula): bool
    {
        return $usuario->esAdministrador();
    }

    /** RN 4.9: el retiro exige motivo; lo ejecuta el admin. */
    public function retirar(User $usuario, Matricula $matricula): bool
    {
        return $usuario->esAdministrador()
            && $matricula->estado === EstadoMatricula::Activa;
    }

    /** RN 4.10: la nota la carga el docente del grupo; el admin corrige. */
    public function registrarNota(User $usuario, Matricula $matricula): bool
    {
        if ($usuario->esAdministrador()) {
            return true;
        }

        return $usuario->esDocente() && $matricula->grupo->docente_id === $usuario->id;
    }

    /** Una matricula nunca se borra (RN 4.8). */
    public function delete(User $usuario, Matricula $matricula): bool
    {
        return false;
    }
}
