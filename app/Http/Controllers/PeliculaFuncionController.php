<?php

namespace App\Http\Controllers;

use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use App\Services\ProgramacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * RF-01: gestión de funciones / cartelera.
 * Una función se programa eligiendo una película y una sala de los catálogos.
 * El servidor copia los datos del catálogo (instantánea) y comprueba los cruces de horario.
 */
class PeliculaFuncionController extends Controller
{
    private const MENSAJES = [
        'required'       => 'El campo :attribute es obligatorio.',
        'integer'        => 'El campo :attribute debe ser un número entero.',
        'numeric'        => 'El campo :attribute debe ser un número.',
        'gt'             => 'El campo :attribute debe ser mayor que :value.',
        'max'            => 'El campo :attribute no puede superar :max.',
        'date'           => 'El campo :attribute debe ser una fecha y hora válidas.',
        'after_or_equal' => 'La fecha y hora de la función no puede ser anterior a la actual.',
        'in'             => 'El valor de :attribute no es válido.',
        'exists'         => 'El valor seleccionado en :attribute no existe.',
    ];

    private const ATRIBUTOS = [
        'pelicula_id'     => 'película',
        'sala_id'         => 'sala',
        'fecha_hora'      => 'fecha y hora',
        'precio_unitario' => 'precio unitario',
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

    /** Formulario para programar: solo ofrece películas y salas activas. */
    public function create(): View
    {
        return view('funciones.create', [
            'peliculas' => Pelicula::activas()->orderBy('titulo')->get(),
            'salas'     => Sala::activas()->orderBy('nombre')->get(),
        ]);
    }

    /** Registrar una función nueva (con comprobación de cruces). */
    public function store(Request $request, ProgramacionService $servicio): RedirectResponse
    {
        $datos = $this->validar($request);

        $servicio->programarUna(
            (int) $datos['pelicula_id'],
            (int) $datos['sala_id'],
            Carbon::parse($datos['fecha_hora']),
            $datos['precio_unitario']
        );

        return redirect()->route('funciones.index')
            ->with('exito', 'Función registrada correctamente.');
    }

    /** Formulario de edición: incluye la película y la sala actuales aunque estén inactivas. */
    public function edit(PeliculaFuncion $funcion): View
    {
        return view('funciones.edit', [
            'funcion'      => $funcion,
            'peliculas'    => Pelicula::where('activa', true)->orWhere('id', $funcion->pelicula_id)->orderBy('titulo')->get(),
            'salas'        => Sala::where('activa', true)->orWhere('id', $funcion->sala_id)->orderBy('nombre')->get(),
            'tieneBoletos' => $funcion->boletos()->exists(),
        ]);
    }

    /** Actualizar una función. */
    public function update(Request $request, PeliculaFuncion $funcion, ProgramacionService $servicio): RedirectResponse
    {
        return DB::transaction(function () use ($request, $funcion, $servicio): RedirectResponse {
            // Releer bajo bloqueo: una venta o anulación puede cambiar la función mientras se edita.
            $actual = PeliculaFuncion::whereKey($funcion->id_funcion)->lockForUpdate()->firstOrFail();
            $tieneBoletos = $actual->boletos()->exists();

            $datos = $this->validar($request, $actual, $tieneBoletos);

            // Reactivar solo si la función todavía no empezó.
            if ($datos['estado'] === 'Activa' && $actual->estado === 'Inactiva' && $actual->fecha_hora->isPast()) {
                return back()->withInput()->withErrors([
                    'operacion' => 'No se puede reactivar una función que ya empezó.',
                ]);
            }

            if ($tieneBoletos) {
                // Con boletos solo cambian el precio (para ventas futuras) y el estado.
                if ($actual->fecha_hora->isPast()) {
                    return back()->withInput()->withErrors([
                        'operacion' => 'La función ya empezó y tiene boletos: no se puede modificar.',
                    ]);
                }

                $actual->update([
                    'precio_unitario' => $datos['precio_unitario'],
                    'estado'          => $datos['estado'],
                ]);
            } elseif (! $this->cambiaProgramacion($datos, $actual)) {
                // Sin cambio de película, sala ni horario: solo precio y estado.
                $actual->update([
                    'precio_unitario' => $datos['precio_unitario'],
                    'estado'          => $datos['estado'],
                ]);
            } else {
                $servicio->reprogramar($actual, $datos);
            }

            return redirect()->route('funciones.index')
                ->with('exito', 'Función actualizada correctamente.');
        }, 3);
    }

    /** Desactivar una función (detiene nuevas ventas; sigue ocupando la sala). */
    public function desactivar(PeliculaFuncion $funcion): RedirectResponse
    {
        $funcion->update(['estado' => 'Inactiva']);

        return redirect()->route('funciones.index')
            ->with('exito', 'La función quedó inactiva.');
    }

    /** Reactivar una función inactiva, solo si todavía no empezó. */
    public function activar(PeliculaFuncion $funcion): RedirectResponse
    {
        if ($funcion->fecha_hora->isPast()) {
            return redirect()->route('funciones.index')
                ->with('error', 'No se puede reactivar una función que ya empezó.');
        }

        $funcion->update(['estado' => 'Activa']);

        return redirect()->route('funciones.index')
            ->with('exito', 'La función quedó activa.');
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

    /** ¿Se está cambiando la película, la sala o el horario? */
    private function cambiaProgramacion(array $datos, PeliculaFuncion $actual): bool
    {
        return (int) $datos['pelicula_id'] !== (int) $actual->pelicula_id
            || (int) $datos['sala_id'] !== (int) $actual->sala_id
            || ! $this->mismaFecha($datos['fecha_hora'] ?? null, $actual);
    }

    /** Compara fecha y hora hasta el minuto, sin depender del formato de entrada. */
    private function mismaFecha(?string $entrada, PeliculaFuncion $actual): bool
    {
        if ($entrada === null || $entrada === '') {
            return false;
        }

        try {
            return Carbon::parse($entrada)->format('Y-m-d H:i') === $actual->fecha_hora->format('Y-m-d H:i');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Reglas de validación compartidas por crear y actualizar.
     * CA-01.1 (obligatorios), CA-01.2 (precio mayor que cero) y CA-01.4 (fecha no pasada).
     * Si la función ya tiene boletos solo se validan el precio y el estado.
     */
    private function validar(Request $request, ?PeliculaFuncion $funcion = null, bool $soloPrecioYEstado = false): array
    {
        $reglas = [
            'precio_unitario' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ];

        if ($funcion !== null) {
            $reglas['estado'] = ['required', 'in:Activa,Inactiva'];
        }

        if (! $soloPrecioYEstado) {
            $reglasFecha = ['required', 'date'];

            // La fecha solo debe ser futura si es una función nueva o si se cambió la fecha.
            if ($funcion === null || ! $this->mismaFecha($request->input('fecha_hora'), $funcion)) {
                $reglasFecha[] = 'after_or_equal:now';
            }

            $reglas += [
                'pelicula_id' => ['required', 'integer', 'exists:peliculas,id'],
                'sala_id'     => ['required', 'integer', 'exists:salas,id'],
                'fecha_hora'  => $reglasFecha,
            ];
        }

        return $request->validate($reglas, self::MENSAJES, self::ATRIBUTOS);
    }
}
