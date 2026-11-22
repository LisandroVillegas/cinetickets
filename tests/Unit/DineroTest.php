<?php

namespace Tests\Unit;

use App\Services\BoletoService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Pruebas unitarias de cálculo exacto de dinero en centavos enteros (Contrato v1, sección 4).
 */
class DineroTest extends TestCase
{
    public function test_calcula_sin_errores_de_punto_flotante_en_centavos(): void
    {
        $servicio = new BoletoService();

        // En punto flotante estándar, 0.10 * 3 suele producir 0.30000000000000004
        $resultado =$servicio->calcularMonto(3, '0.10');

        $this->assertEquals(0.30,$resultado);
        $this->assertSame('0.30', number_format((float)$resultado, 2, '.', ''));
    }

    public function test_rechaza_desbordamiento_del_limite_decimal_10_2(): void
    {
        $servicio = new BoletoService();

        $this->expectException(ValidationException::class);

        // El máximo permitido por DECIMAL(10,2) es 99999999.99
        $servicio->calcularMonto(2, '60000000.00');
    }
}