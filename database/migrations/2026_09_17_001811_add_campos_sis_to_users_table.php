<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completa users con los campos del SIS que faltaban.
     * apellidos, documento, telefono, correo_institucional y activo
     * ya se agregaron en la migracion 2026_09_16_190059.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identificadores de sistemas externos. Nullable: no todos los tienen.
            $table->string('google_id')->nullable()->unique()->after('activo');
            $table->string('moodle_user_id')->nullable()->after('google_id');
            $table->string('crm_id')->nullable()->unique()->after('moodle_user_id');

            // RN 1.7: obligar a cambiar la contrasena en el primer ingreso.
            // El esquema SQL no la lista, pero la regla la exige.
            $table->boolean('debe_cambiar_password')->default(true)->after('crm_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropUnique(['crm_id']);
            $table->dropColumn(['google_id', 'moodle_user_id', 'crm_id', 'debe_cambiar_password']);
        });
    }
};
