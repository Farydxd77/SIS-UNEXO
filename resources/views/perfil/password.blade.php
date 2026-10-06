@extends('layouts.app')

@section('titulo', 'Cambiar contrasena')

@section('content')

    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Cambiar contrasena</h5>
                </div>
                <div class="card-body">

                    @if ($obligatorio)
                        <div class="alert alert-warning small">
                            Es tu primer ingreso. Debes cambiar la contrasena temporal
                            antes de poder usar el sistema.
                        </div>
                    @endif

                    <form action="{{ route('password.actualizar') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="password_actual" class="form-label">Contrasena actual</label>
                            <input type="password" name="password_actual" id="password_actual" required
                                   class="form-control @error('password_actual') is-invalid @enderror">
                            @error('password_actual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva contrasena</label>
                            <input type="password" name="password" id="password" required
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Minimo 8 caracteres.</div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Repetir la nueva</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="form-control">
                        </div>

                        <button type="submit" class="btn btn-unexo w-100">Guardar</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
