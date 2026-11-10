<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PeliculaFuncionController;

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

