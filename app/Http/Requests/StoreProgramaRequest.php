<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            // RN 2.1: el codigo de programa es unico.
            'codigo' => ['required', 'string', 'max:50', 'unique:programas,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'cantidad_modulos' => ['required', 'integer', 'min:1', 'max:50'],
            'precio_contado' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['codigo' => strtoupper(trim((string) $this->input('codigo')))]);
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El codigo del programa es obligatorio.',
            'codigo.unique' => 'Ya existe un programa con ese codigo.',
            'nombre.required' => 'El nombre del programa es obligatorio.',
            'cantidad_modulos.required' => 'Indica de cuantos modulos consta el programa.',
            'cantidad_modulos.min' => 'El programa debe tener al menos 1 modulo.',
            'precio_contado.required' => 'El precio al contado es obligatorio.',
            'precio_contado.min' => 'El precio no puede ser negativo.',
        ];
    }
}
