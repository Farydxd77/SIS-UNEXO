@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <h2 class="text-unexo mb-3">Mi perfil</h2>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Datos oficiales</div>
                <div class="card-body">
                    <div class="alert alert-secondary small mb-3">
                        Estos datos salen en los certificados, asi que solo el administrador
                        puede modificarlos. Si algo esta mal, avisale.
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary">Nombres</dt>
                        <dd class="col-sm-8">{{ $usuario->name }}</dd>

                        <dt class="col-sm-4 text-secondary">Apellidos</dt>
                        <dd class="col-sm-8">{{ $usuario->apellidos }}</dd>

                        <dt class="col-sm-4 text-secondary">Documento</dt>
                        <dd class="col-sm-8">{{ $usuario->documento }}</dd>

                        <dt class="col-sm-4 text-secondary">Correo institucional</dt>
                        <dd class="col-sm-8">{{ $usuario->correo_institucional ?? '--' }}</dd>

                        <dt class="col-sm-4 text-secondary">Correo personal</dt>
                        <dd class="col-sm-8">{{ $usuario->email }}</dd>

                        <dt class="col-sm-4 text-secondary">Roles</dt>
                        <dd class="col-sm-8">
                            @foreach ($usuario->roles as $rol)
                                <span class="badge text-bg-primary">{{ ucfirst($rol->nombre) }}</span>
                            @endforeach
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Lo que si puedes editar</div>
                <div class="card-body">
                    <form action="{{ route('perfil.actualizar') }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        @method('PUT')

                        <div class="col-md-8">
                            <label for="telefono" class="form-label">Telefono</label>
                            <input type="text" name="telefono" id="telefono" required
                                   value="{{ old('telefono', $usuario->telefono) }}"
                                   class="form-control @error('telefono') is-invalid @enderror">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-unexo w-100">Guardar telefono</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span>Contrasena</span>
                    <a href="{{ route('password.cambio') }}" class="btn btn-outline-primary btn-sm">Cambiar contrasena</a>
                </div>
            </div>

        </div>
    </div>

@endsection
