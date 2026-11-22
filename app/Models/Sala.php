<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una sala tiene muchas funciones (relación 1:N).
 */
class Sala extends Model
{
    use HasFactory;

    protected $table = 'salas';

    protected $fillable = [
        'nombre',
        'capacidad',
        'activa',
    ];

    protected $casts = [
        'capacidad' => 'integer',
        'activa'    => 'boolean',
    ];

    /** Solo salas activas (las únicas donde se puede programar). */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /** Funciones de esta sala. La columna sala_id llega en la Fase 2. */
    public function funciones(): HasMany
    {
        return $this->hasMany(PeliculaFuncion::class, 'sala_id');
    }
}