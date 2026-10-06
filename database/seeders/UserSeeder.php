<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Un usuario por rol, contrasena 'password' (seccion 6 de la spec),
 * mas alumnos y docentes extra para poder demostrar el sistema.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Rol::where('nombre', User::ROL_ADMINISTRADOR)->firstOrFail();
        $docente = Rol::where('nombre', User::ROL_DOCENTE)->firstOrFail();
        $estudiante = Rol::where('nombre', User::ROL_ESTUDIANTE)->firstOrFail();

        $usuarios = [
            [
                'datos' => [
                    'name' => 'Carlos',
                    'apellidos' => 'Mendoza Rojas',
                    'documento' => '10000001',
                    'email' => 'admin@unexo.test',
                    'correo_institucional' => 'c.mendoza@unexo.edu.bo',
                    'telefono' => '77700001',
                    'password' => 'password',
                    'activo' => true,
                    // El admin de demo no necesita cambiar la clave al entrar.
                    'debe_cambiar_password' => false,
                ],
                'roles' => [$admin->id],
            ],
            [
                'datos' => [
                    'name' => 'Lucia',
                    'apellidos' => 'Fernandez Paz',
                    'documento' => '10000002',
                    'email' => 'docente@unexo.test',
                    'correo_institucional' => 'l.fernandez@unexo.edu.bo',
                    'telefono' => '77700002',
                    'password' => 'password',
                    'activo' => true,
                    'debe_cambiar_password' => false,
                ],
                'roles' => [$docente->id],
            ],
            [
                'datos' => [
                    'name' => 'Miguel',
                    'apellidos' => 'Quispe Aliaga',
                    'documento' => '10000003',
                    'email' => 'estudiante@unexo.test',
                    'correo_institucional' => null,
                    'telefono' => '77700003',
                    'password' => 'password',
                    'activo' => true,
                    'debe_cambiar_password' => false,
                ],
                'roles' => [$estudiante->id],
            ],
            [
                // Caso de prueba: un usuario con DOS roles a la vez.
                'datos' => [
                    'name' => 'Ana',
                    'apellidos' => 'Rivera Soto',
                    'documento' => '10000004',
                    'email' => 'mixto@unexo.test',
                    'correo_institucional' => 'a.rivera@unexo.edu.bo',
                    'telefono' => '77700004',
                    'password' => 'password',
                    'activo' => true,
                    'debe_cambiar_password' => false,
                ],
                'roles' => [$docente->id, $estudiante->id],
            ],
            [
                // Caso de prueba: inactivo, no debe poder iniciar sesion (RN 1.2).
                'datos' => [
                    'name' => 'Rosa',
                    'apellidos' => 'Villalba Nina',
                    'documento' => '10000005',
                    'email' => 'inactivo@unexo.test',
                    'correo_institucional' => null,
                    'telefono' => '77700005',
                    'password' => 'password',
                    'activo' => false,
                    'debe_cambiar_password' => false,
                ],
                'roles' => [$estudiante->id],
            ],
            [
                // Segundo docente, para probar el choque de horarios.
                'datos' => [
                    'name' => 'Jorge',
                    'apellidos' => 'Salazar Vaca',
                    'documento' => '10000006',
                    'email' => 'docente2@unexo.test',
                    'correo_institucional' => 'j.salazar@unexo.edu.bo',
                    'telefono' => '77700006',
                    'password' => 'password',
                    'activo' => true,
                    'debe_cambiar_password' => false,
                ],
                'roles' => [$docente->id],
            ],
        ];

        foreach ($usuarios as $u) {
            $usuario = User::firstOrCreate(
                ['email' => $u['datos']['email']],
                $u['datos'],
            );

            $usuario->roles()->sync($u['roles']);
        }

        // 24 estudiantes mas: suficientes para superar el cupo minimo de 20
        // en un grupo y demostrar la habilitacion.
        User::factory()->count(24)->create()->each(
            fn (User $user) => $user->roles()->sync([$estudiante->id]),
        );
    }
}
