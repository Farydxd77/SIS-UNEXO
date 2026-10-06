@extends('layouts.app')

@section('titulo', 'Editar programa')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Editar: {{ $programa->codigo }} - {{ $programa->nombre }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('programas.update', $programa) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('programas.campos')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Actualizar</button>
                            <a href="{{ route('programas.show', $programa) }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
