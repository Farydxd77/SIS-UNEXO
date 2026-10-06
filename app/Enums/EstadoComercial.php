<?php

namespace App\Enums;

/**
 * Estado comercial de programas y modulos (Modulo 2).
 * Maquina de estados:  borrador -> abierto <-> cerrado
 */
enum EstadoComercial: string
{
    case Borrador = 'borrador';
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
        };
    }

    /** Clase de color Bootstrap para la etiqueta de estado. */
    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'secondary',
            self::Abierto => 'success',
            self::Cerrado => 'dark',
        };
    }

    /**
     * A que estados se puede pasar desde este.
     * RN 2.6: volver a borrador solo si nunca tuvo grupos ni inscritos
     * (eso se valida aparte, aqui solo se declara que la transicion existe).
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Borrador => [self::Abierto],
            self::Abierto => [self::Cerrado, self::Borrador],
            self::Cerrado => [self::Abierto, self::Borrador],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }
}
