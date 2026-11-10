<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pruebas de RF-01 (CRUD de funciones).
 * Cada prueba indica el caso del plan (CP-xx en docs/plan-de-pruebas.md)
 * y el criterio de aceptación (CA-xx.x) que verifica.
 */
class PeliculaFuncionCrudTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Datos de apoyo
    // ------------------------------------------------------------------

    /** Datos válidos para registrar una función. */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'titulo'          => 'Aventura en el Espacio',
            'genero'          => 'Ciencia ficción',
            'duracion_min'    => 120,
            'sala'            => 'Sala 1',
            'fecha_hora'      => now()->addDays(2)->format('Y-m-d\TH:i'),
            'precio_unitario' => 15000,
            'capacidad_total' => 100,
        ], $cambios);
    }

    /** Crea una función directamente en la base de datos. */
    private function crearFuncion(array $cambios = []): PeliculaFuncion
    {
        return PeliculaFuncion::create(array_merge([
            'titulo'               => 'La Casa del Lago',
            'genero'               => 'Drama',
            'duracion_min'         => 105,
            'sala'                 => 'Sala 2',
            'fecha_hora'           => now()->addDays(3),
            'precio_unitario'      => 12000,
            'capacidad_total'      => 100,
            'asientos_disponibles' => 100,
            'estado'               => 'Activa',
        ], $cambios))->fresh();
    }

    /** Crea un boleto de una función. */
    private function crearBoleto(PeliculaFuncion $funcion): BoletoReserva
    {
        return BoletoReserva::create([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente de Prueba',
            'email_cliente'     => 'cliente.prueba@correo.test',
            'cantidad_entradas' => 2,
            'monto_total'       => 24000,
            'estado'            => 'Confirmado',
        ]);
    }

    /** Datos para editar: los mismos de la función, con los cambios indicados. */
    private function datosEdicion(PeliculaFuncion $funcion, array $cambios = []): array
    {
        return array_merge([
            'titulo'          => $funcion->titulo,
            'genero'          => $funcion->genero,
            'duracion_min'    => $funcion->duracion_min,
            'sala'            => $funcion->sala,
            'fecha_hora'      => $funcion->fecha_hora->format('Y-m-d\TH:i'),
            'precio_unitario' => $funcion->precio_unitario,
            'capacidad_total' => $funcion->capacidad_total,
            'estado'          => $funcion->estado,
        ], $cambios);
    }

    // ------------------------------------------------------------------
    // Proveedores de datos (partición de equivalencia y valores límite)
    // ------------------------------------------------------------------

    public static function camposObligatorios(): array
    {
        return [
            'titulo'          => ['titulo'],
            'genero'          => ['genero'],
            'duracion_min'    => ['duracion_min'],
            'sala'            => ['sala'],
            'fecha_hora'      => ['fecha_hora'],
            'precio_unitario' => ['precio_unitario'],
            'capacidad_total' => ['capacidad_total'],
        ];
    }

    public static function valoresInvalidos(): array
    {
        return [
            'CP-09 precio igual a cero (límite)' => ['precio_unitario', 0],
            'CP-10 precio negativo'              => ['precio_unitario', -1],
            'CP-12 capacidad igual a cero'       => ['capacidad_total', 0],
            'CP-13 capacidad negativa'           => ['capacidad_total', -5],
            'CP-15 capacidad no entera'          => ['capacidad_total', 10.5],
            'CP-16 duración igual a cero'        => ['duracion_min', 0],
            'CP-17 precio con texto'             => ['precio_unitario', 'abc'],
        ];
    }

    public static function limitesValidos(): array
    {
        return [
            'CP-11 precio mínimo 0.01'  => ['precio_unitario', 0.01],
            'CP-14 capacidad mínima 1'  => ['capacidad_total', 1],
            'duración mínima 1'         => ['duracion_min', 1],
        ];
    }

    // ------------------------------------------------------------------
    // Consulta
    // ------------------------------------------------------------------

    public function test_cp31_la_cartelera_carga(): void
    {
        $this->crearFuncion();

        $this->get(route('funciones.index'))
            ->assertOk()
            ->assertSee('La Casa del Lago');
    }

    // ------------------------------------------------------------------
    // Registro: CA-01.1, CA-01.2, CA-01.3 y CA-01.4
    // ------------------------------------------------------------------

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

    #[DataProvider('limitesValidos')]
    public function test_cp11_cp14_ca_01_2_acepta_los_valores_limite_validos(string $campo, mixed $valor): void
    {
        $this->post(route('funciones.store'), $this->datosValidos([$campo => $valor]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_cp18_ca_01_3_los_asientos_disponibles_inician_igual_a_la_capacidad(): void
    {
        $this->post(route('funciones.store'), $this->datosValidos(['capacidad_total' => 80]));

        $funcion = PeliculaFuncion::firstOrFail();

        $this->assertEquals(80, $funcion->capacidad_total);
        $this->assertEquals(80, $funcion->asientos_disponibles);
        $this->assertSame('Activa', $funcion->estado);
    }

    public function test_cp19_ca_01_4_rechaza_una_fecha_pasada(): void
    {
        $fechaPasada = now()->subMinute()->format('Y-m-d\TH:i');

        $this->post(route('funciones.store'), $this->datosValidos(['fecha_hora' => $fechaPasada]))
            ->assertSessionHasErrors('fecha_hora');

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_cp20_ca_01_4_acepta_una_fecha_futura(): void
    {
        $fechaFutura = now()->addMinutes(10)->format('Y-m-d\TH:i');

        $this->post(route('funciones.store'), $this->datosValidos(['fecha_hora' => $fechaFutura]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    // ------------------------------------------------------------------
    // Eliminación y desactivación: CA-01.5
    // ------------------------------------------------------------------

    public function test_cp21_elimina_una_funcion_sin_boletos(): void
    {
        $funcion = $this->crearFuncion();

        $this->delete(route('funciones.destroy', $funcion))
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHas('exito');

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

    // ------------------------------------------------------------------
    // Actualización: Decisión 2 (capacidad vs. asientos vendidos)
    // ------------------------------------------------------------------

    public function test_cp24_rechaza_una_capacidad_menor_que_los_asientos_vendidos(): void
    {
        // Capacidad 100 con 70 disponibles => 30 vendidos.
        $funcion = $this->crearFuncion(['capacidad_total' => 100, 'asientos_disponibles' => 70]);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['capacidad_total' => 20]))
            ->assertSessionHasErrors('capacidad_total');

        $this->assertEquals(100, $funcion->fresh()->capacidad_total);
        $this->assertEquals(70, $funcion->fresh()->asientos_disponibles);
    }

    public function test_cp25_capacidad_igual_a_los_vendidos_deja_cero_disponibles(): void
    {
        $funcion = $this->crearFuncion(['capacidad_total' => 100, 'asientos_disponibles' => 70]);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['capacidad_total' => 30]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(0, $funcion->fresh()->asientos_disponibles);
    }

    public function test_cp26_recalcula_los_asientos_disponibles_al_cambiar_la_capacidad(): void
    {
        $funcion = $this->crearFuncion(['capacidad_total' => 100, 'asientos_disponibles' => 70]);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['capacidad_total' => 50]))
            ->assertSessionHasNoErrors();

        // 50 de capacidad menos 30 vendidos => 20 disponibles.
        $this->assertEquals(20, $funcion->fresh()->asientos_disponibles);
    }

    // ------------------------------------------------------------------
    // Actualización: regla de la fecha al editar
    // ------------------------------------------------------------------

    public function test_cp27_permite_editar_el_precio_de_una_funcion_cuya_fecha_ya_paso(): void
    {
        $funcion = $this->crearFuncion(['fecha_hora' => now()->subDay()]);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['precio_unitario' => 18000]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(18000, (float) $funcion->fresh()->precio_unitario);
    }

    public function test_cp28_rechaza_cambiar_la_fecha_a_una_fecha_pasada(): void
    {
        $funcion = $this->crearFuncion();
        $fechaPasada = now()->subDays(2)->format('Y-m-d\TH:i');

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['fecha_hora' => $fechaPasada]))
            ->assertSessionHasErrors('fecha_hora');
    }
}
