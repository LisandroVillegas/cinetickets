<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de películas: cada película se registra una sola vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peliculas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 100);
            $table->string('genero', 50);
            $table->unsignedInteger('duracion_min');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        // La base de datos también protege la regla (duración mayor que cero).
        // SQLite, usado en las pruebas, no soporta este ALTER TABLE.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE peliculas ADD CONSTRAINT chk_peliculas_duracion CHECK (duracion_min > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('peliculas');
    }
};