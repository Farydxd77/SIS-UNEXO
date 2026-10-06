<div class="row">
    <div class="col-md-3 mb-3">
        <label for="codigo" class="form-label">Codigo <span class="text-danger">*</span></label>
        <input type="text" name="codigo" id="codigo" required
               value="{{ old('codigo', $programa->codigo ?? '') }}"
               class="form-control text-uppercase @error('codigo') is-invalid @enderror">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Ej: DAE-2025</div>
    </div>

    <div class="col-md-9 mb-3">
        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="nombre" id="nombre" required
               value="{{ old('nombre', $programa->nombre ?? '') }}"
               class="form-control @error('nombre') is-invalid @enderror">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="descripcion" class="form-label">Descripcion</label>
    <textarea name="descripcion" id="descripcion" rows="3"
              class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $programa->descripcion ?? '') }}</textarea>
    @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Obligatoria para poder abrir el programa (RN 2.4).</div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="cantidad_modulos" class="form-label">Cantidad de modulos <span class="text-danger">*</span></label>
        <input type="number" name="cantidad_modulos" id="cantidad_modulos" min="1" required
               value="{{ old('cantidad_modulos', $programa->cantidad_modulos ?? '') }}"
               class="form-control @error('cantidad_modulos') is-invalid @enderror">
        @error('cantidad_modulos') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">De cuantos modulos consta. El programa no se abre hasta tenerlos todos.</div>
    </div>

    <div class="col-md-6 mb-3">
        <label for="precio_contado" class="form-label">Precio al contado <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Bs</span>
            <input type="number" name="precio_contado" id="precio_contado" step="0.01" min="0" required
                   value="{{ old('precio_contado', $programa->precio_contado ?? '') }}"
                   class="form-control @error('precio_contado') is-invalid @enderror">
            @error('precio_contado') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
