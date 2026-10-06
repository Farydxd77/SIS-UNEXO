<?php

namespace App\Http\Controllers;

use App\Actions\Matriculas\RegistrarNotas;
use App\Enums\EstadoMatricula;
use App\Enums\ResultadoMatricula;
use App\Http\Requests\RegistrarNotasRequest;
use App\Models\Grupo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * MODULO 4 - Registro de notas.
 * RN 4.10: solo el docente del grupo carga sus notas; el admin corrige.
 */
class NotaController extends Controller
{
    public function edit(Grupo $grupo): View|RedirectResponse
    {
        Gate::authorize('registrarNotas', $grupo);

        if (! GrupoController::admiteNotas($grupo)) {
            return redirect()->route('grupos.show', $grupo)
                ->with('error', "Las notas se cargan cuando el grupo esta en curso o finalizado (ahora: {$grupo->estado->etiqueta()}).");
        }

        $grupo->load(['modulo', 'docente']);

        return view('notas.edit', [
            'grupo' => $grupo,
            'matriculas' => $grupo->matriculas()
                ->with('estudiante')
                ->whereIn('estado', [EstadoMatricula::Activa->value, EstadoMatricula::Finalizada->value])
                ->get()
                ->sortBy(fn ($m) => $m->estudiante->apellidos),
            'esAdmin' => auth()->user()->esAdministrador(),
            'notaMinima' => ResultadoMatricula::NOTA_MINIMA_APROBACION,
        ]);
    }

    public function update(RegistrarNotasRequest $request, Grupo $grupo, RegistrarNotas $registrar): RedirectResponse
    {
        if (! GrupoController::admiteNotas($grupo)) {
            return redirect()->route('grupos.show', $grupo)
                ->with('error', 'Las notas se cargan cuando el grupo esta en curso o finalizado.');
        }

        $resultado = $registrar->ejecutar($grupo, $request->validated('notas'), $request->user());

        $mensaje = "{$resultado['guardadas']} nota(s) guardada(s).";
        if ($resultado['finalizadas'] > 0) {
            $mensaje .= " Todas las notas estan cargadas: {$resultado['finalizadas']} matricula(s) pasaron a finalizada.";
        }

        return redirect()->route('grupos.notas.edit', $grupo)->with('exito', $mensaje);
    }
}
