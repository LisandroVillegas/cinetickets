<?php

namespace App\Http\Controllers;

use App\Models\PeliculaFuncion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RF-01: gestión de funciones / cartelera (CRUD de la entidad padre).
 */
class PeliculaFuncionController extends Controller
{
    private const MENSAJES = [
        'required'       => 'El campo :attribute es obligatorio.',
        'string'         => 'El campo :attribute debe ser texto.',
        'max'            => 'El campo :attribute no puede superar :max.',
        'integer'        => 'El campo :attribute debe ser un número entero.',
        'numeric'        => 'El campo :attribute debe ser un número.',
        'gt'             => 'El campo :attribute debe ser mayor que :value.',
        'date'           => 'El campo :attribute debe ser una fecha y hora válidas.',
        'after_or_equal' => 'La fecha y hora de la función no puede ser anterior a la actual.',
        'in'             => 'El valor de :attribute no es válido.',
    ];

    private const ATRIBUTOS = [
        'titulo'          => 'título',
        'genero'          => 'género',
        'duracion_min'    => 'duración (min)',
        'sala'            => 'sala',
        'fecha_hora'      => 'fecha y hora',
        'precio_unitario' => 'precio unitario',
        'capacidad_total' => 'capacidad total',
        'estado'          => 'estado',
    ];

    /** Consultar la cartelera (lista todas las funciones con su número de boletos). */
    public function index(): View
    {
        $funciones = PeliculaFuncion::withCount('boletos')
            ->orderBy('fecha_hora')
            ->get();

        return view('funciones.index', compact('funciones'));
    }

    /** Muestra el formulario para registrar una función. */
    public function create(): View
    {
        return view('funciones.create');
    }

    /** Registrar una función nueva. */
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        // CA-01.3: al crear, los asientos disponibles son iguales a la capacidad total.
        $datos['asientos_disponibles'] = $datos['capacidad_total'];
        $datos['estado'] = 'Activa';

        PeliculaFuncion::create($datos);

        return redirect()->route('funciones.index')
            ->with('exito', 'Función registrada correctamente.');
    }

    /** Muestra el formulario para editar una función existente. */
    public function edit(PeliculaFuncion $funcion): View
    {
        return view('funciones.edit', compact('funcion'));
    }

    /** Actualizar una función. */
    public function update(Request $request, PeliculaFuncion $funcion): RedirectResponse
    {
        $datos = $this->validar($request, $funcion);

        // Decisión 2: la capacidad nueva no puede ser menor que los asientos ya vendidos.
        $vendidos = $funcion->capacidad_total - $funcion->asientos_disponibles;

        if ($datos['capacidad_total'] < $vendidos) {
            return back()->withInput()->withErrors([
                'capacidad_total' => "La capacidad no puede ser menor que los asientos ya vendidos ($vendidos).",
            ]);
        }

        // Los asientos disponibles se recalculan; nunca quedan negativos.
        $datos['asientos_disponibles'] = $datos['capacidad_total'] - $vendidos;

        $funcion->update($datos);

        return redirect()->route('funciones.index')
            ->with('exito', 'Función actualizada correctamente.');
    }

    /** Desactivar una función (inactivación lógica). */
    public function desactivar(PeliculaFuncion $funcion): RedirectResponse
    {
        $funcion->update(['estado' => 'Inactiva']);

        return redirect()->route('funciones.index')
            ->with('exito', 'La función quedó inactiva.');
    }

    /** Eliminar físicamente; solo si no tiene boletos (CA-01.5 y Decisión 6). */
    public function destroy(PeliculaFuncion $funcion): RedirectResponse
    {
        if ($funcion->boletos()->exists()) {
            return redirect()->route('funciones.index')
                ->with('error', 'La función tiene boletos registrados: no se puede eliminar, solo desactivar.');
        }

        $funcion->delete();

        return redirect()->route('funciones.index')
            ->with('exito', 'Función eliminada correctamente.');
    }

    /**
     * Reglas de validación compartidas por crear y actualizar.
     * CA-01.1 (obligatorios), CA-01.2 (mayores a cero) y CA-01.4 (fecha no pasada).
     */
    private function validar(Request $request, ?PeliculaFuncion $funcion = null): array
    {
        $reglasFecha = ['required', 'date'];

        // La fecha solo debe ser futura si es una función nueva o si se cambió la fecha.
        // Así se puede editar el precio de una función cuya fecha no se toca.
        $fechaCambio = $funcion === null
            || $request->input('fecha_hora') !== $funcion->fecha_hora->format('Y-m-d\TH:i');

        if ($fechaCambio) {
            $reglasFecha[] = 'after_or_equal:now';
        }

        $reglas = [
            'titulo'          => ['required', 'string', 'max:100'],
            'genero'          => ['required', 'string', 'max:50'],
            'duracion_min'    => ['required', 'integer', 'gt:0'],
            'sala'            => ['required', 'string', 'max:20'],
            'fecha_hora'      => $reglasFecha,
            'precio_unitario' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'capacidad_total' => ['required', 'integer', 'gt:0'],
        ];

        if ($funcion !== null) {
            $reglas['estado'] = ['required', 'in:Activa,Inactiva'];
        }

        return $request->validate($reglas, self::MENSAJES, self::ATRIBUTOS);
    }
}
