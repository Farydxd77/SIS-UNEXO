@extends('layouts.app')

@section('titulo', 'Matriculas')

@section('content')

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h2 class="text-unexo mb-0">Matriculas</h2>
            <small class="text-secondary">{{ $matriculas->total() }} matricula(s). Nunca se borran: se retiran o se anulan.</small>
        </div>
        <a href="{{ route('matriculas.create') }}" class="btn btn-unexo">+ Inscribir</a>
    </div>

    <form action="{{ route('matriculas.index') }}" method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="buscar" class="form-control" placeholder="Alumno: nombre o documento..." value="{{ $buscar }}">
            </div>
            <div class="col-md-3">
                <select name="grupo" class="form-select">
                    <option value="">-- Todos los grupos --</option>
                    @foreach ($grupos as $g)
                        <option value="{{ $g->id }}" @selected((string) $grupoId === (string) $g->id)>{{ $g->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="estado" class="form-select">
                    <option value="">-- Todos los estados --</option>
                    @foreach ($estados as $e)
                        <option value="{{ $e->value }}" @selected($estado === $e->value)>{{ $e->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-secondary flex-grow-1" type="submit">Filtrar</button>
                @if ($buscar || $estado || $grupoId)
                    <a href="{{ route('matriculas.index') }}" class="btn btn-outline-danger">X</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover tabla-compacta align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Alumno</th>
                        <th>Grupo</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th class="text-end">Nota</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matriculas as $matricula)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $matricula->estudiante->nombre_completo }}</div>
                                <div class="small text-secondary">{{ $matricula->estudiante->documento }}</div>
                            </td>
                            <td>
                                <a href="{{ route('grupos.show', $matricula->grupo) }}">{{ $matricula->grupo->nombre_completo }}</a>
                                <div>
                                    @if ($programasCompletos->contains($matricula->user_id.'-'.$matricula->grupo->cohorte_id))
                                        <span class="badge rounded-pill text-bg-primary">Programa completo</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">Modulo suelto</span>
                                    @endif
                                </div>
                            </td>
                            <td class="small">
                                {{ $matricula->origen->etiqueta() }}
                                @if ($matricula->crm_lead_id) <div class="text-secondary">{{ $matricula->crm_lead_id }}</div> @endif
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $matricula->estado->color() }}" title="{{ $matricula->estado->descripcion() }}">
                                    {{ $matricula->estado->etiqueta() }}
                                </span>
                            </td>
                            <td class="text-end">{{ $matricula->nota_final ?? '--' }}</td>
                            <td class="text-end">
                                {{-- La reubicacion se hace desde el detalle del grupo cancelado. --}}
                                @include('matriculas.acciones', ['matricula' => $matricula, 'alternativas' => collect()])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">No hay matriculas que coincidan con el filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($matriculas->hasPages())
        <div class="mt-3">{{ $matriculas->links() }}</div>
    @endif

@endsection
