@extends('layouts.app')

@section('titulo', 'Oferta academica')

@section('content')

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h2 class="text-unexo mb-1">Oferta academica</h2>
            <p class="text-secondary mb-0">Programas completos y modulos sueltos con inscripciones abiertas. Para inscribirte, contacta con UNEXO.</p>
        </div>
        <div class="input-group" style="max-width: 320px">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" id="buscar-curso" class="form-control" placeholder="Buscar curso...">
        </div>
    </div>

    {{-- Filtro como el de "Mis cursos" de Moodle. --}}
    <ul class="nav nav-pills mb-4 gap-1" id="filtro-oferta">
        <li class="nav-item"><button type="button" class="nav-link active" data-filtro="todo">Todo <span class="badge text-bg-light border ms-1">{{ $programas->count() + $modulos->count() }}</span></button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-filtro="programa"><i class="bi bi-mortarboard me-1"></i>Programas <span class="badge text-bg-light border ms-1">{{ $programas->count() }}</span></button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-filtro="modulo"><i class="bi bi-puzzle me-1"></i>Modulos sueltos <span class="badge text-bg-light border ms-1">{{ $modulos->count() }}</span></button></li>
    </ul>

    <section class="seccion-oferta mb-5" data-tipo="programa">
        <h5 class="mb-3"><i class="bi bi-mortarboard me-2"></i>Programas completos</h5>
        <div class="row g-4">
            @forelse ($programas as $programa)
                @php $proxima = $programa->cohortes->first(); @endphp
                <div class="col-sm-6 col-lg-4 col-xxl-3 tarjeta-oferta" data-nombre="{{ Str::lower($programa->nombre.' '.$programa->codigo.' '.$programa->modulos->pluck('nombre')->join(' ')) }}">
                    <div class="card curso-tarjeta h-100">
                        <x-curso-imagen :semilla="$programa->id" :alto="120" />
                        <div class="card-body d-flex flex-column">
                            <div class="curso-categoria mb-1">Programa &middot; {{ $programa->modulos->count() }} modulos &middot; {{ $programa->modulos->sum('horas') }} h</div>
                            <h6 class="curso-nombre fs-6 mb-2">{{ $programa->nombre }}</h6>
                            <p class="small text-secondary mb-3">{{ Str::limit($programa->descripcion, 120) }}</p>

                            <button class="btn btn-sm btn-outline-secondary mb-2 text-start d-flex justify-content-between align-items-center" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#modulos-{{ $programa->id }}">
                                <span><i class="bi bi-list-ol me-1"></i>Ver modulos</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="collapse" id="modulos-{{ $programa->id }}">
                                <ol class="small ps-3 mb-2">
                                    @foreach ($programa->modulos as $modulo)
                                        <li class="mb-1">{{ $modulo->nombre }} <span class="text-secondary">({{ $modulo->horas }} h)</span></li>
                                    @endforeach
                                </ol>
                            </div>

                            <div class="small mt-auto pt-2">
                                @if ($proxima)
                                    <i class="bi bi-calendar-event text-success me-1"></i>Proxima cohorte: <strong>{{ $proxima->fecha_inicio->format('d/m/Y') }}</strong>
                                @else
                                    <i class="bi bi-calendar-x text-secondary me-1"></i><span class="text-secondary">Proximamente nuevas fechas</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                            <span class="small text-secondary">Al contado</span>
                            <span class="fw-bold text-unexo">Bs {{ number_format($programa->precio_contado, 2) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-secondary">No hay programas abiertos en este momento.</div>
            @endforelse
        </div>
    </section>

    <section class="seccion-oferta mb-4" data-tipo="modulo">
        <h5 class="mb-3"><i class="bi bi-puzzle me-2"></i>Modulos sueltos</h5>
        <div class="row g-4">
            @forelse ($modulos as $modulo)
                @php $proximo = $modulo->grupos->first(); @endphp
                <div class="col-sm-6 col-lg-4 col-xxl-3 tarjeta-oferta" data-nombre="{{ Str::lower($modulo->nombre.' '.$modulo->codigo) }}">
                    <div class="card curso-tarjeta h-100">
                        <x-curso-imagen :semilla="$modulo->id" :alto="120" />
                        <div class="card-body d-flex flex-column">
                            <div class="curso-categoria mb-1">Modulo &middot; {{ $modulo->codigo }} &middot; {{ $modulo->horas }} h</div>
                            <h6 class="curso-nombre fs-6 mb-2">{{ $modulo->nombre }}</h6>
                            @if ($proximo)
                                <div class="small text-success mb-2"><i class="bi bi-circle-fill me-1" style="font-size: .5rem; vertical-align: middle"></i>Inscripciones abiertas</div>
                            @endif
                            <div class="small text-secondary mb-3">
                                <i class="bi bi-check2-circle me-1"></i>Requisitos: {{ $modulo->requisitos ?: 'Ninguno' }}
                            </div>

                            <div class="small mt-auto">
                                @forelse ($modulo->grupos->take(2) as $grupo)
                                    <div class="d-flex gap-2 mb-1">
                                        <i class="bi bi-calendar-event text-success"></i>
                                        <span>
                                            Desde <strong>{{ $grupo->fecha_inicio->format('d/m/Y') }}</strong>
                                            <span class="d-block text-secondary">{{ $grupo->resumenHorario() ?: 'Horario por definir' }}</span>
                                        </span>
                                    </div>
                                @empty
                                    <i class="bi bi-calendar-x text-secondary me-1"></i><span class="text-secondary">Sin convocatoria abierta</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                            <span class="small text-secondary">Precio</span>
                            <span class="fw-bold text-unexo">Bs {{ number_format($modulo->precio, 2) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-secondary">No hay modulos abiertos.</div>
            @endforelse
        </div>
    </section>

    <p class="text-center text-secondary d-none py-5" id="sin-resultados">
        <i class="bi bi-search fs-2 d-block mb-2"></i>No hay cursos que coincidan con tu busqueda.
    </p>

@endsection

@push('scripts')
<script>
    // Filtro por tipo y busqueda por nombre, sin recargar la pagina.
    (function () {
        let filtro = 'todo';
        const buscador = document.getElementById('buscar-curso');

        function aplicar() {
            const texto = buscador.value.trim().toLowerCase();
            let visibles = 0;

            document.querySelectorAll('.seccion-oferta').forEach(function (seccion) {
                const tipoVisible = filtro === 'todo' || seccion.dataset.tipo === filtro;
                let enSeccion = 0;

                seccion.querySelectorAll('.tarjeta-oferta').forEach(function (tarjeta) {
                    const coincide = tipoVisible && tarjeta.dataset.nombre.includes(texto);
                    tarjeta.classList.toggle('d-none', !coincide);
                    if (coincide) enSeccion++;
                });

                seccion.classList.toggle('d-none', !tipoVisible || (texto !== '' && enSeccion === 0));
                visibles += enSeccion;
            });

            document.getElementById('sin-resultados').classList.toggle('d-none', visibles > 0 || texto === '');
        }

        document.querySelectorAll('#filtro-oferta [data-filtro]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                document.querySelectorAll('#filtro-oferta .nav-link').forEach(b => b.classList.remove('active'));
                boton.classList.add('active');
                filtro = boton.dataset.filtro;
                aplicar();
            });
        });

        buscador.addEventListener('input', aplicar);
    })();
</script>
@endpush
