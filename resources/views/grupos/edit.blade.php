@extends('layouts.app')

@section('titulo', 'Editar grupo')

@section('content')

    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Editar {{ $grupo->nombre_completo }}</h5>
                    <span class="badge text-bg-{{ $grupo->estado->color() }}">{{ $grupo->estado->etiqueta() }}</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('grupos.update', $grupo) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('grupos.campos')
                        @php($volverACohorte = request('volver', old('volver')) === 'cohorte' && $grupo->cohorte_id)
                        @if ($volverACohorte)
                            {{-- Abierto desde la cohorte: al guardar se vuelve a ella. --}}
                            <input type="hidden" name="volver" value="cohorte">
                        @endif
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-unexo">Guardar cambios</button>
                            <a href="{{ $volverACohorte ? route('cohortes.show', $grupo->cohorte_id) : route('grupos.show', $grupo) }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
