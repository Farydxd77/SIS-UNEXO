@extends('layouts.app')

@section('titulo', 'Inscribir alumno')

@section('content')

    <div class="row justify-content-center">
        <div class="col-xl-8">

            {{-- Paso 1: buscar a la persona por documento (RN 4.4). --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><h5 class="mb-0">1. Buscar persona por documento</h5></div>
                <div class="card-body">
                    <form action="{{ route('matriculas.create') }}" method="GET" class="d-flex gap-2">
                        @if ($grupoPreseleccionado)
                            <input type="hidden" name="grupo" value="{{ $grupoPreseleccionado }}">
                        @endif
                        <input type="text" name="documento" class="form-control" placeholder="Numero de documento" value="{{ $documento }}" required>
                        <button type="submit" class="btn btn-secondary">Buscar</button>
                    </form>
                </div>
            </div>

            @if ($documento !== '')
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0">2. Datos de la inscripcion</h5></div>
                    <div class="card-body">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('matriculas.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="documento" value="{{ $documento }}">

                            @if ($persona)
                                <div class="alert alert-success">
                                    <strong>{{ $persona->nombre_completo }}</strong> ya esta registrado
                                    ({{ $persona->email }}). Se reutiliza su cuenta.
                                    @unless ($persona->esEstudiante())
                                        <div class="small">No tiene rol estudiante: se le asignara al inscribirse.</div>
                                    @endunless
                                    @unless ($persona->activo)
                                        <div class="small text-danger">Esta desactivado: reactivalo antes de inscribirlo.</div>
                                    @endunless
                                </div>
                            @else
                                <div class="alert alert-warning small">
                                    No existe nadie con el documento <strong>{{ $documento }}</strong>. Completa sus datos para crearlo.
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="name">Nombres <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="apellidos">Apellidos <span class="text-danger">*</span></label>
                                        <input type="text" name="apellidos" id="apellidos" class="form-control" value="{{ old('apellidos') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="email">Correo <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="telefono">Telefono <span class="text-danger">*</span></label>
                                        <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono') }}" required>
                                    </div>
                                </div>
                            @endif

                            @php($tipo = old('tipo', 'grupo'))
                            <div class="mb-3">
                                <span class="form-label d-block">Inscribir a</span>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipo-grupo" value="grupo" @checked($tipo === 'grupo')>
                                    <label class="form-check-label" for="tipo-grupo">Un modulo suelto</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo" id="tipo-cohorte" value="cohorte" @checked($tipo === 'cohorte')>
                                    <label class="form-check-label" for="tipo-cohorte">El programa completo (todos los modulos de una cohorte)</label>
                                </div>
                                <div class="form-text">
                                    Programa completo: se inscribe a todos los modulos de la cohorte de una vez;
                                    todos sus grupos deben estar en convocatoria o habilitados.
                                </div>
                            </div>

                            <div class="mb-3" id="bloque-grupo">
                                <label class="form-label" for="grupo_id">Grupo (en convocatoria o habilitado)</label>
                                <select name="grupo_id" id="grupo_id" class="form-select">
                                    <option value="">-- Elige un grupo --</option>
                                    @foreach ($grupos as $grupo)
                                        <option value="{{ $grupo->id }}"
                                                data-requisitos="{{ json_encode([$grupo->modulo->nombre => $grupo->modulo->requisitos]) }}"
                                                @selected(old('grupo_id', $grupoPreseleccionado) == $grupo->id)>
                                            {{ $grupo->nombre_completo }}
                                            &middot; {{ $grupo->fecha_inicio->format('d/m/y') }}
                                            &middot; {{ $grupo->vigentes_count }}/{{ $grupo->cupo_maximo ?? '∞' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3" id="bloque-cohorte">
                                <label class="form-label" for="cohorte_id">Cohorte</label>
                                <select name="cohorte_id" id="cohorte_id" class="form-select">
                                    <option value="">-- Elige una cohorte --</option>
                                    @foreach ($cohortes as $cohorte)
                                        <option value="{{ $cohorte->id }}"
                                                data-requisitos="{{ json_encode($cohorte->grupos->mapWithKeys(fn ($g) => [$g->modulo->nombre => $g->modulo->requisitos])) }}"
                                                @selected(old('cohorte_id') == $cohorte->id)>
                                            {{ $cohorte->nombre }} ({{ $cohorte->grupos->count() }} grupos)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Se crean todas las matriculas o ninguna.</div>
                            </div>

                            {{-- RN 2.9: los requisitos son informativos, nunca bloquean. --}}
                            <div class="card bg-body-tertiary mb-3">
                                <div class="card-body small">
                                    <div class="fw-semibold mb-1">Requisitos</div>
                                    <ul class="mb-0" id="lista-requisitos">
                                        <li class="text-secondary">Elige un grupo o cohorte para ver sus requisitos.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input @error('acepto_requisitos') is-invalid @enderror" type="checkbox"
                                       name="acepto_requisitos" id="acepto_requisitos" value="1" @checked(old('acepto_requisitos'))>
                                <label class="form-check-label" for="acepto_requisitos">
                                    La persona conoce y acepta los requisitos
                                </label>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-unexo">Inscribir</button>
                                <a href="{{ route('matriculas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    (function () {
        const bloqueGrupo = document.getElementById('bloque-grupo');
        if (!bloqueGrupo) return;

        const bloqueCohorte = document.getElementById('bloque-cohorte');
        const selectGrupo = document.getElementById('grupo_id');
        const selectCohorte = document.getElementById('cohorte_id');
        const lista = document.getElementById('lista-requisitos');

        const tipoActual = () => document.querySelector('input[name="tipo"]:checked')?.value ?? 'grupo';

        const pintarRequisitos = () => {
            const select = tipoActual() === 'grupo' ? selectGrupo : selectCohorte;
            const opcion = select.selectedOptions[0];
            lista.innerHTML = '';

            if (!opcion || !opcion.dataset.requisitos) {
                lista.innerHTML = '<li class="text-secondary">Elige un grupo o cohorte para ver sus requisitos.</li>';
                return;
            }

            Object.entries(JSON.parse(opcion.dataset.requisitos)).forEach(([modulo, requisito]) => {
                const li = document.createElement('li');
                li.textContent = modulo + ': ' + (requisito || 'sin requisitos');
                lista.appendChild(li);
            });
        };

        const alternar = () => {
            const esGrupo = tipoActual() === 'grupo';
            bloqueGrupo.hidden = !esGrupo;
            bloqueCohorte.hidden = esGrupo;
            pintarRequisitos();
        };

        document.querySelectorAll('input[name="tipo"]').forEach(r => r.addEventListener('change', alternar));
        selectGrupo.addEventListener('change', pintarRequisitos);
        selectCohorte.addEventListener('change', pintarRequisitos);
        alternar();
    })();
</script>
@endpush
