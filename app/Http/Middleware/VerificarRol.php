<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de rol. Se usa en las rutas asi:
 *
 *   Route::middleware('rol:administrador')->group(...)
 *   Route::middleware('rol:administrador,docente')->group(...)   // cualquiera de los dos
 *
 * Los permisos se validan en el SERVIDOR, no ocultando botones:
 * si un docente escribe la URL a mano, recibe 403.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('login');
        }

        // RN 1.2: un usuario desactivado no opera, aunque tenga sesion viva.
        if (! $usuario->activo) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta esta desactivada.']);
        }

        if (! $usuario->tieneAlgunRol($roles)) {
            abort(403, 'No tienes permiso para acceder a esta seccion.');
        }

        return $next($request);
    }
}
