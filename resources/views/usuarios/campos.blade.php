{{--
    Campos compartidos por crear y editar.
    $usuario puede no existir (en crear); el ?? lo cubre.
--}}

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Nombres <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror"
               id="name" name="name" value="{{ old('name', $usuario->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('apellidos') is-invalid @enderror"
               id="apellidos" name="apellidos" value="{{ old('apellidos', $usuario->apellidos ?? '') }}" required>
        @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="documento" class="form-label">Documento / Carnet <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('documento') is-invalid @enderror"
               id="documento" name="documento" value="{{ old('documento', $usuario->documento ?? '') }}" required>
        @error('documento') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="telefono" class="form-label">Telefono <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('telefono') is-invalid @enderror"
               id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono ?? '') }}" required>
        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">Correo personal <span class="text-danger">*</span></label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email', $usuario->email ?? '') }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="correo_institucional" class="form-label">
            Correo institucional <span class="text-secondary small">(opcional)</span>
        </label>
        <input type="email" class="form-control @error('correo_institucional') is-invalid @enderror"
               id="correo_institucional" name="correo_institucional"
               value="{{ old('correo_institucional', $usuario->correo_institucional ?? '') }}">
        @error('correo_institucional') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="password" class="form-label">
            Contraseña
            @if (isset($usuario))
                <span class="text-secondary small">(dejala vacia para no cambiarla)</span>
            @else
                <span class="text-danger">*</span>
            @endif
        </label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               id="password" name="password" {{ isset($usuario) ? '' : 'required' }}>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="password_confirmation" class="form-label">Repetir contraseña</label>
        <input type="password" class="form-control"
               id="password_confirmation" name="password_confirmation" {{ isset($usuario) ? '' : 'required' }}>
    </div>
</div>

<hr>

<div class="mb-3">
    <label class="form-label">Roles</label>
    <div class="border rounded p-3">
        @forelse ($roles as $rol)
            {{--
                name="roles[]" con corchetes = llega al servidor como array.
                El value es el id, que es lo que sync() necesita.
            --}}
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       id="rol_{{ $rol->id }}"
                       name="roles[]"
                       value="{{ $rol->id }}"
                       @checked(in_array($rol->id, old('roles', $rolesDelUsuario ?? [])))>
                <label class="form-check-label" for="rol_{{ $rol->id }}">
                    {{ ucfirst($rol->nombre) }}
                    @if ($rol->descripcion)
                        <span class="text-secondary small">- {{ $rol->descripcion }}</span>
                    @endif
                </label>
            </div>
        @empty
            <p class="text-secondary mb-0">
                No hay roles creados. <a href="{{ route('roles.create') }}">Crea el primero</a>.
            </p>
        @endforelse
    </div>
    @error('roles') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
</div>

<div class="form-check form-switch mb-3">
    {{--
        El hidden de value=0 es un truco necesario: un checkbox sin marcar
        NO se envia. El hidden garantiza que siempre llegue algo.
        Si el checkbox esta marcado, su valor 1 gana por ir despues.
    --}}
    <input type="hidden" name="activo" value="0">
    <input type="checkbox" class="form-check-input" role="switch"
           id="activo" name="activo" value="1"
           @checked(old('activo', $usuario->activo ?? true))>
    <label class="form-check-label" for="activo">Cuenta activa (puede iniciar sesion)</label>
</div>
