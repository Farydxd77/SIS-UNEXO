<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dias y horas de clase de un grupo. Un grupo puede tener varias filas
     * (ej: lunes 19-21 y miercoles 19-21).
     * Sin esta tabla NO se puede validar el choque de horarios del docente (RN 3.4).
     */
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            // CASCADE, no RESTRICT: un horario no existe sin su grupo.
            // Es una de las dos excepciones del esquema.
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->smallInteger('dia_semana');   // 1=lunes ... 7=domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->index('grupo_id', 'idx_horarios_grupo');
        });

        // SQLite (el motor de los tests) no admite ALTER TABLE ADD CONSTRAINT.
        // En PostgreSQL si. Las mismas reglas se validan ademas en los
        // Enums y en los Form Requests, asi que la aplicacion las respeta igual.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE horarios ADD CONSTRAINT ck_horarios_dia
            CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE horarios ADD CONSTRAINT ck_horarios_horas
            CHECK (hora_fin > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
