@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Editar: {{ $usuario->nombre_completo }}</h4>
                </div>
                <div class="card-body">

                    <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @include('usuarios.campos')

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
