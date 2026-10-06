{{--
    Imagen por defecto de un curso, como las de Moodle: un patron geometrico
    (estilo GeoPattern) en tonos apagados, distinto y estable para cada id.
--}}
@props(['semilla' => 0, 'alto' => 110, 'ancho' => null])

@php
    $bases = ['#5b7fa3', '#7d6b98', '#a0705f', '#5f8f7b', '#9a8a5e', '#5f8a99', '#88718a', '#6f8b5c'];
    $base = $bases[$semilla % count($bases)];
    $variante = intdiv($semilla, count($bases)) % 3;
    $tono = fn (float $alfa, bool $claro = true) => ($claro ? 'rgba(255,255,255,' : 'rgba(0,0,0,').$alfa.')';

    // Una celda de 60x60 que se repite: triangulos, rombos o circulos superpuestos.
    $figuras = '';
    for ($fila = 0; $fila < 3; $fila++) {
        for ($col = 0; $col < 3; $col++) {
            $alfa = (($semilla * 7 + $fila * 3 + $col * 5) % 9) / 60 + .02;
            $claro = ($fila + $col + $semilla) % 2 === 0;
            $x = $col * 20;
            $y = $fila * 20;
            $relleno = $tono($alfa, $claro);

            $figuras .= match ($variante) {
                0 => ($fila + $col) % 2 === 0
                    ? "<polygon points='{$x},".($y + 20)." ".($x + 10).",{$y} ".($x + 20).",".($y + 20)."' fill='{$relleno}'/>"
                    : "<polygon points='{$x},{$y} ".($x + 10).",".($y + 20)." ".($x + 20).",{$y}' fill='{$relleno}'/>",
                1 => "<polygon points='".($x + 10).",{$y} ".($x + 20).",".($y + 10)." ".($x + 10).",".($y + 20)." {$x},".($y + 10)."' fill='{$relleno}'/>",
                default => "<circle cx='".($x + 10)."' cy='".($y + 10)."' r='14' fill='{$relleno}'/>",
            };
        }
    }

    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='60' height='60'><rect width='60' height='60' fill='{$base}'/>{$figuras}</svg>";
    $fondo = 'data:image/svg+xml;base64,'.base64_encode($svg);
@endphp

<div {{ $attributes->merge(['class' => 'curso-imagen position-relative']) }}
     style="height: {{ $alto }}px; @if ($ancho) width: {{ $ancho }}px; @endif background-color: {{ $base }}; background-image: url('{{ $fondo }}'); background-size: 60px 60px;">
    {{ $slot }}
</div>
