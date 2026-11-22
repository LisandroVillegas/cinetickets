<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una película tiene muchas funciones (relación 1:N).
 */
class Pelicula extends Model
{
    use HasFactory;

    protected $table = 'peliculas';

    protected $fillable = [
        'titulo',
        'genero',
        'duracion_min',
        'activa',
    ];

    protected $casts = [
        'duracion_min' => 'integer',
        'activa'       => 'boolean',
    ];

    /** Solo películas activas (las únicas que se pueden programar). */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /** Funciones de esta película. La columna pelicula_id llega en la Fase 2. */
    public function funciones(): HasMany
    {
        return $this->hasMany(PeliculaFuncion::class, 'pelicula_id');
    }
}