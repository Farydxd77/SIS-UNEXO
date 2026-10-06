@extends('layouts.app')

@section('titulo', 'Asignar docentes y Meet')

@section('content')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="text-unexo mb-0">Asignar docentes y Meet</h2>
            <small class="text-secondary">
                {{ $cohorte->nombre }} &middot; {{ $cohorte->programa->nombre }} &middot; {{ $grupos->count() }} grupo(s)
            </small>
        </div>
        <a href="{{ route('cohortes.show', $cohorte) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver a la cohorte</a>
    </div>

    @if ($grupos->isEmpty())
        <div class="alert alert-info">Esta cohorte no tiene grupos que se puedan editar.</div>
    @else
        <form action="{{ route('cohortes.asignar.guardar', $cohorte) }}" method="POST" id="form-asignar">
            @csrf
            @method('PUT')

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="copiar-horario">
                    <i class="bi bi-files me-1"></i>Copiar el horario del primer grupo a todos
                </button>
                <span class="small text-secondary">Las fechas y cupos se editan en cada grupo; aqui solo docente, Meet y horario.</span>
            </div>

            @foreach ($grupos as $grupo)
                @php
                    $base = "grupos.{$grupo->id}";
                    $filas = old("{$base}.horarios", $grupo->horarios->map(fn ($h) => [
                        'dia_semana' => $h->dia_semana->value,
                        'hora_inicio' => $h->horaInicioCorta(),
                        'hora_fin' => $h->horaFinCorta(),
                    ])->all());
                    $erroresGrupo = collect($errors->getMessages())
                        ->filter(fn ($m, $campo) => str_starts_with($campo, $base.'.'))
                        ->flatten()
                        ->unique();
                @endphp

                <div class="card mb-3 grupo-asignar {{ $erroresGrupo->isNotEmpty() ? 'border-danger' : '' }}" data-grupo="{{ $grupo->id }}">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill text-bg-light border">{{ $loop->iteration }}</span>
                            <span>{{ $grupo->modulo->nombre }} <span class="fw-normal text-secondary">&middot; Version {{ $grupo->version }}</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 small fw-normal">
                            <span class="text-secondary">{{ $grupo->fecha_inicio->format('d/m/Y') }} - {{ $grupo->fecha_fin->format('d/m/Y') }}</span>
                            <span class="badge text-bg-{{ $grupo->estado->color() }}">{{ $grupo->estado->etiqueta() }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($erroresGrupo->isNotEmpty())
                            <div class="alert alert-danger py-2 small">
                                @foreach ($erroresGrupo as $mensaje)
                                    <div>{{ $mensaje }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="docente-{{ $grupo->id }}">Docente</label>
                                <select name="grupos[{{ $grupo->id }}][docente_id]" id="docente-{{ $grupo->id }}" class="form-select">
                                    <option value="">-- Sin asignar --</option>
                                    @foreach ($docentes as $docente)
                                        <option value="{{ $docente->id }}" @selected((string) old("{$base}.docente_id", $grupo->docente_id) === (string) $docente->id)>{{ $docente->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="meet-{{ $grupo->id }}">Enlace de Meet</label>
                                <input type="url" name="grupos[{{ $grupo->id }}][enlace_meet]" id="meet-{{ $grupo->id }}" class="form-control"
                                       value="{{ old("{$base}.enlace_meet", $grupo->enlace_meet) }}" placeholder="https://meet.google.com/abc-defg-hij">
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="form-label mb-0">Horarios semanales</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary agregar-horario"><i class="bi bi-plus-lg me-1"></i>Agregar horario</button>
                                </div>
                                <div class="horarios" data-siguiente="{{ count($filas) }}">
                                    @foreach ($filas as $i => $fila)
                                        <div class="row g-2 mb-2 fila-horario">
                                            <div class="col-sm-4">
                                                <select name="grupos[{{ $grupo->id }}][horarios][{{ $i }}][dia_semana]" class="form-select form-select-sm campo-dia" required>
                                                    @foreach ($dias as $dia)
                                                        <option value="{{ $dia->value }}" @selected((int) ($fila['dia_semana'] ?? 0) === $dia->value)>{{ $dia->etiqueta() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-5 col-sm-3"><input type="time" name="grupos[{{ $grupo->id }}][horarios][{{ $i }}][hora_inicio]" value="{{ $fila['hora_inicio'] ?? '' }}" class="form-control form-control-sm campo-inicio" required></div>
                                            <div class="col-5 col-sm-3"><input type="time" name="grupos[{{ $grupo->id }}][horarios][{{ $i }}][hora_fin]" value="{{ $fila['hora_fin'] ?? '' }}" class="form-control form-control-sm campo-fin" required></div>
                                            <div class="col-2 col-sm-2 text-end"><button type="button" class="btn-accion peligro quitar-horario" title="Quitar"><i class="bi bi-x-lg"></i></button></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="small text-secondary sin-horarios {{ count($filas) ? 'd-none' : '' }}">Sin horarios. Para pasar a convocatoria necesita al menos uno.</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="card card-body d-flex flex-row flex-wrap justify-content-between align-items-center gap-3 position-sticky bottom-0 shadow-sm" style="background: #fff">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="abrir_convocatoria" value="1" id="abrir-convocatoria" @checked(old('abrir_convocatoria'))>
                    <label class="form-check-label" for="abrir-convocatoria">
                        Al guardar, pasar a <strong>En convocatoria</strong> los grupos planificados que tengan horario
                    </label>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('cohortes.show', $cohorte) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar todo</button>
                </div>
            </div>
        </form>

        <template id="plantilla-fila">
            <div class="row g-2 mb-2 fila-horario">
                <div class="col-sm-4">
                    <select name="grupos[__g__][horarios][__i__][dia_semana]" class="form-select form-select-sm campo-dia" required>
                        @foreach ($dias as $dia)
                            <option value="{{ $dia->value }}">{{ $dia->etiqueta() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-5 col-sm-3"><input type="time" name="grupos[__g__][horarios][__i__][hora_inicio]" value="19:00" class="form-control form-control-sm campo-inicio" required></div>
                <div class="col-5 col-sm-3"><input type="time" name="grupos[__g__][horarios][__i__][hora_fin]" value="21:00" class="form-control form-control-sm campo-fin" required></div>
                <div class="col-2 col-sm-2 text-end"><button type="button" class="btn-accion peligro quitar-horario" title="Quitar"><i class="bi bi-x-lg"></i></button></div>
            </div>
        </template>
    @endif

@endsection

@push('scripts')
<script>
    (function () {
        const plantilla = document.getElementById('plantilla-fila');
        if (!plantilla) return;

        function agregarFila(tarjeta, valores) {
            const contenedor = tarjeta.querySelector('.horarios');
            const i = Number(contenedor.dataset.siguiente || 0);
            contenedor.dataset.siguiente = i + 1;

            const html = plantilla.innerHTML.replaceAll('__g__', tarjeta.dataset.grupo).replaceAll('__i__', i);
            contenedor.insertAdjacentHTML('beforeend', html);

            const fila = contenedor.lastElementChild;
            if (valores) {
                fila.querySelector('.campo-dia').value = valores.dia;
                fila.querySelector('.campo-inicio').value = valores.inicio;
                fila.querySelector('.campo-fin').value = valores.fin;
            }
            tarjeta.querySelector('.sin-horarios').classList.add('d-none');
        }

        document.querySelectorAll('.grupo-asignar').forEach(function (tarjeta) {
            tarjeta.querySelector('.agregar-horario').addEventListener('click', () => agregarFila(tarjeta));
            tarjeta.querySelector('.horarios').addEventListener('click', function (e) {
                const boton = e.target.closest('.quitar-horario');
                if (!boton) return;
                boton.closest('.fila-horario').remove();
                tarjeta.querySelector('.sin-horarios').classList.toggle('d-none', tarjeta.querySelectorAll('.fila-horario').length > 0);
            });
        });

        // El horario del primer grupo se copia al resto (reemplaza sus filas).
        document.getElementById('copiar-horario').addEventListener('click', function () {
            const tarjetas = Array.from(document.querySelectorAll('.grupo-asignar'));
            const filas = Array.from(tarjetas[0].querySelectorAll('.fila-horario')).map(f => ({
                dia: f.querySelector('.campo-dia').value,
                inicio: f.querySelector('.campo-inicio').value,
                fin: f.querySelector('.campo-fin').value,
            }));

            tarjetas.slice(1).forEach(function (tarjeta) {
                tarjeta.querySelectorAll('.fila-horario').forEach(f => f.remove());
                filas.forEach(v => agregarFila(tarjeta, v));
                tarjeta.querySelector('.sin-horarios').classList.toggle('d-none', filas.length > 0);
            });
        });
    })();
</script>
@endpush
