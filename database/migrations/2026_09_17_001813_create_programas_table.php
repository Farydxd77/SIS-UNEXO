<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrupador jerarquico del catalogo. Ej: "Data Analysis Expert" con 6 modulos.
     * NO tiene fechas ni docente: eso vive en cohortes y grupos.
     */
    public function up(): void
    {
        Schema::create('programas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('cantidad_modulos');
            $table->decimal('precio_contado', 10, 2)->default(0);
            $table->string('estado_comercial', 20)->default('borrador');
            $table->timestamps();

            $table->index('estado_comercial', 'idx_programas_estado');
        });

        // Los CHECK del esquema SQL, creados directamente en PostgreSQL.
        // SQLite (el motor de los tests) no admite ALTER TABLE ADD CONSTRAINT.
        // En PostgreSQL si. Las mismas reglas se validan ademas en los
        // Enums y en los Form Requests, asi que la aplicacion las respeta igual.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE programas ADD CONSTRAINT ck_programas_estado
            CHECK (estado_comercial IN ('borrador', 'abierto', 'cerrado'))");
        DB::statement('ALTER TABLE programas ADD CONSTRAINT ck_programas_cantidad
            CHECK (cantidad_modulos > 0)');
        DB::statement('ALTER TABLE programas ADD CONSTRAINT ck_programas_precio
            CHECK (precio_contado >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('programas');
    }
};
