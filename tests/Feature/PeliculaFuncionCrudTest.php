<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pruebas de RF-01 (CRUD de funciones adaptado al contrato v1 con catálogos).
 */
class PeliculaFuncionCrudTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(array $cambios = []): array
    {
        $pelicula = Pelicula::factory()->create([
            'titulo'       => 'Aventura en el Espacio',
            'genero'       => 'Ciencia ficción',
            'duracion_min' => 120,
        ]);

        $sala = Sala::factory()->create([
            'nombre'    => 'Sala 1',
            'capacidad' => 100,
        ]);

        return array_merge([
            'pelicula_id'     => $pelicula->id,
            'sala_id'         => $sala->id,
            'fecha_hora'      => now()->addDays(2)->format('Y-m-d H:i'),
            'precio_unitario' => '15000.00',
        ], $cambios);
    }

    private function crearFuncion(array $cambios = []): PeliculaFuncion
    {
        $pelicula = Pelicula::factory()->create([
            'titulo'       => 'La Casa del Lago',
            'genero'       => 'Drama',
            'duracion_min' => 105,
        ]);

        $sala = Sala::factory()->create([
            'nombre'    => 'Sala 2',
            'capacidad' => $cambios['capacidad_total'] ?? 100,
        ]);

        return PeliculaFuncion::create(array_merge([
            'pelicula_id'          => $pelicula->id,
            'sala_id'              => $sala->id,
            'titulo'               => $pelicula->titulo,
            'genero'               => $pelicula->genero,
            'duracion_min'         => $pelicula->duracion_min,
            'sala'                 => $sala->nombre,
            'fecha_hora'           => now()->addDays(3),
            'precio_unitario'      => 12000,
            'capacidad_total'      => $sala->capacidad,
            'asientos_disponibles' => $sala->capacidad,
            'estado'               => 'Activa',
        ], $cambios))->fresh();
    }

    private function crearBoleto(PeliculaFuncion $funcion): BoletoReserva
    {
        return BoletoReserva::create([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente de Prueba',
            'email_cliente'     => 'cliente.prueba@correo.test',
            'cantidad_entradas' => 2,
            'precio_unitario'   => 12000,
            'monto_total'       => 24000,
            'estado'            => 'Confirmado',
            'clave_operacion'   => (string) Str::uuid(),
        ]);
    }

    private function datosEdicion(PeliculaFuncion $funcion, array $cambios = []): array
    {
        return array_merge([
            'pelicula_id'     => $funcion->pelicula_id,
            'sala_id'         => $funcion->sala_id,
            'fecha_hora'      => $funcion->fecha_hora->format('Y-m-d H:i'),
            'precio_unitario' => (string) $funcion->precio_unitario,
        ], $cambios);
    }

    public static function camposObligatorios(): array
    {
        return [
            'pelicula_id'     => ['pelicula_id'],
            'sala_id'         => ['sala_id'],
            'fecha_hora'      => ['fecha_hora'],
            'precio_unitario' => ['precio_unitario'],
        ];
    }

    public static function valoresInvalidos(): array
    {
        return [
            'CP-09 precio igual a cero (límite)' => ['precio_unitario', 0],
            'CP-10 precio negativo'              => ['precio_unitario', -1],
            'CP-17 precio con texto'             => ['precio_unitario', 'abc'],
        ];
    }

    public function test_cp31_la_cartelera_carga(): void
    {
        $this->crearFuncion();

        $this->get(route('funciones.index'))
            ->assertOk()
            ->assertSee('La Casa del Lago');
    }

    public function test_cp01_registra_una_funcion_con_datos_validos(): void
    {
        $this->post(route('funciones.store'), $this->datosValidos())
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
        $this->assertDatabaseHas('Pelicula_Funcion', ['titulo' => 'Aventura en el Espacio']);
    }

    #[DataProvider('camposObligatorios')]
    public function test_cp02_a_cp08_ca_01_1_cada_campo_es_obligatorio(string $campo): void
    {
        $datos = $this->datosValidos();
        unset($datos[$campo]);

        $this->post(route('funciones.store'), $datos)->assertSessionHasErrors($campo);

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    #[DataProvider('valoresInvalidos')]
    public function test_cp09_a_cp17_ca_01_2_rechaza_valores_invalidos(string $campo, mixed $valor): void
    {
        $this->post(route('funciones.store'), $this->datosValidos([$campo => $valor]))
            ->assertSessionHasErrors($campo);

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_cp11_ca_01_2_acepta_precio_minimo_valido(): void
    {
        $this->post(route('funciones.store'), $this->datosValidos(['precio_unitario' => '0.01']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_cp18_ca_01_3_los_asientos_disponibles_inician_igual_a_la_capacidad_de_sala(): void
    {
        $sala = Sala::factory()->create(['capacidad' => 80]);

        $this->post(route('funciones.store'), $this->datosValidos(['sala_id' => $sala->id]));

        $funcion = PeliculaFuncion::firstOrFail();

        $this->assertEquals(80, $funcion->capacidad_total);
        $this->assertEquals(80, $funcion->asientos_disponibles);
        $this->assertSame('Activa', $funcion->estado);
    }

    public function test_cp19_ca_01_4_rechaza_una_fecha_pasada(): void
    {
        $fechaPasada = now()->subMinute()->format('Y-m-d H:i');

        $this->post(route('funciones.store'), $this->datosValidos(['fecha_hora' => $fechaPasada]))
            ->assertSessionHasErrors('fecha_hora');

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_cp20_ca_01_4_acepta_una_fecha_futura(): void
    {
        $fechaFutura = now()->addMinutes(10)->format('Y-m-d H:i');

        $this->post(route('funciones.store'), $this->datosValidos(['fecha_hora' => $fechaFutura]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_cp21_elimina_una_funcion_sin_boletos(): void
    {
        $funcion = $this->crearFuncion();

        $this->delete(route('funciones.destroy', $funcion))
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('Pelicula_Funcion', ['id_funcion' => $funcion->id_funcion]);
    }

    public function test_cp22_ca_01_5_no_elimina_una_funcion_con_boletos(): void
    {
        $funcion = $this->crearFuncion();
        $this->crearBoleto($funcion);

        $this->delete(route('funciones.destroy', $funcion))
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('Pelicula_Funcion', ['id_funcion' => $funcion->id_funcion]);
    }

    public function test_cp23_desactiva_una_funcion(): void
    {
        $funcion = $this->crearFuncion();

        $this->patch(route('funciones.desactivar', $funcion))
            ->assertRedirect(route('funciones.index'));

        $this->assertSame('Inactiva', $funcion->fresh()->estado);
    }

    public function test_cp26_recalcula_los_asientos_disponibles_al_cambiar_de_sala_sin_boletos(): void
    {
        $funcion   = $this->crearFuncion(['capacidad_total' => 100, 'asientos_disponibles' => 100]);
        $nuevaSala = Sala::factory()->create(['capacidad' => 50]);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['sala_id' => $nuevaSala->id]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(50, $funcion->fresh()->capacidad_total);
        $this->assertEquals(50, $funcion->fresh()->asientos_disponibles);
    }

    public function test_cp27_rechaza_editar_el_precio_de_una_funcion_cuya_fecha_ya_paso(): void
    {
        $funcion = $this->crearFuncion(['fecha_hora' => now()->subDay()]);
        $this->crearBoleto($funcion);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['precio_unitario' => '18000.00']))
            ->assertSessionHasErrors();

        $this->assertEquals(12000, (float) $funcion->fresh()->precio_unitario);
    }

    public function test_cp28_rechaza_cambiar_la_fecha_a_una_fecha_pasada(): void
    {
        $funcion = $this->crearFuncion();
        $fechaPasada = now()->subDays(2)->format('Y-m-d H:i');

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['fecha_hora' => $fechaPasada]))
            ->assertSessionHasErrors('fecha_hora');
    }
}