@extends('layouts.app')

@section('titulo', 'Mis cursos')

@section('content')

    <h2 class="text-unexo mb-4">Mis cursos</h2>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Vista general de curso</h5>

            {{-- Controles como los del bloque de Moodle: filtro, busqueda y vista. --}}
            <form action="{{ route('mis-cursos.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-4">
                <input type="hidden" name="vista" value="{{ $vista }}">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        {{ $filtros[$filtro] }}
                    </button>
                    <ul class="dropdown-menu">
                        @foreach ($filtros as $clave => $etiqueta)
                            <li>
                                <a class="dropdown-item {{ $filtro === $clave ? 'active' : '' }}"
                                   href="{{ route('mis-cursos.index', ['filtro' => $clave, 'vista' => $vista, 'buscar' => $buscar ?: null]) }}">{{ $etiqueta }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <input type="hidden" name="filtro" value="{{ $filtro }}">
                <div class="input-group" style="max-width: 280px">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="buscar" value="{{ $buscar }}" class="form-control" placeholder="Buscar">
                </div>
                <div class="dropdown ms-auto">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi {{ $vista === 'lista' ? 'bi-list-ul' : 'bi-grid' }} me-1"></i>{{ $vista === 'lista' ? 'Lista' : 'Tarjeta' }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item {{ $vista === 'tarjetas' ? 'active' : '' }}" href="{{ route('mis-cursos.index', ['filtro' => $filtro, 'vista' => 'tarjetas', 'buscar' => $buscar ?: null]) }}"><i class="bi bi-grid me-2"></i>Tarjeta</a></li>
                        <li><a class="dropdown-item {{ $vista === 'lista' ? 'active' : '' }}" href="{{ route('mis-cursos.index', ['filtro' => $filtro, 'vista' => 'lista', 'buscar' => $buscar ?: null]) }}"><i class="bi bi-list-ul me-2"></i>Lista</a></li>
                    </ul>
                </div>
            </form>

            @if ($cursos->isEmpty())
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    No hay cursos {{ $filtro === 'todos' ? '' : 'en "'.mb_strtolower($filtros[$filtro]).'"' }}{{ $buscar !== '' ? ' que coincidan con "'.$buscar.'"' : '' }}.
                </div>
            @elseif ($vista === 'tarjetas')
                <div class="row g-4">
                    @foreach ($cursos as $curso)
                        <div class="col-sm-6 col-lg-4 col-xxl-3">
                            @include('mis-cursos.tarjeta', ['curso' => $curso])
                        </div>
                    @endforeach
                </div>
            @else
                <ul class="list-group list-group-flush border-top">
                    @foreach ($cursos as $curso)
                        @php $grupo = $curso['grupo']; @endphp
                        @php $url = $curso['matricula'] === null || $curso['matricula']->esVigente() ? route('grupos.show', $grupo) : null; @endphp
                        <li class="list-group-item d-flex align-items-center gap-3 py-3 px-0">
                            <x-curso-imagen :semilla="$grupo->modulo_id" :alto="64" :ancho="96" class="rounded-2 flex-shrink-0" />
                            <div class="flex-grow-1 min-w-0">
                                <div class="curso-categoria">{{ $curso['categoria'] }}</div>
                                @if ($url)
                                    <a href="{{ $url }}" class="curso-nombre">{{ $grupo->modulo->nombre }}</a>
                                @else
                                    <span class="curso-nombre">{{ $grupo->modulo->nombre }}</span>
                                @endif
                                <div class="small text-secondary">
                                    {{ $grupo->fecha_inicio->format('d/m/Y') }} - {{ $grupo->fecha_fin->format('d/m/Y') }}
                                    @if ($grupo->resumenHorario()) &middot; {{ $grupo->resumenHorario() }} @endif
                                </div>
                            </div>
                            <div class="text-end small" style="width: 140px">
                                <div class="progress mb-1" style="height: .35rem"><div class="progress-bar" style="width: {{ $curso['progreso'] }}%; background: var(--unexo)"></div></div>
                                <strong>{{ $curso['progreso'] }}%</strong> completado
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

@endsection
