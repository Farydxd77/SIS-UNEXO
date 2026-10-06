<?php

namespace App\Http\Controllers;

use App\Actions\Grupos\CambiarEstadoGrupo;
use App\Actions\Grupos\GuardarGrupo;
use App\Enums\DiaSemana;
use App\Enums\EstadoCohorte;
use App\Enums\EstadoGrupo;
use App\Exceptions\ReglaDeNegocioException;
use App\Http\Requests\GrupoDeCohorteRequest;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Asignar docentes y Meet": completar todos los grupos de una cohorte en
 * una sola pantalla, en vez de editarlos uno por uno.
 */
class AsignacionCohorteController extends Controller
{
    public function edit(Cohorte $cohorte): View
    {
        return view('cohortes.asignar', [
            'cohorte' => $cohorte->load('programa'),
            'grupos' => $this->gruposEditables($cohorte)->load(['modulo', 'horarios']),
            'docentes' => User::activos()->conRol(User::ROL_DOCENTE)->orderBy('name')->get(),
            'dias' => DiaSemana::cases(),
        ]);
    }

    /**
     * Cada fila pasa por las mismas reglas que editar un grupo. Se guardan en
     * orden dentro de una transaccion: asi una fila tambien choca con lo que
     * se acaba de guardar arriba, y si alguna falla no se guarda ninguna.
     */
    public function update(Request $request, Cohorte $cohorte, GuardarGrupo $guardar, CambiarEstadoGrupo $cambiarEstado): RedirectResponse
    {
        $request->validate(['grupos' => ['required', 'array'], 'abrir_convocatoria' => ['boolean']]);
        $abrirConvocatoria = $request->boolean('abrir_convocatoria');

        try {
            $abiertos = DB::transaction(function () use ($request, $cohorte, $guardar, $cambiarEstado, $abrirConvocatoria) {
                $errores = [];
                $abiertos = 0;

                foreach ($this->gruposEditables($cohorte) as $grupo) {
                    $fila = $request->input("grupos.{$grupo->id}", []);

                    try {
                        $validado = $this->validarFila($request, $grupo, $fila);
                    } catch (ValidationException $e) {
                        foreach ($e->errors() as $campo => $mensajes) {
                            $errores["grupos.{$grupo->id}.{$campo}"] = $mensajes;
                        }

                        continue;
                    }

                    $guardar->actualizar($grupo, $validado->datosGrupo(), $validado->horarios());

                    if ($abrirConvocatoria && $grupo->estado === EstadoGrupo::Planificado && $grupo->puedeIrAConvocatoria()) {
                        try {
                            $cambiarEstado->ejecutar($grupo, EstadoGrupo::EnConvocatoria);
                            $abiertos++;
                        } catch (ReglaDeNegocioException $e) {
                            $errores["grupos.{$grupo->id}.estado"] = [$e->getMessage()];
                        }
                    }
                }

                if ($errores !== []) {
                    // Deshace todo: o se guarda la cohorte entera o nada.
                    throw ValidationException::withMessages($errores);
                }

                // Con todos sus grupos abiertos, la cohorte tambien sale a convocatoria.
                if ($abrirConvocatoria && $cohorte->estado === EstadoCohorte::Planificado
                    && $cohorte->grupos()->where('estado', EstadoGrupo::Planificado)->doesntExist()) {
                    $cohorte->update(['estado' => EstadoCohorte::EnConvocatoria]);
                }

                return $abiertos;
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()
                ->with('error', 'No se guardo nada: revisa los grupos marcados en rojo.');
        }

        $mensaje = 'Docentes, Meet y horarios guardados.';
        if ($abiertos > 0) {
            $mensaje .= " {$abiertos} grupo(s) pasaron a convocatoria.";
        }

        return redirect()->route('cohortes.show', $cohorte)->with('exito', $mensaje);
    }

    /**
     * Los grupos que todavia se pueden editar (ni finalizados ni cancelados).
     *
     * @return Collection<int, Grupo>
     */
    private function gruposEditables(Cohorte $cohorte): Collection
    {
        return $cohorte->grupos()
            ->whereNotIn('estado', [EstadoGrupo::Finalizado, EstadoGrupo::Cancelado])
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->get();
    }

    /**
     * La fila trae docente, Meet y horarios; lo demas (fechas, cupos) se
     * conserva del grupo, que en esta pantalla no se toca.
     *
     * @param  array<string, mixed>  $fila
     */
    private function validarFila(Request $request, Grupo $grupo, array $fila): GrupoDeCohorteRequest
    {
        $validacion = GrupoDeCohorteRequest::create($request->url(), 'PUT', [
            'cohorte_id' => $grupo->cohorte_id,
            'fecha_inicio' => $grupo->fecha_inicio->toDateString(),
            'fecha_fin' => $grupo->fecha_fin->toDateString(),
            'cupo_minimo' => $grupo->cupo_minimo,
            'cupo_maximo' => $grupo->cupo_maximo,
            'docente_id' => ($fila['docente_id'] ?? null) ?: null,
            'enlace_meet' => $fila['enlace_meet'] ?? null,
            'horarios' => array_values($fila['horarios'] ?? []),
        ]);

        $validacion->paraGrupo($grupo)
            ->setContainer(app())
            ->setRedirector(app('redirect'))
            ->setUserResolver($request->getUserResolver());

        $validacion->validateResolved();

        return $validacion;
    }
}
