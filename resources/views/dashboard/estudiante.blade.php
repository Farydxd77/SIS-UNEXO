@extends('layouts.app')

@section('titulo', 'Area personal')

@section('content')

    <h2 class="text-unexo mb-1">Area personal</h2>
    <p class="text-secondary mb-4">
        Mi avance academico &middot; {{ $matriculas->count() }} matricula(s) &middot; {{ $aprobadas }} modulo(s) aprobado(s)
    </p>

    <div class="row g-4">
        <div class="col-xl-8">

            {{-- Como "Cursos accedidos recientemente" de Moodle. --}}
            <section class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-3">
                        <h5 class="mb-0">Mis cursos actuales</h5>
                        <a href="{{ route('mis-cursos.index') }}" class="small">Ver todos mis cursos</a>
                    </div>
                    <div class="row g-3">
                        @forelse ($enCurso->take(6) as $matricula)
                            @php
                                $grupo = $matricula->grupo;
                                $dias = max(1, $grupo->fecha_inicio->diffInDays($grupo->fecha_fin));
                                $transcurrido = (int) round(min(100, max(0, $grupo->fecha_inicio->diffInDays(now(), false) * 100 / $dias)));
                                $meet = $matricula->estado === \App\Enums\EstadoMatricula::Activa && $grupo->enlace_meet;
                            @endphp
                            <div class="col-sm-6 col-lg-4">
                                <x-curso-tarjeta :semilla="$grupo->modulo_id"
                                                 :categoria="$idsProgramas->contains($grupo->cohorte_id) ? 'Programa completo' : 'Modulo suelto'"
                                                 :nombre="$grupo->modulo->nombre" :url="route('grupos.show', $grupo)" :progreso="$transcurrido">
                                    <i class="bi bi-person me-1"></i>{{ $grupo->docente?->nombre_completo ?? 'Docente por asignar' }}
                                    <x-slot:menu>
                                        <li><a class="dropdown-item" href="{{ route('grupos.show', $grupo) }}"><i class="bi bi-box-arrow-in-right me-2"></i>Ir al curso</a></li>
                                        @if ($meet)
                                            <li><a class="dropdown-item" href="{{ $grupo->enlace_meet }}" target="_blank" rel="noopener"><i class="bi bi-camera-video me-2"></i>Entrar a clase (Meet)</a></li>
                                        @endif
                                    </x-slot:menu>
                                </x-curso-tarjeta>
                            </div>
                        @empty
                            <div class="col-12 text-center text-secondary py-4">
                                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>No tienes modulos en curso.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- Bloque "Linea de tiempo" de Moodle: las proximas clases en vivo. --}}
            <section class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Linea de tiempo</h5>
                    @forelse ($proximasClases->groupBy(fn ($s) => $s['fecha']->toDateString()) as $dia => $sesiones)
                        <div class="small fw-bold text-secondary text-uppercase mt-3 mb-2" style="letter-spacing: .04em">
                            {{ \Illuminate\Support\Carbon::parse($dia)->locale('es')->translatedFormat('l, j \\d\\e F') }}
                        </div>
                        @foreach ($sesiones as $sesion)
                            @php $grupo = $sesion['matricula']->grupo; @endphp
                            <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                                <span class="text-secondary small text-nowrap" style="width: 90px">{{ $sesion['horario']->horaInicioCorta() }} - {{ $sesion['horario']->horaFinCorta() }}</span>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-2 flex-shrink-0" style="width: 34px; height: 34px; background: var(--crema); color: var(--naranja-oscuro)">
                                    <i class="bi bi-camera-video"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">Clase en vivo</div>
                                    <div class="small text-secondary text-truncate">{{ $grupo->modulo->nombre }}</div>
                                </div>
                                @if ($sesion['matricula']->estado === \App\Enums\EstadoMatricula::Activa && $grupo->enlace_meet)
                                    <a href="{{ $grupo->enlace_meet }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Entrar</a>
                                @endif
                            </div>
                        @endforeach
                    @empty
                        <div class="text-center text-secondary py-4">
                            <i class="bi bi-calendar2-x fs-2 d-block mb-2"></i>No hay clases en los proximos 14 dias.
                        </div>
                    @endforelse
                </div>
            </section>

            @if ($cohortesActuales->isNotEmpty())
                <h5 class="mt-2 mb-3">Mis programas</h5>
                @foreach ($cohortesActuales as $cohorte)
                    @include('dashboard.cohorte-estudiante')
                @endforeach
            @endif

            @if ($modulosSueltos->isNotEmpty())
                <h5 class="mt-4 mb-3">Mis modulos sueltos</h5>
                <div class="card">
                    <ul class="list-group list-group-flush">
                        @foreach ($modulosSueltos as $matricula)
                            @php $grupo = $matricula->grupo; @endphp
                            <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <x-curso-imagen :semilla="$grupo->modulo_id" :alto="42" :ancho="42" class="rounded-2 flex-shrink-0" />
                                    <div>
                                        <div class="fw-semibold">{{ $grupo->modulo->nombre }}</div>
                                        <div class="small text-secondary">
                                            {{ $grupo->modulo->codigo }} &middot; Version {{ $grupo->version }} &middot;
                                            {{ $grupo->fecha_inicio->format('d/m/y') }} - {{ $grupo->fecha_fin->format('d/m/y') }}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    @if ($matricula->resultado)
                                        <span class="fw-semibold me-1">{{ $matricula->nota_final }}</span>
                                        <span class="badge text-bg-{{ $matricula->resultado->color() }}">{{ $matricula->resultado->etiqueta() }}</span>
                                    @else
                                        <span class="badge text-bg-{{ $matricula->estado->color() }}">{{ $matricula->estado->etiqueta() }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($cohortesAnteriores->isNotEmpty())
                <h5 class="mt-4 mb-3">Programas anteriores</h5>
                @foreach ($cohortesAnteriores as $cohorte)
                    @include('dashboard.cohorte-estudiante')
                @endforeach
            @endif

            <h5 class="mt-4 mb-3">Calificaciones</h5>
            <div class="table-responsive">
                <table class="table tabla-compacta align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Modulo</th>
                            <th class="text-end">Nota</th>
                            <th>Resultado</th>
                            <th>Finalizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($finalizadas as $matricula)
                            <tr>
                                <td>{{ $matricula->grupo->nombre_completo }}</td>
                                <td class="text-end fw-semibold">{{ $matricula->nota_final ?? '--' }}</td>
                                <td>
                                    @if ($matricula->resultado)
                                        <span class="badge text-bg-{{ $matricula->resultado->color() }}">{{ $matricula->resultado->etiqueta() }}</span>
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="small">{{ $matricula->updated_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary py-4">Todavia no hay modulos finalizados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Columna de bloques, como el cajon derecho de Moodle. --}}
        <aside class="col-xl-4">
            @php
                $hoy = now();
                $inicioMes = $hoy->copy()->startOfMonth();
                $huecos = $inicioMes->dayOfWeekIso - 1;
            @endphp
            <section class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Calendario</h5>
                    <div class="text-center fw-semibold mb-2 text-capitalize">{{ $hoy->copy()->locale('es')->translatedFormat('F Y') }}</div>
                    <table class="table table-borderless text-center small mb-2 calendario">
                        <thead>
                            <tr>
                                @foreach (['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'] as $dia)
                                    <th class="text-secondary fw-semibold p-1">{{ $dia }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_chunk(array_merge(array_fill(0, $huecos, null), range(1, $hoy->daysInMonth)), 7) as $semana)
                                <tr>
                                    @foreach (array_pad($semana, 7, null) as $numero)
                                        @php $fecha = $numero ? $inicioMes->copy()->day($numero)->toDateString() : null; @endphp
                                        <td class="p-1">
                                            @if ($numero)
                                                <span class="dia {{ $diasConClase->contains($fecha) ? 'con-clase' : '' }} {{ $numero === $hoy->day ? 'hoy' : '' }}"
                                                      @if ($diasConClase->contains($fecha)) title="Clase en vivo" @endif>{{ $numero }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="small text-secondary"><span class="dia con-clase d-inline-flex me-1" style="width: 18px; height: 18px"></span>Dia con clase en vivo</div>
                </div>
            </section>

            <section class="card">
                <div class="card-body">
                    <h5 class="mb-3">Mi resumen</h5>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Cursos actuales</span><strong>{{ $enCurso->count() }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Modulos aprobados</span><strong>{{ $aprobadas }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span>Clases en los proximos 14 dias</span><strong>{{ $proximasClases->count() }}</strong></div>
                </div>
            </section>
        </aside>
    </div>

@endsection
