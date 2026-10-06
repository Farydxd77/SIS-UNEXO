{{-- Botones de cambio de estado de una matricula (solo admin). --}}
@php($estado = $matricula->estado)

<div class="d-flex flex-wrap gap-1 justify-content-end">
    @if ($estado->puedePasarA(\App\Enums\EstadoMatricula::Activa))
        <form action="{{ route('matriculas.activar', $matricula) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline-success" title="Pago confirmado">Activar</button>
        </form>
    @endif

    @if ($estado->puedePasarA(\App\Enums\EstadoMatricula::Anulada))
        <form action="{{ route('matriculas.anular', $matricula) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline-danger">Anular</button>
        </form>
    @endif

    @if ($estado->puedePasarA(\App\Enums\EstadoMatricula::Retirada))
        {{-- RN 4.9: el retiro exige un motivo. --}}
        <form action="{{ route('matriculas.retirar', $matricula) }}" method="POST" class="d-flex gap-1">
            @csrf @method('PATCH')
            <input type="text" name="motivo_retiro" class="form-control form-control-sm" placeholder="Motivo del retiro" required minlength="5" style="max-width: 11rem">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Retirar</button>
        </form>
    @endif

    {{-- RN 4.12: reubicar al alumno de un grupo cancelado. --}}
    @if ($estado === \App\Enums\EstadoMatricula::Anulada
        && $matricula->grupo->estado === \App\Enums\EstadoGrupo::Cancelado
        && ($alternativas ?? collect())->isNotEmpty())
        <form action="{{ route('matriculas.reubicar', $matricula) }}" method="POST" class="d-flex gap-1">
            @csrf
            <select name="grupo_id" class="form-select form-select-sm" required style="max-width: 11rem">
                @foreach ($alternativas as $alternativa)
                    <option value="{{ $alternativa->id }}">{{ $alternativa->nombre_completo }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Reubicar</button>
        </form>
    @endif
</div>
