<?php

namespace App\Http\Controllers;

use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Models\Grupo;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * "Mis cursos" como en Moodle 4: los grupos del usuario (los que cursa y los
 * que imparte) filtrados en progreso / futuros / pasados.
 */
class MisCursosController extends Controller
{
    public const FILTROS = [
        'todos' => 'Todos',
        'en-progreso' => 'En progreso',
        'futuros' => 'Futuros',
        'pasados' => 'Pasados',
    ];

    public function index(Request $request): View
    {
        $filtro = array_key_exists($request->query('filtro'), self::FILTROS) ? $request->query('filtro') : 'todos';
        $vista = $request->query('vista') === 'lista' ? 'lista' : 'tarjetas';
        $buscar = trim((string) $request->query('buscar'));

        $cursos = $this->cursosDe($request->user()->id, $request->user()->esDocente())
            ->filter(fn (array $c) => $filtro === 'todos' || $c['momento'] === $filtro)
            ->filter(fn (array $c) => $buscar === '' || str_contains(
                mb_strtolower($c['grupo']->modulo->nombre.' '.$c['grupo']->modulo->codigo.' '.$c['categoria']),
                mb_strtolower($buscar)
            ))
            ->values();

        return view('mis-cursos.index', [
            'cursos' => $cursos,
            'filtros' => self::FILTROS,
            'filtro' => $filtro,
            'vista' => $vista,
            'buscar' => $buscar,
        ]);
    }

    /**
     * @return Collection<int, array{grupo: Grupo, matricula: ?Matricula, categoria: string, momento: string, progreso: int}>
     */
    private function cursosDe(int $usuarioId, bool $esDocente): Collection
    {
        $relaciones = ['modulo', 'cohorte.programa', 'cohorte.grupos', 'docente', 'horarios'];

        $matriculas = Matricula::with(array_map(fn ($r) => "grupo.{$r}", $relaciones))
            ->delEstudiante($usuarioId)
            ->get();
        $gruposDelAlumno = $matriculas->pluck('grupo_id');

        $comoAlumno = $matriculas
            ->where('estado', '!=', EstadoMatricula::Anulada)
            ->map(fn (Matricula $m) => $this->curso(
                $m->grupo,
                $m,
                // Un grupo de cohorte tomado solo es un modulo suelto (Cohorte::esProgramaCompletoDe).
                $m->grupo->cohorte?->esProgramaCompletoDe($gruposDelAlumno) ? $m->grupo->cohorte->programa->nombre : 'Modulo suelto',
            ));

        $comoDocente = $esDocente
            ? Grupo::with($relaciones)
                ->delDocente($usuarioId)
                ->where('estado', '!=', EstadoGrupo::Cancelado)
                ->get()
                ->map(fn (Grupo $g) => $this->curso($g, null, $g->cohorte?->programa->nombre ?? 'Modulo suelto'))
            : collect();

        return $comoAlumno->concat($comoDocente)->sortBy(fn (array $c) => $c['grupo']->fecha_inicio)->values();
    }

    /**
     * @return array{grupo: Grupo, matricula: ?Matricula, categoria: string, momento: string, progreso: int}
     */
    private function curso(Grupo $grupo, ?Matricula $matricula, string $categoria): array
    {
        $hoy = now()->startOfDay();

        $momento = match (true) {
            $grupo->fecha_inicio->gt($hoy) => 'futuros',
            $grupo->fecha_fin->lt($hoy) => 'pasados',
            default => 'en-progreso',
        };

        // Como el "% completado" de Moodle: terminado = 100; si no, el avance del calendario.
        $dias = max(1, $grupo->fecha_inicio->diffInDays($grupo->fecha_fin));
        $progreso = $matricula?->estado === EstadoMatricula::Finalizada || $momento === 'pasados'
            ? 100
            : (int) round(min(100, max(0, $grupo->fecha_inicio->diffInDays($hoy, false) * 100 / $dias)));

        return [
            'grupo' => $grupo,
            'matricula' => $matricula,
            'categoria' => $categoria,
            'momento' => $momento,
            'progreso' => $progreso,
        ];
    }
}
