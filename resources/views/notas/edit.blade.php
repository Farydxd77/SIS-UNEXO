@extends('layouts.app')

@section('titulo', 'Notas - '.$grupo->nombre_completo)

@section('content')

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
        <div>
            <h2 class="text-unexo mb-0">Notas &middot; {{ $grupo->nombre_completo }}</h2>
            <span class="text-secondary">
                Docente: {{ $grupo->docente?->nombre_completo ?? 'sin asignar' }}
                &middot; nota minima de aprobacion: {{ $notaMinima }}
            </span>
        </div>
        <a href="{{ route('grupos.show', $grupo) }}" class="btn btn-outline-secondary">Volver al grupo</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info small">
        Carga la nota (0 a 100) de cada alumno con matricula activa. El resultado se calcula solo.
        Cuando todas las notas esten cargadas, las matriculas pasan a <strong>finalizada</strong>.
        @if ($esAdmin) Como administrador tambien puedes corregir notas ya finalizadas. @endif
    </div>

    <form action="{{ route('grupos.notas.update', $grupo) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card shadow-sm mb-3">
            <div class="table-responsive">
                <table class="table tabla-compacta align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Alumno</th>
                            <th>Estado</th>
                            <th style="width: 9rem">Nota</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($matriculas as $matricula)
                            @php($editable = $matricula->estado === \App\Enums\EstadoMatricula::Activa || $esAdmin)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $matricula->estudiante->nombre_completo }}</div>
                                    <div class="small text-secondary">{{ $matricula->estudiante->documento }}</div>
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $matricula->estado->color() }}">{{ $matricula->estado->etiqueta() }}</span>
                                </td>
                                <td>
                                    @if ($editable)
                                        <input type="number" name="notas[{{ $matricula->id }}]" min="0" max="100" step="0.01"
                                               value="{{ old('notas.'.$matricula->id, $matricula->nota_final) }}"
                                               class="form-control form-control-sm @error('notas.'.$matricula->id) is-invalid @enderror">
                                    @else
                                        {{ $matricula->nota_final ?? '--' }}
                                    @endif
                                </td>
                                <td>
                                    @if ($matricula->resultado)
                                        <span class="badge text-bg-{{ $matricula->resultado->color() }}">{{ $matricula->resultado->etiqueta() }}</span>
                                    @else
                                        <span class="text-secondary small">pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">
                                    No hay alumnos con matricula activa en este grupo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($matriculas->isNotEmpty())
            <button type="submit" class="btn btn-unexo">Guardar notas</button>
        @endif
    </form>

@endsection
