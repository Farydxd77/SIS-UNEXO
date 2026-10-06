@extends('layouts.app')

@section('titulo', 'Editar rol')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h4 class="mb-0">Editar: {{ $rol->nombre }}</h4></div>
                <div class="card-body">
                    <form action="{{ route('roles.update', $rol) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('roles.campos')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
