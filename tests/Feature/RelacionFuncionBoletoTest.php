<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de la relación 1:N entre Pelicula_Funcion y Boleto_Reserva.
 */
class RelacionFuncionBoletoTest extends TestCase
{
    use RefreshDatabase;

    private function crearFuncion(): PeliculaFuncion
    {
        return PeliculaFuncion::create([
            'titulo'               => 'Risas de Barrio',
            'genero'               => 'Comedia',
            'duracion_min'         => 95,
            'sala'                 => 'Sala 3',
            'fecha_hora'           => now()->addDays(4),
            'precio_unitario'      => 10000,
            'capacidad_total'      => 60,
            'asientos_disponibles' => 60,
            'estado'               => 'Activa',
        ]);
    }

    private function crearBoleto(PeliculaFuncion $funcion, string $correo): BoletoReserva
    {
        return BoletoReserva::create([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente de Prueba',
            'email_cliente'     => $correo,
            'cantidad_entradas' => 1,
            'monto_total'       => 10000,
            'estado'            => 'Confirmado',
        ]);
    }

    public function test_cp29_una_funcion_tiene_muchos_boletos_y_cada_boleto_pertenece_a_su_funcion(): void
    {
        $funcion = $this->crearFuncion();
        $this->crearBoleto($funcion, 'uno@correo.test');
        $this->crearBoleto($funcion, 'dos@correo.test');

        $this->assertCount(2, $funcion->boletos);
        $this->assertTrue($funcion->boletos->first()->funcion->is($funcion));
    }

    public function test_cp30_la_base_de_datos_impide_borrar_una_funcion_con_boletos(): void
    {
        $funcion = $this->crearFuncion();
        $this->crearBoleto($funcion, 'uno@correo.test');

        $this->expectException(QueryException::class);

        $funcion->delete();
    }
}
