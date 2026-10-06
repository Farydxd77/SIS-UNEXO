@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Usuarios</h2>
            <small class="text-secondary">{{ $usuarios->total() }} usuario(s)</small>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo usuario</a>
    </div>

    <form action="{{ route('usuarios.index') }}" method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Nombre, apellidos, documento o correo..." value="{{ $buscar }}">
            </div>
            <div class="col-md-3">
                <select name="rol" class="form-select">
                    <option value="">-- Todos los roles --</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id }}" @selected($rolId == $rol->id)>
                            {{ ucfirst($rol->nombre) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">-- Todos --</option>
                    <option value="1" @selected($estado === '1')>Activos</option>
                    <option value="0" @selected($estado === '0')>Inactivos</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-secondary flex-grow-1" type="submit">Filtrar</button>
                @if ($buscar || $rolId || $estado !== null)
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-danger">X</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Nombre completo</th>
                        <th>Documento</th>
                        <th>Correo</th>
                        <th>Telefono</th>
                        <th>Roles</th>
                        <th>Estado</th>
                        <th class="text-end" style="width:120px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr class="{{ $usuario->activo ? '' : 'table-secondary' }}">
                            <td class="text-secondary">{{ $usuario->id }}</td>
                            <td>
                                <a href="{{ route('usuarios.show', $usuario) }}" class="d-inline-flex align-items-center gap-2">
                                    <span class="avatar" style="width: 30px; height: 30px; font-size: .7rem">{{ mb_strtoupper(mb_substr($usuario->name, 0, 1).mb_substr($usuario->apellidos ?? '', 0, 1)) }}</span>
                                    {{ $usuario->nombre_completo }}
                                </a>
                            </td>
                            <td>{{ $usuario->documento }}</td>
                            <td class="small">
                                {{ $usuario->email }}
                                @if ($usuario->correo_institucional)
                                    <br><span class="text-secondary">{{ $usuario->correo_institucional }}</span>
                                @endif
                            </td>
                            <td>{{ $usuario->telefono }}</td>
                            <td>
                                @forelse ($usuario->roles as $rol)
                                    <span class="badge text-bg-light border">{{ ucfirst($rol->nombre) }}</span>
                                @empty
                                    <span class="text-secondary small">--</span>
                                @endforelse
                            </td>
                            <td>
                                @if ($usuario->activo)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="acciones">
                                    <a href="{{ route('usuarios.show', $usuario) }}" class="btn-accion" title="Ver perfil"><i class="bi bi-eye"></i><span class="visually-hidden">Ver</span></a>
                                    <a href="{{ route('usuarios.edit', $usuario) }}" class="btn-accion" title="Editar"><i class="bi bi-gear"></i><span class="visually-hidden">Editar</span></a>

                                    @if ($usuario->activo)
                                        <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Desactivar a {{ $usuario->nombre_completo }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-accion peligro" title="Desactivar"><i class="bi bi-person-slash"></i><span class="visually-hidden">Desactivar</span></button>
                                        </form>
                                    @else
                                        <form action="{{ route('usuarios.activar', $usuario) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-accion exito" title="Activar"><i class="bi bi-person-check"></i><span class="visually-hidden">Activar</span></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">
                                No hay usuarios que coincidan con el filtro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($usuarios->hasPages())
        <div class="mt-3">{{ $usuarios->links() }}</div>
    @endif

@endsection
