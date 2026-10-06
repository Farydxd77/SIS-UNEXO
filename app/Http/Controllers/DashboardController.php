<?php

namespace App\Http\Controllers;

use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * Cada rol tiene su propio dashboard al iniciar sesion (seccion 4 de la spec).
 * Este controlador decide cual mostrar segun los roles del usuario.
 */
class DashboardController extends Controller
{
    public function index(): Renderable|RedirectResponse
    {
        $usuario = auth()->user();

        // El orden importa: quien es admin y docente a la vez ve el de admin.
        if ($usuario->esAdministrador()) {
            return $this->administrador();
        }

        if ($usuario->esDocente()) {
            return $this->docente();
        }

        if ($usuario->esEstudiante()) {
            return $this->estudiante();
        }

        // RN 1.3 dice que todo usuario debe tener al menos un rol; si llega
        // aqui es que algo se salto esa regla.
        return view('dashboard.sin-rol');
    }

    private function administrador(): Renderable
    {
        return view('dashboard.administrador', [
            'totalUsuarios' => User::activos()->count(),
            'totalDocentes' => User::activos()->conRol(User::ROL_DOCENTE)->count(),
            'totalEstudiantes' => User::activos()->conRol(User::ROL_ESTUDIANTE)->count(),
            'totalProgramas' => Programa::count(),
            'programasAbiertos' => Programa::abiertos()->count(),
            'totalModulos' => Modulo::count(),
            'modulosAbiertos' => Modulo::abiertos()->count(),
            'totalCohortes' => Cohorte::count(),
            'gruposPorEstado' => Grupo::selectRaw('estado, count(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            // Grupos en convocatoria que ya alcanzaron el cupo: listos para habilitar.
            'gruposEnConvocatoria' => Grupo::with(['modulo', 'docente'])
                ->where('estado', EstadoGrupo::EnConvocatoria)
                ->orderBy('fecha_inicio')
                ->get(),
            'matriculasVigentes' => Matricula::vigentes()->count(),
            'cohortesVigentes' => Cohorte::with('programa')
                ->vigentes()
                ->withCount('grupos')
                ->orderBy('fecha_inicio')
                ->limit(6)
                ->get(),
            // Como la "actividad reciente" de Moodle.
            'ultimasMatriculas' => Matricula::with(['estudiante', 'grupo.modulo'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }

    private function docente(): Renderable
    {
        $usuario = auth()->user();

        return view('dashboard.docente', [
            'grupos' => Grupo::with(['modulo', 'cohorte', 'horarios'])
                ->delDocente($usuario->id)
                ->orderBy('fecha_inicio')
                ->get(),
            'porCalificar' => Grupo::with('modulo')
                ->delDocente($usuario->id)
                ->whereIn('estado', [EstadoGrupo::EnCurso->value, EstadoGrupo::Finalizado->value])
                ->withCount(['matriculas as pendientes_count' => fn ($q) => $q->where('estado', EstadoMatricula::Activa)])
                ->get()
                ->filter(fn (Grupo $g) => $g->pendientes_count > 0),
        ]);
    }

    private function estudiante(): Renderable
    {
        $usuario = auth()->user();

        $matriculas = Matricula::with(['grupo.modulo', 'grupo.docente', 'grupo.horarios'])
            ->delEstudiante($usuario->id)
            ->latest()
            ->get();

        $gruposDelAlumno = $matriculas->pluck('grupo_id');

        // Solo las cohortes en las que entro como programa completo; los
        // grupos de cohorte que tomo por separado son modulos sueltos.
        $cohortes = Cohorte::with([
            'programa',
            'grupos' => fn ($q) => $q->with(['modulo', 'horarios'])->orderBy('fecha_inicio')->orderBy('id'),
        ])
            ->whereIn('id', $matriculas->pluck('grupo.cohorte_id')->filter()->unique())
            ->orderBy('fecha_inicio')
            ->get()
            ->filter(fn (Cohorte $c) => $c->esProgramaCompletoDe($gruposDelAlumno));

        $sueltas = $matriculas->reject(
            fn (Matricula $m) => $cohortes->contains('id', $m->grupo->cohorte_id)
        );

        // Actuales: la que empezo antes (la que esta cursando) va primero.
        // Anteriores: la mas reciente primero.
        [$cohortesActuales, $cohortesAnteriores] = $cohortes->partition(
            fn (Cohorte $c) => ! $c->estado->esHistorico()
        );
        $cohortesAnteriores = $cohortesAnteriores->reverse();

        $vigentes = $matriculas->filter(fn (Matricula $m) => $m->esVigente());
        $hoy = now()->startOfDay();

        return view('dashboard.estudiante', [
            // Bloque "Linea de tiempo" de Moodle: las proximas clases en vivo.
            'proximasClases' => $this->sesiones($vigentes, $hoy, $hoy->copy()->addDays(13))->take(8),
            // Bloque "Calendario": dias del mes con clase.
            'diasConClase' => $this->sesiones($vigentes, $hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth())
                ->map(fn (array $s) => $s['fecha']->toDateString())
                ->unique(),
            'matriculas' => $matriculas,
            'cohortesActuales' => $cohortesActuales,
            'cohortesAnteriores' => $cohortesAnteriores,
            'modulosSueltos' => $sueltas->sortBy('grupo.fecha_inicio'),
            'idsProgramas' => $cohortes->pluck('id'),
            // Si se reinscribio en un grupo, manda la matricula no anulada.
            'matriculaPorGrupo' => $matriculas
                ->sortBy(fn (Matricula $m) => $m->estado === EstadoMatricula::Anulada ? 0 : 1)
                ->keyBy('grupo_id'),
            'enCurso' => $matriculas->filter(fn (Matricula $m) => $m->esVigente()),
            'finalizadas' => $matriculas->where('estado', EstadoMatricula::Finalizada),
            'aprobadas' => $matriculas->filter(
                fn (Matricula $m) => $m->resultado?->value === 'aprobado'
            )->count(),
        ]);
    }

    /**
     * Cada clase en vivo entre dos fechas: un dia cuyo dia de la semana
     * coincide con un horario del grupo, dentro de las fechas del grupo.
     *
     * @param  Collection<int, Matricula>  $matriculas
     * @return Collection<int, array{fecha: CarbonInterface, horario: Horario, matricula: Matricula}>
     */
    private function sesiones(Collection $matriculas, CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        $sesiones = collect();

        foreach (CarbonPeriod::create($desde, $hasta) as $dia) {
            foreach ($matriculas as $matricula) {
                $grupo = $matricula->grupo;

                if ($dia->lt($grupo->fecha_inicio) || $dia->gt($grupo->fecha_fin)) {
                    continue;
                }

                foreach ($grupo->horarios as $horario) {
                    if ($horario->dia_semana->value === $dia->dayOfWeekIso) {
                        $sesiones->push(['fecha' => $dia->copy(), 'horario' => $horario, 'matricula' => $matricula]);
                    }
                }
            }
        }

        return $sesiones->sortBy(fn (array $s) => $s['fecha']->toDateString().' '.$s['horario']->horaInicioCorta())->values();
    }
}
