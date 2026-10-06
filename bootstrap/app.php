<?php

use App\Http\Middleware\ForzarCambioPassword;
use App\Http\Middleware\VerificarRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En Render la app queda detras de su proxy (que termina el HTTPS):
        // sin esto Laravel generaria links http:// en un sitio https://.
        $middleware->trustProxies(at: '*');

        // Alias para poder escribir middleware('rol:administrador') en las rutas.
        $middleware->alias([
            'rol' => VerificarRol::class,
            'password.cambiada' => ForzarCambioPassword::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // En /api/* un 404 responde un JSON limpio, sin el stack trace de debug.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Recurso no encontrado.'], 404);
            }
        });
    })->create();
