<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unidad minima de ensenanza del catalogo. Puede estar en varios
     * programas o venderse suelta (se_oferta_por_separado).
     * No guarda material educativo: solo el temario en texto.
     */
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            // RN 2.9: requisitos es INFORMATIVO, se muestra pero nunca bloquea.
            $table->text('requisitos')->nullable();
            $table->text('temario')->nullable();
            $table->integer('horas')->default(0);
            $table->decimal('precio', 10, 2)->default(0);
            $table->boolean('se_oferta_por_separado')->default(true);
            $table->string('estado_comercial', 20)->default('borrador');
            $table->timestamps();

            $table->index('estado_comercial', 'idx_modulos_estado');
        });

        // SQLite (el motor de los tests) no admite ALTER TABLE ADD CONSTRAINT.
        // En PostgreSQL si. Las mismas reglas se validan ademas en los
        // Enums y en los Form Requests, asi que la aplicacion las respeta igual.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE modulos ADD CONSTRAINT ck_modulos_estado
            CHECK (estado_comercial IN ('borrador', 'abierto', 'cerrado'))");
        DB::statement('ALTER TABLE modulos ADD CONSTRAINT ck_modulos_horas CHECK (horas >= 0)');
        DB::statement('ALTER TABLE modulos ADD CONSTRAINT ck_modulos_precio CHECK (precio >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
