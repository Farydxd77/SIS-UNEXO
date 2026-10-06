@extends('layouts.app')

@section('titulo', 'Estructura del programa')

@section('content')

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="text-unexo mb-0">Estructura: {{ $programa->nombre }}</h2>
            <span class="text-secondary">{{ $programa->codigo }}</span>
            @php($completa = $programa->estructuraCompleta())
            <span class="badge text-bg-{{ $completa ? 'success' : 'warning' }} ms-2">
                {{ $programa->indicadorEstructura() }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('programas.show', $programa) }}" class="btn btn-outline-secondary">Ver programa</a>
            <a href="{{ route('programas.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    @unless ($completa)
        <div class="alert alert-warning">
            El programa declara <strong>{{ $programa->cantidad_modulos }}</strong> modulos y tiene
            <strong>{{ $programa->modulosAsignados() }}</strong> asignados.
            No podras abrirlo hasta completar la estructura (RN 2.5).
        </div>
    @endunless

    <div class="row g-3">
        {{-- Columna izquierda: los modulos del programa, en orden --}}
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    Modulos del programa, en orden
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($programa->modulos as $i => $modulo)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <span class="badge text-bg-dark">{{ $modulo->pivot->orden }}</span>

                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $modulo->codigo }} &middot; {{ $modulo->nombre }}</div>
                                <div class="small text-secondary">
                                    {{ $modulo->horas }}h &middot; Bs {{ number_format($modulo->precio, 2) }}
                                    &middot; {{ $modulo->estado_comercial->etiqueta() }}
                                </div>
                            </div>

                            {{-- Subir --}}
                            <form action="{{ route('programas.modulos.mover', [$programa, $modulo]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="direccion" value="arriba">
                                <button class="btn btn-sm btn-outline-secondary" @disabled($i === 0)
                                        title="Subir">&uarr;</button>
                            </form>

                            {{-- Bajar --}}
                            <form action="{{ route('programas.modulos.mover', [$programa, $modulo]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="direccion" value="abajo">
                                <button class="btn btn-sm btn-outline-secondary"
                                        @disabled($i === $programa->modulos->count() - 1)
                                        title="Bajar">&darr;</button>
                            </form>

                            {{-- Quitar --}}
                            <form action="{{ route('programas.modulos.quitar', [$programa, $modulo]) }}" method="POST"
                                  onsubmit="return confirm('Quitar {{ $modulo->nombre }} del programa?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Quitar">&times;</button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-secondary py-4">
                            El programa todavia no tiene modulos. Agregalos desde la derecha.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Columna derecha: buscador de modulos disponibles --}}
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Agregar modulos</div>
                <div class="card-body">
                    <form action="{{ route('programas.estructura', $programa) }}" method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control"
                                   placeholder="Buscar modulo por nombre o codigo..." value="{{ $buscar }}">
                            <button class="btn btn-secondary" type="submit">Buscar</button>
                        </div>
                    </form>

                    <div class="list-group">
                        @forelse ($disponibles as $modulo)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small">{{ $modulo->codigo }} &middot; {{ $modulo->nombre }}</div>
                                    <div class="small text-secondary">
                                        {{ $modulo->horas }}h &middot;
                                        <span class="badge text-bg-{{ $modulo->estado_comercial->color() }}">
                                            {{ $modulo->estado_comercial->etiqueta() }}
                                        </span>
                                    </div>
                                </div>
                                <form action="{{ route('programas.modulos.agregar', $programa) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="modulo_id" value="{{ $modulo->id }}">
                                    <button class="btn btn-sm btn-unexo">Agregar</button>
                                </form>
                            </div>
                        @empty
                            <div class="list-group-item text-secondary small text-center py-3">
                                @if ($buscar)
                                    Ningun modulo disponible coincide con "{{ $buscar }}".
                                @else
                                    No hay mas modulos disponibles.
                                    <a href="{{ route('modulos.create') }}">Crea uno nuevo</a>.
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
