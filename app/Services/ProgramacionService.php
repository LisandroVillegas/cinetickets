<?php

namespace App\Services;

use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Programación de funciones (contrato v1, sección 3).
 *
 * Regla de ocupación de sala: una función ocupa [inicio, inicio + duración + 15 minutos).
 * Dos intervalos chocan si inicioA < finB e inicioB < finA, comparando fecha y hora completas
 * (por eso también funciona cuando una función termina después de medianoche).
 */
class ProgramacionService
{
    public const PAUSA_MINUTOS = 15;
    public const MAX_COMBINACIONES = 100;

    /** Estados que ocupan la sala. Una función inactiva se puede reactivar, así que no se libera su horario. */
    private const ESTADOS_QUE_OCUPAN = ['Activa', 'Inactiva'];

    /**
     * Vista previa de un lote: no guarda nada.
     *
     * @return array{filas: array<int, array>, puedeGuardar: bool}
     */
    public function previsualizar(array $datos): array
    {
        $pelicula = Pelicula::findOrFail($datos['pelicula_id']);
        $sala = Sala::findOrFail($datos['sala_id']);
        $this->exigirActivos($pelicula, $sala);

        $filas = $this->evaluar($sala, $pelicula, $this->generarInicios($datos));

        return [
            'filas' => array_map(fn (array $fila) => [
                'fecha_hora'    => $fila['inicio']->format('Y-m-d H:i'),
                'fin_ocupacion' => $fila['fin']->format('Y-m-d H:i'),
                'conflicto'     => $fila['conflicto'],
                'mensaje'       => $fila['mensaje'],
            ], $filas),
            'puedeGuardar' => ! collect($filas)->contains('conflicto', true),
        ];
    }

    /**
     * Guarda un lote completo o no guarda nada. Vuelve a validar dentro de la transacción.
     *
     * @return Collection<int, PeliculaFuncion>
     */
    public function programar(array $datos): Collection
    {
        return DB::transaction(function () use ($datos) {
            // Se bloquea la fila de la sala ANTES de buscar cruces: así dos programaciones
            // simultáneas en la misma sala se ejecutan una tras otra.
            $sala = Sala::whereKey($datos['sala_id'])->lockForUpdate()->firstOrFail();
            $pelicula = Pelicula::findOrFail($datos['pelicula_id']);
            $this->exigirActivos($pelicula, $sala);

            $filas = $this->evaluar($sala, $pelicula, $this->generarInicios($datos));

            $conflictos = collect($filas)->where('conflicto', true);

            if ($conflictos->isNotEmpty()) {
                $mensajes = ['No se guardó ninguna función porque hay conflictos de horario:'];

                foreach ($conflictos as $fila) {
                    $mensajes[] = $fila['inicio']->format('Y-m-d H:i') . ' → ' . $fila['mensaje'];
                }

                throw ValidationException::withMessages(['programacion' => $mensajes]);
            }

            return collect($filas)->map(fn (array $fila) => PeliculaFuncion::create(
                $this->instantanea($pelicula, $sala) + [
                    'fecha_hora'      => $fila['inicio'],
                    'precio_unitario' => $datos['precio_unitario'],
                    'estado'          => 'Activa',
                ]
            ));
        });
    }

    /** Programa una sola función (reutiliza las mismas reglas que el lote). */
    public function programarUna(int $peliculaId, int $salaId, Carbon $inicio, string|float|int $precio): PeliculaFuncion
    {
        return $this->programar([
            'pelicula_id'     => $peliculaId,
            'sala_id'         => $salaId,
            'fechas'          => [$inicio->format('Y-m-d')],
            'horarios'        => [$inicio->format('H:i')],
            'precio_unitario' => $precio,
        ])->first();
    }

    /**
     * Reprograma una función SIN boletos. Si cambia de sala se bloquean ambas, en orden de ID.
     */
    public function reprogramar(PeliculaFuncion $funcion, array $datos): PeliculaFuncion
    {
        return DB::transaction(function () use ($funcion, $datos) {
            $ids = collect([$funcion->sala_id, $datos['sala_id']])->map(fn ($id) => (int) $id)->unique()->sort()->values();

            foreach ($ids as $id) {
                Sala::whereKey($id)->lockForUpdate()->firstOrFail();
            }

            $actual = PeliculaFuncion::whereKey($funcion->id_funcion)->lockForUpdate()->firstOrFail();

            if ($actual->boletos()->exists()) {
                throw ValidationException::withMessages([
                    'operacion' => 'La función tiene boletos: no se puede reprogramar.',
                ]);
            }

            $pelicula = Pelicula::findOrFail($datos['pelicula_id']);
            $sala = Sala::findOrFail($datos['sala_id']);
            $this->exigirActivos($pelicula, $sala, $actual);

            $inicio = Carbon::parse($datos['fecha_hora']);
            $fila = $this->evaluar($sala, $pelicula, collect([$inicio]), (int) $actual->id_funcion)[0];

            if ($fila['conflicto']) {
                throw ValidationException::withMessages(['programacion' => [$fila['mensaje']]]);
            }

            $actual->update($this->instantanea($pelicula, $sala) + [
                'fecha_hora'      => $inicio,
                'precio_unitario' => $datos['precio_unitario'],
                'estado'          => $datos['estado'],
            ]);

            return $actual;
        });
    }

