<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:50',
                Rule::unique('roles', 'nombre')->ignore($this->route('rol')),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe otro rol con ese nombre.',
        ];
    }
}
