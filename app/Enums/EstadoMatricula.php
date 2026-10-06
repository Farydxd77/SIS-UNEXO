<?php

namespace App\Enums;

/**
 * Estado de una matricula (Modulo 4).
 *
 *   reservada -> activa -> finalizada
 *       |          |
 *    anulada    retirada
 */
enum EstadoMatricula: string
{
    case Reservada = 'reservada';
    case Activa = 'activa';
    case Retirada = 'retirada';
    case Finalizada = 'finalizada';
    case Anulada = 'anulada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Reservada => 'Reservada',
            self::Activa => 'Activa',
            self::Retirada => 'Retirada',
            self::Finalizada => 'Finalizada',
            self::Anulada => 'Anulada',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Reservada => 'Inscrito, pendiente de pago. Cuenta para el cupo minimo.',
            self::Activa => 'Confirmado. Ve el enlace de Meet.',
            self::Retirada => 'Abandono despues de empezar. Se conserva el registro.',
            self::Finalizada => 'Termino el modulo, con nota y resultado.',
            self::Anulada => 'Nunca se concreto, o su grupo se cancelo.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reservada => 'warning',
            self::Activa => 'success',
            self::Retirada => 'secondary',
            self::Finalizada => 'primary',
            self::Anulada => 'danger',
        };
    }

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Reservada => [self::Activa, self::Anulada],
            self::Activa => [self::Finalizada, self::Retirada, self::Anulada],
            self::Retirada, self::Finalizada, self::Anulada => [],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }

    /** Los estados que cuentan para el cupo minimo de 20. */
    public static function vigentes(): array
    {
        return [self::Reservada->value, self::Activa->value];
    }
}
