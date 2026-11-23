<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pruebas de RF-01 (CRUD de funciones con catálogos de película y sala).
 * Cada prueba indica el caso del plan (CP-xx) y el criterio que verifica.
 */
class PeliculaFuncionCrudTest extends TestCase
{
    use RefreshDatabase;

    private Pelicula $pelicula;
    private Sala $sala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pelicula = Pelicula::create([
            'titulo'       => 'Órbita',
            'genero'       => 'Ciencia ficción',
            'duracion_min' => 120,
            'activa'       => true,
        ]);

        $this->sala = Sala::create([
            'nombre'    => 'Sala 1',
            'capacidad' => 80,
            'activa'    => true,
        ]);
    }

    // ------------------------------------------------------------------
    // Datos de apoyo
    // ------------------------------------------------------------------

    /** Datos que envía el formulario para programar una función. */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'pelicula_id'     => $this->pelicula->id,
            'sala_id'         => $this->sala->id,
            'fecha_hora'      => now()->addDays(2)->format('Y-m-d\TH:i'),
            'precio_unitario' => 15000,
        ], $cambios);
    }

    /** Crea una función directamente en la base de datos, con su instantánea. */
    private function crearFuncion(array $cambios = []): PeliculaFuncion
    {
        return PeliculaFuncion::create(array_merge([
            'pelicula_id'          => $this->pelicula->id,
            'sala_id'              => $this->sala->id,
            'titulo'               => $this->pelicula->titulo,
            'genero'               => $this->pelicula->genero,
            'duracion_min'         => $this->pelicula->duracion_min,
            'sala'                 => $this->sala->nombre,
            'fecha_hora'           => now()->addDays(3),
            'precio_unitario'      => 12000,
            'capacidad_total'      => $this->sala->capacidad,
            'asientos_disponibles' => $this->sala->capacidad,
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
            'monto_total'       => 24000,
            'estado'            => 'Confirmado',
        ]);
    }

    /** Datos para editar: los mismos de la función, con los cambios indicados. */
    private function datosEdicion(PeliculaFuncion $funcion, array $cambios = []): array
    {
        return array_merge([
            'pelicula_id'     => $funcion->pelicula_id,
            'sala_id'         => $funcion->sala_id,
            'fecha_hora'      => $funcion->fecha_hora->format('Y-m-d\TH:i'),
            'precio_unitario' => $funcion->precio_unitario,
            'estado'          => $funcion->estado,
        ], $cambios);
    }

    // ------------------------------------------------------------------
    // Proveedores de datos
    // ------------------------------------------------------------------

    public static function camposObligatorios(): array
    {
        return [
            'película'        => ['pelicula_id'],
            'sala'            => ['sala_id'],
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
            'CP-35 película inexistente'         => ['pelicula_id', 99999],
            'CP-36 sala inexistente'             => ['sala_id', 99999],
        ];
    }

    // ------------------------------------------------------------------
    // Consulta y registro
    // ------------------------------------------------------------------

    public function test_cp31_la_cartelera_carga(): void
    {
        $this->crearFuncion();

        $this->get(route('funciones.index'))
            ->assertOk()
            ->assertSee('Órbita');
    }

    public function test_cp01_registra_una_funcion_con_datos_validos(): void
    {
        $this->post(route('funciones.store'), $this->datosValidos())
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
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
    public function test_cp09_a_cp36_rechaza_valores_invalidos(string $campo, mixed $valor): void
    {
        $this->post(route('funciones.store'), $this->datosValidos([$campo => $valor]))
            ->assertSessionHasErrors($campo);

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_cp11_ca_01_2_acepta_el_precio_minimo_valido(): void
    {
        $this->post(route('funciones.store'), $this->datosValidos(['precio_unitario' => 0.01]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_cp18_el_servidor_copia_los_datos_del_catalogo_y_los_asientos_inician_en_la_capacidad(): void
    {
        $this->post(route('funciones.store'), $this->datosValidos());

        $funcion = PeliculaFuncion::firstOrFail();

        $this->assertEquals($this->pelicula->id, $funcion->pelicula_id);
        $this->assertEquals($this->sala->id, $funcion->sala_id);
        $this->assertSame('Órbita', $funcion->titulo);
        $this->assertSame('Ciencia ficción', $funcion->genero);
        $this->assertEquals(120, $funcion->duracion_min);
        $this->assertSame('Sala 1', $funcion->sala);
        $this->assertEquals(80, $funcion->capacidad_total);
        $this->assertEquals(80, $funcion->asientos_disponibles);
        $this->assertSame('Activa', $funcion->estado);
    }

    public function test_cp32_ignora_los_datos_derivados_que_envie_el_navegador(): void
    {
        $this->post(route('funciones.store'), $this->datosValidos([
            'titulo'               => 'Título manipulado',
            'capacidad_total'      => 9999,
            'asientos_disponibles' => 9999,
        ]));

        $funcion = PeliculaFuncion::firstOrFail();

        $this->assertSame('Órbita', $funcion->titulo);
        $this->assertEquals(80, $funcion->capacidad_total);
        $this->assertEquals(80, $funcion->asientos_disponibles);
    }

    public function test_cp33_rechaza_programar_con_una_pelicula_inactiva(): void
    {
        $this->pelicula->update(['activa' => false]);

        $this->post(route('funciones.store'), $this->datosValidos())
            ->assertSessionHasErrors('pelicula_id');

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_cp34_rechaza_programar_en_una_sala_inactiva(): void
    {
        $this->sala->update(['activa' => false]);

        $this->post(route('funciones.store'), $this->datosValidos())
            ->assertSessionHasErrors('sala_id');

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
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
    // Actualización
    // ------------------------------------------------------------------

    public function test_cp24_con_boletos_solo_cambia_el_precio_y_no_la_sala_ni_la_pelicula(): void
    {
        $funcion = $this->crearFuncion();
        $this->crearBoleto($funcion);
        $otraSala = Sala::create(['nombre' => 'Sala 2', 'capacidad' => 50, 'activa' => true]);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, [
            'sala_id'         => $otraSala->id,
            'precio_unitario' => 18000,
        ]))->assertSessionHasNoErrors();

        $funcion = $funcion->fresh();

        $this->assertEquals(18000, (float) $funcion->precio_unitario);
        $this->assertEquals($this->sala->id, $funcion->sala_id);
        $this->assertSame('Sala 1', $funcion->sala);
        $this->assertEquals(80, $funcion->capacidad_total);
    }

    public function test_cp25_sin_boletos_cambiar_de_sala_recalcula_capacidad_y_asientos(): void
    {
        $funcion = $this->crearFuncion();
        $otraSala = Sala::create(['nombre' => 'Sala 2', 'capacidad' => 50, 'activa' => true]);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['sala_id' => $otraSala->id]))
            ->assertSessionHasNoErrors();

        $funcion = $funcion->fresh();

        $this->assertSame('Sala 2', $funcion->sala);
        $this->assertEquals(50, $funcion->capacidad_total);
        $this->assertEquals(50, $funcion->asientos_disponibles);
    }

    public function test_cp26_no_modifica_una_funcion_con_boletos_que_ya_empezo(): void
    {
        $funcion = $this->crearFuncion(['fecha_hora' => now()->subDay()]);
        $this->crearBoleto($funcion);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['precio_unitario' => 18000]))
            ->assertSessionHasErrors('operacion');

        $this->assertEquals(12000, (float) $funcion->fresh()->precio_unitario);
    }

    public function test_cp27_permite_editar_el_precio_de_una_funcion_sin_boletos_cuya_fecha_ya_paso(): void
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

    public function test_cp37_no_reactiva_una_funcion_inactiva_que_ya_empezo(): void
    {
        $funcion = $this->crearFuncion(['fecha_hora' => now()->subDay(), 'estado' => 'Inactiva']);

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, ['estado' => 'Activa']))
            ->assertSessionHasErrors('operacion');

        $this->assertSame('Inactiva', $funcion->fresh()->estado);
    }
}
