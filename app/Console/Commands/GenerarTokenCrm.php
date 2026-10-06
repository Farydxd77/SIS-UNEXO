<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('crm:token {email=admin@unexo.test : Administrador al que pertenece el token}')]
#[Description('Genera el token con el que el CRM inscribe por la API (revoca el anterior)')]
class GenerarTokenCrm extends Command
{
    /** Nombre fijo: asi se encuentra y se revoca el token anterior. */
    public const NOMBRE_TOKEN = 'crm';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();

        if (! $usuario || ! $usuario->activo || ! $usuario->esAdministrador()) {
            $this->error('El token debe pertenecer a un administrador activo.');

            return self::FAILURE;
        }

        // Un solo token vigente para el CRM: generar uno nuevo invalida el anterior.
        $usuario->tokens()->where('name', self::NOMBRE_TOKEN)->delete();

        $token = $usuario->createToken(self::NOMBRE_TOKEN, ['catalogo:leer', 'matriculas:crear']);

        $this->info('Token del CRM (se muestra una sola vez, guardalo ahora):');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line('Uso, con el header "Authorization: Bearer <token>":');
        $this->line('  GET  '.url('/api/v1/catalogo').'    lo que se puede vender');
        $this->line('  POST '.url('/api/v1/matriculas').'  inscribir');

        return self::SUCCESS;
    }
}
