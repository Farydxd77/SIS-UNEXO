<?php

namespace App\Enums;

/**
 * De donde vino la inscripcion: del CRM por API, o cargada a mano por el admin.
 */
enum OrigenMatricula: string
{
    case Api = 'api';
    case Manual = 'manual';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Api => 'API (CRM)',
            self::Manual => 'Manual',
        };
    }

    /**
     * Por ahora solo se inscribe a quien ya pago, venga del CRM o lo cargue
     * el admin: la matricula nace activa y ve el Meet desde el primer
     * momento. Si algun dia se cobra despues de inscribir, aqui se devuelve
     * Reservada y el admin la activa al confirmar el pago.
     */
    public function estadoInicial(): EstadoMatricula
    {
        return EstadoMatricula::Activa;
    }
}
