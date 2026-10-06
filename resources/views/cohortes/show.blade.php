@extends('layouts.app')

@section('titulo', $cohorte->nombre)

@section('content')

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
        <div>
            <h2 class="text-unexo mb-0">{{ $cohorte->nombre }}</h2>
            <span class="badge text-bg-{{ $cohorte->estado->color() }}">{{ $cohorte->estado->etiqueta() }}</span>
            <span class="text-secondary ms-2">
                <a href="{{ route('programas.show', $cohorte->programa) }}">{{ $cohorte->programa->codigo }}</a>
                &middot; {{ $cohorte->fecha_inicio->format('d/m/Y') }}
                @if ($cohorte->fecha_fin) al {{ $cohorte->fecha_fin->format('d/m/Y') }} @endif
            </span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('cohortes.asignar', $cohorte) }}" class="btn btn-primary"><i class="bi bi-person-video3 me-1"></i>Asignar docentes y Meet</a>
            <a href="{{ route('matriculas.create') }}" class="btn btn-outline-success">Inscribir alumno</a>
            <a href="{{ route('cohortes.edit', $cohorte) }}" class="btn btn-outline-primary">Editar</a>
            <a href="{{ route('cohortes.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <span>Linea de tiempo de grupos</span>
                    <span class="badge text-bg-{{ $gruposListos === $cohorte->grupos->count() ? 'success' : 'warning' }}">
                        {{ $gruposListos }} de {{ $cohorte->grupos->count() }} con docente y horario
                    </span>
                </div>
                <div class="card-body">
                    <ol class="linea-tiempo list-unstyled mb-0">
                        @forelse ($cohorte->grupos as $grupo)
                            <li class="pb-3">
                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <a href="{{ route('grupos.show', $grupo) }}" class="fw-semibold">{{ $grupo->nombre_completo }}</a>
                                    <span class="badge text-bg-{{ $grupo->estado->color() }}">{{ $grupo->estado->etiqueta() }}</span>
                                </div>
                                <div class="small text-secondary">
                                    {{ $grupo->fecha_inicio->format('d/m/Y') }} al {{ $grupo->fecha_fin->format('d/m/Y') }}
                                    &middot; {{ $grupo->docente?->nombre_completo ?? 'sin docente' }}
                                    &middot; {{ $grupo->resumenHorario() ?: 'sin horario' }}
                                </div>
                                <div class="small">
                                    Inscritos:
                                    <span class="badge text-bg-{{ $grupo->vigentes_count >= $grupo->cupo_minimo ? 'success' : 'light border' }}">
                                        {{ $grupo->vigentes_count }} / {{ $grupo->cupo_minimo }}
                                    </span>
                                    <a href="{{ route('grupos.edit', $grupo) }}?volver=cohorte" class="ms-2">Editar este grupo</a>
                                </div>
                            </li>
                        @empty
                            <li class="text-secondary">Esta cohorte no tiene grupos.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="text-secondary small">Inscripciones vigentes (todos los grupos)</div>
                    <div class="fs-3 fw-bold">{{ $totalInscritos }}</div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Estado de la cohorte</div>
                <div class="card-body">
                    @forelse ($cohorte->estado->transicionesPermitidas() as $destino)
                        <form action="{{ route('cohortes.estado', $cohorte) }}" method="POST" class="d-inline">
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
                    <p class="small text-secondary mt-2 mb-0">
                        Cancelar la cohorte cancela sus grupos pendientes y anula sus matriculas.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-danger-subtle">
                <div class="card-body">
                    <form action="{{ route('cohortes.destroy', $cohorte) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Eliminar cohorte</button>
                    </form>
                    <p class="small text-secondary mt-2 mb-0">Solo si se creo por error: sin inscritos y con todos sus grupos planificados.</p>
                </div>
            </div>
        </div>
    </div>

@endsection
