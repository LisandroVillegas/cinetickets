<?php

namespace Tests\Feature;

use App\Models\BoletoReserva;
use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de integridad histórica tras la migración de catálogos (Contrato v1, sección 2).
 */
class MigracionCatalogosTest extends TestCase
{
    use RefreshDatabase;

    public function test_boletos_antiguos_conservan_total_y_tienen_precio_unitario_nulo(): void
    {
        $pelicula = Pelicula::factory()->create(['titulo' => 'Órbita']);
        $sala     = Sala::factory()->create(['nombre' => 'Sala 1', 'capacidad' => 80]);

        $funcion = PeliculaFuncion::create([
            'pelicula_id'          => $pelicula->id,
            'sala_id'              => $sala->id,
            'titulo'               => 'Órbita',
            'genero'               => 'Ciencia Ficción',
            'duracion_min'         => 120,
            'sala'                 => 'Sala 1',
            'fecha_hora'           => now()->addDays(2),
            'precio_unitario'      => 15000,
            'capacidad_total'      => 80,
            'asientos_disponibles' => 78,
            'estado'               => 'Activa',
        ]);

        // Simulamos un registro migrado del esquema antiguo sin precio_unitario ni clave_operacion.
        $boletoAntiguo = BoletoReserva::create([
            'id_funcion'        => $funcion->id_funcion,
            'nombre_cliente'    => 'Cliente Antiguo',
            'email_cliente'     => 'antiguo@correo.test',
            'cantidad_entradas' => 2,
            'precio_unitario'   => null,
            'monto_total'       => 30000,
            'estado'            => 'Confirmado',
            'clave_operacion'   => null,
        ])->fresh();

        $this->assertNull($boletoAntiguo->precio_unitario);
        $this->assertNull($boletoAntiguo->clave_operacion);
        $this->assertEquals(30000.00, (float) $boletoAntiguo->monto_total);
    }
}
