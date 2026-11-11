<?php

namespace App\Services;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lógica de negocio de la venta de boletos (RF-02).
 * Está separada del controlador para poder probarla sin pasar por el navegador.
 */
class BoletoService
{
    /** CA-02.4: monto_total = cantidad_entradas × precio_unitario. */
    public function calcularMonto(int $cantidad, float|string $precioUnitario): float
    {
        return round($cantidad * (float) $precioUnitario, 2);
    }

    /**
     * Registra la venta de boletos y descuenta los asientos (CA-02.1 a CA-02.5).
     * Todo ocurre dentro de una transacción: o se guarda todo, o no se guarda nada.
     *
     * @throws ValidationException si la función no está disponible o la cantidad no es válida.
     */
    public function registrarVenta(
        int $idFuncion,
        string $nombreCliente,
        string $emailCliente,
        int $cantidad
    ): BoletoReserva {
        return DB::transaction(function () use ($idFuncion, $nombreCliente, $emailCliente, $cantidad) {
            // lockForUpdate bloquea la fila de la función hasta terminar la transacción:
            // si dos personas compran a la vez, la segunda espera y ve el cupo ya descontado.
            $funcion = PeliculaFuncion::whereKey($idFuncion)->lockForUpdate()->first();

            // CA-02.1: solo funciones activas, con asientos y que no hayan ocurrido.
            if ($funcion === null
                || $funcion->estado !== 'Activa'
                || $funcion->asientos_disponibles <= 0
                || $funcion->fecha_hora->isPast()) {
                throw ValidationException::withMessages([
                    'id_funcion' => 'La función no está disponible para la venta.',
                ]);
            }

            // CA-02.2: entero positivo.
            if ($cantidad <= 0) {
                throw ValidationException::withMessages([
                    'cantidad_entradas' => 'La cantidad de entradas debe ser mayor que cero.',
                ]);
            }

            // CA-02.3: no superar el cupo, e informar el máximo disponible.
            if ($cantidad > $funcion->asientos_disponibles) {
                throw ValidationException::withMessages([
                    'cantidad_entradas' => "Solo quedan {$funcion->asientos_disponibles} asientos disponibles para esta función.",
                ]);
            }

            // CA-02.4: el monto lo calcula el servidor, nunca el navegador.
            $boleto = BoletoReserva::create([
                'id_funcion'        => $funcion->id_funcion,
                'nombre_cliente'    => $nombreCliente,
                'email_cliente'     => $emailCliente,
                'cantidad_entradas' => $cantidad,
                'monto_total'       => $this->calcularMonto($cantidad, $funcion->precio_unitario),
                'estado'            => 'Confirmado',
            ]);

            // CA-02.5: descuenta los asientos de la función.
            $funcion->decrement('asientos_disponibles', $cantidad);

            return $boleto;
        });
    }
}
