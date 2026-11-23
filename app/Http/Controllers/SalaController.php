<?php

namespace App\Http\Controllers;

use App\Models\Sala;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catálogo de salas. No se eliminan: se inactivan.
 * Cambiar el nombre o la capacidad de una sala no modifica las funciones ya programadas.
 */
class SalaController extends Controller
{
    private const MENSAJES = [
        'required' => 'El campo :attribute es obligatorio.',
        'string'   => 'El campo :attribute debe ser texto.',
        'max'      => 'El campo :attribute no puede superar :max caracteres.',
        'integer'  => 'El campo :attribute debe ser un número entero.',
        'gt'       => 'El campo :attribute debe ser mayor que :value.',
        'unique'   => 'Ya existe una sala con ese nombre.',
    ];

    private const ATRIBUTOS = [
        'nombre'    => 'nombre',
        'capacidad' => 'capacidad',
    ];

    public function index(): View
    {
        $salas = Sala::orderBy('nombre')->paginate(15);

        return view('salas.index', compact('salas'));
    }

    public function create(): View
    {
        return view('salas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Sala::create($this->validar($request));

        return redirect()->route('salas.index')->with('exito', 'Sala registrada correctamente.');
    }

    public function edit(Sala $sala): View
    {
        return view('salas.edit', compact('sala'));
    }

    public function update(Request $request, Sala $sala): RedirectResponse
    {
        $sala->update($this->validar($request, $sala));

        return redirect()->route('salas.index')->with('exito', 'Sala actualizada correctamente.');
    }

    private function validar(Request $request, ?Sala $sala = null): array
    {
        $unico = Rule::unique('salas', 'nombre');

        if ($sala !== null) {
            $unico->ignore($sala->id);
        }

        $datos = $request->validate([
            'nombre'    => ['required', 'string', 'max:20', $unico],
            'capacidad' => ['required', 'integer', 'gt:0'],
        ], self::MENSAJES, self::ATRIBUTOS);

        $datos['activa'] = $request->boolean('activa');

        return $datos;
    }
}
