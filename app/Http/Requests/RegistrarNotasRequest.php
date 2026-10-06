<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarNotasRequest extends FormRequest
{
    /** RN 4.10: solo el docente del grupo (o el admin para corregir). */
    public function authorize(): bool
    {
        return $this->user()?->can('registrarNotas', $this->route('grupo')) ?? false;
    }

    public function rules(): array
    {
        return [
            'notas' => ['required', 'array'],
            // RN 4.11: nota entre 0 y 100. Vacio = todavia sin cargar.
            'notas.*' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'notas.required' => 'No se envio ninguna nota.',
            'notas.*.numeric' => 'Las notas deben ser numericas.',
            'notas.*.between' => 'Cada nota debe estar entre 0 y 100.',
        ];
    }
}
