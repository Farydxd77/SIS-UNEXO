@extends('layouts.app')

@section('titulo', 'Nuevo grupo')

@section('content')

    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Nuevo grupo</h5></div>
                <div class="card-body">
                    <form action="{{ route('grupos.store') }}" method="POST">
                        @csrf
                        @include('grupos.campos')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Crear grupo</button>
                            <a href="{{ route('grupos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
