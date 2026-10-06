<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCohorte;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\View\View;

/**
 * Catalogo en modo lectura para cualquier usuario: solo lo abierto
 * (seccion 4 de la spec, "Estudiante: solo ver lo abierto").
 */
class OfertaController extends Controller
{
    public function index(): View
    {
        return view('oferta.index', [
            'programas' => Programa::abiertos()
                ->with([
                    'modulos',
                    // La proxima edicion que aun admite interesados.
                    'cohortes' => fn ($q) => $q
                        ->whereIn('estado', [EstadoCohorte::Planificado, EstadoCohorte::EnConvocatoria])
                        ->orderBy('fecha_inicio'),
                ])
                ->orderBy('nombre')
                ->get(),
            'modulos' => Modulo::abiertos()
                ->where('se_oferta_por_separado', true)
                ->with(['grupos' => fn ($q) => $q->admiteInscripciones()->with('horarios')->orderBy('fecha_inicio')])
                ->orderBy('nombre')
                ->get(),
        ]);
    }
}
