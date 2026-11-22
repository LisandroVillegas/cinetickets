<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad hija: un boleto o reserva que pertenece a una función.
 */
class BoletoReserva extends Model
{
    use HasFactory;

    protected $table = 'Boleto_Reserva';
    protected $primaryKey = 'id_boleto';

    // La fecha de compra la pone la base de datos (CURRENT_TIMESTAMP).
    public $timestamps = false;

    protected $fillable = [
        'id_funcion',
        'nombre_cliente',
        'email_cliente',
        'cantidad_entradas',
        'monto_total',
        'estado',
        'precio_unitario',
        'clave_operacion',
        'anulada_en',
        'motivo_anulacion',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha_compra' => 'datetime',
        'precio_unitario' => 'decimal:2',
        'anulada_en'      => 'datetime',
    ];

    /** Un boleto pertenece a una función. */
    public function funcion(): BelongsTo
    {
        return $this->belongsTo(PeliculaFuncion::class, 'id_funcion', 'id_funcion');
    }
}