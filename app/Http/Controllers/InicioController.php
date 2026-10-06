<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCohorte;
use App\Models\Cohorte;
use App\Models\Programa;
use Illuminate\View\View;

/**
 * Pagina publica de inicio: lo primero que ve cualquier visitante,
 * con sesion o sin ella. El login se abre desde la barra superior.
 */
class InicioController extends Controller
{
    public function index(): View
    {
        return view('inicio', [
            'programas' => Programa::abiertos()->withCount('modulos')->orderBy('nombre')->get(),
            // Proximas ediciones: las que aun admiten interesados.
            'proximasCohortes' => Cohorte::with('programa')
                ->whereIn('estado', [EstadoCohorte::Planificado, EstadoCohorte::EnConvocatoria])
                ->whereHas('programa', fn ($q) => $q->abiertos())
                ->orderBy('fecha_inicio')
                ->limit(6)
                ->get(),
        ]);
    }
}
