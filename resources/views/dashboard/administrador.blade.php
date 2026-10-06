@extends('layouts.app')

@section('titulo', 'Panel del administrador')

@section('content')

    {{-- Cifras del sitio, como las de la portada de moodle.org. --}}
    <div class="row g-4 pb-4 mb-4 border-bottom">
        @foreach ([
            ['bi-people', $totalUsuarios, 'Usuarios activos', "{$totalDocentes} docentes · {$totalEstudiantes} estudiantes"],
            ['bi-journal-bookmark', $totalProgramas, 'Programas', "{$programasAbiertos} abiertos"],
            ['bi-book', $totalModulos, 'Modulos', "{$modulosAbiertos} abiertos"],
            ['bi-person-check', $matriculasVigentes, 'Matriculas vigentes', "en {$totalCohortes} cohorte(s)"],
        ] as [$icono, $valor, $etiqueta, $detalle])
            <div class="col-6 col-lg-3">
                <div class="cifra d-flex align-items-center gap-3">
                    <i class="bi {{ $icono }}"></i>
                    <div>
                        <div class="numero">{{ number_format($valor) }}</div>
                        <div class="etiqueta">{{ $etiqueta }}</div>
                        <div class="small text-secondary">{{ $detalle }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Como la pestana "General" de Moodle: categoria a la izquierda, enlaces a la derecha. --}}
    @foreach ([
        ['Usuarios', 'Cuentas, datos personales y roles del sistema.', [
            ['Ver lista de usuarios', route('usuarios.index')],
            ['Agregar un usuario', route('usuarios.create')],
            ['Definir roles', route('roles.index')],
        ]],
        ['Catalogo', 'Lo que UNEXO ofrece: programas y los modulos que los componen.', [
            ['Gestionar programas', route('programas.index')],
            ['Gestionar modulos', route('modulos.index')],
            ['Ver la oferta publica', route('oferta.index')],
        ]],
        ['Planificacion', 'Ediciones de cada programa y los grupos que se dictan.', [
            ['Cohortes vigentes', route('cohortes.index')],
            ['Crear una cohorte', route('cohortes.create')],
            ['Historial de cohortes', route('cohortes.index', ['vista' => 'historial'])],
            ['Gestionar grupos', route('grupos.index')],
        ]],
        ['Matriculas', 'Inscripciones manuales y las que llegan del CRM por la API.', [
            ['Inscribir un alumno', route('matriculas.create')],
            ['Ver matriculas', route('matriculas.index')],
        ]],
    ] as [$categoria, $descripcion, $enlaces])
        <div class="row py-3 {{ $loop->last ? '' : 'border-bottom' }}">
            <div class="col-md-4 col-lg-3 mb-2 mb-md-0">
                <h5 class="mb-1">{{ $categoria }}</h5>
                <div class="small text-secondary">{{ $descripcion }}</div>
            </div>
            <div class="col-md-8 col-lg-9">
                <div class="row">
                    @foreach ($enlaces as [$texto, $url])
                        <div class="col-sm-6 py-1"><a href="{{ $url }}">{{ $texto }}</a></div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="row g-4 mt-2">
        <div class="col-xl-8">
            <h5 class="mb-3">Grupos en convocatoria</h5>
            <div class="table-responsive mb-4">
                <table class="table table-hover tabla-compacta align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Grupo</th>
                            <th>Docente</th>
                            <th>Fechas</th>
                            <th class="text-center">Cupo</th>
                            <th>Para habilitar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gruposEnConvocatoria as $grupo)
                            <tr>
                                <td><a href="{{ route('grupos.show', $grupo) }}">{{ $grupo->nombre_completo }}</a></td>
                                <td class="small">{{ $grupo->docente?->nombre_completo ?? '--' }}</td>
                                <td class="small text-nowrap">
                                    {{ $grupo->fecha_inicio->format('d/m/y') }} - {{ $grupo->fecha_fin->format('d/m/y') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge text-bg-{{ $grupo->alcanzoCupoMinimo() ? 'success' : 'warning' }}">
                                        {{ $grupo->indicadorCupo() }}
                                    </span>
                                </td>
                                <td class="small">
                                    @if ($grupo->puedeSerHabilitado())
                                        <span class="text-success">Listo para habilitar</span>
                                    @else
                                        <span class="text-secondary">{{ implode('; ', $grupo->faltantesParaHabilitar()) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No hay grupos en convocatoria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <h5 class="mb-0">Cohortes vigentes</h5>
                <a href="{{ route('cohortes.index') }}" class="small">Ver todas</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover tabla-compacta align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cohorte</th>
                            <th>Programa</th>
                            <th>Inicio</th>
                            <th class="text-center">Grupos</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cohortesVigentes as $cohorte)
                            <tr>
                                <td><a href="{{ route('cohortes.show', $cohorte) }}">{{ $cohorte->nombre }}</a></td>
                                <td class="small">{{ $cohorte->programa->nombre }}</td>
                                <td class="small text-nowrap">{{ $cohorte->fecha_inicio->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $cohorte->grupos_count }}</td>
                                <td><span class="badge text-bg-{{ $cohorte->estado->color() }}">{{ $cohorte->estado->etiqueta() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">No hay cohortes vigentes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-xl-4">
            <h5 class="mb-3">Grupos por estado</h5>
            <table class="table tabla-compacta mb-4">
                <tbody>
                    @forelse ($gruposPorEstado as $estado => $total)
                        @php $estadoGrupo = \App\Enums\EstadoGrupo::from($estado); @endphp
                        <tr>
                            <td><span class="badge text-bg-{{ $estadoGrupo->color() }} me-2">&nbsp;</span>{{ $estadoGrupo->etiqueta() }}</td>
                            <td class="text-end fw-semibold">{{ $total }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-secondary">Todavia no hay grupos.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <h5 class="mb-0">Actividad reciente</h5>
                <a href="{{ route('matriculas.index') }}" class="small">Ver todas</a>
            </div>
            <ul class="list-unstyled mb-0">
                @forelse ($ultimasMatriculas as $matricula)
                    <li class="d-flex gap-3 py-3 {{ $loop->last ? '' : 'border-bottom' }}">
                        <span class="avatar flex-shrink-0">
                            {{ mb_strtoupper(mb_substr($matricula->estudiante->name, 0, 1).mb_substr($matricula->estudiante->apellidos ?? '', 0, 1)) }}
                        </span>
                        <div class="small">
                            <div><strong>{{ $matricula->estudiante->nombre_completo }}</strong> se inscribio en {{ $matricula->grupo->modulo->nombre }}</div>
                            <div class="text-secondary">
                                {{ $matricula->created_at->locale('es')->diffForHumans() }} &middot; {{ $matricula->origen->etiqueta() }}
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-secondary py-3">Sin inscripciones todavia.</li>
                @endforelse
            </ul>
        </div>
    </div>

@endsection
