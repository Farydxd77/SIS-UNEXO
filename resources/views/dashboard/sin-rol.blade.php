@extends('layouts.app')

@section('titulo', 'Sin rol asignado')

@section('content')

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <h4 class="text-unexo">Tu cuenta no tiene rol asignado</h4>
                    <p class="text-secondary mb-0">
                        Contacta con el administrador para que te asigne un rol.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
