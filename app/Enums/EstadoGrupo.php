<?php

namespace App\Enums;

/**
 * Estado de un grupo (Modulo 3). Maquina de estados:
 *
 *   planificado -> en_convocatoria -> habilitado -> en_curso -> finalizado
 *                        \-> cancelado (no llego al cupo minimo)
 */
enum EstadoGrupo: string
{
    case Planificado = 'planificado';
    case EnConvocatoria = 'en_convocatoria';
    case Habilitado = 'habilitado';
    case EnCurso = 'en_curso';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Planificado => 'Planificado',
            self::EnConvocatoria => 'En convocatoria',
            self::Habilitado => 'Habilitado',
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
            self::Habilitado => 'warning',
            self::EnCurso => 'primary',
            self::Finalizado => 'success',
            self::Cancelado => 'danger',
        };
    }

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Planificado => [self::EnConvocatoria, self::Cancelado],
            self::EnConvocatoria => [self::Habilitado, self::Cancelado],
            self::Habilitado => [self::EnCurso, self::Cancelado],
            self::EnCurso => [self::Finalizado, self::Cancelado],
            self::Finalizado, self::Cancelado => [],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }

    /** RN 4.2: solo se puede inscribir en grupos en estos estados. */
    public function admiteInscripciones(): bool
    {
        return in_array($this, [self::EnConvocatoria, self::Habilitado], true);
    }

    /** Estados que cuentan para la validacion de choque de horario (RN 3.4). */
    public static function vigentesParaChoque(): array
    {
        return [
            self::Planificado->value,
            self::EnConvocatoria->value,
            self::Habilitado->value,
            self::EnCurso->value,
        ];
    }
}
