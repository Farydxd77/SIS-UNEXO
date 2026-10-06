<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote del muchos-a-muchos entre users y roles.
     * No tiene id propio: su clave primaria son las dos columnas juntas.
     */
    public function up(): void
    {
        Schema::create('rol_usuario', function (Blueprint $table) {
            // foreignId + constrained = crea la columna Y la clave foranea.
            // cascadeOnDelete: si borras el usuario, sus filas de aqui se van con el.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();

            // Clave primaria compuesta: impide que el mismo usuario
            // tenga el mismo rol dos veces.
            $table->primary(['user_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_usuario');
    }
};
