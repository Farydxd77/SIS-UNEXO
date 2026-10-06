@extends('layouts.app')

@section('titulo', 'Inicio')

@section('content')

    {{-- Portada al estilo de moodle.org: fondo crema, titulo serif y botones de contorno. --}}
    <section class="hero-unexo rounded-4 p-4 p-lg-5 mb-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="mb-3" style="font-size: clamp(2.2rem, 4vw, 3.4rem); line-height: 1.1">Bienvenido a la formacion continua UNEXO</h1>
                <p class="lead mb-4 text-secondary">
                    Programas y modulos en linea, con clases en vivo por Meet, docentes
                    especializados y seguimiento de tu avance academico.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#programas" class="btn btn-primary btn-lg btn-pastilla">Ver cursos <i class="bi bi-arrow-right ms-1"></i></a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-lg btn-pastilla">Ir a mi panel</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg btn-pastilla">Iniciar sesion</a>
                    @endauth
                </div>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-column gap-4 ps-lg-5">
                    <div class="cifra d-flex align-items-center gap-3">
                        <i class="bi bi-journal-bookmark"></i>
                        <div><div class="numero">{{ $programas->count() }}</div><div class="etiqueta">programas abiertos</div></div>
                    </div>
                    <div class="cifra d-flex align-items-center gap-3">
                        <i class="bi bi-calendar-event"></i>
                        <div><div class="numero">{{ $proximasCohortes->count() }}</div><div class="etiqueta">proximas cohortes</div></div>
                    </div>
                    <div class="cifra d-flex align-items-center gap-3">
                        <i class="bi bi-camera-video"></i>
                        <div><div class="numero">100%</div><div class="etiqueta">clases en vivo por Meet</div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="programas" class="mb-5">
        <h3 class="text-unexo mb-3">Cursos disponibles</h3>
        <div class="row g-3">
            @forelse ($programas as $programa)
                <div class="col-sm-6 col-xl-3">
                    <div class="card curso-tarjeta h-100">
                        <x-curso-imagen :semilla="$programa->id" :alto="120" />
                        <div class="card-body">
                            <div class="curso-categoria mb-1">Programa &middot; {{ $programa->codigo }}</div>
                            <div class="curso-nombre mb-2">{{ $programa->nombre }}</div>
                            <p class="card-text small text-secondary mb-0">{{ Str::limit($programa->descripcion, 140) }}</p>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between small">
                            <span><i class="bi bi-book me-1"></i>{{ $programa->modulos_count }} modulo(s)</span>
                            <span class="fw-semibold">Bs {{ number_format($programa->precio_contado, 2) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-secondary">No hay programas abiertos en este momento.</div>
            @endforelse
        </div>
    </section>

    <section class="mb-4">
        <h3 class="text-unexo mb-3">Proximas cohortes</h3>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table tabla-compacta align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cohorte</th>
                            <th>Programa</th>
                            <th>Inicio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proximasCohortes as $cohorte)
                            <tr>
                                <td class="fw-semibold">{{ $cohorte->nombre }}</td>
                                <td>{{ $cohorte->programa->nombre }}</td>
                                <td class="text-nowrap">{{ $cohorte->fecha_inicio->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $cohorte->estado->color() }}">{{ $cohorte->estado->etiqueta() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">Pronto anunciaremos nuevas cohortes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="small text-secondary mt-2">Para inscribirte, contacta con UNEXO.</p>
    </section>

@endsection
