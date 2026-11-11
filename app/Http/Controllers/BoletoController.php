<?php

namespace App\Http\Controllers;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Consulta y edicion de boletos (RF-03).
 * Responsable: Andres Felipe Cuellar.
 */
class BoletoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'cliente' => ['nullable', 'string', 'max:100'],
            'funcion' => ['nullable', 'integer', 'exists:Pelicula_Funcion,id_funcion'],
        ]);

                $consultaBoletos = BoletoReserva::with('funcion');
        $cliente = $filtros['cliente'] ?? '';

        if ($cliente !== '') {
            $consultaBoletos->where(function ($consultaCliente) use ($cliente) {
                $consultaCliente->where('nombre_cliente', 'like', "%{$cliente}%")
                    ->orWhere('email_cliente', 'like', "%{$cliente}%");
            });
        }

        if (!empty($filtros['funcion'])) {
            $consultaBoletos->where('id_funcion', $filtros['funcion']);
        }

        $boletos = $consultaBoletos
            ->orderByDesc('fecha_compra')
            ->orderByDesc('id_boleto')
            ->paginate(15)
            ->withQueryString();

        $funciones = PeliculaFuncion::orderBy('titulo')
            ->orderBy('fecha_hora')
            ->get(['id_funcion', 'titulo', 'fecha_hora']);

        return view('boletos.index', compact('boletos', 'funciones', 'filtros'));
    }

    public function edit(BoletoReserva $boleto): View
    {
        abort_unless($boleto->estado === 'Confirmado', 404);

        $boleto->load('funcion');

        return view('boletos.edit', compact('boleto'));
    }

    public function update(Request $request, BoletoReserva $boleto): RedirectResponse
    {
        abort_unless($boleto->estado === 'Confirmado', 404);

        $datos = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:100'],
            'email_cliente' => ['required', 'string', 'email', 'max:100'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'email' => 'Ingresa un correo electronico valido.',
            'max' => 'El campo :attribute no puede superar :max caracteres.',
        ], [
            'nombre_cliente' => 'nombre del cliente',
            'email_cliente' => 'correo electronico',
        ]);

        // Solo se guardan estos dos campos; cantidad y funcion no se modifican.
        // El UPDATE comprueba el estado en la misma operacion que guarda los datos.
        $actualizados = BoletoReserva::query()
            ->whereKey($boleto->getKey())
            ->where('estado', 'Confirmado')
            ->update($datos);

        if ($actualizados === 0) {
            $boleto->refresh();

            if ($boleto->estado !== 'Confirmado') {
                return redirect()->route('boletos.index')
                    ->with('error', 'El boleto ya no esta confirmado y no se puede editar.');
            }
        }

        return redirect()->route('boletos.index')
            ->with('exito', 'Los datos del cliente se actualizaron correctamente.');
    }
}
