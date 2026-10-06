@extends('layouts.app')

@section('titulo', 'Iniciar sesion')

@section('content')

    {{-- Pantalla de acceso al estilo Moodle: tarjeta centrada con la marca arriba. --}}
    <div class="row justify-content-center py-lg-4">
        <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <div class="card p-3 p-md-4">
                <div class="text-center mb-4">
                    <span class="logo-unexo"><i class="bi bi-mortarboard-fill birrete"></i><span class="marca" style="font-size: 2.2rem">unexo</span></span>
                    <h1 class="mt-3 mb-1" style="font-size: 2rem">Iniciar sesion</h1>
                    <div class="small text-secondary">SIS UNEXO &middot; Formacion continua</div>
                </div>

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label visually-hidden">Correo</label>
                        <input type="email"
                               class="form-control form-control-lg @error('email') is-invalid @enderror"
                               id="email" name="email" placeholder="Correo"
                               value="{{ old('email') }}"
                               required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label visually-hidden">Contraseña</label>
                        <input type="password"
                               class="form-control form-control-lg @error('password') is-invalid @enderror"
                               id="password" name="password" placeholder="Contraseña"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="recordarme" name="recordarme" value="1">
                        <label class="form-check-label" for="recordarme">Recordarme</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Acceder</button>
                </form>

                <hr class="my-4">
                <p class="small text-secondary text-center mb-0">
                    ¿Olvidaste tu contraseña? Pide al administrador que la restablezca.
                </p>
            </div>
        </div>
    </div>

@endsection
