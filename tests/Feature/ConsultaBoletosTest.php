<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\PeliculaFuncion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de RF-03 (consulta, filtros y edición de boletos).
 * Verifican los criterios CA-03.1 y CA-03.2 y la Decisión 1.
 */
class ConsultaBoletosTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Datos de apoyo
    // ------------------------------------------------------------------

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

    private function crearBoleto(PeliculaFuncion $funcion, array $cambios = []): BoletoReserva
    {
        return BoletoReserva::create(array_merge([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Ana Torres',
            'email_cliente'     => 'ana.torres@correo.test',
            'cantidad_entradas' => 2,
            'monto_total'       => 24000,
            'estado'            => 'Confirmado',
        ], $cambios))->fresh();
    }

    // ------------------------------------------------------------------
    // CA-03.1: listado y filtros
    // ------------------------------------------------------------------

    public function test_ca_03_1_lista_los_boletos_con_sus_datos(): void
    {
        $funcion = $this->crearFuncion(['titulo' => 'La Casa del Lago']);
        $this->crearBoleto($funcion, [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'ana@correo.test',
            'monto_total'    => 24000,
        ]);

        $this->get(route('boletos.index'))
            ->assertOk()
            ->assertSee('Ana Torres')
            ->assertSee('ana@correo.test')
            ->assertSee('La Casa del Lago')
            ->assertSee('$24.000,00')
            ->assertSee('Confirmado');
    }

    public function test_ca_03_1_filtra_por_nombre_del_cliente(): void
    {
        $funcion = $this->crearFuncion();

        $this->crearBoleto($funcion, [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'ana@correo.test',
        ]);
        $this->crearBoleto($funcion, [
            'nombre_cliente' => 'Luis Pérez',
            'email_cliente'  => 'luis@correo.test',
        ]);

        $respuesta = $this->get(route('boletos.index', ['cliente' => 'Ana']))
            ->assertOk()
            ->assertSee('Ana Torres')
            ->assertDontSee('Luis Pérez');

        $this->assertSame(1, $respuesta->viewData('boletos')->total());
    }

    public function test_ca_03_1_filtra_por_correo_del_cliente(): void
    {
        $funcion = $this->crearFuncion();

        $this->crearBoleto($funcion, [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'ana@correo.test',
        ]);
        $this->crearBoleto($funcion, [
            'nombre_cliente' => 'Luis Pérez',
            'email_cliente'  => 'luis@correo.test',
        ]);

        $respuesta = $this->get(route('boletos.index', ['cliente' => 'luis@']))
            ->assertOk()
            ->assertSee('Luis Pérez')
            ->assertDontSee('Ana Torres');

        $this->assertSame(1, $respuesta->viewData('boletos')->total());
    }

    public function test_ca_03_1_filtra_por_funcion(): void
    {
        $funcionUno = $this->crearFuncion(['titulo' => 'Función uno']);
        $funcionDos = $this->crearFuncion(['titulo' => 'Función dos']);

        $this->crearBoleto($funcionUno, ['nombre_cliente' => 'Ana Torres', 'email_cliente' => 'ana@correo.test']);
        $this->crearBoleto($funcionUno, ['nombre_cliente' => 'Luis Pérez', 'email_cliente' => 'luis@correo.test']);
        $this->crearBoleto($funcionDos, ['nombre_cliente' => 'Pedro Gómez', 'email_cliente' => 'pedro@correo.test']);

        $respuesta = $this->get(route('boletos.index', ['funcion' => $funcionUno->id_funcion]))
            ->assertOk()
            ->assertSee('Ana Torres')
            ->assertSee('Luis Pérez')
            ->assertDontSee('Pedro Gómez');

        $this->assertSame(2, $respuesta->viewData('boletos')->total());
    }

    public function test_ca_03_1_combina_los_filtros_de_cliente_y_funcion(): void
    {
        $funcionUno = $this->crearFuncion(['titulo' => 'Función uno']);
        $funcionDos = $this->crearFuncion(['titulo' => 'Función dos']);

        $this->crearBoleto($funcionUno, [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'ana.f1@correo.test',
        ]);
        $this->crearBoleto($funcionDos, [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'ana.f2@correo.test',
        ]);

        $respuesta = $this->get(route('boletos.index', [
            'cliente' => 'Ana',
            'funcion' => $funcionUno->id_funcion,
        ]))
            ->assertOk()
            ->assertSee('ana.f1@correo.test')
            ->assertDontSee('ana.f2@correo.test');

        $this->assertSame(1, $respuesta->viewData('boletos')->total());
    }

    public function test_ca_03_1_pagina_los_resultados_de_quince_en_quince(): void
    {
        $funcion = $this->crearFuncion();

        for ($numero = 1; $numero <= 16; $numero++) {
            $this->crearBoleto($funcion, [
                'nombre_cliente' => "Cliente {$numero}",
                'email_cliente'  => "cliente{$numero}@correo.test",
            ]);
        }

        $primeraPagina = $this->get(route('boletos.index'))->assertOk()->viewData('boletos');

        $this->assertSame(16, $primeraPagina->total());
        $this->assertSame(15, $primeraPagina->count());
        $this->assertSame(15, $primeraPagina->perPage());
        $this->assertTrue($primeraPagina->hasMorePages());
        $this->assertEquals(16, $primeraPagina->first()->id_boleto);

        $segundaPagina = $this->get(route('boletos.index', ['page' => 2]))
            ->assertOk()
            ->viewData('boletos');

        $this->assertSame(1, $segundaPagina->count());
        $this->assertSame(2, $segundaPagina->currentPage());
    }

    public function test_ca_03_1_rechaza_un_filtro_de_funcion_inexistente(): void
    {
        $this->get(route('boletos.index', ['funcion' => 999999]))
            ->assertSessionHasErrors('funcion');
    }

    // ------------------------------------------------------------------
    // Edición: solo boletos Confirmado y solo nombre/correo
    // ------------------------------------------------------------------

    public function test_ca_03_1_muestra_el_formulario_de_edicion_de_un_boleto_confirmado(): void
    {
        $funcion = $this->crearFuncion();
        $boleto = $this->crearBoleto($funcion, [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'ana@correo.test',
        ]);

        $this->get(route('boletos.edit', $boleto))
            ->assertOk()
            ->assertSee('Ana Torres')
            ->assertSee('ana@correo.test');
    }

    public function test_ca_03_1_no_permite_editar_un_boleto_cancelado(): void
    {
        $funcion = $this->crearFuncion();
        $boleto = $this->crearBoleto($funcion, ['estado' => 'Cancelado']);

        $this->get(route('boletos.edit', $boleto))->assertNotFound();
        $this->put(route('boletos.update', $boleto), [
            'nombre_cliente' => 'Otro Nombre',
            'email_cliente'  => 'otro@correo.test',
        ])->assertNotFound();
    }

    public function test_decision_1_actualiza_solo_nombre_y_correo(): void
    {
        $funcion = $this->crearFuncion();
        $otraFuncion = $this->crearFuncion(['titulo' => 'Otra función']);
        $boleto = $this->crearBoleto($funcion, [
            'nombre_cliente'    => 'Ana Torres',
            'email_cliente'     => 'ana@correo.test',
            'cantidad_entradas' => 2,
            'monto_total'       => 24000,
        ]);

        $this->put(route('boletos.update', $boleto), [
            'nombre_cliente'    => 'Ana María Torres',
            'email_cliente'     => 'ana.maria@correo.test',
            'cantidad_entradas' => 99,
            'monto_total'       => 1,
            'id_funcion'        => $otraFuncion->id_funcion,
            'estado'            => 'Cancelado',
        ])
            ->assertRedirect(route('boletos.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito');

        $boleto->refresh();

        $this->assertSame('Ana María Torres', $boleto->nombre_cliente);
        $this->assertSame('ana.maria@correo.test', $boleto->email_cliente);
        $this->assertEquals(2, $boleto->cantidad_entradas);
        $this->assertEquals(24000.0, (float) $boleto->monto_total);
        $this->assertEquals($funcion->id_funcion, $boleto->id_funcion);
        $this->assertSame('Confirmado', $boleto->estado);
    }

    public function test_ca_03_2_rechaza_un_correo_invalido_al_editar(): void
    {
        $funcion = $this->crearFuncion();
        $boleto = $this->crearBoleto($funcion, [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'ana@correo.test',
        ]);

        $this->put(route('boletos.update', $boleto), [
            'nombre_cliente' => 'Ana Torres',
            'email_cliente'  => 'no-es-correo',
        ])->assertSessionHasErrors('email_cliente');

        $boleto->refresh();

        $this->assertSame('ana@correo.test', $boleto->email_cliente);
    }

    public function test_ca_03_1_marca_los_boletos_cancelados_como_no_editables(): void
    {
        $funcion = $this->crearFuncion();
        $this->crearBoleto($funcion, ['estado' => 'Cancelado']);

        $this->get(route('boletos.index'))
            ->assertOk()
            ->assertSee('No editable');
    }
}
