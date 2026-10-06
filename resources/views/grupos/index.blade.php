@extends('layouts.app')

@section('titulo', 'Grupos')

@section('content')

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="text-unexo mb-0">Grupos</h2>
            <small class="text-secondary">{{ $grupos->total() }} grupo(s)</small>
        </div>
        <a href="{{ route('grupos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo grupo</a>
    </div>

    <form action="{{ route('grupos.index') }}" method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Estado</option>
                    @foreach ($estados as $e)
                        <option value="{{ $e->value }}" @selected(($filtros['estado'] ?? '') === $e->value)>{{ $e->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="modulo" class="form-select">
                    <option value="">Modulo</option>
                    @foreach ($modulos as $m)
                        <option value="{{ $m->id }}" @selected(($filtros['modulo'] ?? '') == $m->id)>{{ $m->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="docente" class="form-select">
                    <option value="">Docente</option>
                    @foreach ($docentes as $d)
                        <option value="{{ $d->id }}" @selected(($filtros['docente'] ?? '') == $d->id)>{{ $d->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1">
                <input type="date" name="desde" class="form-control" title="Desde" value="{{ $filtros['desde'] ?? '' }}">
            </div>
            <div class="col-6 col-md-1">
                <input type="date" name="hasta" class="form-control" title="Hasta" value="{{ $filtros['hasta'] ?? '' }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-secondary flex-grow-1" type="submit">Filtrar</button>
                @if (array_filter($filtros))
                    <a href="{{ route('grupos.index') }}" class="btn btn-outline-danger">X</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover tabla-compacta align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Grupo</th>
                        <th>Cohorte</th>
                        <th>Docente</th>
                        <th>Fechas</th>
                        <th>Horario</th>
                        <th class="text-center">Cupo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grupos as $grupo)
                        <tr>
                            <td class="fw-semibold">{{ $grupo->nombre_completo }}</td>
                            <td class="small">{{ $grupo->cohorte?->nombre ?? 'Modulo suelto' }}</td>
                            <td class="small">{{ $grupo->docente?->nombre_completo ?? '--' }}</td>
                            <td class="small text-nowrap">
                                {{ $grupo->fecha_inicio->format('d/m/y') }} - {{ $grupo->fecha_fin->format('d/m/y') }}
                            </td>
                            <td class="small">{{ $grupo->resumenHorario() ?: '--' }}</td>
                            <td class="text-center">
                                {{-- Avance hacia el cupo minimo: "14 / 20" --}}
                                <span class="badge text-bg-{{ $grupo->vigentes_count >= $grupo->cupo_minimo ? 'success' : 'warning' }}">
                                    {{ $grupo->vigentes_count }} / {{ $grupo->cupo_minimo }}
                                </span>
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $grupo->estado->color() }}">{{ $grupo->estado->etiqueta() }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones">
                                    <a href="{{ route('grupos.show', $grupo) }}" class="btn-accion" title="Ver"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                                    <a href="{{ route('grupos.edit', $grupo) }}" class="btn-accion" title="Editar"><i class="bi bi-gear"></i><span class="visually-hidden">Editar</span></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No hay grupos que coincidan con el filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($grupos->hasPages())
        <div class="mt-3">{{ $grupos->links() }}</div>
    @endif

@endsection
