<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas de RF-04 (anulación de boletos con motivo obligatorio según contrato v1).
 */
class AnulacionBoletosTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function crearFuncion(array $cambios = []): PeliculaFuncion
    {
        $pelicula = Pelicula::factory()->create([
            'titulo'       => 'Función de prueba',
            'genero'       => 'Drama',
            'duracion_min' => 100,
        ]);

        $sala = Sala::factory()->create([
            'nombre'    => 'Sala 1',
            'capacidad' => $cambios['capacidad_total'] ?? 60,
        ]);

        return PeliculaFuncion::create(array_merge([
            'pelicula_id'          => $pelicula->id,
            'sala_id'              => $sala->id,
            'titulo'               => $pelicula->titulo,
            'genero'               => $pelicula->genero,
            'duracion_min'         => $pelicula->duracion_min,
            'sala'                 => $sala->nombre,
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
            'precio_unitario'   => 10000,
            'monto_total'       => 30000,
            'estado'            => 'Confirmado',
            'clave_operacion'   => (string) Str::uuid(),
        ], $cambios))->fresh();
    }

    public function test_ca_04_1_y_ca_04_2_anula_el_boleto_con_motivo_y_devuelve_los_asientos(): void
    {
        $funcion = $this->crearFuncion([
            'capacidad_total'      => 60,
            'asientos_disponibles' => 50,
        ]);
        $boleto = $this->crearBoleto($funcion, [
            'cantidad_entradas' => 3,
            'monto_total'       => 30000,
        ]);

        $this->patch(route('boletos.anular', $boleto), [
            'motivo_anulacion' => 'Cliente solicitó cancelación por viaje',
        ])
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('success');

        $boleto->refresh();

        $this->assertSame('Cancelado', $boleto->estado);
        $this->assertSame('Cliente solicitó cancelación por viaje', $boleto->motivo_anulacion);
        $this->assertNotNull($boleto->anulada_en);
        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
    }

    public function test_exige_motivo_de_anulacion(): void
    {
        $funcion = $this->crearFuncion(['asientos_disponibles' => 50]);
        $boleto = $this->crearBoleto($funcion, ['cantidad_entradas' => 3]);

        $this->patch(route('boletos.anular', $boleto), [
            'motivo_anulacion' => '',
        ])->assertSessionHasErrors('motivo_anulacion');

        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }

    public function test_ca_04_3_rechaza_anular_dos_veces_el_mismo_boleto(): void
    {
        $funcion = $this->crearFuncion(['asientos_disponibles' => 50]);
        $boleto = $this->crearBoleto($funcion, ['cantidad_entradas' => 3]);

        $this->patch(route('boletos.anular', $boleto), ['motivo_anulacion' => 'Motivo 1'])
            ->assertSessionHas('success');

        $this->patch(route('boletos.anular', $boleto), ['motivo_anulacion' => 'Motivo 2'])
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('error');

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

        $this->patch(route('boletos.anular', $boletoUno), ['motivo_anulacion' => 'Error en compra'])
            ->assertSessionHas('success');

        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Cancelado', $boletoUno->fresh()->estado);
        $this->assertSame('Confirmado', $boletoDos->fresh()->estado);
    }

    public function test_decision_3_permite_anular_una_funcion_inactiva_pero_futura(): void
    {
        $funcion = $this->crearFuncion([
            'estado'     => 'Inactiva',
            'fecha_hora' => now()->addDay(),
        ]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto), ['motivo_anulacion' => 'Solicitud del cliente'])
            ->assertSessionHas('success');

        $this->assertEquals(53, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Cancelado', $boleto->fresh()->estado);
    }

    public function test_decision_3_rechaza_una_funcion_que_ya_comenzo(): void
    {
        $funcion = $this->crearFuncion(['fecha_hora' => now()->subMinute()]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto), ['motivo_anulacion' => 'Tarde'])
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('error');

        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }

    public function test_decision_3_rechaza_la_funcion_exactamente_en_el_instante_actual(): void
    {
        $instante = Carbon::parse('2026-11-20 18:00:00');
        Carbon::setTestNow($instante);

        $funcion = $this->crearFuncion(['fecha_hora' => $instante->copy()]);
        $boleto = $this->crearBoleto($funcion);

        $this->patch(route('boletos.anular', $boleto), ['motivo_anulacion' => 'En punto'])
            ->assertSessionHas('error');

        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }

    public function test_devuelve_404_si_el_boleto_no_existe(): void
    {
        $this->patch(route('boletos.anular', 999999), ['motivo_anulacion' => 'No existe'])
            ->assertNotFound();
    }

    public function test_rechaza_una_devolucion_que_supere_la_capacidad(): void
    {
        $funcion = $this->crearFuncion([
            'capacidad_total'      => 60,
            'asientos_disponibles' => 59,
        ]);
        $boleto = $this->crearBoleto($funcion, ['cantidad_entradas' => 3]);

        $this->patch(route('boletos.anular', $boleto), ['motivo_anulacion' => 'Inconsistente'])
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHas('error');

        $this->assertEquals(59, $funcion->fresh()->asientos_disponibles);
        $this->assertSame('Confirmado', $boleto->fresh()->estado);
    }
}