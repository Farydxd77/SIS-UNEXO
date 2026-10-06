<?php

namespace App\Http\Requests;

use App\Enums\EstadoComercial;
use App\Models\Programa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCohorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'programa_id' => ['required', 'exists:programas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            // Para calcular las fechas tentativas encadenadas de los grupos.
            'semanas_por_modulo' => ['required', 'integer', 'min:1', 'max:52'],
        ];
    }

    /** Reglas que dependen del programa elegido. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $programa = Programa::with('modulos')->find($this->input('programa_id'));

                if ($programa->estado_comercial === EstadoComercial::Borrador) {
                    $validator->errors()->add('programa_id', "El programa \"{$programa->nombre}\" esta en borrador. Abrelo antes de crear una cohorte.");

                    return;
                }

                if (! $programa->estructuraCompleta()) {
                    $validator->errors()->add('programa_id', "El programa tiene {$programa->indicadorEstructura()}. Completa su estructura primero.");

                    return;
                }

                // RN 3.2: solo se crean grupos de modulos que NO esten en borrador.
                $enBorrador = $programa->modulos
                    ->filter(fn ($m) => $m->estado_comercial === EstadoComercial::Borrador)
                    ->pluck('nombre');

                if ($enBorrador->isNotEmpty()) {
                    $validator->errors()->add('programa_id', 'No se pueden generar los grupos: estos modulos siguen en borrador: '.$enBorrador->join(', ').'.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'programa_id.required' => 'Elige el programa de la cohorte.',
            'programa_id.exists' => 'El programa elegido no existe.',
            'nombre.required' => 'El nombre de la edicion es obligatorio. Ej: "DAE - Version 3, Marzo 2027".',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio no es valida.',
            'semanas_por_modulo.required' => 'Indica cuantas semanas dura cada modulo.',
            'semanas_por_modulo.min' => 'Cada modulo debe durar al menos 1 semana.',
        ];
    }
}
