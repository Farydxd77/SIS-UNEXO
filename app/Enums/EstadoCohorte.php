<?php

namespace App\Enums;

/**
 * Estado de una cohorte (edicion de un programa).
 */
enum EstadoCohorte: string
{
    case Planificado = 'planificado';
    case EnConvocatoria = 'en_convocatoria';
    case EnCurso = 'en_curso';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Planificado => 'Planificado',
            self::EnConvocatoria => 'En convocatoria',
            self::EnCurso => 'En curso',
            self::Finalizado => 'Finalizado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planificado => 'secondary',
            self::EnConvocatoria => 'info',
            self::EnCurso => 'primary',
            self::Finalizado => 'success',
            self::Cancelado => 'danger',
        };
    }

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Planificado => [self::EnConvocatoria, self::Cancelado],
            self::EnConvocatoria => [self::EnCurso, self::Cancelado],
            self::EnCurso => [self::Finalizado, self::Cancelado],
            self::Finalizado, self::Cancelado => [],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }

    /** Estados cerrados: la cohorte ya no cambia y pasa al historial. */
    public static function historicos(): array
    {
        return [self::Finalizado, self::Cancelado];
    }

    public function esHistorico(): bool
    {
        return in_array($this, self::historicos(), true);
    }
}
