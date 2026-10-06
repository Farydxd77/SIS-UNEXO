<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Al editar una cohorte solo cambia el nombre: el programa y las fechas
 * ya generaron sus grupos, que se ajustan uno a uno desde cada grupo.
 */
class UpdateCohorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la edicion es obligatorio.',
        ];
    }
}
