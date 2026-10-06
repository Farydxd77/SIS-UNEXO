<?php

namespace App\Enums;

/**
 * Dias de la semana segun el esquema: 1=lunes ... 7=domingo (ISO-8601).
 */
enum DiaSemana: int
{
    case Lunes = 1;
    case Martes = 2;
    case Miercoles = 3;
    case Jueves = 4;
    case Viernes = 5;
    case Sabado = 6;
    case Domingo = 7;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Lunes => 'Lunes',
            self::Martes => 'Martes',
            self::Miercoles => 'Miercoles',
            self::Jueves => 'Jueves',
            self::Viernes => 'Viernes',
            self::Sabado => 'Sabado',
            self::Domingo => 'Domingo',
        };
    }

    public function abreviatura(): string
    {
        return match ($this) {
            self::Lunes => 'Lun',
            self::Martes => 'Mar',
            self::Miercoles => 'Mie',
            self::Jueves => 'Jue',
            self::Viernes => 'Vie',
            self::Sabado => 'Sab',
            self::Domingo => 'Dom',
        };
    }

    /**
     * Plural para los mensajes de error. En espanol los dias que terminan
     * en -s no cambian ("los lunes"); solo sabado y domingo pluralizan.
     */
    public function plural(): string
    {
        return match ($this) {
            self::Lunes => 'lunes',
            self::Martes => 'martes',
            self::Miercoles => 'miercoles',
            self::Jueves => 'jueves',
            self::Viernes => 'viernes',
            self::Sabado => 'sabados',
            self::Domingo => 'domingos',
        };
    }
}
