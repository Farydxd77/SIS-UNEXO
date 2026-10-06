<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoGrupo;
use App\Http\Controllers\Controller;
use App\Http\Resources\ModuloCatalogoResource;
use App\Http\Resources\ProgramaCatalogoResource;
use App\Models\Modulo;
use App\Models\Programa;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Catalogo para el CRM: lo mismo que muestra /oferta (solo lo abierto),
 * con los ids que luego se envian a POST /api/v1/matriculas.
 */
class CatalogoController extends Controller
{
    public function index(): JsonResponse
    {
        $programas = Programa::abiertos()
            ->with([
                'modulos',
                'cohortes' => fn ($q) => $q->enOferta()->orderBy('fecha_inicio'),
                // Inscribirse a la cohorte matricula en sus grupos que siguen en pie.
                'cohortes.grupos' => $this->cargarGrupo(fn ($q) => $q->where('estado', '!=', EstadoGrupo::Cancelado)),
            ])
            ->orderBy('nombre')
            ->get();

        $modulosSueltos = Modulo::abiertos()
            ->where('se_oferta_por_separado', true)
            ->with(['grupos' => $this->cargarGrupo(fn ($q) => $q->admiteInscripciones())])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'programas' => ProgramaCatalogoResource::collection($programas),
            'modulos_sueltos' => ModuloCatalogoResource::collection($modulosSueltos),
        ]);
    }

    /**
     * Lo que necesita GrupoCatalogoResource, cargado de una vez.
     *
     * @param  Closure(Builder): mixed  $filtro
     */
    private function cargarGrupo(Closure $filtro): Closure
    {
        return fn ($q) => $filtro($q)
            ->with(['modulo', 'horarios' => fn ($h) => $h->orderBy('dia_semana')->orderBy('hora_inicio')])
            ->conInscritosVigentes()
            ->orderBy('fecha_inicio');
    }
}
