<?php

namespace App\Http\Controllers;

use App\Models\Pelicula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo de películas. No se eliminan: se inactivan.
 * Cambiar una película no modifica las funciones ya programadas (instantánea).
 */
class PeliculaController extends Controller
{
    private const MENSAJES = [
        'required' => 'El campo :attribute es obligatorio.',
        'string'   => 'El campo :attribute debe ser texto.',
        'max'      => 'El campo :attribute no puede superar :max caracteres.',
        'integer'  => 'El campo :attribute debe ser un número entero.',
        'gt'       => 'El campo :attribute debe ser mayor que :value.',
    ];

    private const ATRIBUTOS = [
        'titulo'       => 'título',
        'genero'       => 'género',
        'duracion_min' => 'duración (min)',
    ];

    public function index(): View
    {
        $peliculas = Pelicula::orderBy('titulo')->paginate(15);

        return view('peliculas.index', compact('peliculas'));
    }

    public function create(): View
    {
        return view('peliculas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Pelicula::create($this->validar($request));

        return redirect()->route('peliculas.index')->with('exito', 'Película registrada correctamente.');
    }

    public function edit(Pelicula $pelicula): View
    {
        return view('peliculas.edit', compact('pelicula'));
    }

    public function update(Request $request, Pelicula $pelicula): RedirectResponse
    {
        $pelicula->update($this->validar($request));

        return redirect()->route('peliculas.index')->with('exito', 'Película actualizada correctamente.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'titulo'       => ['required', 'string', 'max:100'],
            'genero'       => ['required', 'string', 'max:50'],
            'duracion_min' => ['required', 'integer', 'gt:0'],
        ], self::MENSAJES, self::ATRIBUTOS);

        $datos['activa'] = $request->boolean('activa');

        return $datos;
    }
}
