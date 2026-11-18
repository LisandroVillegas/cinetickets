<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase 2, paso C: hace obligatorios pelicula_id y sala_id en Pelicula_Funcion.
 * Solo corre si la conversión (paso B) dejó TODAS las funciones vinculadas.
 * No borra ninguna columna: los campos antiguos se conservan como historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite (pruebas) no permite este ALTER TABLE; en MySQL/MariaDB sí.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $sinVincular = DB::table('Pelicula_Funcion')
            ->whereNull('pelicula_id')
            ->orWhereNull('sala_id')
            ->count();

        if ($sinVincular > 0) {
            throw new RuntimeException(
                "Hay {$sinVincular} funciones sin película o sala. Ejecuta primero la conversión de datos y revísala."
            );
        }

        // MODIFY cambia solo si acepta vacío; el tipo y las llaves foráneas se conservan.
        DB::statement('ALTER TABLE Pelicula_Funcion MODIFY pelicula_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE Pelicula_Funcion MODIFY sala_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE Pelicula_Funcion MODIFY pelicula_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE Pelicula_Funcion MODIFY sala_id BIGINT UNSIGNED NULL');
    }
};