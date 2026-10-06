<?php

namespace App\Http\Requests;

use App\Actions\Grupos\DetectarChoqueHorario;
use App\Enums\EstadoComercial;
use App\Enums\EstadoGrupo;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validacion comun para crear y editar grupos (Modulo 3).
 * StoreGrupoRequest y UpdateGrupoRequest solo cambian lo que difiere.
 */
abstract class GrupoRequest extends FormRequest
{
    /** El grupo que se edita; null al crear. */
    abstract protected function grupoActual(): ?Grupo;

    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'cohorte_id' => ['nullable', 'exists:cohortes,id'],
            'docente_id' => ['nullable', 'exists:users,id'],
            // RN 3.1: fecha_fin >= fecha_inicio
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cupo_minimo' => ['required', 'integer', 'min:1'],
            'cupo_maximo' => ['nullable', 'integer', 'gte:cupo_minimo'],
            'enlace_meet' => ['nullable', 'url', 'max:255'],
            // Filas dinamicas: dia + hora inicio + hora fin.
            'horarios' => ['array'],
            'horarios.*.dia_semana' => ['required', 'integer', 'between:1,7'],
            'horarios.*.hora_inicio' => ['required', 'date_format:H:i'],
            // RN 3.1: hora_fin > hora_inicio
            'horarios.*.hora_fin' => ['required', 'date_format:H:i', 'after:horarios.*.hora_inicio'],
        ];
    }

    /**
     * Las inputs type="time" pueden mandar "19:00:00"; se normaliza a "19:00".
     * Un cupo maximo vacio llega como "" y debe ser null.
     */
    protected function prepareForValidation(): void
    {
        $horarios = collect($this->input('horarios', []))
            ->map(fn ($fila) => [
                'dia_semana' => $fila['dia_semana'] ?? null,
                'hora_inicio' => substr((string) ($fila['hora_inicio'] ?? ''), 0, 5),
                'hora_fin' => substr((string) ($fila['hora_fin'] ?? ''), 0, 5),
            ])
            ->values()
            ->all();

        $this->merge([
            'horarios' => $horarios,
            'cupo_maximo' => $this->filled('cupo_maximo') ? $this->input('cupo_maximo') : null,
            'enlace_meet' => $this->filled('enlace_meet') ? trim($this->input('enlace_meet')) : null,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validarModuloYCohorte($validator);
                $this->validarDocente($validator);
                $this->validarEnlaceMeet($validator);
                $this->validarHorariosSegunEstado($validator);

                // RN 3.4: solo se busca choque si todo lo anterior es valido.
                if ($validator->errors()->isEmpty()) {
                    $this->validarChoqueHorario($validator);
                }
            },
        ];
    }

    /** El modulo del grupo: el que llega al crear, el que ya tiene al editar. */
    protected function moduloId(): ?int
    {
        return $this->grupoActual()?->modulo_id ?? ($this->integer('modulo_id') ?: null);
    }

    private function validarModuloYCohorte(Validator $validator): void
    {
        $modulo = Modulo::find($this->moduloId());

        // RN 3.2 (solo al crear: un grupo existente ya paso este control).
        if (! $this->grupoActual() && $modulo?->estado_comercial === EstadoComercial::Borrador) {
            $validator->errors()->add('modulo_id', "El modulo \"{$modulo->nombre}\" esta en borrador: no se pueden abrir grupos de el.");
        }

        if ($this->filled('cohorte_id') && $modulo) {
            $cohorte = Cohorte::with('programa')->find($this->input('cohorte_id'));

            if (! $cohorte->programa->modulos()->whereKey($modulo->id)->exists()) {
                $validator->errors()->add('cohorte_id', "El modulo \"{$modulo->nombre}\" no forma parte del programa de la cohorte \"{$cohorte->nombre}\".");
            }
        }
    }

    /** RN 3.3: el docente debe tener rol docente y estar activo. */
    private function validarDocente(Validator $validator): void
    {
        if (! $this->filled('docente_id')) {
            return;
        }

        $docente = User::find($this->input('docente_id'));

        if (! $docente->esDocente()) {
            $validator->errors()->add('docente_id', "{$docente->nombre_completo} no tiene rol docente.");
        } elseif (! $docente->activo) {
            $validator->errors()->add('docente_id', "{$docente->nombre_completo} esta desactivado.");
        }
    }

    /** RN 3.8: el enlace de Meet no se repite entre grupos activos. */
    private function validarEnlaceMeet(Validator $validator): void
    {
        if (! $this->filled('enlace_meet')) {
            return;
        }

        $otro = Grupo::with('modulo')
            ->where('enlace_meet', $this->input('enlace_meet'))
            ->whereIn('estado', EstadoGrupo::vigentesParaChoque())
            ->when($this->grupoActual(), fn ($q, $g) => $q->whereKeyNot($g->id))
            ->first();

        if ($otro) {
            $validator->errors()->add('enlace_meet', "Ese enlace de Meet ya lo usa el grupo {$otro->nombre_completo}. Cada grupo necesita su propia sala.");
        }
    }

    /** RN 3.6 sostenida: un grupo ya en convocatoria no puede quedarse sin horario. */
    private function validarHorariosSegunEstado(Validator $validator): void
    {
        $grupo = $this->grupoActual();

        if ($grupo && $grupo->estado !== EstadoGrupo::Planificado && $this->input('horarios') === []) {
            $validator->errors()->add('horarios', "El grupo esta {$grupo->estado->etiqueta()}: debe tener al menos un horario.");
        }
    }

    private function validarChoqueHorario(Validator $validator): void
    {
        if (! $this->filled('docente_id') || $this->input('horarios') === []) {
            return;
        }

        $detector = new DetectarChoqueHorario;

        $choques = $detector->ejecutar(
            docenteId: (int) $this->input('docente_id'),
            fechaInicio: $this->date('fecha_inicio')->toDateString(),
            fechaFin: $this->date('fecha_fin')->toDateString(),
            horarios: $this->input('horarios'),
            // Al editar, el grupo no debe chocar consigo mismo.
            grupoIdExcluido: $this->grupoActual()?->id,
        );

        if ($choques->isNotEmpty()) {
            $validator->errors()->add('horarios', $detector->mensaje($choques));
        }
    }

    public function messages(): array
    {
        return [
            'modulo_id.required' => 'Elige el modulo del grupo.',
            'modulo_id.exists' => 'El modulo elegido no existe.',
            'cohorte_id.exists' => 'La cohorte elegida no existe.',
            'docente_id.exists' => 'El docente elegido no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'cupo_minimo.required' => 'El cupo minimo es obligatorio.',
            'cupo_minimo.min' => 'El cupo minimo debe ser al menos 1.',
            'cupo_maximo.gte' => 'El cupo maximo no puede ser menor que el minimo.',
            'enlace_meet.url' => 'El enlace de Meet debe ser una URL valida (https://meet.google.com/...).',
            'horarios.*.dia_semana.required' => 'Cada fila de horario necesita un dia.',
            'horarios.*.dia_semana.between' => 'El dia de la semana no es valido.',
            'horarios.*.hora_inicio.required' => 'Cada fila de horario necesita hora de inicio.',
            'horarios.*.hora_inicio.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'horarios.*.hora_fin.required' => 'Cada fila de horario necesita hora de fin.',
            'horarios.*.hora_fin.date_format' => 'La hora de fin debe tener formato HH:MM.',
            'horarios.*.hora_fin.after' => 'La hora de fin debe ser posterior a la de inicio.',
        ];
    }

    /** Los datos del grupo sin las filas de horario, que van a otra tabla. */
    public function datosGrupo(): array
    {
        return collect($this->validated())->except('horarios')->all();
    }

    public function horarios(): array
    {
        return $this->validated('horarios', []);
    }
}
