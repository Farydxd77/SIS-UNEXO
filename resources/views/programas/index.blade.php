@extends('layouts.app')

@section('titulo', 'Programas')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="text-unexo mb-0">Programas</h2>
            <small class="text-secondary">{{ $programas->total() }} programa(s) en el catalogo</small>
        </div>
        <a href="{{ route('programas.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo programa</a>
    </div>

    <form action="{{ route('programas.index') }}" method="GET" class="card card-body mb-3">
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
                    <a href="{{ route('programas.index') }}" class="btn btn-outline-danger">X</a>
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
                        <th>Estructura</th>
                        <th class="text-end">Precio contado</th>
                        <th class="text-center">Cohortes</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programas as $programa)
                        <tr>
                            <td class="fw-semibold">{{ $programa->codigo }}</td>
                            <td>{{ $programa->nombre }}</td>
                            <td>
                                @php($completa = $programa->modulos_count >= $programa->cantidad_modulos)
                                <span class="badge text-bg-{{ $completa ? 'success' : 'warning' }}">
                                    {{ $programa->modulos_count }} de {{ $programa->cantidad_modulos }} modulos
                                </span>
                            </td>
                            <td class="text-end">Bs {{ number_format($programa->precio_contado, 2) }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-light border">{{ $programa->cohortes_count }}</span>
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $programa->estado_comercial->color() }}">
                                    {{ $programa->estado_comercial->etiqueta() }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones">
                                    <a href="{{ route('programas.estructura', $programa) }}" class="btn-accion" title="Estructura (modulos y orden)"><i class="bi bi-diagram-3"></i><span class="visually-hidden">Estructura</span></a>
                                    <a href="{{ route('programas.show', $programa) }}" class="btn-accion" title="Ver"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                                    <a href="{{ route('programas.edit', $programa) }}" class="btn-accion" title="Editar"><i class="bi bi-gear"></i><span class="visually-hidden">Editar</span></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                No hay programas que coincidan con el filtro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($programas->hasPages())
        <div class="mt-3">{{ $programas->links() }}</div>
    @endif

@endsection
