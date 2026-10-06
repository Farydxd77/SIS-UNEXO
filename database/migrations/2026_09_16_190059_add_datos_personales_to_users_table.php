<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade los datos personales a la tabla users que ya trae Laravel.
     * Fijate en Schema::table (modificar) en vez de Schema::create (crear).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ->after() solo coloca la columna en ese sitio; es cosmetico.
            $table->string('apellidos')->after('name');

            // El documento es string y no numero: puede llevar ceros delante o letras.
            $table->string('documento', 30)->unique()->after('apellidos');

            // El unico opcional. nullable() permite NULL en la base de datos.
            $table->string('correo_institucional')->nullable()->unique()->after('email');

            $table->string('telefono', 30)->after('correo_institucional');

            // boolean en PostgreSQL es un tipo nativo: guarda true/false, PHP lo ve igual.
            $table->boolean('activo')->default(true)->after('password');
        });
    }

    /**
     * Deshace exactamente lo de arriba. Lo que permite php artisan migrate:rollback.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Los indices unique hay que quitarlos antes que la columna.
            $table->dropUnique(['documento']);
            $table->dropUnique(['correo_institucional']);

            $table->dropColumn(['apellidos', 'documento', 'correo_institucional', 'telefono', 'activo']);
        });
    }
};
