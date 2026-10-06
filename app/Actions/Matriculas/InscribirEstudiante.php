<?php

namespace App\Actions\Matriculas;

use App\Enums\OrigenMatricula;
use App\Exceptions\ReglaDeNegocioException;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inscripcion de una persona a uno o varios grupos (Modulo 4).
 *
 * - Grupo suelto: se pasa una coleccion con un solo grupo.
 * - Cohorte completa: se pasan todos sus grupos. RN 4.6: o se crean
 *   todas las matriculas o ninguna, nunca un alumno a medio programa.
 *
 * Queda preparada para la API del CRM (seccion 9): basta con llamarla con
 * origen = api y el crm_lead_id. La matricula nace activa: por ahora solo
 * se inscribe a quien ya pago (OrigenMatricula::estadoInicial).
 */
class InscribirEstudiante
{
    /**
     * @param  array  $persona  documento, y si es nueva: name, apellidos, email, telefono
     * @param  Collection<int, Grupo>  $grupos
     * @return array{usuario: User, matriculas: Collection, password_temporal: ?string}
     */
    public function ejecutar(
        array $persona,
        Collection $grupos,
        OrigenMatricula $origen = OrigenMatricula::Manual,
        ?string $crmLeadId = null,
    ): array {
        if ($grupos->isEmpty()) {
            throw new ReglaDeNegocioException('No hay grupos en los que inscribir.');
        }

        // RN 4.7: el mismo lead del CRM no se procesa dos veces.
        if ($crmLeadId !== null && Matricula::where('crm_lead_id', $crmLeadId)->exists()) {
            throw new ReglaDeNegocioException("El lead {$crmLeadId} del CRM ya fue procesado.");
        }

        return DB::transaction(function () use ($persona, $grupos, $origen, $crmLeadId) {
            [$usuario, $passwordTemporal] = $this->obtenerOCrearPersona($persona);

            // RN 4.5: si no tenia rol estudiante, se le asigna al matricularse.
            $rolEstudiante = Rol::where('nombre', User::ROL_ESTUDIANTE)->firstOrFail();
            $usuario->roles()->syncWithoutDetaching([$rolEstudiante->id]);

            $problemas = [];
            $matriculas = collect();

            foreach ($grupos as $grupo) {
                // Se relee con bloqueo para que dos inscripciones simultaneas
                // no superen juntas el cupo maximo.
                $grupo = Grupo::with('modulo')->lockForUpdate()->findOrFail($grupo->id);

                if ($error = $this->motivoRechazo($usuario, $grupo)) {
                    $problemas[] = $error;

                    continue;
                }

                $matriculas->push(Matricula::create([
                    'user_id' => $usuario->id,
                    'grupo_id' => $grupo->id,
                    'estado' => $origen->estadoInicial(),
                    'origen' => $origen,
                    'crm_lead_id' => $crmLeadId,
                    'acepto_requisitos' => true,
                    'fecha_aceptacion' => now(),
                ]));
            }

            // Al lanzar la excepcion dentro de la transaccion se deshace TODO,
            // incluida la persona si se acababa de crear.
            if ($problemas !== []) {
                throw new ReglaDeNegocioException(
                    'No se realizo la inscripcion. '.implode(' ', $problemas)
                );
            }

            return [
                'usuario' => $usuario,
                'matriculas' => $matriculas,
                'password_temporal' => $passwordTemporal,
            ];
        });
    }

    /**
     * RN 4.4: la persona se identifica por documento; si ya existe, se reutiliza.
     *
     * @return array{0: User, 1: ?string}
     */
    private function obtenerOCrearPersona(array $persona): array
    {
        $usuario = User::where('documento', $persona['documento'])->first();

        if ($usuario) {
            if (! $usuario->activo) {
                throw new ReglaDeNegocioException(
                    "{$usuario->nombre_completo} esta desactivado. Reactivalo antes de inscribirlo."
                );
            }

            return [$usuario, null];
        }

        // Persona nueva: contrasena temporal que debera cambiar al entrar (RN 1.7).
        // En modo prueba (CRM_PASSWORD_PRUEBA en el .env) todos reciben esa
        // misma contrasena y entran sin el cambio obligatorio.
        $passwordPrueba = config('services.crm.password_prueba');
        $temporal = $passwordPrueba ?: Str::password(10, symbols: false);

        $usuario = User::create([
            'name' => $persona['name'],
            'apellidos' => $persona['apellidos'],
            'documento' => $persona['documento'],
            'email' => $persona['email'],
            'telefono' => $persona['telefono'] ?? null,
            'password' => $temporal,
            'activo' => true,
            'debe_cambiar_password' => ! $passwordPrueba,
        ]);

        return [$usuario, $temporal];
    }

    /** Devuelve por que no se puede inscribir en el grupo, o null si se puede. */
    private function motivoRechazo(User $usuario, Grupo $grupo): ?string
    {
        $nombre = $grupo->nombre_completo;

        // RN 4.2
        if (! $grupo->estado->admiteInscripciones()) {
            return "{$nombre} esta {$grupo->estado->etiqueta()}: solo se inscribe en grupos en convocatoria o habilitados.";
        }

        // RN 4.1
        if ($grupo->matriculas()->where('user_id', $usuario->id)->exists()) {
            return "{$usuario->nombre_completo} ya esta matriculado en {$nombre}.";
        }

        // RN 4.3
        if (! $grupo->tieneEspacio()) {
            return "{$nombre} ya alcanzo su cupo maximo de {$grupo->cupo_maximo}.";
        }

        return null;
    }
}
