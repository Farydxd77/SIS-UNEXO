<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'documento' => ['required', 'string', 'max:30', 'unique:users,documento'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'correo_institucional' => ['nullable', 'email', 'max:255', 'unique:users,correo_institucional'],
            'telefono' => ['required', 'string', 'max:30'],
            // 'confirmed' exige que venga tambien password_confirmation y que coincida.
            'password' => ['required', 'confirmed', Password::min(8)],
            'activo' => ['boolean'],
            // Los roles llegan como array de ids desde los checkboxes.
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    /**
     * Se ejecuta ANTES de validar. Un checkbox sin marcar no se envia,
     * asi que aqui lo convertimos en false explicitamente.
     */
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
            'documento.unique' => 'Ya existe un usuario con ese documento.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato valido.',
            'email.unique' => 'Ese correo ya esta registrado.',
            'correo_institucional.unique' => 'Ese correo institucional ya esta registrado.',
            'telefono.required' => 'El telefono es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'roles.required' => 'El usuario debe tener al menos un rol (RN 1.3).',
            'roles.min' => 'El usuario debe tener al menos un rol (RN 1.3).',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }
}
