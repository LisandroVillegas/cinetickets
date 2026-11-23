<?php

namespace App\Http\Controllers;

use App\Services\ProgramacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Programación por lote: una película, una sala, un precio, varias fechas y varios horarios.
 */
class ProgramacionController extends Controller
{
    private const MENSAJES = [
        'required'    => 'El campo :attribute es obligatorio.',
        'integer'     => 'El campo :attribute debe ser un número entero.',
        'numeric'     => 'El campo :attribute debe ser un número.',
        'gt'          => 'El campo :attribute debe ser mayor que :value.',
        'max'         => 'El campo :attribute no puede superar :max.',
        'min'         => 'Debes indicar al menos :min valor en :attribute.',
        'array'       => 'El campo :attribute no es válido.',
        'exists'      => 'El valor seleccionado en :attribute no existe.',
        'date_format' => 'El campo :attribute debe tener el formato :format.',
        'distinct'    => 'El campo :attribute tiene un valor repetido.',
    ];

    private const ATRIBUTOS = [
        'pelicula_id'     => 'película',
        'sala_id'         => 'sala',
        'fechas'          => 'fechas',
        'fechas.*'        => 'fecha',
        'horarios'        => 'horarios',
        'horarios.*'      => 'horario',
        'precio_unitario' => 'precio unitario',
    ];

    /** Muestra qué funciones se crearían y cuáles chocan. No guarda nada. */
    public function previsualizar(Request $request, ProgramacionService $servicio): View
    {
        $datos = $this->validar($request);
        $resultado = $servicio->previsualizar($datos);

        return view('funciones.programacion-preview', [
            'datos'        => $datos,
            'filas'        => $resultado['filas'],
            'puedeGuardar' => $resultado['puedeGuardar'],
        ]);
    }

    /** Guarda el lote completo (todo o nada). */
    public function store(Request $request, ProgramacionService $servicio): RedirectResponse
    {
        $datos = $this->validar($request);
        $funciones = $servicio->programar($datos);

        return redirect()->route('funciones.index')
            ->with('exito', "Se programaron {$funciones->count()} funciones.");
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'pelicula_id'     => ['required', 'integer', 'exists:peliculas,id'],
            'sala_id'         => ['required', 'integer', 'exists:salas,id'],
            'fechas'          => ['required', 'array', 'min:1', 'max:31'],
            'fechas.*'        => ['required', 'date_format:Y-m-d', 'distinct'],
            'horarios'        => ['required', 'array', 'min:1'],
            'horarios.*'      => ['required', 'date_format:H:i', 'distinct'],
            'precio_unitario' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ], self::MENSAJES, self::ATRIBUTOS);

        if (count($datos['fechas']) * count($datos['horarios']) > ProgramacionService::MAX_COMBINACIONES) {
            throw ValidationException::withMessages([
                'fechas' => 'La programación no puede superar ' . ProgramacionService::MAX_COMBINACIONES . ' combinaciones de fecha y horario.',
            ]);
        }

        return $datos;
    }
}
