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
 * Pruebas de RF-02 (venta y emisión de boletos adaptado al contrato v1).
 */
class VentaBoletosTest extends TestCase
{
    use RefreshDatabase;

    private function crearFuncion(array $cambios = []): PeliculaFuncion
    {
        $pelicula = Pelicula::factory()->create([
            'titulo'       => $cambios['titulo'] ?? 'Aventura en el Espacio',
            'genero'       => 'Ciencia ficción',
            'duracion_min' => 120,
        ]);

        $sala = Sala::factory()->create([
            'nombre'    => 'Sala 1',
            'capacidad' => $cambios['capacidad_total'] ?? 100,
        ]);

        return PeliculaFuncion::create(array_merge([
            'pelicula_id'          => $pelicula->id,
            'sala_id'              => $sala->id,
            'titulo'               => $pelicula->titulo,
            'genero'               => $pelicula->genero,
            'duracion_min'         => $pelicula->duracion_min,
            'sala'                 => $sala->nombre,
            'fecha_hora'           => now()->addDays(2),
            'precio_unitario'      => 15000,
            'capacidad_total'      => $sala->capacidad,
            'asientos_disponibles' => $sala->capacidad,
            'estado'               => 'Activa',
        ], $cambios))->fresh();
    }

    private function datosVenta(PeliculaFuncion $funcion, array $cambios = []): array
    {
        return array_merge([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Ana Torres',
            'email_cliente'     => 'ana.torres@correo.test',
            'cantidad_entradas' => 2,
            'clave_operacion'   => (string) Str::uuid(),
        ], $cambios);
    }

    public function test_ca_02_1_el_formulario_lista_solo_peliculas_con_funciones_vendibles(): void
    {
        $disponible = $this->crearFuncion(['titulo' => 'Película disponible']);
        $this->crearFuncion(['titulo' => 'Película inactiva', 'estado' => 'Inactiva']);
        $this->crearFuncion(['titulo' => 'Película agotada', 'asientos_disponibles' => 0]);
        $this->crearFuncion(['titulo' => 'Película pasada', 'fecha_hora' => now()->subDay()]);

        $this->get(route('ventas.create'))
            ->assertOk()
            ->assertSee($disponible->titulo)
            ->assertDontSee('Película inactiva')
            ->assertDontSee('Película agotada')
            ->assertDontSee('Película pasada');
    }

    public static function funcionesNoVendibles(): array
    {
        return [
            'inactiva'  => ['inactiva'],
            'cancelada' => ['cancelada'],
            'agotada'   => ['agotada'],
            'pasada'    => ['pasada'],
        ];
    }

    #[DataProvider('funcionesNoVendibles')]
    public function test_ca_02_1_rechaza_funciones_no_vendibles(string $caso): void
    {
        $cambios = match ($caso) {
            'inactiva'  => ['estado' => 'Inactiva'],
            'cancelada' => ['estado' => 'Cancelada'],
            'agotada'   => ['asientos_disponibles' => 0],
            'pasada'    => ['fecha_hora' => now()->subDay()],
        };

        $funcion = $this->crearFuncion($cambios);

        $this->post(route('ventas.store'), $this->datosVenta($funcion))
            ->assertSessionHasErrors('id_funcion');

        $this->assertDatabaseCount('Boleto_Reserva', 0);
        $this->assertEquals(
            $funcion->asientos_disponibles,
            $funcion->fresh()->asientos_disponibles
        );
    }

    public function test_ca_02_1_rechaza_una_funcion_inexistente(): void
    {
        $this->post(route('ventas.store'), [
            'id_funcion'        => 999999,
            'nombre_cliente'    => 'Ana Torres',
            'email_cliente'     => 'ana.torres@correo.test',
            'cantidad_entradas' => 1,
            'clave_operacion'   => (string) Str::uuid(),
        ])->assertSessionHasErrors('id_funcion');

        $this->assertDatabaseCount('Boleto_Reserva', 0);
    }

    public static function cantidadesInvalidas(): array
    {
        return [
            'cero'     => [0],
            'negativa' => [-1],
            'decimal'  => [1.5],
            'texto'    => ['abc'],
            'nula'     => [null],
        ];
    }

