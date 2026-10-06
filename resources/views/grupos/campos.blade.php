{{-- Campos compartidos por crear y editar un grupo. --}}

@php
    // Filas de horario: lo que el usuario acaba de enviar (si hubo error)
    // o lo guardado en la base de datos.
    $filasHorario = old('horarios', $grupo->exists
        ? $grupo->horarios->map(fn ($h) => [
            'dia_semana' => $h->dia_semana->value,
            'hora_inicio' => $h->horaInicioCorta(),
            'hora_fin' => $h->horaFinCorta(),
        ])->all()
        : []);
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa el formulario:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="modulo_id" class="form-label">Modulo <span class="text-danger">*</span></label>
        @if ($grupo->exists)
            <input type="text" class="form-control" value="{{ $grupo->nombre_completo }}" disabled>
            <div class="form-text">El modulo y la version no cambian al editar.</div>
        @else
            <select name="modulo_id" id="modulo_id" required class="form-select @error('modulo_id') is-invalid @enderror">
                <option value="">-- Elige un modulo --</option>
                @foreach ($modulos as $modulo)
                    <option value="{{ $modulo->id }}" @selected(old('modulo_id', $grupo->modulo_id) == $modulo->id)>
                        {{ $modulo->codigo }} &middot; {{ $modulo->nombre }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">La version se calcula sola. Los modulos en borrador no aparecen.</div>
        @endif
    </div>

    <div class="col-md-6 mb-3">
        <label for="cohorte_id" class="form-label">Cohorte (opcional)</label>
        <select name="cohorte_id" id="cohorte_id" class="form-select @error('cohorte_id') is-invalid @enderror">
            <option value="">-- Modulo suelto --</option>
            @foreach ($cohortes as $cohorte)
                <option value="{{ $cohorte->id }}" @selected(old('cohorte_id', $grupo->cohorte_id) == $cohorte->id)>
                    {{ $cohorte->nombre }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="docente_id" class="form-label">Docente</label>
        <select name="docente_id" id="docente_id" class="form-select @error('docente_id') is-invalid @enderror">
            <option value="">-- Sin asignar --</option>
            @foreach ($docentes as $docente)
                <option value="{{ $docente->id }}" @selected(old('docente_id', $grupo->docente_id) == $docente->id)>
                    {{ $docente->nombre_completo }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Solo docentes activos. Se valida que no tenga choque de horario.</div>
    </div>

    <div class="col-md-6 mb-3">
        <label for="enlace_meet" class="form-label">Enlace de Meet</label>
        <input type="url" name="enlace_meet" id="enlace_meet" placeholder="https://meet.google.com/abc-defg-hij"
               value="{{ old('enlace_meet', $grupo->enlace_meet) }}"
               class="form-control @error('enlace_meet') is-invalid @enderror">
        <div class="form-text">Cada grupo tiene su propia sala.</div>
    </div>
</div>

<div class="row">
    <div class="col-6 col-md-3 mb-3">
        <label for="fecha_inicio" class="form-label">Fecha inicio <span class="text-danger">*</span></label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" required
               value="{{ old('fecha_inicio', $grupo->fecha_inicio?->format('Y-m-d')) }}"
               class="form-control @error('fecha_inicio') is-invalid @enderror">
    </div>
    <div class="col-6 col-md-3 mb-3">
        <label for="fecha_fin" class="form-label">Fecha fin <span class="text-danger">*</span></label>
        <input type="date" name="fecha_fin" id="fecha_fin" required
               value="{{ old('fecha_fin', $grupo->fecha_fin?->format('Y-m-d')) }}"
               class="form-control @error('fecha_fin') is-invalid @enderror">
    </div>
    <div class="col-6 col-md-3 mb-3">
        <label for="cupo_minimo" class="form-label">Cupo minimo <span class="text-danger">*</span></label>
        <input type="number" name="cupo_minimo" id="cupo_minimo" min="1" required
               value="{{ old('cupo_minimo', $grupo->cupo_minimo) }}"
               class="form-control @error('cupo_minimo') is-invalid @enderror">
    </div>
    <div class="col-6 col-md-3 mb-3">
        <label for="cupo_maximo" class="form-label">Cupo maximo</label>
        <input type="number" name="cupo_maximo" id="cupo_maximo" min="1"
               value="{{ old('cupo_maximo', $grupo->cupo_maximo) }}"
               class="form-control @error('cupo_maximo') is-invalid @enderror">
    </div>
</div>

<div class="card mb-3 @error('horarios') border-danger @enderror">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Horarios</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-horario">+ Agregar fila</button>
    </div>
    <div class="card-body">
        <div id="filas-horario">
            @foreach ($filasHorario as $i => $fila)
                <div class="row g-2 mb-2 fila-horario">
                    <div class="col-12 col-md-4">
                        <select name="horarios[{{ $i }}][dia_semana]" class="form-select" required>
                            @foreach ($dias as $dia)
                                <option value="{{ $dia->value }}" @selected((int) ($fila['dia_semana'] ?? 0) === $dia->value)>{{ $dia->etiqueta() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-5 col-md-3">
                        <input type="time" name="horarios[{{ $i }}][hora_inicio]" class="form-control" required value="{{ $fila['hora_inicio'] ?? '' }}">
                    </div>
                    <div class="col-5 col-md-3">
                        <input type="time" name="horarios[{{ $i }}][hora_fin]" class="form-control" required value="{{ $fila['hora_fin'] ?? '' }}">
                    </div>
                    <div class="col-2 col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100 quitar-horario" title="Quitar fila">&times;</button>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="small text-secondary mb-0" id="sin-horarios" @if (count($filasHorario) > 0) hidden @endif>
            Sin horarios. Para pasar a convocatoria necesita al menos uno.
        </p>
    </div>
</div>

{{-- Plantilla de fila: el JS la clona con un indice nuevo. --}}
<template id="plantilla-horario">
    <div class="row g-2 mb-2 fila-horario">
        <div class="col-12 col-md-4">
            <select name="horarios[__i__][dia_semana]" class="form-select" required>
                @foreach ($dias as $dia)
                    <option value="{{ $dia->value }}">{{ $dia->etiqueta() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-5 col-md-3">
            <input type="time" name="horarios[__i__][hora_inicio]" class="form-control" required value="19:00">
        </div>
        <div class="col-5 col-md-3">
            <input type="time" name="horarios[__i__][hora_fin]" class="form-control" required value="21:00">
        </div>
        <div class="col-2 col-md-2">
            <button type="button" class="btn btn-outline-danger w-100 quitar-horario" title="Quitar fila">&times;</button>
        </div>
    </div>
</template>

@push('scripts')
<script>
    (function () {
        const contenedor = document.getElementById('filas-horario');
        const plantilla = document.getElementById('plantilla-horario');
        const aviso = document.getElementById('sin-horarios');
        // Indice siempre creciente: evita nombres repetidos al quitar y agregar.
        let siguiente = {{ count($filasHorario) }};

        const actualizarAviso = () => {
            aviso.hidden = contenedor.querySelectorAll('.fila-horario').length > 0;
        };

        document.getElementById('agregar-horario').addEventListener('click', () => {
            const html = plantilla.innerHTML.replaceAll('__i__', siguiente++);
            contenedor.insertAdjacentHTML('beforeend', html);
            actualizarAviso();
        });

        contenedor.addEventListener('click', (e) => {
            if (e.target.classList.contains('quitar-horario')) {
                e.target.closest('.fila-horario').remove();
                actualizarAviso();
            }
        });
    })();
</script>
@endpush
