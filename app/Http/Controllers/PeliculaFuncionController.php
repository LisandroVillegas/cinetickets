<?php

namespace App\Http\Controllers;

use App\Models\PeliculaFuncion;
use App\Models\Pelicula;
use App\Models\Sala;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * RF-01: gestión de funciones / cartelera.
 * Ahora integrado con catálogos (Fase 2).
 */
class PeliculaFuncionController extends Controller
{
    private const MENSAJES = [
        'required'       => 'El campo :attribute es obligatorio.',
        'numeric'        => 'El campo :attribute debe ser un número.',
        'gt'             => 'El campo :attribute debe ser mayor que :value.',
        'date'           => 'El campo :attribute debe ser una fecha y hora válidas.',
        'after_or_equal' => 'La fecha y hora de la función no puede ser anterior a la actual.',
        'in'             => 'El valor de :attribute no es válido.',
        'exists'         => 'El valor seleccionado para :attribute no es válido.',
    ];

    private const ATRIBUTOS = [
        'pelicula_id'     => 'película',
        'sala_id'         => 'sala',
        'fecha_hora'      => 'fecha y hora',
        'precio_unitario' => 'precio unitario',
        'estado'          => 'estado',
    ];

    /** Consultar la cartelera */
    public function index(): View
    {
        $funciones = PeliculaFuncion::withCount('boletos')
            ->orderBy('fecha_hora')
            ->get();

        return view('funciones.index', compact('funciones'));
    }

    /** Muestra el formulario para registrar una función. */
    /** Muestra el formulario para registrar una función. */
    public function create(): View
    {
        // Cargamos todas las películas y salas ordenadas
        $peliculas = Pelicula::orderBy('titulo')->get();
        $salas = Sala::orderBy('nombre')->get();

        return view('funciones.create', compact('peliculas', 'salas'));
    }

    /** Registrar una función nueva (con snapshot). */
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $pelicula = Pelicula::findOrFail($datos['pelicula_id']);
        $sala = Sala::findOrFail($datos['sala_id']);

        // CREACIÓN DE LA INSTANTÁNEA (Snapshot)
        $datos['titulo'] = $pelicula->titulo;
        $datos['genero'] = $pelicula->genero;
        $datos['duracion_min'] = $pelicula->duracion_min;
        $datos['sala'] = $sala->nombre;
        $datos['capacidad_total'] = $sala->capacidad; 
        $datos['asientos_disponibles'] = $sala->capacidad;
        $datos['estado'] = 'Activa';

        PeliculaFuncion::create($datos);

        return redirect()->route('funciones.index')
            ->with('exito', 'Función registrada correctamente.');
    }

    /** Muestra el formulario para editar una función existente. */
   /** Muestra el formulario para editar una función existente. */
    public function edit(PeliculaFuncion $funcion): View
    {
        $peliculas = Pelicula::orderBy('titulo')->get();
        $salas = Sala::orderBy('nombre')->get();

        return view('funciones.edit', compact('funcion', 'peliculas', 'salas'));
    }
    /** Actualizar una función. */
    public function update(Request $request, PeliculaFuncion $funcion): RedirectResponse
    {
        return DB::transaction(function () use ($request, $funcion): RedirectResponse {
            $actual = PeliculaFuncion::whereKey($funcion->id_funcion)->lockForUpdate()->firstOrFail();
            $datos = $this->validar($request, $actual);

            $pelicula = Pelicula::findOrFail($datos['pelicula_id']);
            $sala = Sala::findOrFail($datos['sala_id']);

            $vendidos = $actual->capacidad_total - $actual->asientos_disponibles;
            $nuevaCapacidad = $sala->capacidad; 

            if ($nuevaCapacidad < $vendidos) {
                return back()->withInput()->withErrors([
                    'sala_id' => "La capacidad de la nueva sala elegida ({$nuevaCapacidad}) no puede ser menor que los asientos ya vendidos ({$vendidos}).",
                ]);
            }

            // ACTUALIZACIÓN DE LA INSTANTÁNEA
            $datos['titulo'] = $pelicula->titulo;
            $datos['genero'] = $pelicula->genero;
            $datos['duracion_min'] = $pelicula->duracion_min;
            $datos['sala'] = $sala->nombre;
            $datos['capacidad_total'] = $nuevaCapacidad;
            $datos['asientos_disponibles'] = $nuevaCapacidad - $vendidos;

            $actual->update($datos);

            return redirect()->route('funciones.index')
                ->with('exito', 'Función actualizada correctamente.');
        }, 3);
    }

    public function desactivar(PeliculaFuncion $funcion): RedirectResponse
    {
        $funcion->update(['estado' => 'Inactiva']);
        return redirect()->route('funciones.index')->with('exito', 'La función quedó inactiva.');
    }

    public function destroy(PeliculaFuncion $funcion): RedirectResponse
    {
        if ($funcion->boletos()->exists()) {
            return redirect()->route('funciones.index')
                ->with('error', 'La función tiene boletos registrados: no se puede eliminar, solo desactivar.');
        }

        $funcion->delete();
        return redirect()->route('funciones.index')->with('exito', 'Función eliminada correctamente.');
    }

    private function validar(Request $request, ?PeliculaFuncion $funcion = null): array
    {
        $reglasFecha = ['required', 'date'];

        $fechaCambio = $funcion === null
            || $request->input('fecha_hora') !== $funcion->fecha_hora->format('Y-m-d\TH:i');

        if ($fechaCambio) {
            $reglasFecha[] = 'after_or_equal:now';
        }

        $reglas = [
            'pelicula_id'     => ['required', 'exists:peliculas,id'],
            'sala_id'         => ['required', 'exists:salas,id'],
            'fecha_hora'      => $reglasFecha,
            'precio_unitario' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ];

        if ($funcion !== null) {
            $reglas['estado'] = ['required', 'in:Activa,Inactiva'];
        }

        return $request->validate($reglas, self::MENSAJES, self::ATRIBUTOS);
    }
}