    #[DataProvider('cantidadesInvalidas')]
    public function test_ca_02_2_rechaza_cantidades_invalidas(mixed $cantidad): void
    {
        $funcion = $this->crearFuncion();

        $this->post(route('ventas.store'), $this->datosVenta($funcion, [
            'cantidad_entradas' => $cantidad,
        ]))->assertSessionHasErrors('cantidad_entradas');

        $this->assertDatabaseCount('Boleto_Reserva', 0);
        $this->assertEquals(100, $funcion->fresh()->asientos_disponibles);
    }

    public function test_ca_02_2_permite_comprar_exactamente_los_asientos_disponibles(): void
    {
        $funcion = $this->crearFuncion([
            'capacidad_total'      => 10,
            'asientos_disponibles' => 4,
        ]);

        $this->post(route('ventas.store'), $this->datosVenta($funcion, [
            'cantidad_entradas' => 4,
        ]))->assertSessionHasNoErrors();

        $this->assertEquals(0, $funcion->fresh()->asientos_disponibles);
    }

    public function test_ca_02_3_rechaza_el_sobrecupo(): void
    {
        $funcion = $this->crearFuncion([
            'capacidad_total'      => 10,
            'asientos_disponibles' => 4,
        ]);

        $this->post(route('ventas.store'), $this->datosVenta($funcion, [
            'cantidad_entradas' => 5,
        ]))->assertSessionHasErrors('cantidad_entradas');

        $this->assertDatabaseCount('Boleto_Reserva', 0);
        $this->assertEquals(4, $funcion->fresh()->asientos_disponibles);
    }

    public function test_ca_02_4_y_ca_02_5_registra_la_venta_y_redirige_al_comprobante(): void
    {
        $funcion = $this->crearFuncion([
            'precio_unitario'      => 12500,
            'capacidad_total'      => 50,
            'asientos_disponibles' => 40,
        ]);

        $respuesta = $this->post(route('ventas.store'), $this->datosVenta($funcion, [
            'cantidad_entradas' => 3,
        ]));

        $boleto = BoletoReserva::firstOrFail();

        $respuesta->assertRedirect(route('boletos.show', $boleto))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertEquals($funcion->id_funcion, $boleto->id_funcion);
        $this->assertSame('Ana Torres', $boleto->nombre_cliente);
        $this->assertSame('ana.torres@correo.test', $boleto->email_cliente);
        $this->assertEquals(3, $boleto->cantidad_entradas);
        $this->assertEquals(12500.0, (float) $boleto->precio_unitario);
        $this->assertEquals(37500.0, (float) $boleto->monto_total);
        $this->assertSame('Confirmado', $boleto->estado);
        $this->assertEquals(37, $funcion->fresh()->asientos_disponibles);
    }

    public function test_ca_02_4_el_monto_se_calcula_en_el_servidor_e_ignora_el_enviado(): void
    {
        $funcion = $this->crearFuncion(['precio_unitario' => 10000]);

        $this->post(route('ventas.store'), $this->datosVenta($funcion, [
            'cantidad_entradas' => 2,
            'monto_total'       => 1,
        ]))->assertSessionHasNoErrors();

        $this->assertEquals(20000.0, (float) BoletoReserva::firstOrFail()->monto_total);
    }

    public static function correosInvalidos(): array
    {
        return [
            'sin arroba'  => ['correo-invalido'],
            'sin dominio' => ['usuario@'],
            'con espacio' => ['usuario con espacio@dominio.com'],
        ];
    }

    #[DataProvider('correosInvalidos')]
    public function test_ca_03_2_rechaza_correos_invalidos(string $correo): void
    {
        $funcion = $this->crearFuncion();

        $this->post(route('ventas.store'), $this->datosVenta($funcion, [
            'email_cliente' => $correo,
        ]))->assertSessionHasErrors('email_cliente');

        $this->assertDatabaseCount('Boleto_Reserva', 0);
    }

    public static function correosValidos(): array
    {
        return [
            'simple'              => ['cliente@correo.test'],
            'con puntos y signos' => ['ana.torres+cine@sub.dominio.com'],
        ];
    }

    #[DataProvider('correosValidos')]
    public function test_ca_03_2_acepta_correos_validos(string $correo): void
    {
        $funcion = $this->crearFuncion();

        $this->post(route('ventas.store'), $this->datosVenta($funcion, [
            'email_cliente' => $correo,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('Boleto_Reserva', ['email_cliente' => $correo]);
    }
}