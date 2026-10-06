<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Http\Requests\StoreProgramaRequest;
use App\Http\Requests\UpdateProgramaRequest;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramaController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->query('buscar');
        $estado = $request->query('estado');

        $programas = Programa::query()
            ->withCount(['modulos', 'cohortes'])
            ->when($buscar, fn ($q, $buscar) => $q->where(function ($sub) use ($buscar) {
                $sub->whereLike('nombre', "%{$buscar}%")
                    ->orWhereLike('codigo', "%{$buscar}%");
            }))
            ->when($estado, fn ($q, $estado) => $q->where('estado_comercial', $estado))
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return view('programas.index', [
            'programas' => $programas,
            'buscar' => $buscar,
            'estado' => $estado,
            'estados' => EstadoComercial::cases(),
        ]);
    }

    public function create(): View
    {
        return view('programas.create');
    }

    public function store(StoreProgramaRequest $request): RedirectResponse
    {
        $programa = Programa::create($request->validated());

        return redirect()->route('programas.estructura', $programa)
            ->with('exito', "Programa creado. Ahora asignale sus {$programa->cantidad_modulos} modulos.");
    }

    public function show(Programa $programa): View
    {
        $programa->load(['modulos', 'cohortes.grupos']);

        return view('programas.show', compact('programa'));
    }

    public function edit(Programa $programa): View
    {
        return view('programas.edit', compact('programa'));
    }

    public function update(UpdateProgramaRequest $request, Programa $programa): RedirectResponse
    {
        $programa->update($request->validated());

        return redirect()->route('programas.show', $programa)
            ->with('exito', "Programa \"{$programa->nombre}\" actualizado.");
    }

    /** RN 2.7: no se elimina un programa con relaciones; se cierra. */
    public function destroy(Programa $programa): RedirectResponse
    {
        if ($programa->modulos()->exists() || $programa->cohortes()->exists()) {
            return redirect()->route('programas.index')->with(
                'error',
                "No se puede eliminar \"{$programa->nombre}\": tiene modulos asignados o cohortes abiertas. Cierralo en su lugar."
            );
        }

        $nombre = $programa->nombre;
        $programa->delete();

        return redirect()->route('programas.index')->with('exito', "Programa \"{$nombre}\" eliminado.");
    }

    /*
    |--------------------------------------------------------------------------
    | Pantalla de estructura (la mas importante del Modulo 2)
    |--------------------------------------------------------------------------
    */

    public function estructura(Request $request, Programa $programa): View
    {
        $programa->load('modulos');
        $yaAsignados = $programa->modulos->pluck('id');

        $buscar = $request->query('buscar');

        // Candidatos: modulos que NO estan ya en este programa.
        $disponibles = Modulo::query()
            ->whereNotIn('id', $yaAsignados)
            ->when($buscar, fn ($q, $buscar) => $q->where(function ($sub) use ($buscar) {
                $sub->whereLike('nombre', "%{$buscar}%")
                    ->orWhereLike('codigo', "%{$buscar}%");
            }))
            ->orderBy('codigo')
            ->limit(20)
            ->get();

        return view('programas.estructura', compact('programa', 'disponibles', 'buscar'));
    }

    /** Agrega un modulo al final del programa. */
    public function agregarModulo(Request $request, Programa $programa): RedirectResponse
    {
        $datos = $request->validate([
            'modulo_id' => ['required', 'exists:modulos,id'],
        ]);

        // RN 2.2: un modulo no se agrega dos veces al mismo programa.
        if ($programa->modulos()->whereKey($datos['modulo_id'])->exists()) {
            return back()->with('error', 'Ese modulo ya esta en el programa.');
        }

        $modulo = Modulo::findOrFail($datos['modulo_id']);

        $programa->modulos()->attach($modulo->id, ['orden' => $programa->siguienteOrden()]);

        return back()->with('exito', "Modulo \"{$modulo->nombre}\" agregado en la posicion ".($programa->modulosAsignados()).'.');
    }

    /** Quita un modulo y recompacta el orden para no dejar huecos. */
    public function quitarModulo(Programa $programa, Modulo $modulo): RedirectResponse
    {
        // RN 2.8: no se quita un modulo de un programa que ya tiene alumnos inscritos.
        $tieneInscritos = DB::table('matriculas')
            ->join('grupos', 'grupos.id', '=', 'matriculas.grupo_id')
            ->whereIn('grupos.cohorte_id', $programa->cohortes()->select('id'))
            ->where('grupos.modulo_id', $modulo->id)
            ->exists();

        if ($tieneInscritos) {
            return back()->with(
                'error',
                "No se puede quitar \"{$modulo->nombre}\": ya hay alumnos inscritos en sus grupos dentro de este programa."
            );
        }

        DB::transaction(function () use ($programa, $modulo) {
            $programa->modulos()->detach($modulo->id);
            $this->recompactarOrden($programa);
        });

        return back()->with('exito', "Modulo \"{$modulo->nombre}\" quitado del programa.");
    }

    /**
     * Mueve un modulo una posicion arriba o abajo.
     * Se intercambian los ordenes de los dos modulos implicados.
     */
    public function moverModulo(Request $request, Programa $programa, Modulo $modulo): RedirectResponse
    {
        $datos = $request->validate([
            'direccion' => ['required', 'in:arriba,abajo'],
        ]);

        $modulos = $programa->modulos; // ordenados por pivot.orden
        $posicion = $modulos->search(fn (Modulo $m) => $m->id === $modulo->id);

        if ($posicion === false) {
            return back()->with('error', 'Ese modulo no esta en el programa.');
        }

        $destino = $datos['direccion'] === 'arriba' ? $posicion - 1 : $posicion + 1;

        if ($destino < 0 || $destino >= $modulos->count()) {
            return back(); // ya esta en el extremo, no hay nada que hacer
        }

        // Importante: los datos del pivote (el 'orden') solo vienen en las
        // instancias de la relacion. El $modulo que inyecta la ruta NO los trae.
        $actual = $modulos->get($posicion);
        $otro = $modulos->get($destino);

        // El unique(programa_id, orden) impide tener dos veces el mismo orden,
        // asi que se pasa por un valor temporal antes de intercambiar. Tiene que
        // ser positivo: el CHECK ck_pm_orden exige orden > 0, asi que se usa un
        // hueco por encima del maximo actual en lugar de un negativo.
        DB::transaction(function () use ($programa, $actual, $otro) {
            $ordenA = $actual->pivot->orden;
            $ordenB = $otro->pivot->orden;
            $temporal = $programa->siguienteOrden();

            $programa->modulos()->updateExistingPivot($actual->id, ['orden' => $temporal]);
            $programa->modulos()->updateExistingPivot($otro->id, ['orden' => $ordenA]);
            $programa->modulos()->updateExistingPivot($actual->id, ['orden' => $ordenB]);
        });

        return back()->with('exito', 'Orden actualizado.');
    }

    /** Cambia el estado comercial del programa. */
    public function cambiarEstado(Request $request, Programa $programa): RedirectResponse
    {
        $datos = $request->validate([
            'estado_comercial' => ['required', 'string'],
        ]);

        $destino = EstadoComercial::tryFrom($datos['estado_comercial']);

        if (! $destino) {
            return back()->with('error', 'Estado no valido.');
        }

        if (! $programa->estado_comercial->puedePasarA($destino)) {
            return back()->with(
                'error',
                "No se puede pasar de {$programa->estado_comercial->etiqueta()} a {$destino->etiqueta()}."
            );
        }

        if ($destino === EstadoComercial::Abierto) {
            // RN 2.4: datos completos.
            if (! $programa->datosCompletos()) {
                return back()->with('error', 'Para abrir el programa faltan datos: nombre, descripcion y precio.');
            }

            // RN 2.5: no puede abrirse con menos modulos que cantidad_modulos.
            if (! $programa->estructuraCompleta()) {
                return back()->with(
                    'error',
                    'No se puede abrir el programa: tiene '.$programa->indicadorEstructura()
                    .'. Completa la estructura primero.'
                );
            }
        }

        // RN 2.6
        if ($destino === EstadoComercial::Borrador && $programa->tieneActividad()) {
            return back()->with('error', 'No se puede volver a borrador: el programa ya tiene cohortes abiertas.');
        }

        $programa->update(['estado_comercial' => $destino]);

        return back()->with('exito', "Programa ahora en estado {$destino->etiqueta()}.");
    }

    /** Deja los ordenes como 1,2,3... sin huecos. */
    private function recompactarOrden(Programa $programa): void
    {
        $modulos = $programa->modulos()->get();

        // Primero se aparcan por encima del maximo actual para no chocar con el
        // unique(programa_id, orden). No se usan negativos porque el CHECK
        // ck_pm_orden solo admite orden > 0.
        $offset = (int) $programa->modulos()->max('orden');

        foreach ($modulos as $i => $modulo) {
            $programa->modulos()->updateExistingPivot($modulo->id, ['orden' => $offset + $i + 1]);
        }

        foreach ($modulos as $i => $modulo) {
            $programa->modulos()->updateExistingPivot($modulo->id, ['orden' => $i + 1]);
        }
    }
}
