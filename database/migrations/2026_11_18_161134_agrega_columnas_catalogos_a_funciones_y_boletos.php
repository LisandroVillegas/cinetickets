<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2, paso A: agrega las columnas del rediseño sin modificar ningún dato.
 * Todas aceptan vacío (nullable); la conversión de datos viene en el paso B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Pelicula_Funcion', function (Blueprint $table) {
            // Las tablas peliculas y salas usan id() = BIGINT sin signo.
            $table->unsignedBigInteger('pelicula_id')->nullable()->after('id_funcion');
            $table->unsignedBigInteger('sala_id')->nullable()->after('pelicula_id');
        });

        Schema::table('Boleto_Reserva', function (Blueprint $table) {
            // Precio histórico: los boletos antiguos quedan en null (no se inventa).
            $table->decimal('precio_unitario', 10, 2)->nullable()->after('cantidad_entradas');
            // Clave contra el doble envío de una venta.
            $table->uuid('clave_operacion')->nullable()->unique()->after('monto_total');
            // Datos de la anulación (los boletos ya anulados quedan en null).
            $table->dateTime('anulada_en')->nullable();
            $table->string('motivo_anulacion', 255)->nullable();
        });

        // Llaves foráneas: SQLite (pruebas) no las agrega sobre tablas existentes.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('Pelicula_Funcion', function (Blueprint $table) {
                $table->foreign('pelicula_id')->references('id')->on('peliculas')->restrictOnDelete();
                $table->foreign('sala_id')->references('id')->on('salas')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('Pelicula_Funcion', function (Blueprint $table) {
                $table->dropForeign(['pelicula_id']);
                $table->dropForeign(['sala_id']);
            });
        }

        Schema::table('Boleto_Reserva', function (Blueprint $table) {
            $table->dropUnique(['clave_operacion']);
            $table->dropColumn(['precio_unitario', 'clave_operacion', 'anulada_en', 'motivo_anulacion']);
        });

        Schema::table('Pelicula_Funcion', function (Blueprint $table) {
            $table->dropColumn(['pelicula_id', 'sala_id']);
        });
    }
};