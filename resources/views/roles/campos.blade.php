<div class="mb-3">
    <label for="nombre" class="form-label">Nombre del rol <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('nombre') is-invalid @enderror"
           id="nombre" name="nombre" value="{{ old('nombre', $rol->nombre ?? '') }}" required>
    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">En minusculas y en singular: estudiante, docente, administrador.</div>
</div>

<div class="mb-3">
    <label for="descripcion" class="form-label">
        Descripcion <span class="text-secondary small">(opcional)</span>
    </label>
    <textarea class="form-control @error('descripcion') is-invalid @enderror"
              id="descripcion" name="descripcion" rows="2">{{ old('descripcion', $rol->descripcion ?? '') }}</textarea>
    @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
