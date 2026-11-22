<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de idempotencia de venta y conservación de precio histórico (Contrato v1, sección 4).
 */
class IdempotenciaVentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_reintentar_misma_clave_y_mismos_datos_devuelve_mismo_boleto_sin_descontar_doble(): void
    {
        $funcion = PeliculaFuncion::factory()->create([
            'capacidad_total'      => 50,
            'asientos_disponibles' => 50,
            'precio_unitario'      => '15000.00',
        ]);

        $clave = (string) Str::uuid();
        $payload = [
            'id_funcion'        => $funcion->id_funcion,
            'cantidad_entradas' => 2,
            'nombre_cliente'    => 'Carlos Ruiz',
            'email_cliente'     => 'carlos@correo.test',
            'clave_operacion'   => $clave,
        ];

        $this->post(route('ventas.store'), $payload)->assertSessionHasNoErrors();
        $boletoUno = BoletoReserva::firstOrFail();

        // Segundo envío idéntico (ej. doble clic o reintento de red).
        $respuestaDos = $this->post(route('ventas.store'), $payload);
        $respuestaDos->assertRedirect(route('boletos.show', $boletoUno))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Boleto_Reserva', 1);
        $this->assertEquals(48, $funcion->fresh()->asientos_disponibles);
    }

    public function test_misma_clave_con_datos_distintos_rechaza_sin_crear_otra_venta(): void
    {
        $funcion = PeliculaFuncion::factory()->create([
            'capacidad_total'      => 50,
            'asientos_disponibles' => 50,
        ]);

        $clave = (string) Str::uuid();

        $this->post(route('ventas.store'), [
            'id_funcion'        => $funcion->id_funcion,
            'cantidad_entradas' => 2,
            'nombre_cliente'    => 'Carlos Ruiz',
            'email_cliente'     => 'carlos@correo.test',
            'clave_operacion'   => $clave,
        ])->assertSessionHasNoErrors();

        // Misma clave pero distinta cantidad o cliente.
        $this->post(route('ventas.store'), [
            'id_funcion'        => $funcion->id_funcion,
            'cantidad_entradas' => 4,
            'nombre_cliente'    => 'Otra Persona',
            'email_cliente'     => 'otra@correo.test',
            'clave_operacion'   => $clave,
        ])->assertSessionHasErrors('clave_operacion');

        $this->assertDatabaseCount('Boleto_Reserva', 1);
        $this->assertEquals(48, $funcion->fresh()->asientos_disponibles);
    }

    public function test_cambiar_precio_de_funcion_no_altera_comprobantes_antiguos(): void
    {
        $funcion = PeliculaFuncion::factory()->create([
            'precio_unitario'      => '15000.00',
            'capacidad_total'      => 80,
            'asientos_disponibles' => 80,
        ]);

        $this->post(route('ventas.store'), [
            'id_funcion'        => $funcion->id_funcion,
            'cantidad_entradas' => 2,
            'nombre_cliente'    => 'Elena Gómez',
            'email_cliente'     => 'elena@correo.test',
            'clave_operacion'   => (string) Str::uuid(),
        ])->assertSessionHasNoErrors();

        $boletoAntiguo = BoletoReserva::firstOrFail();

        // Cambiamos precio de la función a 22000.00.
        $this->put(route('funciones.update', $funcion), [
            'pelicula_id'     => $funcion->pelicula_id,
            'sala_id'         => $funcion->sala_id,
            'fecha_hora'      => $funcion->fecha_hora->format('Y-m-d H:i'),
            'precio_unitario' => '22000.00',
        ])->assertSessionHasNoErrors();

        $boletoAntiguo->refresh();

        $this->assertEquals(15000.00, (float) $boletoAntiguo->precio_unitario);
        $this->assertEquals(30000.00, (float) $boletoAntiguo->monto_total);
    }
}