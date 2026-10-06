@extends('layouts.app')

@section('titulo', 'Nuevo programa')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Nuevo programa</h5></div>
                <div class="card-body">
                    <form action="{{ route('programas.store') }}" method="POST">
                        @csrf
                        @include('programas.campos')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Guardar y asignar modulos</button>
                            <a href="{{ route('programas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
