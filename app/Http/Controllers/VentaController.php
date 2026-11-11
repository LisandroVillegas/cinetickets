<?php

namespace App\Http\Controllers;

use App\Models\PeliculaFuncion;
use App\Services\BoletoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RF-02: venta y emisión de boletos.
 */
class VentaController extends Controller
{
    private const MENSAJES = [
        'required' => 'El campo :attribute es obligatorio.',
        'string'   => 'El campo :attribute debe ser texto.',
        'max'      => 'El campo :attribute no puede superar :max caracteres.',
        'integer'  => 'El campo :attribute debe ser un número entero.',
        'gt'       => 'El campo :attribute debe ser mayor que :value.',
        'exists'   => 'La función seleccionada no existe.',
        'regex'    => 'El correo electrónico no tiene un formato válido.',
    ];

    private const ATRIBUTOS = [
        'id_funcion'        => 'función',
        'nombre_cliente'    => 'nombre del cliente',
        'email_cliente'     => 'correo electrónico',
        'cantidad_entradas' => 'cantidad de entradas',
    ];

    /** Formulario de compra: solo lista las funciones que se pueden vender (CA-02.1). */
    public function create(): View
    {
        $funciones = PeliculaFuncion::where('estado', 'Activa')
            ->where('asientos_disponibles', '>', 0)
            ->where('fecha_hora', '>=', now())
            ->orderBy('fecha_hora')
            ->get();

        return view('ventas.create', compact('funciones'));
    }

    /** Valida los datos y delega la venta en el servicio. */
    public function store(Request $request, BoletoService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'id_funcion'        => ['required', 'integer', 'exists:Pelicula_Funcion,id_funcion'],
            'nombre_cliente'    => ['required', 'string', 'max:100'],
            // CA-03.2: formato de correo con expresión regular estándar.
            'email_cliente'     => ['required', 'max:100', 'regex:/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/'],
            'cantidad_entradas' => ['required', 'integer', 'gt:0'],
        ], self::MENSAJES, self::ATRIBUTOS);

        $boleto = $servicio->registrarVenta(
            (int) $datos['id_funcion'],
            $datos['nombre_cliente'],
            $datos['email_cliente'],
            (int) $datos['cantidad_entradas']
        );

        return redirect()->route('ventas.create')->with(
            'exito',
            'Compra confirmada. Boleto #' . $boleto->id_boleto . ' por un total de $' . number_format($boleto->monto_total, 2) . '.'
        );
    }
}
