<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entidad padre de CineTickets: funciones de la cartelera.
 * Equivale a la tabla Pelicula_Funcion del modelo de datos (sección 2.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Pelicula_Funcion', function (Blueprint $table) {
            $table->increments('id_funcion');
            $table->string('titulo', 100);
            $table->string('genero', 50);
            $table->integer('duracion_min');
            $table->string('sala', 20);
            $table->dateTime('fecha_hora');
            $table->decimal('precio_unitario', 10, 2);
            $table->integer('capacidad_total');
            $table->integer('asientos_disponibles');
            $table->enum('estado', ['Activa', 'Inactiva'])->default('Activa');
        });

        // Reglas que la base de datos también protege (CA-01.2 y aforo coherente).
        if (DB::getDriverName() !== 'sqlite') {
        DB::statement('ALTER TABLE Pelicula_Funcion ADD CONSTRAINT chk_funcion_duracion CHECK (duracion_min > 0)');
        DB::statement('ALTER TABLE Pelicula_Funcion ADD CONSTRAINT chk_funcion_precio CHECK (precio_unitario > 0)');
        DB::statement('ALTER TABLE Pelicula_Funcion ADD CONSTRAINT chk_funcion_capacidad CHECK (capacidad_total > 0)');
        DB::statement('ALTER TABLE Pelicula_Funcion ADD CONSTRAINT chk_funcion_asientos CHECK (asientos_disponibles >= 0 AND asientos_disponibles <= capacidad_total)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('Pelicula_Funcion');
    }
};
