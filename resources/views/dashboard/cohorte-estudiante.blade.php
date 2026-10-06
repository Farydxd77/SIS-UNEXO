{{-- Una cohorte vista por el estudiante: sus modulos en orden y su avance. --}}
@php
    $aprobados = $cohorte->grupos->filter(
        fn ($g) => $matriculaPorGrupo->get($g->id)?->resultado === \App\Enums\ResultadoMatricula::Aprobado
    )->count();
    $total = $cohorte->grupos->count();
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-semibold">{{ $cohorte->programa->nombre }}</div>
            <div class="small text-secondary">
                {{ $cohorte->nombre }} &middot; desde {{ $cohorte->fecha_inicio->format('d/m/Y') }}
            </div>
        </div>
        <span class="badge text-bg-{{ $cohorte->estado->color() }}">{{ $cohorte->estado->etiqueta() }}</span>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between small mb-1">
            <span>Avance del programa</span>
            <span class="fw-semibold">{{ $aprobados }} de {{ $total }} modulo(s) aprobado(s)</span>
        </div>
        <div class="progress mb-4" role="progressbar" style="height: .6rem">
            <div class="progress-bar bg-success" style="width: {{ $total ? round($aprobados * 100 / $total) : 0 }}%"></div>
        </div>

        <ul class="list-unstyled linea-tiempo mb-0">
            @foreach ($cohorte->grupos as $grupo)
                @php
                    $matricula = $matriculaPorGrupo->get($grupo->id);
                    $paso = match (true) {
                        $matricula?->resultado === \App\Enums\ResultadoMatricula::Aprobado => 'paso-aprobado',
                        $matricula?->resultado === \App\Enums\ResultadoMatricula::Reprobado => 'paso-reprobado',
                        (bool) $matricula?->esVigente() => 'paso-actual',
                        default => 'paso-pendiente',
                    };
                @endphp
                <li class="{{ $paso }} pb-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            {{-- GrupoPolicy solo deja ver el detalle con matricula vigente. --}}
                            @if ($matricula?->esVigente())
                                <a href="{{ route('grupos.show', $grupo) }}" class="fw-semibold text-decoration-none">{{ $grupo->modulo->nombre }}</a>
                            @else
                                <span class="fw-semibold">{{ $grupo->modulo->nombre }}</span>
                            @endif
                            <div class="small text-secondary">
                                {{ $grupo->fecha_inicio->format('d/m/y') }} - {{ $grupo->fecha_fin->format('d/m/y') }}
                                @if ($grupo->resumenHorario())
                                    &middot; {{ $grupo->resumenHorario() }}
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            @if ($matricula?->resultado)
                                <span class="fw-semibold me-1">{{ $matricula->nota_final }}</span>
                                <span class="badge text-bg-{{ $matricula->resultado->color() }}">{{ $matricula->resultado->etiqueta() }}</span>
                            @elseif ($matricula)
                                <span class="badge text-bg-{{ $matricula->estado->color() }}">{{ $matricula->estado->etiqueta() }}</span>
                                {{-- RN 3.9: el enlace solo con matricula ACTIVA. --}}
                                @if ($matricula->estado === \App\Enums\EstadoMatricula::Activa && $grupo->enlace_meet)
                                    <a href="{{ $grupo->enlace_meet }}" target="_blank" rel="noopener" class="btn btn-sm btn-unexo ms-1">Entrar</a>
                                @endif
                            @else
                                <span class="badge text-bg-light border">Sin inscribir</span>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
