<?php

namespace App\Http\Controllers;

use App\Actions\Matriculas\InscribirEstudiante;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Exceptions\ReglaDeNegocioException;
use App\Http\Requests\StoreMatriculaRequest;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * MODULO 4 - Matriculas. Solo administrador (por ahora, carga manual).
 * Una matricula nunca se borra: se retira o se anula (RN 4.8).
 */
class MatriculaController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->query('buscar');
        $estado = $request->query('estado');
        $grupoId = $request->query('grupo');

        $matriculas = Matricula::query()
            ->with(['estudiante', 'grupo.modulo'])
            ->when($buscar, fn ($q, $buscar) => $q->whereHas('estudiante', function ($sub) use ($buscar) {
                $sub->whereLike('name', "%{$buscar}%")
                    ->orWhereLike('apellidos', "%{$buscar}%")
                    ->orWhereLike('documento', "%{$buscar}%");
            }))
            ->when($estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($grupoId, fn ($q, $id) => $q->where('grupo_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('matriculas.index', [
            'matriculas' => $matriculas,
            'programasCompletos' => $this->programasCompletos($matriculas->getCollection()),
            'estados' => EstadoMatricula::cases(),
            'grupos' => Grupo::with('modulo')->orderByDesc('fecha_inicio')->get(),
            'buscar' => $buscar,
            'estado' => $estado,
            'grupoId' => $grupoId,
        ]);
    }

    /**
     * Paso 1: buscar la persona por documento.
     * Paso 2: elegir grupo o cohorte y aceptar los requisitos.
     */
    public function create(Request $request): View
    {
        $documento = trim((string) $request->query('documento'));

        return view('matriculas.create', [
            'documento' => $documento,
            'persona' => $documento !== '' ? User::where('documento', $documento)->first() : null,
            'grupos' => Grupo::with(['modulo', 'horarios', 'cohorte'])
                ->admiteInscripciones()
                ->withCount(['matriculas as vigentes_count' => fn ($q) => $q->vigentes()])
                ->orderBy('fecha_inicio')
                ->get(),
            'cohortes' => Cohorte::with(['programa', 'grupos.modulo'])
                ->whereNotIn('estado', ['finalizado', 'cancelado'])
                ->orderBy('fecha_inicio')
                ->get(),
            'grupoPreseleccionado' => $request->query('grupo'),
        ]);
    }

    public function store(StoreMatriculaRequest $request, InscribirEstudiante $inscribir): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $resultado = $inscribir->ejecutar($datos, $request->grupos(), OrigenMatricula::Manual);
        } catch (ReglaDeNegocioException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $usuario = $resultado['usuario'];
        $cantidad = $resultado['matriculas']->count();

        $mensaje = $datos['tipo'] === 'grupo'
            ? "{$usuario->nombre_completo} inscrito al modulo suelto, con acceso al Meet."
            : "{$usuario->nombre_completo} inscrito al programa completo ({$cantidad} modulos), con acceso al Meet.";
        if ($resultado['password_temporal']) {
            $mensaje .= " Se creo su cuenta con la contrasena temporal: {$resultado['password_temporal']}";
        }

        return redirect()->route('matriculas.index', ['buscar' => $usuario->documento])
            ->with('exito', $mensaje);
    }

    /** reservada -> activa: pago confirmado. Desde aqui ve el enlace de Meet. */
    public function activar(Matricula $matricula): RedirectResponse
    {
        return $this->transicion($matricula, EstadoMatricula::Activa);
    }

    /** reservada|activa -> anulada: nunca se concreto. */
    public function anular(Matricula $matricula): RedirectResponse
    {
        return $this->transicion($matricula, EstadoMatricula::Anulada);
    }

    /** activa -> retirada. RN 4.9: el retiro exige un motivo. */
    public function retirar(Request $request, Matricula $matricula): RedirectResponse
    {
        $datos = $request->validate(
            ['motivo_retiro' => ['required', 'string', 'min:5', 'max:1000']],
            [
                'motivo_retiro.required' => 'El retiro exige un motivo.',
                'motivo_retiro.min' => 'Describe el motivo del retiro con un poco mas de detalle.',
            ],
        );

        return $this->transicion($matricula, EstadoMatricula::Retirada, $datos);
    }

    /**
     * RN 4.12: si su grupo se cancelo, el alumno se reubica en otro grupo del
     * mismo modulo. La matricula anulada se conserva (RN 4.8) y se crea otra.
     */
    public function reubicar(Request $request, Matricula $matricula): RedirectResponse
    {
        $datos = $request->validate(
            ['grupo_id' => ['required', 'exists:grupos,id']],
            ['grupo_id.required' => 'Elige el grupo destino.'],
        );

        $matricula->load(['grupo', 'estudiante']);

        if ($matricula->grupo->estado !== EstadoGrupo::Cancelado || $matricula->estado !== EstadoMatricula::Anulada) {
            return back()->with('error', 'Solo se reubican matriculas anuladas de grupos cancelados.');
        }

        $destino = Grupo::with('modulo')->findOrFail($datos['grupo_id']);

        if ($destino->modulo_id !== $matricula->grupo->modulo_id) {
            return back()->with('error', 'Solo se puede reubicar en un grupo del mismo modulo.');
        }

        try {
            DB::transaction(function () use ($matricula, $destino) {
                $destino = Grupo::with('modulo')->lockForUpdate()->findOrFail($destino->id);

                if (! $destino->estado->admiteInscripciones()) {
                    throw new ReglaDeNegocioException("{$destino->nombre_completo} no admite inscripciones.");
                }
                if ($destino->matriculas()->where('user_id', $matricula->user_id)->exists()) {
                    throw new ReglaDeNegocioException("{$matricula->estudiante->nombre_completo} ya esta en {$destino->nombre_completo}.");
                }
                if (! $destino->tieneEspacio()) {
                    throw new ReglaDeNegocioException("{$destino->nombre_completo} ya alcanzo su cupo maximo.");
                }

                Matricula::create([
                    'user_id' => $matricula->user_id,
                    'grupo_id' => $destino->id,
                    // Ya habia pagado: entra igual que una inscripcion nueva.
                    'estado' => $matricula->origen->estadoInicial(),
                    'origen' => $matricula->origen,
                    'crm_lead_id' => $matricula->crm_lead_id,
                    'acepto_requisitos' => $matricula->acepto_requisitos,
                    'fecha_aceptacion' => $matricula->fecha_aceptacion,
                ]);
            });
        } catch (ReglaDeNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', "{$matricula->estudiante->nombre_completo} reubicado en {$destino->nombre_completo}.");
    }

    /**
     * Que matriculas de la pagina son parte de un programa completo: el
     * alumno esta en todos los grupos de esa cohorte (Cohorte::esProgramaCompletoDe).
     *
     * @param  Collection<int, Matricula>  $matriculas
     * @return Collection<int, string> claves "user_id-cohorte_id"
     */
    private function programasCompletos(Collection $matriculas): Collection
    {
        $cohortes = Cohorte::with('grupos')
            ->whereIn('id', $matriculas->pluck('grupo.cohorte_id')->filter()->unique())
            ->get();

        if ($cohortes->isEmpty()) {
            return collect();
        }

        $gruposPorAlumno = Matricula::whereIn('user_id', $matriculas->pluck('user_id')->unique())
            ->whereIn('grupo_id', $cohortes->flatMap->grupos->pluck('id'))
            ->get(['user_id', 'grupo_id'])
            ->groupBy('user_id')
            ->map->pluck('grupo_id');

        return $gruposPorAlumno->flatMap(fn ($grupos, $userId) => $cohortes
            ->filter(fn (Cohorte $c) => $c->esProgramaCompletoDe($grupos))
            ->map(fn (Cohorte $c) => "{$userId}-{$c->id}"))
            ->values();
    }

    private function transicion(Matricula $matricula, EstadoMatricula $destino, array $extra = []): RedirectResponse
    {
        if (! $matricula->estado->puedePasarA($destino)) {
            return back()->with(
                'error',
                "No se puede pasar la matricula de {$matricula->estado->etiqueta()} a {$destino->etiqueta()}."
            );
        }

        $matricula->update(['estado' => $destino, ...$extra]);

        return back()->with('exito', "Matricula de {$matricula->estudiante->nombre_completo} ahora {$destino->etiqueta()}.");
    }
}
