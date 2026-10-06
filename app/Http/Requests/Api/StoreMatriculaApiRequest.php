<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\StoreMatriculaRequest;

/**
 * Inscripcion que envia el CRM por la API (seccion 9). Mismas reglas que la
 * carga manual, mas el id del lead, obligatorio para no procesarlo dos
 * veces (RN 4.7).
 */
class StoreMatriculaApiRequest extends StoreMatriculaRequest
{
    /**
     * Con la API abierta (CRM_API_REQUIERE_TOKEN=false) no hay usuario que
     * revisar. Con token, debe ser de un administrador activo.
     */
    public function authorize(): bool
    {
        if (! config('services.crm.requiere_token')) {
            return true;
        }

        $usuario = $this->user();

        return $usuario !== null && $usuario->activo && $usuario->esAdministrador();
    }

    public function rules(): array
    {
        return parent::rules() + [
            'crm_lead_id' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'crm_lead_id.required' => 'El id del lead del CRM es obligatorio.',
        ];
    }
}
