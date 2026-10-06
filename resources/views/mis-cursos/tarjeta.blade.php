{{-- Tarjeta de un curso del usuario (alumno o docente), como en Moodle. --}}
@php
    $grupo = $curso['grupo'];
    $matricula = $curso['matricula'];
    // GrupoPolicy: el alumno solo abre el curso con matricula vigente; el docente, siempre el suyo.
    $url = $matricula === null || $matricula->esVigente() ? route('grupos.show', $grupo) : null;
    $meet = $grupo->enlace_meet && ($matricula === null || $matricula->estado === \App\Enums\EstadoMatricula::Activa);
@endphp

<x-curso-tarjeta :semilla="$grupo->modulo_id" :categoria="$curso['categoria']" :nombre="$grupo->modulo->nombre"
                 :url="$url" :progreso="$curso['progreso']">
    @if ($matricula === null)
        <span class="badge text-bg-light border me-1">Docente</span>
    @elseif ($matricula->resultado)
        <span class="badge text-bg-{{ $matricula->resultado->color() }} me-1">{{ $matricula->resultado->etiqueta() }} &middot; {{ $matricula->nota_final }}</span>
    @endif
    {{ $grupo->fecha_inicio->format('d/m/y') }} - {{ $grupo->fecha_fin->format('d/m/y') }}

    <x-slot:menu>
        @if ($url)
            <li><a class="dropdown-item" href="{{ $url }}"><i class="bi bi-box-arrow-in-right me-2"></i>Ir al curso</a></li>
        @endif
        @if ($meet && $curso['momento'] !== 'pasados')
            <li><a class="dropdown-item" href="{{ $grupo->enlace_meet }}" target="_blank" rel="noopener"><i class="bi bi-camera-video me-2"></i>Entrar a clase (Meet)</a></li>
        @endif
        @if ($matricula === null)
            <li><a class="dropdown-item" href="{{ route('grupos.notas.edit', $grupo) }}"><i class="bi bi-pencil-square me-2"></i>Calificar</a></li>
        @endif
    </x-slot:menu>
</x-curso-tarjeta>
