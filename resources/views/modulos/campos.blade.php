{{-- Campos compartidos por crear y editar un modulo. --}}

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="codigo" class="form-label">Codigo <span class="text-danger">*</span></label>
        <input type="text" name="codigo" id="codigo" required
               value="{{ old('codigo', $modulo->codigo ?? '') }}"
               class="form-control text-uppercase @error('codigo') is-invalid @enderror">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Ej: SQL-01</div>
    </div>

    <div class="col-md-9 mb-3">
        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="nombre" id="nombre" required
               value="{{ old('nombre', $modulo->nombre ?? '') }}"
               class="form-control @error('nombre') is-invalid @enderror">
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="descripcion" class="form-label">Descripcion</label>
    <textarea name="descripcion" id="descripcion" rows="2"
              class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $modulo->descripcion ?? '') }}</textarea>
    @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Obligatoria para poder abrir el modulo (RN 2.4).</div>
</div>

<div class="mb-3">
    <label for="requisitos" class="form-label">Requisitos</label>
    <textarea name="requisitos" id="requisitos" rows="2"
              class="form-control @error('requisitos') is-invalid @enderror">{{ old('requisitos', $modulo->requisitos ?? '') }}</textarea>
    @error('requisitos') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">
        <strong>Informativos.</strong> Se muestran al inscribirse pero nunca bloquean la matricula (RN 2.9).
    </div>
</div>

<div class="mb-3">
    <label for="temario" class="form-label">Temario</label>
    <textarea name="temario" id="temario" rows="6"
              class="form-control font-monospace @error('temario') is-invalid @enderror">{{ old('temario', $modulo->temario ?? '') }}</textarea>
    @error('temario') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Un tema por linea. El sistema no almacena material de clase, solo el temario.</div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="horas" class="form-label">Horas <span class="text-danger">*</span></label>
        <input type="number" name="horas" id="horas" min="1" required
               value="{{ old('horas', $modulo->horas ?? '') }}"
               class="form-control @error('horas') is-invalid @enderror">
        @error('horas') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="precio" class="form-label">Precio <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">Bs</span>
            <input type="number" name="precio" id="precio" step="0.01" min="0" required
                   value="{{ old('precio', $modulo->precio ?? '') }}"
                   class="form-control @error('precio') is-invalid @enderror">
            @error('precio') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label d-block">Venta suelta</label>
        <div class="form-check form-switch mt-2">
            {{-- El hidden garantiza que llegue un valor aunque el check este vacio. --}}
            <input type="hidden" name="se_oferta_por_separado" value="0">
            <input type="checkbox" name="se_oferta_por_separado" value="1" role="switch"
                   id="se_oferta_por_separado" class="form-check-input"
                   @checked(old('se_oferta_por_separado', $modulo->se_oferta_por_separado ?? true))>
            <label class="form-check-label" for="se_oferta_por_separado">
                Se puede tomar sin el programa
            </label>
        </div>
    </div>
</div>
