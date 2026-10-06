<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clase asociativa N:M entre programas y modulos.
     * 'orden' es la secuencia del modulo DENTRO de ese programa:
     * el mismo modulo puede ser 3ro en un programa y 1ro en otro.
     *
     * Esta tabla SI tiene id propio (asi lo pide el esquema), a diferencia
     * de rol_user que usa clave compuesta.
     */
    public function up(): void
    {
        Schema::create('programa_modulo', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete = ON DELETE RESTRICT: no se borra un programa
            // ni un modulo que esten en uso (RNF2).
            $table->foreignId('programa_id')->constrained('programas')->restrictOnDelete();
            $table->foreignId('modulo_id')->constrained('modulos')->restrictOnDelete();
            $table->integer('orden');
            $table->timestamps();

            // RN 2.2: un modulo no se repite dentro del mismo programa.
            $table->unique(['programa_id', 'modulo_id'], 'uq_pm_programa_modulo');
            // RN 2.3: dos modulos no ocupan la misma posicion en el mismo programa.
            $table->unique(['programa_id', 'orden'], 'uq_pm_programa_orden');

            $table->index(['programa_id', 'orden'], 'idx_pm_programa');
        });

        // SQLite (el motor de los tests) no admite ALTER TABLE ADD CONSTRAINT.
        // En PostgreSQL si. Las mismas reglas se validan ademas en los
        // Enums y en los Form Requests, asi que la aplicacion las respeta igual.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE programa_modulo ADD CONSTRAINT ck_pm_orden CHECK (orden > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_modulo');
    }
};
