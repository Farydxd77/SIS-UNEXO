@extends('layouts.app')

@section('titulo', 'Modulos')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="text-unexo mb-0">Modulos</h2>
            <small class="text-secondary">{{ $modulos->total() }} modulo(s) en el catalogo</small>
        </div>
        <a href="{{ route('modulos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo modulo</a>
    </div>

    <form action="{{ route('modulos.index') }}" method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-6">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por nombre o codigo..." value="{{ $buscar }}">
            </div>
            <div class="col-md-4">
                <select name="estado" class="form-select">
                    <option value="">-- Todos los estados --</option>
                    @foreach ($estados as $e)
                        <option value="{{ $e->value }}" @selected($estado === $e->value)>{{ $e->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-secondary flex-grow-1" type="submit">Filtrar</button>
                @if ($buscar || $estado)
                    <a href="{{ route('modulos.index') }}" class="btn btn-outline-danger">X</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover tabla-compacta align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th class="text-end">Horas</th>
                        <th class="text-end">Precio</th>
                        <th class="text-center">Programas</th>
                        <th class="text-center">Grupos</th>
                        <th>Suelto</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($modulos as $modulo)
                        <tr>
                            <td class="fw-semibold">{{ $modulo->codigo }}</td>
                            <td>{{ $modulo->nombre }}</td>
                            <td class="text-end">{{ $modulo->horas }}h</td>
                            <td class="text-end">Bs {{ number_format($modulo->precio, 2) }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-light border">{{ $modulo->programas_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-light border">{{ $modulo->grupos_count }}</span>
                            </td>
                            <td>
                                @if ($modulo->se_oferta_por_separado)
                                    <span class="text-success">Si</span>
                                @else
                                    <span class="text-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $modulo->estado_comercial->color() }}">
                                    {{ $modulo->estado_comercial->etiqueta() }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones">
                                    <a href="{{ route('modulos.show', $modulo) }}" class="btn-accion" title="Ver"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                                    <a href="{{ route('modulos.edit', $modulo) }}" class="btn-accion" title="Editar"><i class="bi bi-gear"></i><span class="visually-hidden">Editar</span></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4">
                                No hay modulos que coincidan con el filtro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($modulos->hasPages())
        <div class="mt-3">{{ $modulos->links() }}</div>
    @endif

@endsection
