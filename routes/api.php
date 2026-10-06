<?php

use App\Http\Controllers\Api\V1\MatriculaController;
use App\Http\Middleware\AccesoApiCrm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API
|--------------------------------------------------------------------------
| Llevan el prefijo /api automaticamente y son stateless (sin sesion ni CSRF).
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Integracion con el CRM (v1)
|--------------------------------------------------------------------------
| Por ahora abierta (CRM_API_REQUIERE_TOKEN=false en el .env). Con la
| variable en true exige el token Bearer de "php artisan crm:token".
*/
Route::prefix('v1')
    ->middleware(AccesoApiCrm::class)
    ->group(function () {
        Route::post('matriculas', [MatriculaController::class, 'store'])->name('api.v1.matriculas.store');
    });
