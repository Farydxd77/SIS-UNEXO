{{--
    Tarjeta de curso de Moodle 4: imagen, categoria, nombre, menu de
    acciones (los tres puntos) y, si aplica, la barra de progreso.
    El contenido extra va en el slot; las opciones del menu, en el slot "menu".
--}}
@props(['semilla' => 0, 'categoria' => '', 'nombre', 'url' => null, 'progreso' => null, 'textoProgreso' => 'completado', 'menu' => null])

<div {{ $attributes->merge(['class' => 'card curso-tarjeta h-100']) }}>
    @if ($url)
        <a href="{{ $url }}" tabindex="-1" aria-hidden="true"><x-curso-imagen :semilla="$semilla" :alto="120" /></a>
    @else
        <x-curso-imagen :semilla="$semilla" :alto="120" />
    @endif

    <div class="card-body d-flex flex-column pb-2">
        <div class="d-flex align-items-start gap-2">
            <div class="flex-grow-1 min-w-0">
                <div class="curso-categoria text-truncate mb-1">{{ $categoria }}</div>
                @if ($url)
                    <a href="{{ $url }}" class="curso-nombre d-block">{{ $nombre }}</a>
                @else
                    <span class="curso-nombre d-block">{{ $nombre }}</span>
                @endif
            </div>
            @if ($menu && trim($menu->toHtml()) !== '')
                <div class="dropdown">
                    <button class="btn-accion" type="button" data-bs-toggle="dropdown" aria-label="Acciones del curso">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm small">{{ $menu }}</ul>
                </div>
            @endif
        </div>

        @if ($slot->isNotEmpty())
            <div class="small text-secondary mt-2">{{ $slot }}</div>
        @endif
    </div>

    @if ($progreso !== null)
        <div class="px-3 pb-3 mt-auto">
            <div class="progress mb-1" role="progressbar" aria-valuenow="{{ $progreso }}" aria-valuemin="0" aria-valuemax="100" style="height: .35rem">
                <div class="progress-bar" style="width: {{ $progreso }}%; background: var(--unexo)"></div>
            </div>
            <div class="small"><strong>{{ $progreso }}%</strong> {{ $textoProgreso }}</div>
        </div>
    @endif
</div>
