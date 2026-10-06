@extends('layouts.app')

@section('titulo', 'Nuevo modulo')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Nuevo modulo</h5></div>
                <div class="card-body">
                    <form action="{{ route('modulos.store') }}" method="POST">
                        @csrf
                        @include('modulos.campos')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Guardar</button>
                            <a href="{{ route('modulos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                        <div class="form-text mt-2">Nace en estado borrador; podras abrirlo desde su detalle.</div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
