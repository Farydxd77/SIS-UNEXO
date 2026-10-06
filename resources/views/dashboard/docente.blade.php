@extends('layouts.app')

@section('titulo', 'Panel del docente')

@section('content')

    <h2 class="text-unexo mb-1">Mis grupos</h2>
    <p class="text-secondary">Solo ves los grupos que tienes asignados.</p>

    @if ($porCalificar->isNotEmpty())
        <div class="alert alert-warning">
            <strong>Tienes notas pendientes de cargar:</strong>
            {{ $porCalificar->map(fn ($g) => $g->nombre_completo.' ('.$g->pendientes_count.' alumnos)')->join(', ') }}
        </div>
    @endif

    <div class="row g-3">
        @forelse ($grupos as $grupo)
            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">{{ $grupo->nombre_completo }}</span>
                        <span class="badge text-bg-{{ $grupo->estado->color() }}">{{ $grupo->estado->etiqueta() }}</span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-secondary">Modulo</dt>
                            <dd class="col-7">{{ $grupo->modulo->nombre }} ({{ $grupo->modulo->horas }}h)</dd>

                            <dt class="col-5 text-secondary">Cohorte</dt>
                            <dd class="col-7">{{ $grupo->cohorte?->nombre ?? 'Modulo suelto' }}</dd>

                            <dt class="col-5 text-secondary">Fechas</dt>
                            <dd class="col-7">
                                {{ $grupo->fecha_inicio->format('d/m/Y') }} al {{ $grupo->fecha_fin->format('d/m/Y') }}
                            </dd>

                            <dt class="col-5 text-secondary">Horario</dt>
                            <dd class="col-7">{{ $grupo->resumenHorario() ?: '--' }}</dd>

                            <dt class="col-5 text-secondary">Inscritos</dt>
                            <dd class="col-7">{{ $grupo->indicadorCupo() }}</dd>
                        </dl>

                        {{-- RN 3.9: el docente del grupo si puede ver el enlace. --}}
                        @if ($grupo->puedeVerEnlaceMeet(auth()->user()) && $grupo->enlace_meet)
                            <a href="{{ $grupo->enlace_meet }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-unexo w-100 mt-3">Entrar a la clase (Meet)</a>
                        @endif

                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('grupos.show', $grupo) }}" class="btn btn-sm btn-outline-secondary flex-fill">Ver alumnos</a>
                            @if (\App\Http\Controllers\GrupoController::admiteNotas($grupo))
                                <a href="{{ route('grupos.notas.edit', $grupo) }}" class="btn btn-sm btn-outline-primary flex-fill">Cargar notas</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center text-secondary py-5">
                        Todavia no tienes grupos asignados.
                    </div>
                </div>
            </div>
        @endforelse
    </div>

@endsection
