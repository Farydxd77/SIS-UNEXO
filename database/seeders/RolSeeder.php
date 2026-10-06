<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    /**
     * Los 3 roles del sistema. Son FILAS de la tabla roles.
     */
    public function run(): void
    {
        $roles = [
            ['nombre' => 'estudiante', 'descripcion' => 'Alumno matriculado en programas.'],
            ['nombre' => 'docente', 'descripcion' => 'Imparte modulos y califica alumnos.'],
            ['nombre' => 'administrador', 'descripcion' => 'Acceso total al sistema.'],
        ];

        foreach ($roles as $rol) {
            // firstOrCreate: si ya existe no lo duplica, asi el seeder
            // se puede relanzar sin miedo.
            Rol::firstOrCreate(['nombre' => $rol['nombre']], $rol);
        }
    }
}
