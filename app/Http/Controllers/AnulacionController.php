<?php

namespace App\Http\Controllers;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/** RF-04: anulación de boletos y devolución de asientos. */
class AnulacionController extends Controller
{
    public function anular(BoletoReserva $boleto): RedirectResponse
    {
        $error = DB::transaction(function () use ($boleto): ?string {
            // Mantener el orden función -> boleto en las operaciones concurrentes.
            $funcion = PeliculaFuncion::whereKey($boleto->id_funcion)->lockForUpdate()->firstOrFail();
            $actual = BoletoReserva::whereKey($boleto->id_boleto)->lockForUpdate()->firstOrFail();

            if ($actual->id_funcion !== $funcion->id_funcion) {
                return 'La función del boleto cambió. Recarga el listado e intenta de nuevo.';
            }

            if ($actual->estado !== 'Confirmado') {
                return 'El boleto ya está cancelado. No se devolvieron asientos nuevamente.';
            }

            if ($funcion->fecha_hora->lessThanOrEqualTo(now())) {
                return 'No se puede anular un boleto de una función que ya comenzó.';
            }

            $disponibles = $funcion->asientos_disponibles + $actual->cantidad_entradas;
            if ($actual->cantidad_entradas <= 0 || $disponibles > $funcion->capacidad_total) {
                return 'No se puede anular: los cupos registrados son inconsistentes. Solicita su revisión.';
            }

            $funcion->update(['asientos_disponibles' => $disponibles]);
            $actual->update(['estado' => 'Cancelado']);

            return null;
        }, 3);

        return redirect()->route('boletos.index')->with(
            $error === null ? 'exito' : 'error',
            $error ?? 'Boleto anulado correctamente. Los asientos quedaron disponibles.'
        );
    }
}
