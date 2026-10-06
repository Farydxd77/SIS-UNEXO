<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:50',
                Rule::unique('modulos', 'codigo')->ignore($this->route('modulo')),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'requisitos' => ['nullable', 'string'],
            'temario' => ['nullable', 'string'],
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
            'codigo.unique' => 'Ya existe otro modulo con ese codigo.',
            'nombre.required' => 'El nombre del modulo es obligatorio.',
            'horas.min' => 'Las horas deben ser mayores que cero.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}
