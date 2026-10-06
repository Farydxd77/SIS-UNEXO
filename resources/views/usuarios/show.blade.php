@extends('layouts.app')

@section('titulo', $usuario->nombre_completo)

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $usuario->nombre_completo }}</h4>
                    @if ($usuario->activo)
                        <span class="badge text-bg-success">Activo</span>
                    @else
                        <span class="badge text-bg-secondary">Inactivo</span>
                    @endif
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary">ID</dt>
                        <dd class="col-sm-8">{{ $usuario->id }}</dd>

                        <dt class="col-sm-4 text-secondary">Documento</dt>
                        <dd class="col-sm-8">{{ $usuario->documento }}</dd>

                        <dt class="col-sm-4 text-secondary">Correo personal</dt>
                        <dd class="col-sm-8">{{ $usuario->email }}</dd>

                        <dt class="col-sm-4 text-secondary">Correo institucional</dt>
                        <dd class="col-sm-8">{{ $usuario->correo_institucional ?? '--' }}</dd>

                        <dt class="col-sm-4 text-secondary">Telefono</dt>
                        <dd class="col-sm-8">{{ $usuario->telefono }}</dd>

                        <dt class="col-sm-4 text-secondary">Roles</dt>
                        <dd class="col-sm-8">
                            @forelse ($usuario->roles as $rol)
                                <span class="badge text-bg-primary">{{ ucfirst($rol->nombre) }}</span>
                            @empty
                                <span class="text-secondary">sin roles asignados</span>
                            @endforelse
                        </dd>

                        <dt class="col-sm-4 text-secondary">Registrado</dt>
                        <dd class="col-sm-8">{{ $usuario->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-primary">Editar</a>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver al listado</a>
                </div>
            </div>
        </div>
    </div>

@endsection
