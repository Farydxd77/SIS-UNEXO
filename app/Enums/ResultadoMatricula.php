<?php

namespace App\Enums;

/**
 * Resultado academico del modulo. Se calcula a partir de la nota final.
 */
enum ResultadoMatricula: string
{
    case Aprobado = 'aprobado';
    case Reprobado = 'reprobado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Aprobado => 'Aprobado',
            self::Reprobado => 'Reprobado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aprobado => 'success',
            self::Reprobado => 'danger',
        };
    }

    /**
     * Nota minima de aprobacion.
     * PENDIENTE de definir con el cliente (seccion 9 de la spec);
     * se deja en 51 y centralizado aqui para cambiarlo en un solo sitio.
     */
    public const NOTA_MINIMA_APROBACION = 51;

    public static function segunNota(float $nota): self
    {
        return $nota >= self::NOTA_MINIMA_APROBACION ? self::Aprobado : self::Reprobado;
    }
}
