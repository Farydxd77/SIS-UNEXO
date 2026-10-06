@extends('layouts.app')

@section('titulo', 'Editar modulo')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Editar: {{ $modulo->codigo }} - {{ $modulo->nombre }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('modulos.update', $modulo) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('modulos.campos')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Actualizar</button>
                            <a href="{{ route('modulos.show', $modulo) }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
