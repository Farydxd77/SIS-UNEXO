<?php

use App\Http\Controllers\Api\V1\CatalogoController;
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
Route::prefix('v1')->group(function () {
    // Lo que el CRM puede vender: programas, cohortes y grupos abiertos.
    Route::get('catalogo', [CatalogoController::class, 'index'])
        ->middleware(AccesoApiCrm::class.':catalogo:leer')
        ->name('api.v1.catalogo.index');

    Route::post('matriculas', [MatriculaController::class, 'store'])
        ->middleware(AccesoApiCrm::class.':matriculas:crear')
        ->name('api.v1.matriculas.store');
});
