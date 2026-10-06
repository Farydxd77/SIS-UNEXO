@extends('layouts.app')

@section('titulo', $historial ? 'Historial de cohortes' : 'Cohortes')

@section('content')

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            {{-- Vigentes / historial se eligen en la navegacion terciaria del layout. --}}
            <h2 class="text-unexo mb-0">{{ $historial ? 'Historial de cohortes' : 'Cohortes vigentes' }}</h2>
            <small class="text-secondary">
                @if ($historial)
                    {{ $totalHistoricas }} cohorte(s) finalizadas o canceladas, con sus resultados
                @else
                    {{ $totalVigentes }} cohorte(s) &middot; cada cohorte es una edicion de un programa
                @endif
            </small>
        </div>
        <a href="{{ route('cohortes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva cohorte</a>
    </div>

    <form action="{{ route('cohortes.index') }}" method="GET" class="card card-body mb-3">
        @if ($historial)
            <input type="hidden" name="vista" value="historial">
        @endif
        <div class="row g-2">
            <div class="col-md-6">
                <select name="programa" class="form-select">
                    <option value="">-- Todos los programas --</option>
                    @foreach ($programas as $p)
                        <option value="{{ $p->id }}" @selected((string) $programaId === (string) $p->id)>{{ $p->codigo }} &middot; {{ $p->nombre }}</option>
                    @endforeach
                </select>
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
                @if ($programaId || $estado)
                    <a href="{{ route('cohortes.index', $historial ? ['vista' => 'historial'] : []) }}" class="btn btn-outline-danger">X</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover tabla-compacta align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cohorte</th>
                        <th>Programa</th>
                        <th>Fechas</th>
                        <th class="text-center">Grupos</th>
                        @if ($historial)
                            <th class="text-center">Matriculas</th>
                            <th class="text-center">Aprobadas</th>
                        @endif
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cohortes as $cohorte)
                        <tr>
                            <td class="fw-semibold">{{ $cohorte->nombre }}</td>
                            <td>{{ $cohorte->programa->codigo }}</td>
                            <td class="small text-nowrap">
                                {{ $cohorte->fecha_inicio->format('d/m/Y') }}
                                @if ($cohorte->fecha_fin) - {{ $cohorte->fecha_fin->format('d/m/Y') }} @endif
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-light border">{{ $cohorte->grupos_count }}</span>
                            </td>
                            @if ($historial)
                                <td class="text-center">{{ $cohorte->matriculas_count }}</td>
                                <td class="text-center">
                                    {{ $cohorte->aprobadas_count }}
                                    @if ($cohorte->matriculas_count > 0)
                                        <span class="small text-secondary">({{ round($cohorte->aprobadas_count * 100 / $cohorte->matriculas_count) }}%)</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="badge text-bg-{{ $cohorte->estado->color() }}">{{ $cohorte->estado->etiqueta() }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="acciones">
                                    <a href="{{ route('cohortes.show', $cohorte) }}" class="btn-accion" title="Ver"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                                    @unless ($historial)
                                        <a href="{{ route('cohortes.edit', $cohorte) }}" class="btn-accion" title="Editar"><i class="bi bi-gear"></i><span class="visually-hidden">Editar</span></a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $historial ? 8 : 6 }}" class="text-center text-secondary py-4">
                                {{ $historial ? 'Todavia no hay cohortes finalizadas o canceladas.' : 'No hay cohortes que coincidan con el filtro.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($cohortes->hasPages())
        <div class="mt-3">{{ $cohortes->links() }}</div>
    @endif

@endsection
