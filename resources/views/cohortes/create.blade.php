@extends('layouts.app')

@section('titulo', 'Nueva cohorte')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Nueva cohorte</h5></div>
                <div class="card-body">
                    <div class="alert alert-info small">
                        Al guardar, el sistema genera automaticamente <strong>un grupo por cada modulo</strong>
                        del programa, en orden y con fechas tentativas encadenadas. Despues completas cada grupo:
                        docente, horario y enlace de Meet.
                    </div>

                    <form action="{{ route('cohortes.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="programa_id" class="form-label">Programa <span class="text-danger">*</span></label>
                            <select name="programa_id" id="programa_id" required
                                    class="form-select @error('programa_id') is-invalid @enderror">
                                <option value="">-- Elige un programa --</option>
                                @foreach ($programas as $programa)
                                    <option value="{{ $programa->id }}" @selected(old('programa_id') == $programa->id)>
                                        {{ $programa->codigo }} &middot; {{ $programa->nombre }}
                                        ({{ $programa->modulos_count }} de {{ $programa->cantidad_modulos }} modulos)
                                    </option>
                                @endforeach
                            </select>
                            @error('programa_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Los programas en borrador no aparecen.</div>
                        </div>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la edicion <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="nombre" required value="{{ old('nombre') }}"
                                   placeholder="DAE - Version 3, Marzo 2027"
                                   class="form-control @error('nombre') is-invalid @enderror">
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha de inicio <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" required value="{{ old('fecha_inicio') }}"
                                       class="form-control @error('fecha_inicio') is-invalid @enderror">
                                @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semanas_por_modulo" class="form-label">Semanas por modulo <span class="text-danger">*</span></label>
                                <input type="number" name="semanas_por_modulo" id="semanas_por_modulo" min="1" max="52" required
                                       value="{{ old('semanas_por_modulo', 4) }}"
                                       class="form-control @error('semanas_por_modulo') is-invalid @enderror">
                                @error('semanas_por_modulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">El grupo 2 empieza cuando termina el 1.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Crear cohorte y generar grupos</button>
                            <a href="{{ route('cohortes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
