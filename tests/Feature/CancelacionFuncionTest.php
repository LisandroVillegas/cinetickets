<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de cancelación masiva de función y devolución atómica de aforo (Contrato v1, secciones 3 y 4).
 */
class CancelacionFuncionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancela_funcion_con_varios_boletos_y_devuelve_aforo_integro_una_sola_vez(): void
    {
        $funcion = PeliculaFuncion::factory()->create([
            'capacidad_total'      => 80,
            'asientos_disponibles' => 75,
            'estado'               => 'Activa',
        ]);

        $boleto1 = BoletoReserva::create([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente Uno',
            'email_cliente'     => 'uno@correo.test',
            'cantidad_entradas' => 2,
            'precio_unitario'   => 15000,
            'monto_total'       => 30000,
            'estado'            => 'Confirmado',
            'clave_operacion'   => (string) Str::uuid(),
        ]);

        $boleto2 = BoletoReserva::create([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente Dos',
            'email_cliente'     => 'dos@correo.test',
            'cantidad_entradas' => 3,
            'precio_unitario'   => 15000,
            'monto_total'       => 45000,
            'estado'            => 'Confirmado',
            'clave_operacion'   => (string) Str::uuid(),
        ]);

        // Cancelamos la función completa.
        $this->patch(route('funciones.cancelar', $funcion), [
            'motivo_anulacion' => 'Falla eléctrica en la sala',
        ])
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHas('success');

        $this->assertSame('Cancelada', $funcion->fresh()->estado);
        $this->assertEquals(80, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Cancelado', $boleto1->fresh()->estado);
        $this->assertSame('Cancelado', $boleto2->fresh()->estado);

        // Repetir la petición no duplica devolución ni altera el aforo.
        $this->patch(route('funciones.cancelar', $funcion), [
            'motivo_anulacion' => 'Reintento',
        ]);

        $this->assertEquals(80, $funcion->fresh()->asientos_disponibles);
    }

    public function test_no_permite_cancelar_funcion_sin_motivo_ni_funcion_pasada(): void
    {
        $funcionFutura = PeliculaFuncion::factory()->create([
            'fecha_hora' => now()->addDays(2),
            'estado'     => 'Activa',
        ]);

        $this->patch(route('funciones.cancelar', $funcionFutura), [
            'motivo_anulacion' => '',
        ])->assertSessionHasErrors('motivo_anulacion');

        $this->assertSame('Activa', $funcionFutura->fresh()->estado);

        $funcionPasada = PeliculaFuncion::factory()->create([
            'fecha_hora' => now()->subHour(),
            'estado'     => 'Activa',
        ]);

        $this->patch(route('funciones.cancelar', $funcionPasada), [
            'motivo_anulacion' => 'Intento tardío',
        ])->assertSessionHasErrors();

        $this->assertSame('Activa', $funcionPasada->fresh()->estado);
    }
}