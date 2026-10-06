<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acceso a la API del CRM. Con CRM_API_REQUIERE_TOKEN=false queda abierta
 * (por ahora, mientras se acuerda con el cliente); si no, exige el token
 * de "php artisan crm:token", de un administrador activo y con el permiso
 * de la ruta: AccesoApiCrm::class.':catalogo:leer'.
 */
class AccesoApiCrm
{
    public function handle(Request $request, Closure $next, string $permiso = 'matriculas:crear'): Response
    {
        if (! config('services.crm.requiere_token')) {
            return $next($request);
        }

        Auth::shouldUse('sanctum');
        $usuario = $request->user();

        if (! $usuario) {
            throw new AuthenticationException;
        }

        abort_unless($usuario->activo && $usuario->esAdministrador(), 403, 'El token debe pertenecer a un administrador activo.');
        abort_unless($usuario->tokenCan($permiso), 403, "El token no tiene el permiso {$permiso}.");

        return $next($request);
    }
}
