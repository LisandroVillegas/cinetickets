<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PeliculaFuncionController;
use App\Http\Controllers\AnulacionController;
use App\Http\Controllers\BoletoController;
use App\Http\Controllers\VentaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::redirect('/', '/funciones');

Route::patch('funciones/{funcion}/desactivar', [PeliculaFuncionController::class, 'desactivar'])
    ->name('funciones.desactivar');

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
