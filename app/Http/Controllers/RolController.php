<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRolRequest;
use App\Http\Requests\UpdateRolRequest;
use App\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RolController extends Controller
{
    public function index(): View
    {
        // withCount('usuarios') añade la propiedad usuarios_count con un COUNT
        // hecho en SQL, sin traer los usuarios.
        $roles = Rol::withCount('usuarios')->orderBy('nombre')->paginate(10);

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create');
    }

    public function store(StoreRolRequest $request): RedirectResponse
    {
        $rol = Rol::create($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('exito', "Rol \"{$rol->nombre}\" creado correctamente.");
    }

    public function edit(Rol $rol): View
    {
        return view('roles.edit', compact('rol'));
    }

    public function update(UpdateRolRequest $request, Rol $rol): RedirectResponse
    {
        $rol->update($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('exito', "Rol \"{$rol->nombre}\" actualizado correctamente.");
    }

    /**
     * Este si borra de verdad, pero solo si no lo tiene nadie asignado.
     */
    public function destroy(Rol $rol): RedirectResponse
    {
        if ($rol->usuarios()->exists()) {
            return redirect()
                ->route('roles.index')
                ->with('error', "No se puede borrar el rol \"{$rol->nombre}\": hay usuarios que lo tienen asignado.");
        }

        $nombre = $rol->nombre;
        $rol->delete();

        return redirect()
            ->route('roles.index')
            ->with('exito', "Rol \"{$nombre}\" eliminado.");
    }
}
