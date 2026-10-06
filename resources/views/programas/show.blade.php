@extends('layouts.app')

@section('titulo', $programa->nombre)

@section('content')

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="text-unexo mb-0">{{ $programa->codigo }} &middot; {{ $programa->nombre }}</h2>
            <span class="badge text-bg-{{ $programa->estado_comercial->color() }}">
                {{ $programa->estado_comercial->etiqueta() }}
            </span>
            <span class="text-secondary ms-2">
                Bs {{ number_format($programa->precio_contado, 2) }} al contado
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('programas.estructura', $programa) }}" class="btn btn-outline-dark">Estructura</a>
            <a href="{{ route('programas.edit', $programa) }}" class="btn btn-outline-primary">Editar</a>
            <a href="{{ route('programas.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Descripcion</div>
                <div class="card-body">
                    <p class="mb-0">{{ $programa->descripcion ?? 'Sin descripcion.' }}</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <span>Modulos, en orden</span>
                    <span class="badge text-bg-{{ $programa->estructuraCompleta() ? 'success' : 'warning' }}">
                        {{ $programa->indicadorEstructura() }}
                    </span>
                </div>
                <ol class="list-group list-group-flush list-group-numbered">
                    @forelse ($programa->modulos as $modulo)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <a href="{{ route('modulos.show', $modulo) }}">{{ $modulo->codigo }}</a>
                                {{ $modulo->nombre }}
                                <span class="small text-secondary">({{ $modulo->horas }}h)</span>
                            </span>
                            <span class="small text-secondary">Bs {{ number_format($modulo->precio, 2) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">
                            Sin modulos asignados.
                            <a href="{{ route('programas.estructura', $programa) }}">Asignarlos</a>.
                        </li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Estado comercial</div>
                <div class="card-body">
                    <p class="small text-secondary">
                        Actual: <strong>{{ $programa->estado_comercial->etiqueta() }}</strong>
                    </p>
                    @forelse ($programa->estado_comercial->transicionesPermitidas() as $destino)
                        <form action="{{ route('programas.estado', $programa) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="estado_comercial" value="{{ $destino->value }}">
                            <button type="submit" class="btn btn-sm btn-outline-{{ $destino->color() }} mb-1">
                                Pasar a {{ $destino->etiqueta() }}
                            </button>
                        </form>
                    @empty
                        <span class="text-secondary small">No hay transiciones disponibles.</span>
                    @endforelse
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    Cohortes ({{ $programa->cohortes->count() }})
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($programa->cohortes as $cohorte)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $cohorte->nombre }}</strong>
                                <span class="badge text-bg-{{ $cohorte->estado->color() }}">
                                    {{ $cohorte->estado->etiqueta() }}
                                </span>
                            </div>
                            <div class="small text-secondary">
                                Desde {{ $cohorte->fecha_inicio->format('d/m/Y') }}
                                &middot; {{ $cohorte->grupos->count() }} grupo(s)
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary small">
                            Este programa no tiene cohortes abiertas todavia.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

@endsection
