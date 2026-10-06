<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Edicion concreta de un MODULO: cuando se dicta, con que docente y donde.
     * Aqui SI hay fechas, docente y enlace de Meet.
     *
     * cohorte_id NULL  -> grupo de modulo suelto
     * cohorte_id lleno -> grupo que forma parte de una cohorte de programa
     */
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulo_id')->constrained('modulos')->restrictOnDelete();
            $table->foreignId('cohorte_id')->nullable()->constrained('cohortes')->restrictOnDelete();
            // El docente es un user con rol docente (RN 3.3). Nullable porque
            // al generarse la cohorte los grupos nacen sin docente asignado.
            $table->foreignId('docente_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->integer('version');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('cupo_minimo')->default(20);
            $table->integer('cupo_maximo')->nullable();
            $table->string('enlace_meet')->nullable();
            $table->string('estado', 20)->default('planificado');
            $table->timestamps();

            // RN 3.5: no existen dos "Version 4" del mismo modulo.
            $table->unique(['modulo_id', 'version'], 'uq_grupos_modulo_version');

            $table->index('modulo_id', 'idx_grupos_modulo');
            $table->index('cohorte_id', 'idx_grupos_cohorte');
            $table->index('docente_id', 'idx_grupos_docente');
            $table->index('estado', 'idx_grupos_estado');
        });

        // SQLite (el motor de los tests) no admite ALTER TABLE ADD CONSTRAINT.
        // En PostgreSQL si. Las mismas reglas se validan ademas en los
        // Enums y en los Form Requests, asi que la aplicacion las respeta igual.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE grupos ADD CONSTRAINT ck_grupos_estado
            CHECK (estado IN ('planificado', 'en_convocatoria', 'habilitado',
                              'en_curso', 'finalizado', 'cancelado'))");
        DB::statement('ALTER TABLE grupos ADD CONSTRAINT ck_grupos_fechas
            CHECK (fecha_fin >= fecha_inicio)');
        DB::statement('ALTER TABLE grupos ADD CONSTRAINT ck_grupos_cupos
            CHECK (cupo_minimo > 0 AND (cupo_maximo IS NULL OR cupo_maximo >= cupo_minimo))');
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
