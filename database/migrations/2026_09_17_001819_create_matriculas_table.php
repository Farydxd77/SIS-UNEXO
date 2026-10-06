<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clase asociativa N:M entre users y grupos, con atributos propios.
     * Es el segundo camino que conecta users con grupos: aqui el usuario
     * es ESTUDIANTE (muchos por grupo); en grupos.docente_id es DOCENTE (uno).
     */
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            // RESTRICT: RNF2, un estudiante con historial academico no se borra.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete();
            $table->string('estado', 20)->default('reservada');
            $table->string('origen', 10)->default('manual');
            $table->string('crm_lead_id', 100)->nullable();
            $table->boolean('acepto_requisitos')->default(false);
            $table->timestamp('fecha_aceptacion')->nullable();
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->string('resultado', 15)->nullable();
            $table->text('motivo_retiro')->nullable();
            $table->timestamps();

            // RN 4.1: un alumno no se inscribe dos veces al mismo grupo.
            $table->unique(['user_id', 'grupo_id'], 'uq_matriculas_user_grupo');
            // RN 4.7: idempotencia. Si el CRM reenvia la misma inscripcion,
            // la base de datos la rechaza. Los NULL no chocan entre si,
            // asi que las matriculas manuales no se ven afectadas.
            $table->unique(['crm_lead_id', 'grupo_id'], 'uq_matriculas_lead_grupo');

            $table->index(['grupo_id', 'estado'], 'idx_matriculas_grupo');
            $table->index('user_id', 'idx_matriculas_user');
        });

        // SQLite (el motor de los tests) no admite ALTER TABLE ADD CONSTRAINT.
        // En PostgreSQL si. Las mismas reglas se validan ademas en los
        // Enums y en los Form Requests, asi que la aplicacion las respeta igual.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE matriculas ADD CONSTRAINT ck_matriculas_estado
            CHECK (estado IN ('reservada', 'activa', 'retirada', 'finalizada', 'anulada'))");
        DB::statement("ALTER TABLE matriculas ADD CONSTRAINT ck_matriculas_origen
            CHECK (origen IN ('api', 'manual'))");
        DB::statement("ALTER TABLE matriculas ADD CONSTRAINT ck_matriculas_resultado
            CHECK (resultado IS NULL OR resultado IN ('aprobado', 'reprobado'))");
        DB::statement('ALTER TABLE matriculas ADD CONSTRAINT ck_matriculas_nota
            CHECK (nota_final IS NULL OR nota_final BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
