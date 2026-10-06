<?php

namespace App\Http\Controllers;

use App\Actions\Grupos\CambiarEstadoGrupo;
use App\Actions\Grupos\GuardarGrupo;
use App\Enums\DiaSemana;
use App\Enums\EstadoGrupo;
use App\Exceptions\ReglaDeNegocioException;
use App\Http\Requests\StoreGrupoRequest;
use App\Http\Requests\UpdateGrupoRequest;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * MODULO 3 - CRUD de grupos y sus horarios.
 *
 * El ABM es solo del admin (middleware 'rol'). El detalle (show) lo ven
 * tambien el docente del grupo y sus alumnos: eso lo decide GrupoPolicy.
 */
class GrupoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['estado', 'modulo', 'docente', 'desde', 'hasta']);

        $grupos = Grupo::query()
            ->with(['modulo', 'cohorte', 'docente', 'horarios'])
            ->withCount(['matriculas as vigentes_count' => fn ($q) => $q->vigentes()])
            ->when($filtros['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($filtros['modulo'] ?? null, fn ($q, $v) => $q->where('modulo_id', $v))
            ->when($filtros['docente'] ?? null, fn ($q, $v) => $q->where('docente_id', $v))
            // Grupos que se dictan (al menos en parte) dentro del rango.
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->where('fecha_fin', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->where('fecha_inicio', '<=', $v))
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        return view('grupos.index', [
            'grupos' => $grupos,
            'filtros' => $filtros,
            'estados' => EstadoGrupo::cases(),
            'modulos' => Modulo::orderBy('nombre')->get(),
            'docentes' => User::conRol(User::ROL_DOCENTE)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('grupos.create', [
            'grupo' => new Grupo([
                'cupo_minimo' => 20,
                'modulo_id' => $request->query('modulo'),
            ]),
            ...$this->datosFormulario(),
        ]);
    }

    public function store(StoreGrupoRequest $request, GuardarGrupo $guardar): RedirectResponse
    {
        $grupo = $guardar->crear(
            [...$request->datosGrupo(), 'estado' => EstadoGrupo::Planificado],
            $request->horarios(),
        );

        return redirect()->route('grupos.show', $grupo)
            ->with('exito', "Grupo \"{$grupo->nombre_completo}\" creado en estado planificado.");
    }

    public function show(Grupo $grupo): View
    {
        Gate::authorize('view', $grupo);

        $usuario = auth()->user();
        $grupo->load(['modulo', 'cohorte', 'docente', 'horarios']);

        $puedeVerAlumnos = $usuario->can('verAlumnos', $grupo);

        return view('grupos.show', [
            'grupo' => $grupo,
            'puedeVerAlumnos' => $puedeVerAlumnos,
            'puedeVerMeet' => $usuario->can('verEnlaceMeet', $grupo),
            'puedeRegistrarNotas' => $usuario->can('registrarNotas', $grupo) && $this->admiteNotas($grupo),
            'esAdmin' => $usuario->esAdministrador(),
            'matriculas' => $puedeVerAlumnos
                ? $grupo->matriculas()->with(['estudiante', 'grupo'])->orderBy('estado')->get()
                : collect(),
            // RN 4.12: grupos del mismo modulo donde reubicar si este se cancela.
            'alternativas' => $grupo->estado === EstadoGrupo::Cancelado
                ? Grupo::with('modulo')
                    ->where('modulo_id', $grupo->modulo_id)
                    ->whereKeyNot($grupo->id)
                    ->admiteInscripciones()
                    ->get()
                : collect(),
        ]);
    }

    public function edit(Grupo $grupo): View|RedirectResponse
    {
        if ($this->estaCerrado($grupo)) {
            return redirect()->route('grupos.show', $grupo)
                ->with('error', "El grupo esta {$grupo->estado->etiqueta()}: ya no se puede editar.");
        }

        $grupo->load('horarios');

        return view('grupos.edit', [
            'grupo' => $grupo,
            ...$this->datosFormulario(),
        ]);
    }

    public function update(UpdateGrupoRequest $request, Grupo $grupo, GuardarGrupo $guardar): RedirectResponse
    {
        if ($this->estaCerrado($grupo)) {
            return redirect()->route('grupos.show', $grupo)
                ->with('error', "El grupo esta {$grupo->estado->etiqueta()}: ya no se puede editar.");
        }

        $guardar->actualizar($grupo, $request->datosGrupo(), $request->horarios());

        // Si se abrio desde la cohorte, se vuelve a ella para seguir con el siguiente grupo.
        $destino = $request->input('volver') === 'cohorte' && $grupo->cohorte_id
            ? route('cohortes.show', $grupo->cohorte_id)
            : route('grupos.show', $grupo);

        return redirect($destino)->with('exito', "Grupo \"{$grupo->nombre_completo}\" actualizado.");
    }

    /** RN 3.10: no se elimina un grupo con inscritos; se cancela. */
    public function destroy(Grupo $grupo): RedirectResponse
    {
        if (! auth()->user()->can('delete', $grupo)) {
            return back()->with('error', "No se puede eliminar \"{$grupo->nombre_completo}\": tiene inscritos. Cancelalo en su lugar.");
        }

        $nombre = $grupo->nombre_completo;
        $grupo->delete(); // los horarios se van en cascada

        return redirect()->route('grupos.index')->with('exito', "Grupo \"{$nombre}\" eliminado.");
    }

    public function cambiarEstado(Request $request, Grupo $grupo, CambiarEstadoGrupo $cambiar): RedirectResponse
    {
        Gate::authorize('cambiarEstado', $grupo);

        $destino = EstadoGrupo::tryFrom((string) $request->input('estado'));

        if (! $destino) {
            return back()->with('error', 'Estado no valido.');
        }

        try {
            $cambiar->ejecutar($grupo, $destino);
        } catch (ReglaDeNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        $mensaje = "Grupo ahora en estado {$destino->etiqueta()}.";
        if ($destino === EstadoGrupo::Cancelado) {
            $mensaje .= ' Sus matriculas vigentes pasaron a anulada; puedes reubicar a los alumnos en otro grupo del mismo modulo.';
        }

        return back()->with('exito', $mensaje);
    }

    /** Las notas se cargan cuando el grupo se esta dictando o ya termino. */
    public static function admiteNotas(Grupo $grupo): bool
    {
        return in_array($grupo->estado, [EstadoGrupo::EnCurso, EstadoGrupo::Finalizado], true);
    }

    private function estaCerrado(Grupo $grupo): bool
    {
        return in_array($grupo->estado, [EstadoGrupo::Finalizado, EstadoGrupo::Cancelado], true);
    }

    private function datosFormulario(): array
    {
        return [
            // RN 3.2: solo modulos que no esten en borrador.
            'modulos' => Modulo::disponiblesParaGrupo()->orderBy('nombre')->get(),
            'cohortes' => Cohorte::with('programa')
                ->whereNotIn('estado', ['finalizado', 'cancelado'])
                ->orderByDesc('fecha_inicio')
                ->get(),
            // RN 3.3: docentes con rol docente y activos.
            'docentes' => User::activos()->conRol(User::ROL_DOCENTE)->orderBy('name')->get(),
            'dias' => DiaSemana::cases(),
        ];
    }
}
