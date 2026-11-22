<?php

namespace Database\Factories;

use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PeliculaFuncion>
 */
class FuncionFactory extends Factory
{
    protected $model = PeliculaFuncion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pelicula = Pelicula::factory()->create();
        $sala     = Sala::factory()->create();

        return [
            'pelicula_id'          => $pelicula->id,
            'sala_id'              => $sala->id,
            'titulo'               => $pelicula->titulo,
            'genero'               => $pelicula->genero,
            'duracion_min'         => $pelicula->duracion_min,
            'sala'                 => $sala->nombre,
            'fecha_hora'           => now()->addDays(2)->setTime(18, 0, 0),
            'precio_unitario'      => '15000.00',
            'capacidad_total'      => $sala->capacidad,
            'asientos_disponibles' => $sala->capacidad,
            'estado'               => 'Activa',
        ];
    }
}