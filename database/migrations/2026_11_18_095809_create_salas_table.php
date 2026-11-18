<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de salas: nombre único y capacidad física.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20)->unique();
            $table->unsignedInteger('capacidad');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE salas ADD CONSTRAINT chk_salas_capacidad CHECK (capacidad > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salas');
    }
};