<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Http\Requests\StoreModuloRequest;
use App\Http\Requests\UpdateModuloRequest;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuloController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->query('buscar');
        $estado = $request->query('estado');

        $modulos = Modulo::query()
            ->withCount(['grupos', 'programas'])
            ->when($buscar, fn ($q, $buscar) => $q->where(function ($sub) use ($buscar) {
                $sub->whereLike('nombre', "%{$buscar}%")
                    ->orWhereLike('codigo', "%{$buscar}%");
            }))
            ->when($estado, fn ($q, $estado) => $q->where('estado_comercial', $estado))
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return view('modulos.index', [
            'modulos' => $modulos,
            'buscar' => $buscar,
            'estado' => $estado,
            'estados' => EstadoComercial::cases(),
        ]);
    }

    public function create(): View
    {
        return view('modulos.create');
    }

    public function store(StoreModuloRequest $request): RedirectResponse
    {
        $modulo = Modulo::create($request->validated());

        return redirect()->route('modulos.show', $modulo)
            ->with('exito', "Modulo \"{$modulo->nombre}\" creado en estado borrador.");
    }

    /** Detalle: en que programas esta y que grupos se abrieron de el. */
    public function show(Modulo $modulo): View
    {
        $modulo->load([
            'programas',
            'grupos.docente',
            'grupos.cohorte',
            'grupos.horarios',
        ]);

        return view('modulos.show', compact('modulo'));
    }

    public function edit(Modulo $modulo): View
    {
        return view('modulos.edit', compact('modulo'));
    }

    public function update(UpdateModuloRequest $request, Modulo $modulo): RedirectResponse
    {
        $modulo->update($request->validated());

        return redirect()->route('modulos.show', $modulo)
            ->with('exito', "Modulo \"{$modulo->nombre}\" actualizado.");
    }

    /**
     * RN 2.7: no se elimina un modulo con relaciones; en ese caso se cierra.
     */
    public function destroy(Modulo $modulo): RedirectResponse
    {
        if ($modulo->tieneActividad()) {
            return redirect()->route('modulos.index')->with(
                'error',
                "No se puede eliminar \"{$modulo->nombre}\": esta en ".$modulo->programas()->count()
                .' programa(s) y tiene '.$modulo->grupos()->count().' grupo(s). Cierralo en lugar de eliminarlo.'
            );
        }

        $nombre = $modulo->nombre;
        $modulo->delete();

        return redirect()->route('modulos.index')->with('exito', "Modulo \"{$nombre}\" eliminado.");
    }

    /**
     * Maquina de estados comerciales: borrador -> abierto <-> cerrado
     */
    public function cambiarEstado(Request $request, Modulo $modulo): RedirectResponse
    {
        $datos = $request->validate([
            'estado_comercial' => ['required', 'string'],
        ]);

        $destino = EstadoComercial::tryFrom($datos['estado_comercial']);

        if (! $destino) {
            return back()->with('error', 'Estado no valido.');
        }

        if (! $modulo->estado_comercial->puedePasarA($destino)) {
            return back()->with(
                'error',
                "No se puede pasar de {$modulo->estado_comercial->etiqueta()} a {$destino->etiqueta()}."
            );
        }

        // RN 2.4: para abrir, el registro debe estar completo.
        if ($destino === EstadoComercial::Abierto && ! $modulo->datosCompletos()) {
            return back()->with(
                'error',
                'Para abrir el modulo faltan datos: necesita nombre, descripcion y precio.'
            );
        }

        // RN 2.6: solo se vuelve a borrador si nunca tuvo grupos ni inscritos.
        if ($destino === EstadoComercial::Borrador && $modulo->tieneActividad()) {
            return back()->with(
                'error',
                'No se puede volver a borrador: el modulo ya esta en programas o tiene grupos abiertos.'
            );
        }

        $modulo->update(['estado_comercial' => $destino]);

        return back()->with('exito', "Modulo ahora en estado {$destino->etiqueta()}.");
    }
}
