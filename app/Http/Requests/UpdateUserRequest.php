<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // El usuario que se esta editando, para excluirlo de los unique.
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'documento' => ['required', 'string', 'max:30', Rule::unique('users', 'documento')->ignore($usuario)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'correo_institucional' => ['nullable', 'email', 'max:255', Rule::unique('users', 'correo_institucional')->ignore($usuario)],
            'telefono' => ['required', 'string', 'max:30'],
            // 'nullable': si lo dejas vacio, se conserva la contraseña actual.
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'activo' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'documento.required' => 'El numero de documento es obligatorio.',
            'documento.unique' => 'Ya existe otro usuario con ese documento.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Ese correo ya esta registrado en otro usuario.',
            'correo_institucional.unique' => 'Ese correo institucional ya esta en otro usuario.',
            'telefono.required' => 'El telefono es obligatorio.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
