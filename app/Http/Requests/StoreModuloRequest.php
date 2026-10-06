<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            // RN 2.1: el codigo de modulo es unico.
            'codigo' => ['required', 'string', 'max:50', 'unique:modulos,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'requisitos' => ['nullable', 'string'],
            'temario' => ['nullable', 'string'],
            // RN 2.10: horas > 0 y precio >= 0.
            'horas' => ['required', 'integer', 'min:1'],
            'precio' => ['required', 'numeric', 'min:0'],
            'se_oferta_por_separado' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => strtoupper(trim((string) $this->input('codigo'))),
            'se_oferta_por_separado' => $this->boolean('se_oferta_por_separado'),
        ]);
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El codigo del modulo es obligatorio.',
            'codigo.unique' => 'Ya existe un modulo con ese codigo.',
            'nombre.required' => 'El nombre del modulo es obligatorio.',
            'horas.required' => 'Las horas son obligatorias.',
            'horas.min' => 'Las horas deben ser mayores que cero.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}
