<?php

namespace Database\Factories;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoletoReserva>
 */
class BoletoFactory extends Factory
{
    protected $model = BoletoReserva::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $funcion  = PeliculaFuncion::factory()->create();
        $cantidad = 2;
        $precio   = (float) $funcion->precio_unitario;

        return [
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente Prueba ' . fake()->numberBetween(1, 500),
            'email_cliente'     => 'cliente' . fake()->unique()->numberBetween(1, 9999) . '@example.test',
            'cantidad_entradas' => $cantidad,
            'precio_unitario'   => number_format($precio, 2, '.', ''),
            'monto_total'       => number_format($precio * $cantidad, 2, '.', ''),
            'estado'            => 'Confirmado',
            'clave_operacion'   => (string) Str::uuid(),
            'anulada_en'        => null,
            'motivo_anulacion'  => null,
        ];
    }
}