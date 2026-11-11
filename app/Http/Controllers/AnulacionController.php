<?php

namespace App\Http\Controllers;

use App\Models\BoletoReserva;

/**
 * Anulación de boletos (RF-04). Esqueleto: lo reemplaza su responsable.
 * Responsable: Jorge Alexander Henao Bueno.
 */
class AnulacionController extends Controller
{
    public function anular(BoletoReserva $boleto)
    {
        abort(501, 'Pendiente: anulación de boletos (RF-04).');
    }
}
