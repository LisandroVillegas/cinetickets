<?php

namespace App\Http\Controllers;

use App\Models\BoletoReserva;
use Illuminate\Http\Request;

/**
 * Consulta y edición de boletos (RF-03). Esqueleto: lo reemplaza su responsable.
 * Responsable: Andrés Felipe Cuéllar.
 */
class BoletoController extends Controller
{
    public function index(Request $request)
    {
        abort(501, 'Pendiente: listado y filtros de boletos (RF-03).');
    }

    public function edit(BoletoReserva $boleto)
    {
        abort(501, 'Pendiente: formulario de edición de boleto.');
    }

    public function update(Request $request, BoletoReserva $boleto)
    {
        abort(501, 'Pendiente: guardar edición de boleto.');
    }
}
