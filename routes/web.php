<?php

use App\Http\Controllers\AsignacionCohorteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\MisCursosController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publico
|--------------------------------------------------------------------------
*/
Route::get('/', [InicioController::class, 'index'])->name('inicio');

/*
|--------------------------------------------------------------------------
| Autenticacion
|--------------------------------------------------------------------------
| La ruta DEBE llamarse 'login': es el nombre al que el middleware auth
| redirige cuando alguien intenta entrar sin sesion.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Cambio de contrasena obligatorio (RN 1.7)
|--------------------------------------------------------------------------
| Fuera del grupo 'password.cambiada' a proposito: son justamente las
| rutas que el usuario SI puede visitar mientras tiene el cambio pendiente.
*/
Route::middleware('auth')->group(function () {
    Route::get('/password/cambio', [PerfilController::class, 'formularioPassword'])->name('password.cambio');
    Route::put('/password/cambio', [PerfilController::class, 'actualizarPassword'])->name('password.actualizar');
});

/*
|--------------------------------------------------------------------------
| Zona privada - todos los roles
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'password.cambiada'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Mi perfil: ver datos propios, editar solo el telefono.
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil.show');
    Route::put('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');

    /*
    |----------------------------------------------------------------------
    | Solo administrador
    |----------------------------------------------------------------------
    | El middleware 'rol' valida en el SERVIDOR. Si un docente escribe la
    | URL a mano, recibe 403; no basta con esconder el boton en la vista.
    */
    Route::middleware('rol:administrador')->group(function () {

        // MODULO 1 - Identidad
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'usuario']);
        Route::post('usuarios/{usuario}/activar', [UserController::class, 'activar'])
            ->name('usuarios.activar');
        Route::post('usuarios/{usuario}/restablecer-password', [UserController::class, 'restablecerPassword'])
            ->name('usuarios.restablecer');

        Route::resource('roles', RolController::class)
            ->parameters(['roles' => 'rol'])
            ->except('show');

        // MODULO 2 - Catalogo academico
        Route::resource('modulos', ModuloController::class)
            ->parameters(['modulos' => 'modulo']);
        Route::patch('modulos/{modulo}/estado', [ModuloController::class, 'cambiarEstado'])
            ->name('modulos.estado');

        Route::resource('programas', ProgramaController::class)
            ->parameters(['programas' => 'programa']);
        Route::patch('programas/{programa}/estado', [ProgramaController::class, 'cambiarEstado'])
            ->name('programas.estado');

        // Pantalla de estructura: asignar y ordenar los modulos del programa.
        Route::get('programas/{programa}/estructura', [ProgramaController::class, 'estructura'])
            ->name('programas.estructura');
        Route::post('programas/{programa}/modulos', [ProgramaController::class, 'agregarModulo'])
            ->name('programas.modulos.agregar');
        Route::delete('programas/{programa}/modulos/{modulo}', [ProgramaController::class, 'quitarModulo'])
            ->name('programas.modulos.quitar');
        Route::patch('programas/{programa}/modulos/{modulo}/mover', [ProgramaController::class, 'moverModulo'])
            ->name('programas.modulos.mover');

        // MODULO 3 - Planificacion operativa
        Route::resource('cohortes', CohorteController::class)
            ->parameters(['cohortes' => 'cohorte']);
        Route::patch('cohortes/{cohorte}/estado', [CohorteController::class, 'cambiarEstado'])
            ->name('cohortes.estado');
        // Completar todos los grupos de la cohorte en una sola pantalla.
        Route::get('cohortes/{cohorte}/asignar', [AsignacionCohorteController::class, 'edit'])
            ->name('cohortes.asignar');
        Route::put('cohortes/{cohorte}/asignar', [AsignacionCohorteController::class, 'update'])
            ->name('cohortes.asignar.guardar');

        // El show de grupos va fuera: tambien lo ven docente y alumnos.
        Route::resource('grupos', GrupoController::class)
            ->parameters(['grupos' => 'grupo'])
            ->except('show');
        Route::patch('grupos/{grupo}/estado', [GrupoController::class, 'cambiarEstado'])
            ->name('grupos.estado');

        // MODULO 4 - Matriculas (nunca se borran: RN 4.8)
        Route::get('matriculas', [MatriculaController::class, 'index'])->name('matriculas.index');
        Route::get('matriculas/crear', [MatriculaController::class, 'create'])->name('matriculas.create');
        Route::post('matriculas', [MatriculaController::class, 'store'])->name('matriculas.store');
        Route::patch('matriculas/{matricula}/activar', [MatriculaController::class, 'activar'])->name('matriculas.activar');
        Route::patch('matriculas/{matricula}/anular', [MatriculaController::class, 'anular'])->name('matriculas.anular');
        Route::patch('matriculas/{matricula}/retirar', [MatriculaController::class, 'retirar'])->name('matriculas.retirar');
        Route::post('matriculas/{matricula}/reubicar', [MatriculaController::class, 'reubicar'])->name('matriculas.reubicar');
    });

    // Catalogo en modo lectura: solo lo abierto. Cualquier rol.
    Route::get('/oferta', [OfertaController::class, 'index'])->name('oferta.index');

    // "Mis cursos" de Moodle: lo que cursa y lo que imparte. Cualquier rol.
    Route::get('/mis-cursos', [MisCursosController::class, 'index'])->name('mis-cursos.index');

    /*
    |----------------------------------------------------------------------
    | Grupos: detalle y notas
    |----------------------------------------------------------------------
    | La ruta deja pasar a cualquier usuario autenticado, pero GrupoPolicy
    | decide en el servidor: un docente que escribe a mano la URL de un
    | grupo ajeno recibe 403.
    */
    Route::get('grupos/{grupo}', [GrupoController::class, 'show'])
        ->whereNumber('grupo')
        ->name('grupos.show');

    Route::middleware('rol:administrador,docente')->group(function () {
        Route::get('grupos/{grupo}/notas', [NotaController::class, 'edit'])->name('grupos.notas.edit');
        Route::put('grupos/{grupo}/notas', [NotaController::class, 'update'])->name('grupos.notas.update');
    });
});
