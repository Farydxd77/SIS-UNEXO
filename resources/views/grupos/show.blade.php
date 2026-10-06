@extends('layouts.app')

@section('titulo', $grupo->nombre_completo)

@section('content')

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
        <div>
            <h2 class="text-unexo mb-0">{{ $grupo->nombre_completo }}</h2>
            <span class="badge text-bg-{{ $grupo->estado->color() }}">{{ $grupo->estado->etiqueta() }}</span>
            <span class="text-secondary ms-2">{{ $grupo->cohorte?->nombre ?? 'Modulo suelto' }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($puedeRegistrarNotas)
                <a href="{{ route('grupos.notas.edit', $grupo) }}" class="btn btn-unexo">Registrar notas</a>
            @endif
            @if ($esAdmin)
                @if ($grupo->estado->admiteInscripciones())
                    <a href="{{ route('matriculas.create', ['grupo' => $grupo->id]) }}" class="btn btn-outline-success">Inscribir alumno</a>
                @endif
                <a href="{{ route('grupos.edit', $grupo) }}" class="btn btn-outline-primary">Editar</a>
                <a href="{{ route('grupos.index') }}" class="btn btn-outline-secondary">Volver</a>
            @else
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Volver</a>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Informacion</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary">Modulo</dt>
                        <dd class="col-7">{{ $grupo->modulo->nombre }} ({{ $grupo->modulo->horas }}h)</dd>

                        <dt class="col-5 text-secondary">Docente</dt>
                        <dd class="col-7">{{ $grupo->docente?->nombre_completo ?? 'Sin asignar' }}</dd>

                        <dt class="col-5 text-secondary">Fechas</dt>
                        <dd class="col-7">{{ $grupo->fecha_inicio->format('d/m/Y') }} al {{ $grupo->fecha_fin->format('d/m/Y') }}</dd>

                        <dt class="col-5 text-secondary">Horario</dt>
                        <dd class="col-7">
                            @forelse ($grupo->horarios as $horario)
                                <div>{{ $horario->dia_semana->etiqueta() }} {{ $horario->horaInicioCorta() }} - {{ $horario->horaFinCorta() }}</div>
                            @empty
                                Sin horario
                            @endforelse
                        </dd>

                        <dt class="col-5 text-secondary">Inscritos</dt>
                        <dd class="col-7">
                            <span class="badge text-bg-{{ $grupo->alcanzoCupoMinimo() ? 'success' : 'warning' }}">{{ $grupo->indicadorCupo() }}</span>
                            @if ($grupo->cupo_maximo) <span class="text-secondary">(max. {{ $grupo->cupo_maximo }})</span> @endif
                        </dd>

                        <dt class="col-5 text-secondary">Requisitos</dt>
                        <dd class="col-7">{{ $grupo->modulo->requisitos ?: 'Ninguno' }}</dd>

                        <dt class="col-5 text-secondary">Enlace de Meet</dt>
                        <dd class="col-7">
                            {{-- RN 3.9: solo docente del grupo, admin y alumnos con matricula activa. --}}
                            @if ($puedeVerMeet && $grupo->enlace_meet)
                                <a href="{{ $grupo->enlace_meet }}" target="_blank" rel="noopener" class="text-break">{{ $grupo->enlace_meet }}</a>
                            @elseif ($puedeVerMeet)
                                <span class="text-secondary">Sin registrar</span>
                            @else
                                <span class="text-secondary">Disponible con tu matricula activa</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            @if ($esAdmin)
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Estado del grupo</div>
                    <div class="card-body">
                        @forelse ($grupo->estado->transicionesPermitidas() as $destino)
                            <form action="{{ route('grupos.estado', $grupo) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="estado" value="{{ $destino->value }}">
                                <button type="submit" class="btn btn-sm btn-outline-{{ $destino->color() }} mb-1">
                                    Pasar a {{ $destino->etiqueta() }}
                                </button>
                            </form>
                        @empty
                            <span class="text-secondary small">No hay transiciones disponibles.</span>
                        @endforelse

                        @if ($grupo->estado === \App\Enums\EstadoGrupo::EnConvocatoria && ! $grupo->puedeSerHabilitado())
                            <p class="small text-secondary mt-2 mb-0">
                                Para habilitar: {{ implode('; ', $grupo->faltantesParaHabilitar()) }}.
                            </p>
                        @endif
                    </div>
                </div>

                @if ($grupo->matriculas()->doesntExist())
                    <form action="{{ route('grupos.destroy', $grupo) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Eliminar grupo (sin inscritos)</button>
                    </form>
                @endif
            @endif
        </div>

        <div class="col-lg-7">
            @if ($puedeVerAlumnos)
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Alumnos ({{ $matriculas->count() }})</div>
                    <div class="table-responsive">
                        <table class="table tabla-compacta align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Alumno</th>
                                    <th>Estado</th>
                                    <th class="text-end">Nota</th>
                                    @if ($esAdmin) <th class="text-end">Acciones</th> @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($matriculas as $matricula)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $matricula->estudiante->nombre_completo }}</div>
                                            <div class="small text-secondary">
                                                {{ $matricula->estudiante->documento }} &middot; {{ $matricula->origen->etiqueta() }}
                                            </div>
                                            @if ($matricula->motivo_retiro)
                                                <div class="small text-secondary">Motivo: {{ $matricula->motivo_retiro }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge text-bg-{{ $matricula->estado->color() }}">{{ $matricula->estado->etiqueta() }}</span>
                                        </td>
                                        <td class="text-end">
                                            {{ $matricula->nota_final ?? '--' }}
                                            @if ($matricula->resultado)
                                                <span class="badge text-bg-{{ $matricula->resultado->color() }}">{{ $matricula->resultado->etiqueta() }}</span>
                                            @endif
                                        </td>
                                        @if ($esAdmin)
                                            <td class="text-end">
                                                @include('matriculas.acciones', ['matricula' => $matricula, 'alternativas' => $alternativas])
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-4">Todavia no hay alumnos inscritos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-unexo">Temario</h6>
                        <p class="mb-0" style="white-space: pre-line">{{ $grupo->modulo->temario ?: 'Sin temario registrado.' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection
