@extends('layouts.app')

@section('titulo', 'Editar cohorte')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Editar cohorte</h5></div>
                <div class="card-body">
                    <form action="{{ route('cohortes.update', $cohorte) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la edicion <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="nombre" required
                                   value="{{ old('nombre', $cohorte->nombre) }}"
                                   class="form-control @error('nombre') is-invalid @enderror">
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <p class="small text-secondary">
                            Programa: <strong>{{ $cohorte->programa->nombre }}</strong> &middot;
                            inicio {{ $cohorte->fecha_inicio->format('d/m/Y') }}.
                            Las fechas de cada grupo se ajustan desde el propio grupo.
                        </p>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Guardar</button>
                            <a href="{{ route('cohortes.show', $cohorte) }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
