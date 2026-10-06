<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Matriculas\InscribirEstudiante;
use App\Enums\OrigenMatricula;
use App\Exceptions\ReglaDeNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMatriculaApiRequest;
use App\Http\Resources\MatriculaResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

/**
 * Inscripciones que llegan del CRM. El CRM solo envia a quien ya pago, asi
 * que las matriculas nacen activas (OrigenMatricula::estadoInicial).
 */
class MatriculaController extends Controller
{
    public function store(StoreMatriculaApiRequest $request, InscribirEstudiante $inscribir): JsonResponse
    {
        try {
            $resultado = $inscribir->ejecutar(
                $request->validated(),
                $request->grupos(),
                OrigenMatricula::Api,
                $request->validated('crm_lead_id'),
            );
        } catch (ReglaDeNegocioException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $usuario = $resultado['usuario'];

        return response()->json([
            'usuario' => [
                'id' => $usuario->id,
                'documento' => $usuario->documento,
                'nombre' => $usuario->nombre_completo,
                'email' => $usuario->email,
                // Solo si la cuenta se acaba de crear; debe cambiarla al entrar.
                'password_temporal' => $resultado['password_temporal'],
            ],
            'matriculas' => MatriculaResource::collection(
                (new Collection($resultado['matriculas']->all()))->load('grupo.modulo')
            ),
        ], 201);
    }
}
