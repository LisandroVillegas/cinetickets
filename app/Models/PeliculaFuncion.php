<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Entidad padre: una función de la cartelera (relación 1:N con BoletoReserva).
 */
class PeliculaFuncion extends Model
{
    use HasFactory;

    // Laravel supone nombres en plural y una columna "id"; aquí los indicamos a mano.
    protected $table = 'Pelicula_Funcion';
    protected $primaryKey = 'id_funcion';

    // La tabla no tiene created_at ni updated_at.
    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'genero',
        'duracion_min',
        'sala',
        'fecha_hora',
        'precio_unitario',
        'capacidad_total',
        'asientos_disponibles',
        'estado',
        'pelicula_id',
        'sala_id',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'precio_unitario' => 'decimal:2',
    ];

    /** Una función tiene muchos boletos. */
    public function boletos(): HasMany
    {
        return $this->hasMany(BoletoReserva::class, 'id_funcion', 'id_funcion');
    }

    public function pelicula(): BelongsTo
    {
    return $this->belongsTo(Pelicula::class, 'pelicula_id');
    }

// Se llama salaRelacion porque "sala" ya es una columna de texto.
public function salaRelacion(): BelongsTo
    {
    return $this->belongsTo(Sala::class, 'sala_id');
    }
}