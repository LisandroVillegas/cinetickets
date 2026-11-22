<?php

namespace Database\Factories;

use App\Models\Pelicula;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pelicula>
 */
class PeliculaFactory extends Factory
{
    protected $model = Pelicula::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo'       => 'Película ' . fake()->words(2, true),
            'genero'       => fake()->randomElement(['Acción', 'Comedia', 'Drama', 'Ciencia Ficción']),
            'duracion_min' => 120,
            'activa'       => true,
        ];
    }
}