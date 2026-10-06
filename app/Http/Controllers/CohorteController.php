<?php

namespace App\Http\Controllers;

use App\Actions\Cohortes\GenerarGruposDeCohorte;
use App\Actions\Grupos\CambiarEstadoGrupo;
use App\Enums\EstadoCohorte;
use App\Enums\EstadoComercial;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Enums\ResultadoMatricula;
use App\Exceptions\ReglaDeNegocioException;
use App\Http\Requests\StoreCohorteRequest;
use App\Http\Requests\UpdateCohorteRequest;
use App\Models\Cohorte;
use App\Models\Matricula;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * MODULO 3 - CRUD de cohortes. Solo administrador.
 */
class CohorteController extends Controller
{
    /**
     * Dos pestanas: las vigentes (se trabaja con ellas) y el historial
     * (finalizadas o canceladas), con sus resultados.
     */
    public function index(Request $request): View
    {
        $historial = $request->query('vista') === 'historial';
        $programaId = $request->query('programa');
        $estado = $request->query('estado');

        $estados = array_values(array_filter(
            EstadoCohorte::cases(),
            fn (EstadoCohorte $e) => $e->esHistorico() === $historial
        ));

        $cohortes = Cohorte::query()
            ->with('programa')
            ->withCount('grupos')
            ->when($historial, fn ($q) => $q->historicas()->withCount([
                'matriculas as matriculas_count' => fn ($m) => $m->where('matriculas.estado', '!=', EstadoMatricula::Anulada),
                'matriculas as aprobadas_count' => fn ($m) => $m->where('matriculas.resultado', ResultadoMatricula::Aprobado),
            ]), fn ($q) => $q->vigentes())
            ->when($programaId, fn ($q, $id) => $q->where('programa_id', $id))
            ->when($estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        return view('cohortes.index', [
            'cohortes' => $cohortes,
            'historial' => $historial,
            'totalVigentes' => Cohorte::vigentes()->count(),
            'totalHistoricas' => Cohorte::historicas()->count(),
            'programas' => Programa::orderBy('codigo')->get(),
            'estados' => $estados,
            'programaId' => $programaId,
            'estado' => $estado,
        ]);
    }

    public function create(): View
    {
        return view('cohortes.create', [
            // Solo programas que pueden generar grupos.
            'programas' => Programa::withCount('modulos')
                ->where('estado_comercial', '!=', EstadoComercial::Borrador)
                ->orderBy('codigo')
                ->get(),
        ]);
    }

    /**
     * Al guardar, el sistema genera automaticamente un grupo por cada modulo
     * del programa. Cohorte + grupos en una sola transaccion.
     */
    public function store(StoreCohorteRequest $request, GenerarGruposDeCohorte $generar): RedirectResponse
    {
        $datos = $request->validated();

        $cohorte = DB::transaction(function () use ($datos, $generar) {
            $cohorte = Cohorte::create([
                'programa_id' => $datos['programa_id'],
                'nombre' => $datos['nombre'],
                'fecha_inicio' => $datos['fecha_inicio'],
                'estado' => EstadoCohorte::Planificado,
            ]);

            $generar->ejecutar($cohorte, (int) $datos['semanas_por_modulo']);

            return $cohorte;
        });

        // Siguiente paso natural: completar todos los grupos en una sola pantalla.
        return redirect()->route('cohortes.asignar', $cohorte)->with(
            'exito',
            "Cohorte creada con {$cohorte->grupos()->count()} grupos. Ahora asigna a cada uno su docente, horario y enlace de Meet."
        );
    }

    /** Detalle: sus grupos en orden, tipo linea de tiempo. */
    public function show(Cohorte $cohorte): View
    {
        $cohorte->load([
            'programa',
            'grupos' => fn ($q) => $q->with(['modulo', 'docente', 'horarios'])
                ->withCount(['matriculas as vigentes_count' => fn ($m) => $m->vigentes()])
                ->orderBy('fecha_inicio')
                ->orderBy('id'),
        ]);

        return view('cohortes.show', [
            'cohorte' => $cohorte,
            'totalInscritos' => $cohorte->totalInscritos(),
            'gruposListos' => $cohorte->gruposListos(),
        ]);
    }

    public function edit(Cohorte $cohorte): View
    {
        return view('cohortes.edit', compact('cohorte'));
    }

    public function update(UpdateCohorteRequest $request, Cohorte $cohorte): RedirectResponse
    {
        $cohorte->update($request->validated());

        return redirect()->route('cohortes.show', $cohorte)
            ->with('exito', "Cohorte \"{$cohorte->nombre}\" actualizada.");
    }

    /**
     * Solo se elimina una cohorte recien creada por error: sin inscritos y
     * con todos sus grupos planificados. En cualquier otro caso, se cancela.
     */
    public function destroy(Cohorte $cohorte): RedirectResponse
    {
        $grupos = $cohorte->grupos()->select('id');

        $tieneInscritos = Matricula::whereIn('grupo_id', $grupos)->exists();
        $avanzada = $cohorte->grupos()->where('estado', '!=', EstadoGrupo::Planificado)->exists();

        if ($tieneInscritos || $avanzada) {
            return back()->with('error', "No se puede eliminar \"{$cohorte->nombre}\": ya tiene inscritos o grupos en marcha. Cancelala en su lugar.");
        }

        DB::transaction(function () use ($cohorte) {
            // Los horarios se borran en cascada con cada grupo.
            $cohorte->grupos()->delete();
            $cohorte->delete();
        });

        return redirect()->route('cohortes.index')
            ->with('exito', "Cohorte \"{$cohorte->nombre}\" eliminada.");
    }

    /**
     * Cambia el estado de la cohorte. Cancelarla cancela tambien sus grupos
     * pendientes y anula sus matriculas (RN 3.11).
     */
    public function cambiarEstado(Request $request, Cohorte $cohorte, CambiarEstadoGrupo $cambiarGrupo): RedirectResponse
    {
        $destino = EstadoCohorte::tryFrom((string) $request->input('estado'));

        if (! $destino) {
            return back()->with('error', 'Estado no valido.');
        }

        if (! $cohorte->estado->puedePasarA($destino)) {
            return back()->with('error', "No se puede pasar la cohorte de {$cohorte->estado->etiqueta()} a {$destino->etiqueta()}.");
        }

        try {
            DB::transaction(function () use ($cohorte, $destino, $cambiarGrupo) {
                $cohorte->update(['estado' => $destino]);

                if ($destino === EstadoCohorte::Cancelado) {
                    foreach ($cohorte->grupos as $grupo) {
                        if ($grupo->estado->puedePasarA(EstadoGrupo::Cancelado)) {
                            $cambiarGrupo->ejecutar($grupo, EstadoGrupo::Cancelado);
                        }
                    }
                }
            });
        } catch (ReglaDeNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', "Cohorte ahora en estado {$destino->etiqueta()}.");
    }
}
