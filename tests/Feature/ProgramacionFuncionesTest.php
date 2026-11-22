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
 * Pruebas de programación individual y por lotes con pausa de 15 minutos (Contrato v1, sección 3).
 */
class ProgramacionFuncionesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_lote_de_dos_fechas_por_dos_horarios_crea_cuatro_funciones_independientes(): void
    {
        $pelicula = Pelicula::factory()->create(['duracion_min' => 120]);
        $sala     = Sala::factory()->create(['capacidad' => 80]);

        $fecha1 = now()->addDays(2)->format('Y-m-d');
        $fecha2 = now()->addDays(3)->format('Y-m-d');

        $this->post(route('funciones.programacion.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fechas'          => [$fecha1, $fecha2],
            'horarios'        => ['15:00', '19:00'],
            'precio_unitario' => '15000.00',
        ])
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 4);
    }

    public function test_permite_mismo_horario_en_salas_distintas(): void
    {
        $pelicula = Pelicula::factory()->create(['duracion_min' => 120]);
        $sala1    = Sala::factory()->create(['nombre' => 'Sala 1']);
        $sala2    = Sala::factory()->create(['nombre' => 'Sala 2']);
        $fecha    = now()->addDays(2)->format('Y-m-d');

        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala1->id,
            'fecha_hora'      => "{$fecha} 18:00",
            'precio_unitario' => '15000.00',
        ])->assertSessionHasNoErrors();

        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala2->id,
            'fecha_hora'      => "{$fecha} 18:00",
            'precio_unitario' => '15000.00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 2);
    }

    public function test_respeta_pausa_de_15_minutos_entre_funciones(): void
    {
        $pelicula = Pelicula::factory()->create(['duracion_min' => 120]);
        $sala     = Sala::factory()->create();
        $fecha    = now()->addDays(2)->format('Y-m-d');

        // Primera función a las 18:00 (dura 120 min + 15 min pausa = ocupa hasta 20:15).
        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fecha_hora'      => "{$fecha} 18:00",
            'precio_unitario' => '15000.00',
        ])->assertSessionHasNoErrors();

        // A las 20:14 debe rechazar por solapamiento.
        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fecha_hora'      => "{$fecha} 20:14",
            'precio_unitario' => '15000.00',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);

        // A las 20:15 exactas debe permitir.
        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fecha_hora'      => "{$fecha} 20:15",
            'precio_unitario' => '15000.00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 2);
    }

    public function test_detecta_colision_al_cruzar_medianoche(): void
    {
        $pelicula = Pelicula::factory()->create(['duracion_min' => 120]);
        $sala     = Sala::factory()->create();
        $dia1     = now()->addDays(2)->format('Y-m-d');
        $dia2     = now()->addDays(3)->format('Y-m-d');

        // Inicia 23:00 del día 1 + 120 min + 15 min = termina 01:15 del día 2.
        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fecha_hora'      => "{$dia1} 23:00",
            'precio_unitario' => '15000.00',
        ])->assertSessionHasNoErrors();

        // Intentar programar a la 01:00 del día 2 debe chocar.
        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fecha_hora'      => "{$dia2} 01:00",
            'precio_unitario' => '15000.00',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_si_una_fila_del_lote_tiene_conflicto_no_guarda_ninguna(): void
    {
        $pelicula = Pelicula::factory()->create(['duracion_min' => 120]);
        $sala     = Sala::factory()->create();
        $fecha1   = now()->addDays(2)->format('Y-m-d');
        $fecha2   = now()->addDays(3)->format('Y-m-d');

        // Ocupamos previamente el horario de las 19:00 en fecha2.
        PeliculaFuncion::factory()->create([
            'pelicula_id' => $pelicula->id,
            'sala_id'     => $sala->id,
            'fecha_hora'  => "{$fecha2} 19:00:00",
        ]);

        // Intentamos lote de 4 combinaciones donde 1 choca: debe insertar 0 nuevas.
        $this->post(route('funciones.programacion.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fechas'          => [$fecha1, $fecha2],
            'horarios'        => ['15:00', '19:00'],
            'precio_unitario' => '15000.00',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_rechaza_fechas_o_horarios_duplicados_y_mas_de_100_combinaciones(): void
    {
        $pelicula = Pelicula::factory()->create();
        $sala     = Sala::factory()->create();
        $fecha    = now()->addDays(2)->format('Y-m-d');

        // Horarios repetidos en el lote.
        $this->post(route('funciones.programacion.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fechas'          => [$fecha],
            'horarios'        => ['18:00', '18:00'],
            'precio_unitario' => '15000.00',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_no_permite_programar_con_catalogo_inactivo_pero_conserva_funciones_previas(): void
    {
        $pelicula = Pelicula::factory()->create(['activa' => true]);
        $sala     = Sala::factory()->create(['activa' => true]);

        $funcionPrevia = PeliculaFuncion::factory()->create([
            'pelicula_id' => $pelicula->id,
            'sala_id'     => $sala->id,
        ]);

        // Inactivamos la película.
        $pelicula->update(['activa' => false]);

        $this->post(route('funciones.store'), [
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fecha_hora'      => now()->addDays(5)->format('Y-m-d H:i'),
            'precio_unitario' => '15000.00',
        ])->assertSessionHasErrors('pelicula_id');

        $this->assertDatabaseHas('Pelicula_Funcion', ['id_funcion' => $funcionPrevia->id_funcion]);
    }

    public function test_bloquea_cambio_de_programacion_si_tiene_boletos_historicos(): void
    {
        $funcion      = PeliculaFuncion::factory()->create();
        $otraPelicula = Pelicula::factory()->create();
        $otraSala     = Sala::factory()->create();

        BoletoReserva::create([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente Histórico',
            'email_cliente'     => 'historico@correo.test',
            'cantidad_entradas' => 1,
            'precio_unitario'   => 15000,
            'monto_total'       => 15000,
            'estado'            => 'Confirmado',
            'clave_operacion'   => (string) Str::uuid(),
        ]);

        $this->put(route('funciones.update', $funcion), [
            'pelicula_id'     => $otraPelicula->id,
            'sala_id'         => $otraSala->id,
            'fecha_hora'      => now()->addDays(6)->format('Y-m-d H:i'),
            'precio_unitario' => '15000.00',
        ])->assertSessionHasErrors();

        $this->assertEquals($funcion->pelicula_id, $funcion->fresh()->pelicula_id);
        $this->assertEquals($funcion->sala_id, $funcion->fresh()->sala_id);
    }
}