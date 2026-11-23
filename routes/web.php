<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PeliculaFuncionController;
use App\Http\Controllers\AnulacionController;
use App\Http\Controllers\BoletoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PeliculaController;
use App\Http\Controllers\ProgramacionController;
use App\Http\Controllers\SalaController;

Route::redirect('/', '/funciones');

// Catálogos
Route::resource('peliculas', PeliculaController::class)->except(['show', 'destroy']);
Route::resource('salas', SalaController::class)->except(['show', 'destroy']);

// Programación por lote (estáticas: van ANTES del resource de funciones)
Route::post('funciones/programacion/previsualizar', [ProgramacionController::class, 'previsualizar'])
    ->name('funciones.programacion.preview');
Route::post('funciones/programacion', [ProgramacionController::class, 'store'])
    ->name('funciones.programacion.store');

// Estado de las funciones
Route::patch('funciones/{funcion}/desactivar', [PeliculaFuncionController::class, 'desactivar'])
    ->name('funciones.desactivar');
Route::patch('funciones/{funcion}/activar', [PeliculaFuncionController::class, 'activar'])
    ->name('funciones.activar');

// CRUD de funciones
Route::resource('funciones', PeliculaFuncionController::class)
    ->parameters(['funciones' => 'funcion'])
    ->except(['show']);

// Venta de boletos (RF-02): Lisandro
Route::get('ventas/crear', [VentaController::class, 'create'])->name('ventas.create');
Route::post('ventas', [VentaController::class, 'store'])->name('ventas.store');

// Consulta y edición de boletos (RF-03): Andrés
Route::get('boletos', [BoletoController::class, 'index'])->name('boletos.index');
Route::get('boletos/{boleto}/editar', [BoletoController::class, 'edit'])->name('boletos.edit');
Route::put('boletos/{boleto}', [BoletoController::class, 'update'])->name('boletos.update');

// Anulación de boletos (RF-04): Alexander
Route::patch('boletos/{boleto}/anular', [AnulacionController::class, 'anular'])->name('boletos.anular');