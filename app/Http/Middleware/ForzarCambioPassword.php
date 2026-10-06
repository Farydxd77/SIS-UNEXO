<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RN 1.7: obligacion de cambiar la contrasena en el primer ingreso.
 *
 * Si el usuario tiene debe_cambiar_password = true, se le redirige a la
 * pantalla de cambio y no puede navegar a ningun otro sitio hasta hacerlo.
 */
class ForzarCambioPassword
{
    /** Rutas que SI puede visitar mientras tiene el cambio pendiente. */
    private const PERMITIDAS = [
        'password.cambio',
        'password.actualizar',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->debe_cambiar_password && ! $request->routeIs(self::PERMITIDAS)) {
            return redirect()->route('password.cambio')
                ->with('aviso', 'Por seguridad, debes cambiar tu contrasena antes de continuar.');
        }

        return $next($request);
    }
}
