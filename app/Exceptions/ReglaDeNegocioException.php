<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando una operacion viola una regla de negocio (RN x.y).
 *
 * Lanzarla dentro de DB::transaction() deshace todo lo escrito, y el
 * controlador la atrapa para mostrar el mensaje al usuario.
 */
class ReglaDeNegocioException extends RuntimeException {}
