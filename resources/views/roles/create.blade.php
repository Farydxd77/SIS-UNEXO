@extends('layouts.app')

@section('titulo', 'Nuevo rol')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h4 class="mb-0">Nuevo rol</h4></div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf
                        @include('roles.campos')
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
