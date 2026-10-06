<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Edicion concreta de un PROGRAMA. Ej: "DAE - Version 3, Marzo 2027".
     * Agrupa los grupos de cada uno de sus modulos.
     */
    public function up(): void
    {
        Schema::create('cohortes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas')->restrictOnDelete();
            $table->string('nombre');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('planificado');
            $table->timestamps();

            $table->index('programa_id', 'idx_cohortes_programa');
        });

        // SQLite (el motor de los tests) no admite ALTER TABLE ADD CONSTRAINT.
        // En PostgreSQL si. Las mismas reglas se validan ademas en los
        // Enums y en los Form Requests, asi que la aplicacion las respeta igual.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE cohortes ADD CONSTRAINT ck_cohortes_estado
            CHECK (estado IN ('planificado', 'en_convocatoria', 'en_curso', 'finalizado', 'cancelado'))");
        DB::statement('ALTER TABLE cohortes ADD CONSTRAINT ck_cohortes_fechas
            CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cohortes');
    }
};
