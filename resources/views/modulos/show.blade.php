@extends('layouts.app')

@section('titulo', $modulo->nombre)

@section('content')

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="text-unexo mb-0">{{ $modulo->codigo }} &middot; {{ $modulo->nombre }}</h2>
            <span class="badge text-bg-{{ $modulo->estado_comercial->color() }}">
                {{ $modulo->estado_comercial->etiqueta() }}
            </span>
            <span class="text-secondary ms-2">{{ $modulo->horas }} horas &middot; Bs {{ number_format($modulo->precio, 2) }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('modulos.edit', $modulo) }}" class="btn btn-outline-primary">Editar</a>
            <a href="{{ route('modulos.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Descripcion y requisitos</div>
                <div class="card-body">
                    <p>{{ $modulo->descripcion ?? 'Sin descripcion.' }}</p>
                    <hr>
                    <div class="small text-secondary mb-1">Requisitos (informativos, no bloquean la matricula)</div>
                    <p class="mb-0">{{ $modulo->requisitos ?? '--' }}</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Temario</div>
                <div class="card-body">
                    @if ($modulo->temario)
                        <pre class="mb-0 small" style="white-space: pre-wrap;">{{ $modulo->temario }}</pre>
                    @else
                        <span class="text-secondary">Sin temario cargado.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- Maquina de estados comerciales --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Estado comercial</div>
                <div class="card-body">
                    <p class="small text-secondary">
                        Actual: <strong>{{ $modulo->estado_comercial->etiqueta() }}</strong>.
                        Transiciones posibles desde aqui:
                    </p>
                    @forelse ($modulo->estado_comercial->transicionesPermitidas() as $destino)
                        <form action="{{ route('modulos.estado', $modulo) }}" method="POST" class="d-inline">
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

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">
                    En que programas esta ({{ $modulo->programas->count() }})
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($modulo->programas as $programa)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <a href="{{ route('programas.show', $programa) }}">{{ $programa->codigo }}</a>
                                <span class="text-secondary small">{{ $programa->nombre }}</span>
                            </span>
                            <span class="badge text-bg-light border">posicion {{ $programa->pivot->orden }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary small">No esta en ningun programa.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    Grupos abiertos de este modulo ({{ $modulo->grupos->count() }})
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($modulo->grupos as $grupo)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>Version {{ $grupo->version }}</strong>
                                <span class="badge text-bg-{{ $grupo->estado->color() }}">{{ $grupo->estado->etiqueta() }}</span>
                            </div>
                            <div class="small text-secondary">
                                {{ $grupo->fecha_inicio->format('d/m/Y') }} al {{ $grupo->fecha_fin->format('d/m/Y') }}
                                &middot; {{ $grupo->docente?->nombre_completo ?? 'sin docente' }}
                                &middot; {{ $grupo->resumenHorario() ?: 'sin horario' }}
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary small">Nunca se ha abierto un grupo de este modulo.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

@endsection