    /** Un catálogo inactivo no admite nuevas programaciones (salvo que la función ya lo tuviera). */
    private function exigirActivos(Pelicula $pelicula, Sala $sala, ?PeliculaFuncion $actual = null): void
    {
        if (! $pelicula->activa && (! $actual || (int) $actual->pelicula_id !== (int) $pelicula->id)) {
            throw ValidationException::withMessages(['pelicula_id' => 'La película seleccionada está inactiva.']);
        }

        if (! $sala->activa && (! $actual || (int) $actual->sala_id !== (int) $sala->id)) {
            throw ValidationException::withMessages(['sala_id' => 'La sala seleccionada está inactiva.']);
        }
    }

    /** Copia los datos de la película y de la sala a la función (instantánea). */
    private function instantanea(Pelicula $pelicula, Sala $sala): array
    {
        return [
            'pelicula_id'          => $pelicula->id,
            'sala_id'              => $sala->id,
            'titulo'               => $pelicula->titulo,
            'genero'               => $pelicula->genero,
            'duracion_min'         => $pelicula->duracion_min,
            'sala'                 => $sala->nombre,
            'capacidad_total'      => $sala->capacidad,
            'asientos_disponibles' => $sala->capacidad,
        ];
    }

    /** Combina todas las fechas con todos los horarios. */
    private function generarInicios(array $datos): Collection
    {
        $inicios = collect();

        foreach ($datos['fechas'] as $fecha) {
            foreach ($datos['horarios'] as $hora) {
                $inicios->push(Carbon::createFromFormat('Y-m-d H:i', $fecha . ' ' . $hora));
            }
        }

        if ($inicios->count() > self::MAX_COMBINACIONES) {
            throw ValidationException::withMessages([
                'fechas' => 'La programación no puede superar ' . self::MAX_COMBINACIONES . ' combinaciones de fecha y horario.',
            ]);
        }

        return $inicios;
    }

    /**
     * Revisa cada inicio: que sea futuro, que no choque con otras filas del lote
     * y que no choque con funciones ya guardadas en la sala.
     *
     * @return array<int, array{inicio: Carbon, fin: Carbon, conflicto: bool, mensaje: ?string}>
     */
    private function evaluar(Sala $sala, Pelicula $pelicula, Collection $inicios, ?int $excluirId = null): array
    {
        $ocupacion = $pelicula->duracion_min + self::PAUSA_MINUTOS;

        $filas = $inicios->map(fn (Carbon $inicio) => [
            'inicio'    => $inicio,
            'fin'       => $inicio->copy()->addMinutes($ocupacion),
            'conflicto' => false,
            'mensaje'   => null,
        ])->values()->all();

        // 1. El inicio debe ser estrictamente posterior al reloj del servidor.
        foreach ($filas as $i => $fila) {
            if ($fila['inicio']->lessThanOrEqualTo(now())) {
                $filas[$i]['conflicto'] = true;
                $filas[$i]['mensaje'] = 'La fecha y hora debe ser posterior a la actual.';
            }
        }

        // 2. Cruces entre las filas del propio lote.
        foreach ($filas as $j => $b) {
            foreach ($filas as $i => $a) {
                if ($i >= $j || $filas[$j]['conflicto']) {
                    continue;
                }

                if ($a['inicio']->lt($b['fin']) && $b['inicio']->lt($a['fin'])) {
                    $filas[$j]['conflicto'] = true;
                    $filas[$j]['mensaje'] = 'Se cruza con otra fila del mismo lote (' . $a['inicio']->format('Y-m-d H:i') . ').';
                }
            }
        }

        // 3. Cruces con funciones que ya existen en la sala.
        $minInicio = collect($filas)->pluck('inicio')->sort()->first();
        $maxFin = collect($filas)->pluck('fin')->sort()->last();

        $existentes = PeliculaFuncion::where('sala_id', $sala->id)
            ->whereIn('estado', self::ESTADOS_QUE_OCUPAN)
            ->when($excluirId !== null, fn ($consulta) => $consulta->where('id_funcion', '!=', $excluirId))
            ->where('fecha_hora', '<', $maxFin)
            ->where('fecha_hora', '>=', $minInicio->copy()->subDay())
            ->get();

        foreach ($filas as $i => $fila) {
            if ($fila['conflicto']) {
                continue;
            }

            foreach ($existentes as $existente) {
                $existenteInicio = $existente->fecha_hora;
                $existenteFin = $existenteInicio->copy()->addMinutes($existente->duracion_min + self::PAUSA_MINUTOS);

                if ($fila['inicio']->lt($existenteFin) && $existenteInicio->lt($fila['fin'])) {
                    $filas[$i]['conflicto'] = true;
                    $filas[$i]['mensaje'] = "Choca con la función #{$existente->id_funcion} ({$existente->titulo}), "
                        . 'que ocupa la sala de ' . $existenteInicio->format('H:i') . ' a ' . $existenteFin->format('H:i') . '.';
                    break;
                }
            }
        }

        return $filas;
    }
}
