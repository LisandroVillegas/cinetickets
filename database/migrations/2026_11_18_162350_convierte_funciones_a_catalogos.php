<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fase 2, paso B: convierte los datos de Pelicula_Funcion a los catálogos.
 *
 * - Una película por cada combinación EXACTA de título, género y duración.
 * - Una sala por cada nombre; su capacidad sale de las funciones.
 * - Si hay datos ambiguos, se DETIENE antes de cambiar nada.
 * - No toca boletos, montos, fechas, estados ni aforos.
 */
return new class extends Migration
{
    public function up(): void
    {
        $funciones = DB::table('Pelicula_Funcion')
            ->select('id_funcion', 'titulo', 'genero', 'duracion_min', 'sala', 'capacidad_total')
            ->orderBy('id_funcion')
            ->get();

        if ($funciones->isEmpty()) {
            return;
        }

        $this->detenerSiHayAmbiguedad($funciones);

        // Solo hay inserciones y actualizaciones de datos: si algo falla, todo se deshace.
        DB::transaction(function () use ($funciones) {
            $ahora = now();
            $peliculas = [];
            $salas = [];

            foreach ($funciones as $f) {
                $titulo = trim($f->titulo);
                $genero = trim($f->genero);
                $nombreSala = trim($f->sala);

                // --- Película: se crea una sola vez por combinación exacta ---
                $clave = $titulo . '|' . $genero . '|' . $f->duracion_min;

                if (! isset($peliculas[$clave])) {
                    $existente = DB::table('peliculas')
                        ->where('titulo', $titulo)
                        ->where('genero', $genero)
                        ->where('duracion_min', $f->duracion_min)
                        ->first();

                    $peliculas[$clave] = $existente
                        ? $existente->id
                        : DB::table('peliculas')->insertGetId([
                            'titulo'       => $titulo,
                            'genero'       => $genero,
                            'duracion_min' => $f->duracion_min,
                            'activa'       => true,
                            'created_at'   => $ahora,
                            'updated_at'   => $ahora,
                        ]);
                }

                // --- Sala: por nombre; si ya existe, su capacidad debe coincidir ---
                if (! isset($salas[$nombreSala])) {
                    $existente = DB::table('salas')->where('nombre', $nombreSala)->first();

                    if ($existente !== null && (int) $existente->capacidad !== (int) $f->capacidad_total) {
                        throw new RuntimeException(
                            "La sala «{$nombreSala}» ya existe con capacidad {$existente->capacidad}, "
                            . "pero sus funciones tienen {$f->capacidad_total}. Resuélvelo a mano antes de migrar."
                        );
                    }

                    $salas[$nombreSala] = $existente
                        ? $existente->id
                        : DB::table('salas')->insertGetId([
                            'nombre'     => $nombreSala,
                            'capacidad'  => $f->capacidad_total,
                            'activa'     => true,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ]);
                }

                // --- Vincular la función con sus catálogos ---
                DB::table('Pelicula_Funcion')
                    ->where('id_funcion', $f->id_funcion)
                    ->update([
                        'pelicula_id' => $peliculas[$clave],
                        'sala_id'     => $salas[$nombreSala],
                    ]);
            }

            // --- Comprobación final: ninguna función puede quedar sin vincular ---
            $sinVincular = DB::table('Pelicula_Funcion')
                ->where(fn ($q) => $q->whereNull('pelicula_id')->orWhereNull('sala_id'))
                ->count();

            if ($sinVincular > 0) {
                throw new RuntimeException("Quedaron {$sinVincular} funciones sin vincular. Se deshizo la conversión.");
            }
        });
    }

    /** Detiene la migración si los datos no se pueden convertir sin adivinar. */
    private function detenerSiHayAmbiguedad(Collection $funciones): void
    {
        $problemas = [];

        // Mismo título con género o duración distintos, o títulos que solo cambian en mayúsculas o espacios.
        foreach ($funciones->groupBy(fn ($f) => mb_strtolower(trim($f->titulo))) as $titulo => $grupo) {
            $variantes = $grupo
                ->map(fn ($f) => trim($f->titulo) . ' | ' . trim($f->genero) . ' | ' . $f->duracion_min)
                ->unique();

            if ($variantes->count() > 1) {
                $problemas[] = "Película «{$titulo}» con variantes: " . $variantes->implode(' ; ');
            }
        }

        // Mismo nombre de sala con capacidades distintas, o nombres que solo cambian en mayúsculas o espacios.
        foreach ($funciones->groupBy(fn ($f) => mb_strtolower(trim($f->sala))) as $nombre => $grupo) {
            $nombres = $grupo->map(fn ($f) => trim($f->sala))->unique();
            $capacidades = $grupo->pluck('capacidad_total')->unique();

            if ($nombres->count() > 1 || $capacidades->count() > 1) {
                $problemas[] = "Sala «{$nombre}»: nombres [" . $nombres->implode(', ')
                    . '] y capacidades [' . $capacidades->implode(', ') . ']';
            }
        }

        if ($problemas !== []) {
            throw new RuntimeException(
                "Migración detenida: hay datos ambiguos que deben resolverse a mano.\n- " . implode("\n- ", $problemas)
            );
        }
    }

    /**
     * Deshace solo los vínculos. Los catálogos creados se conservan: borrarlos podría
     * eliminar datos que el equipo agregó después. Al ejecutar up() otra vez se reutilizan.
     */
    public function down(): void
    {
        DB::table('Pelicula_Funcion')->update(['pelicula_id' => null, 'sala_id' => null]);
    }
};