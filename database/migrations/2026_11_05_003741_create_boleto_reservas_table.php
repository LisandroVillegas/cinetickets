<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entidad hija de CineTickets: boletos o reservas de una función.
 * Equivale a la tabla Boleto_Reserva del modelo de datos (sección 2.2).
 * Debe ejecutarse DESPUÉS de la migración de Pelicula_Funcion (por la llave foránea).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Boleto_Reserva', function (Blueprint $table) {
            $table->increments('id_boleto');
            $table->unsignedInteger('id_funcion');
            $table->string('nombre_cliente', 100);
            $table->string('email_cliente', 100);
            $table->integer('cantidad_entradas');
            $table->decimal('monto_total', 10, 2);
            $table->timestamp('fecha_compra')->useCurrent();
            $table->enum('estado', ['Confirmado', 'Cancelado'])->default('Confirmado');

            // Decisión 6: no se puede borrar una función que tenga boletos.
            $table->foreign('id_funcion')
                ->references('id_funcion')
                ->on('Pelicula_Funcion')
                ->restrictOnDelete();

            $table->index('email_cliente');
        });
        if (DB::getDriverName() !== 'sqlite') {
        DB::statement('ALTER TABLE Boleto_Reserva ADD CONSTRAINT chk_boleto_cantidad CHECK (cantidad_entradas > 0)');
        DB::statement('ALTER TABLE Boleto_Reserva ADD CONSTRAINT chk_boleto_monto CHECK (monto_total >= 0)');
         }
    }

    public function down(): void
    {
        Schema::dropIfExists('Boleto_Reserva');
    }
};
