<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El esquema del SIS llama a la tabla puente 'rol_user'.
     * Renombramos en lugar de crear y borrar: asi no se pierden
     * las asignaciones de rol que ya existen.
     */
    public function up(): void
    {
        if (Schema::hasTable('rol_usuario') && ! Schema::hasTable('rol_user')) {
            Schema::rename('rol_usuario', 'rol_user');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rol_user') && ! Schema::hasTable('rol_usuario')) {
            Schema::rename('rol_user', 'rol_usuario');
        }
    }
};
