<?php

namespace Tests\Feature;

use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de los catálogos de película y sala.
 */
class CatalogosTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_una_pelicula_valida(): void
    {
        $this->post(route('peliculas.store'), [
            'titulo' => 'Órbita', 'genero' => 'Ciencia ficción', 'duracion_min' => 120, 'activa' => 1,
        ])->assertRedirect(route('peliculas.index'));

        $this->assertDatabaseHas('peliculas', ['titulo' => 'Órbita', 'duracion_min' => 120, 'activa' => 1]);
    }

    public function test_rechaza_una_pelicula_con_duracion_cero_o_sin_titulo(): void
    {
        $this->post(route('peliculas.store'), ['titulo' => 'X', 'genero' => 'Y', 'duracion_min' => 0])
            ->assertSessionHasErrors('duracion_min');

        $this->post(route('peliculas.store'), ['titulo' => '', 'genero' => 'Y', 'duracion_min' => 90])
            ->assertSessionHasErrors('titulo');

        $this->assertDatabaseCount('peliculas', 0);
    }

    public function test_registra_una_sala_valida(): void
    {
        $this->post(route('salas.store'), ['nombre' => 'Sala 1', 'capacidad' => 80, 'activa' => 1])
            ->assertRedirect(route('salas.index'));

        $this->assertDatabaseHas('salas', ['nombre' => 'Sala 1', 'capacidad' => 80]);
    }

    public function test_rechaza_una_sala_con_nombre_repetido_o_capacidad_cero(): void
    {
        Sala::create(['nombre' => 'Sala 1', 'capacidad' => 80, 'activa' => true]);

        $this->post(route('salas.store'), ['nombre' => 'Sala 1', 'capacidad' => 50])
            ->assertSessionHasErrors('nombre');

        $this->post(route('salas.store'), ['nombre' => 'Sala 2', 'capacidad' => 0])
            ->assertSessionHasErrors('capacidad');

        $this->assertDatabaseCount('salas', 1);
    }

    public function test_editar_una_sala_no_cambia_las_funciones_ya_programadas(): void
    {
        $pelicula = Pelicula::create(['titulo' => 'Órbita', 'genero' => 'Ciencia ficción', 'duracion_min' => 120, 'activa' => true]);
        $sala = Sala::create(['nombre' => 'Sala 1', 'capacidad' => 80, 'activa' => true]);

        $funcion = PeliculaFuncion::create([
            'pelicula_id' => $pelicula->id, 'sala_id' => $sala->id,
            'titulo' => 'Órbita', 'genero' => 'Ciencia ficción', 'duracion_min' => 120, 'sala' => 'Sala 1',
            'fecha_hora' => now()->addDays(5), 'precio_unitario' => 15000,
            'capacidad_total' => 80, 'asientos_disponibles' => 80, 'estado' => 'Activa',
        ]);

        $this->put(route('salas.update', $sala), ['nombre' => 'Sala Premium', 'capacidad' => 10, 'activa' => 1])
            ->assertSessionHasNoErrors();

        // La función conserva la instantánea con la que se programó.
        $funcion = $funcion->fresh();
        $this->assertSame('Sala 1', $funcion->sala);
        $this->assertEquals(80, $funcion->capacidad_total);
    }

    public function test_inactivar_un_catalogo_no_modifica_las_funciones_existentes(): void
    {
        $pelicula = Pelicula::create(['titulo' => 'Órbita', 'genero' => 'Ciencia ficción', 'duracion_min' => 120, 'activa' => true]);
        $sala = Sala::create(['nombre' => 'Sala 1', 'capacidad' => 80, 'activa' => true]);

        $funcion = PeliculaFuncion::create([
            'pelicula_id' => $pelicula->id, 'sala_id' => $sala->id,
            'titulo' => 'Órbita', 'genero' => 'Ciencia ficción', 'duracion_min' => 120, 'sala' => 'Sala 1',
            'fecha_hora' => now()->addDays(5), 'precio_unitario' => 15000,
            'capacidad_total' => 80, 'asientos_disponibles' => 80, 'estado' => 'Activa',
        ]);

        $this->put(route('peliculas.update', $pelicula), [
            'titulo' => 'Órbita', 'genero' => 'Ciencia ficción', 'duracion_min' => 120, 'activa' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $pelicula->fresh()->activa);
        $this->assertSame('Activa', $funcion->fresh()->estado);
    }
}
