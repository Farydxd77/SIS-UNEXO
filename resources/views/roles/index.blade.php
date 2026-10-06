@extends('layouts.app')

@section('titulo', 'Roles')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Roles</h2>
            <small class="text-secondary">{{ $roles->total() }} rol(es) en el sistema</small>
        </div>
        <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo rol</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Nombre</th>
                        <th>Descripcion</th>
                        <th class="text-center">Usuarios</th>
                        <th class="text-end" style="width:180px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $rol)
                        <tr>
                            <td class="text-secondary">{{ $rol->id }}</td>
                            <td class="fw-semibold">{{ ucfirst($rol->nombre) }}</td>
                            <td class="text-secondary">{{ $rol->descripcion ?? '--' }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-light border">{{ $rol->usuarios_count }}</span>
                            </td>
                            <td class="text-end">
                                <div class="acciones">
                                    <a href="{{ route('roles.edit', $rol) }}" class="btn-accion" title="Editar"><i class="bi bi-gear"></i><span class="visually-hidden">Editar</span></a>

                                    {{-- Si el rol lo tiene alguien, el boton se desactiva --}}
                                    @if ($rol->usuarios_count > 0)
                                        <span class="btn-accion opacity-25" title="Tiene {{ $rol->usuarios_count }} usuario(s) asignados"><i class="bi bi-trash"></i></span>
                                    @else
                                        <form action="{{ route('roles.destroy', $rol) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Eliminar el rol {{ $rol->nombre }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-accion peligro" title="Eliminar"><i class="bi bi-trash"></i><span class="visually-hidden">Eliminar</span></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">No hay roles. Crea el primero.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($roles->hasPages())
        <div class="mt-3">{{ $roles->links() }}</div>
    @endif

@endsection
