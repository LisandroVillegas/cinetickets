<?php

namespace Tests\Feature;

use App\Models\Pelicula;
use App\Models\PeliculaFuncion;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pruebas de la programación: regla de cruces (pausa de 15 minutos), lote atómico,
 * vista previa, reprogramación y reactivación.
 */
class ProgramacionTest extends TestCase
{
    use RefreshDatabase;

    private Pelicula $pelicula; // dura 120 minutos
    private Sala $sala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pelicula = Pelicula::create([
            'titulo' => 'Órbita', 'genero' => 'Ciencia ficción', 'duracion_min' => 120, 'activa' => true,
        ]);

        $this->sala = Sala::create(['nombre' => 'Sala 1', 'capacidad' => 80, 'activa' => true]);
    }

    // ------------------------------------------------------------------
    // Datos de apoyo
    // ------------------------------------------------------------------

    /** Medianoche de un día futuro (por defecto, dentro de 5 días). */
    private function dia(int $dias = 5): Carbon
    {
        return now()->addDays($dias)->startOfDay();
    }

    private function crearFuncion(Carbon $inicio, array $cambios = []): PeliculaFuncion
    {
        return PeliculaFuncion::create(array_merge([
            'pelicula_id'          => $this->pelicula->id,
            'sala_id'              => $this->sala->id,
            'titulo'               => $this->pelicula->titulo,
            'genero'               => $this->pelicula->genero,
            'duracion_min'         => $this->pelicula->duracion_min,
            'sala'                 => $this->sala->nombre,
            'fecha_hora'           => $inicio,
            'precio_unitario'      => 15000,
            'capacidad_total'      => $this->sala->capacidad,
            'asientos_disponibles' => $this->sala->capacidad,
            'estado'               => 'Activa',
        ], $cambios))->fresh();
    }

    private function datosLote(array $cambios = []): array
    {
        return array_merge([
            'pelicula_id'     => $this->pelicula->id,
            'sala_id'         => $this->sala->id,
            'fechas'          => [$this->dia()->format('Y-m-d')],
            'horarios'        => ['18:00'],
            'precio_unitario' => 15000,
        ], $cambios);
    }

    // ------------------------------------------------------------------
    // Regla de cruces con pausa de 15 minutos
    // ------------------------------------------------------------------

    public function test_una_funcion_de_120_minutos_desde_las_18_permite_la_siguiente_a_las_20_15(): void
    {
        $this->crearFuncion($this->dia()->setTime(18, 0));

        $this->post(route('funciones.programacion.store'), $this->datosLote(['horarios' => ['20:15']]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 2);
    }

    public function test_rechaza_la_siguiente_funcion_a_las_20_14(): void
    {
        $this->crearFuncion($this->dia()->setTime(18, 0));

        $this->post(route('funciones.programacion.store'), $this->datosLote(['horarios' => ['20:14']]))
            ->assertSessionHasErrors('programacion');

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_el_cruce_se_detecta_cuando_la_funcion_termina_despues_de_medianoche(): void
    {
        // 22:30 + 120 min + 15 min = 00:45 del día siguiente.
        $this->crearFuncion($this->dia()->setTime(22, 30));
        $diaSiguiente = $this->dia()->addDay()->format('Y-m-d');

        $this->post(route('funciones.programacion.store'), $this->datosLote([
            'fechas' => [$diaSiguiente], 'horarios' => ['00:40'],
        ]))->assertSessionHasErrors('programacion');

        $this->post(route('funciones.programacion.store'), $this->datosLote([
            'fechas' => [$diaSiguiente], 'horarios' => ['00:45'],
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 2);
    }

    public function test_una_funcion_inactiva_sigue_ocupando_la_sala(): void
    {
        $this->crearFuncion($this->dia()->setTime(18, 0), ['estado' => 'Inactiva']);

        $this->post(route('funciones.programacion.store'), $this->datosLote(['horarios' => ['19:00']]))
            ->assertSessionHasErrors('programacion');
    }

    public function test_otra_sala_no_choca(): void
    {
        $this->crearFuncion($this->dia()->setTime(18, 0));
        $otraSala = Sala::create(['nombre' => 'Sala 2', 'capacidad' => 50, 'activa' => true]);

        $this->post(route('funciones.programacion.store'), $this->datosLote(['sala_id' => $otraSala->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 2);
    }

    public function test_la_programacion_individual_tambien_rechaza_un_cruce(): void
    {
        $this->crearFuncion($this->dia()->setTime(18, 0));

        $this->post(route('funciones.store'), [
            'pelicula_id'     => $this->pelicula->id,
            'sala_id'         => $this->sala->id,
            'fecha_hora'      => $this->dia()->setTime(19, 0)->format('Y-m-d\TH:i'),
            'precio_unitario' => 15000,
        ])->assertSessionHasErrors('programacion');

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    // ------------------------------------------------------------------
    // Lote: todo o nada
    // ------------------------------------------------------------------

    public function test_el_lote_crea_una_funcion_por_cada_fecha_y_horario(): void
    {
        $datos = $this->datosLote([
            'fechas'   => [$this->dia(5)->format('Y-m-d'), $this->dia(6)->format('Y-m-d')],
            'horarios' => ['12:00', '18:00'],
        ]);

        $this->post(route('funciones.programacion.store'), $datos)
            ->assertRedirect(route('funciones.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 4);

        foreach (PeliculaFuncion::all() as $funcion) {
            $this->assertEquals(80, $funcion->capacidad_total);
            $this->assertEquals(80, $funcion->asientos_disponibles);
            $this->assertSame('Sala 1', $funcion->sala);
        }
    }

    public function test_si_una_combinacion_choca_no_se_guarda_ninguna(): void
    {
        $this->crearFuncion($this->dia(6)->setTime(18, 0));

        $datos = $this->datosLote([
            'fechas'   => [$this->dia(5)->format('Y-m-d'), $this->dia(6)->format('Y-m-d')],
            'horarios' => ['18:00'],
        ]);

        $this->post(route('funciones.programacion.store'), $datos)->assertSessionHasErrors('programacion');

        // Solo existe la función que ya estaba: la del día 5 tampoco se guardó.
        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    public function test_dos_horarios_del_mismo_lote_que_se_cruzan_se_rechazan(): void
    {
        $this->post(route('funciones.programacion.store'), $this->datosLote(['horarios' => ['18:00', '19:00']]))
            ->assertSessionHasErrors('programacion');

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_rechaza_fechas_duplicadas(): void
    {
        $fecha = $this->dia()->format('Y-m-d');

        $this->post(route('funciones.programacion.store'), $this->datosLote(['fechas' => [$fecha, $fecha]]))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_rechaza_mas_de_100_combinaciones(): void
    {
        $fechas = [];
        for ($i = 1; $i <= 11; $i++) {
            $fechas[] = $this->dia($i + 4)->format('Y-m-d');
        }

        $horarios = [];
        for ($i = 0; $i < 10; $i++) {
            $horarios[] = sprintf('%02d:00', 8 + $i);
        }

        $this->post(route('funciones.programacion.store'), $this->datosLote(['fechas' => $fechas, 'horarios' => $horarios]))
            ->assertSessionHasErrors('fechas');

        $this->assertDatabaseCount('Pelicula_Funcion', 0);
    }

    public function test_la_vista_previa_marca_los_conflictos_y_no_guarda_nada(): void
    {
        $this->crearFuncion($this->dia()->setTime(18, 0));

        $respuesta = $this->post(route('funciones.programacion.preview'), $this->datosLote(['horarios' => ['19:00', '22:00']]));

        $respuesta->assertOk()->assertViewHas('puedeGuardar', false);

        $filas = $respuesta->viewData('filas');
        $this->assertTrue($filas[0]['conflicto']);
        $this->assertFalse($filas[1]['conflicto']);

        $this->assertDatabaseCount('Pelicula_Funcion', 1);
    }

    // ------------------------------------------------------------------
    // Reprogramación y reactivación
    // ------------------------------------------------------------------

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

    public function test_reprogramar_no_choca_consigo_misma(): void
    {
        $funcion = $this->crearFuncion($this->dia()->setTime(18, 0));

        $this->put(route('funciones.update', $funcion), $this->datosEdicion($funcion, [
            'fecha_hora' => $this->dia()->setTime(18, 30)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasNoErrors();

        $this->assertSame('18:30', $funcion->fresh()->fecha_hora->format('H:i'));
    }

    public function test_reprogramar_rechaza_un_cruce_con_otra_funcion(): void
    {
        $this->crearFuncion($this->dia()->setTime(18, 0));
        $segunda = $this->crearFuncion($this->dia()->setTime(21, 0));

        $this->put(route('funciones.update', $segunda), $this->datosEdicion($segunda, [
            'fecha_hora' => $this->dia()->setTime(19, 0)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasErrors('programacion');

        $this->assertSame('21:00', $segunda->fresh()->fecha_hora->format('H:i'));
    }

    public function test_activa_una_funcion_inactiva_que_todavia_no_empezo(): void
    {
        $funcion = $this->crearFuncion($this->dia()->setTime(18, 0), ['estado' => 'Inactiva']);

        $this->patch(route('funciones.activar', $funcion))->assertSessionHas('exito');

        $this->assertSame('Activa', $funcion->fresh()->estado);
    }

    public function test_no_activa_una_funcion_inactiva_que_ya_empezo(): void
    {
        $funcion = $this->crearFuncion(now()->subDay(), ['estado' => 'Inactiva']);

        $this->patch(route('funciones.activar', $funcion))->assertSessionHas('error');

        $this->assertSame('Inactiva', $funcion->fresh()->estado);
    }
}
