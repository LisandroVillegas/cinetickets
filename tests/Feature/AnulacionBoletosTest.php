<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pruebas de RF-04 (anulación de boletos y devolución de asientos).
 * Verifican los criterios CA-04.1 a CA-04.3 y la Decisión 3.
 */
class AnulacionBoletosTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Datos de apoyo
    // ------------------------------------------------------------------

    private function crearFuncion(array $cambios = []): PeliculaFuncion
    {
        return PeliculaFuncion::create(array_merge([
            'titulo'               => 'Función de prueba',
            'genero'               => 'Drama',
            'duracion_min'         => 100,
            'sala'                 => 'Sala 1',
            'fecha_hora'           => now()->addDay(),
            'precio_unitario'      => 10000,
            'capacidad_total'      => 60,
            'asientos_disponibles' => 50,
            'estado'               => 'Activa',
        ], $cambios))->fresh();
    }

    private function crearBoleto(PeliculaFuncion $funcion, array $cambios = []): BoletoReserva
    {
        return BoletoReserva::create(array_merge([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Ana Torres',
            'email_cliente'     => 'ana@correo.test',
            'cantidad_entradas' => 3,
            'monto_total'       => 30000,
            'estado'            => 'Confirmado',
        ], $cambios))->fresh();
    }

    // ------------------------------------------------------------------
    // CA-04.1 y CA-04.2: cancelar y devolver asientos una sola vez
    // ------------------------------------------------------------------

    public function test_ca_04_1_y_ca_04_2_anula_el_boleto_y_devuelve_los_asientos(): void
    {
        $funcion = $this->crearFuncion([
            'capacidad_total'      => 60,
            'asientos_disponibles' => 50,
        ]);
        $boleto = $this->crearBoleto($funcion, [
            'cantidad_entradas' => 3,
            'monto_total'       => 30000,
        ]);

        $this->patch(route('boletos.anular', $boleto))
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('exito', 'Boleto anulado correctamente. Los asientos quedaron disponibles.');

        $boleto->refresh();

        $this->assertSame('Cancelado', $boleto->estado);
        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
        $this->assertDatabaseHas('Boleto_Reserva', [
            'id_boleto' => $boleto->id_boleto,
            'estado'    => 'Cancelado',
        ]);

        // El registro se conserva con los datos de la compra.
        $this->assertSame('Ana Torres', $boleto->nombre_cliente);
        $this->assertSame('ana@correo.test', $boleto->email_cliente);
        $this->assertEquals(3, $boleto->cantidad_entradas);
        $this->assertEquals(30000.0, (float) $boleto->monto_total);
        $this->assertNotNull($boleto->fecha_compra);
    }

    public function test_ca_04_3_rechaza_anular_dos_veces_el_mismo_boleto(): void
    {
        $funcion = $this->crearFuncion(['asientos_disponibles' => 50]);
        $boleto = $this->crearBoleto($funcion, ['cantidad_entradas' => 3]);

        $this->patch(route('boletos.anular', $boleto))
            ->assertSessionHas('exito');

        $this->patch(route('boletos.anular', $boleto))
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('error', 'El boleto ya está cancelado. No se devolvieron asientos nuevamente.');

        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Cancelado', $boleto->fresh()->estado);
    }

    public function test_ca_04_2_solo_devuelve_los_asientos_del_boleto_anulado(): void
    {
        $funcion = $this->crearFuncion([
            'capacidad_total'      => 60,
            'asientos_disponibles' => 50,
        ]);
        $boletoUno = $this->crearBoleto($funcion, [
            'nombre_cliente'    => 'Ana Torres',
            'cantidad_entradas' => 3,
        ]);
        $boletoDos = $this->crearBoleto($funcion, [
            'nombre_cliente'    => 'Luis Pérez',
            'email_cliente'     => 'luis@correo.test',
            'cantidad_entradas' => 2,
        ]);

        $this->patch(route('boletos.anular', $boletoUno))
            ->assertSessionHas('exito');

        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Cancelado', $boletoUno->fresh()->estado);
        $this->assertSame('Confirmado', $boletoDos->fresh()->estado);
    }

    // ------------------------------------------------------------------
    // Decisión 3: solo funciones que aún no han comenzado
    // ------------------------------------------------------------------

    public function test_decision_3_permite_anular_una_funcion_inactiva_pero_futura(): void
    {
        $funcion = $this->crearFuncion([
            'estado'     => 'Inactiva',
            'fecha_hora' => now()->addDay(),
        ]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto))
            ->assertSessionHas('exito');

        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Cancelado', $boleto->fresh()->estado);
    }

    public function test_decision_3_rechaza_una_funcion_que_ya_comenzo(): void
    {
        $funcion = $this->crearFuncion(['fecha_hora' => now()->subMinute()]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto))
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('error', 'No se puede anular un boleto de una función que ya comenzó.');

        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }

    public function test_decision_3_rechaza_la_funcion_exactamente_en_el_instante_actual(): void
    {
        $instante = Carbon::parse('2026-11-20 18:00:00');
        Carbon::setTestNow($instante);

        $funcion = $this->crearFuncion(['fecha_hora' => $instante->copy()]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto))
            ->assertSessionHas('error');

        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }

    public function test_decision_3_permite_anular_un_segundo_despues_del_inicio(): void
    {
        $instante = Carbon::parse('2026-11-20 18:00:00');
        Carbon::setTestNow($instante);

        $funcion = $this->crearFuncion([
            'fecha_hora' => $instante->copy()->addSecond(),
        ]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto))
            ->assertSessionHas('exito');

        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Cancelado', $boleto->fresh()->estado);
    }

    public function test_decision_3_rechaza_un_segundo_antes_del_inicio(): void
    {
        $instante = Carbon::parse('2026-11-20 18:00:00');
        Carbon::setTestNow($instante);

        $funcion = $this->crearFuncion([
            'fecha_hora' => $instante->copy()->subSecond(),
        ]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto))
            ->assertSessionHas('error');

        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }

    // ------------------------------------------------------------------
    // Manejo de errores e integridad
    // ------------------------------------------------------------------

    public function test_devuelve_404_si_el_boleto_no_existe(): void
    {
        $this->patch(route('boletos.anular', 999999))->assertNotFound();
    }

    public function test_rechaza_una_devolucion_que_supere_la_capacidad(): void
    {
        $funcion = $this->crearFuncion([
            'capacidad_total'      => 60,
            'asientos_disponibles' => 59,
        ]);
        $boleto = $this->crearBoleto($funcion, ['cantidad_entradas' => 3]);

        $this->patch(route('boletos.anular', $boleto))
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('error', 'No se puede anular: los cupos registrados son inconsistentes. Solicita su revisión.');

        $this->assertEquals(59, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }

    public function test_rechaza_un_boleto_con_cantidad_invalida(): void
    {
        $funcion = $this->crearFuncion(['asientos_disponibles' => 50]);
        $boleto = $this->crearBoleto($funcion, [
            'cantidad_entradas' => 0,
            'monto_total'       => 0,
        ]);

        $this->patch(route('boletos.anular', $boleto))
            ->assertSessionHas('error');

        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }
}
