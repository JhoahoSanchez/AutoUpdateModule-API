<?php

use App\Http\Controllers\VersionController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

//Ruta para buscar una nueva actualizacion
Route::post('buscar-actualizacion', [VersionController::class, 'existeActualizacionDisponible']);

Route::get('descargar-actualizacion/{elemento}/{version}', [VersionController::class, 'descargarActualizacion']);


//TODO: BORRAR
Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', fn () => Log::debug('test'));
