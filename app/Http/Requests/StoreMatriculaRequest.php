<?php

namespace App\Http\Requests;

use App\Enums\EstadoGrupo;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Inscripcion manual (Modulo 4). La persona se busca por documento:
 * si ya existe se reutiliza (RN 4.4) y no hace falta cargar sus datos.
 */
class StoreMatriculaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function personaExistente(): ?User
    {
        return User::where('documento', trim((string) $this->input('documento')))->first();
    }

    /**
     * Los grupos en los que se inscribe: el grupo elegido (modulo suelto), o
     * todos los de la cohorte que siguen en pie (programa completo).
     *
     * @return Collection<int, Grupo>
     */
    public function grupos(): Collection
    {
        if ($this->validated('tipo') === 'grupo') {
            return Grupo::whereKey($this->validated('grupo_id'))->get();
        }

        return Cohorte::findOrFail($this->validated('cohorte_id'))->grupos()
            ->where('estado', '!=', EstadoGrupo::Cancelado)
            ->orderBy('fecha_inicio')
            ->get();
    }

    public function rules(): array
    {
        $reglas = [
            'documento' => ['required', 'string', 'max:30'],
            'tipo' => ['required', 'in:grupo,cohorte'],
            'grupo_id' => ['required_if:tipo,grupo', 'nullable', 'exists:grupos,id'],
            'cohorte_id' => ['required_if:tipo,cohorte', 'nullable', 'exists:cohortes,id'],
            // Aceptacion explicita de los requisitos (informativos, RN 2.9).
            'acepto_requisitos' => ['accepted'],
        ];

        // Persona nueva: se piden sus datos para crearla.
        if (! $this->personaExistente()) {
            $reglas += [
                'name' => ['required', 'string', 'max:255'],
                'apellidos' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'telefono' => ['required', 'string', 'max:30'],
            ];
        }

        return $reglas;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['documento' => trim((string) $this->input('documento'))]);
    }

    public function messages(): array
    {
        return [
            'documento.required' => 'El documento de la persona es obligatorio.',
            'tipo.required' => 'Elige si se inscribe a un modulo suelto o al programa completo.',
            'grupo_id.required_if' => 'Elige el grupo.',
            'cohorte_id.required_if' => 'Elige la cohorte.',
            'acepto_requisitos.accepted' => 'La persona debe aceptar los requisitos del modulo para inscribirse.',
            'name.required' => 'La persona no esta registrada: el nombre es obligatorio.',
            'apellidos.required' => 'La persona no esta registrada: los apellidos son obligatorios.',
            'email.required' => 'La persona no esta registrada: el correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato valido.',
            'email.unique' => 'Ese correo ya pertenece a otro usuario con distinto documento.',
            'telefono.required' => 'La persona no esta registrada: el telefono es obligatorio.',
        ];
    }
}
