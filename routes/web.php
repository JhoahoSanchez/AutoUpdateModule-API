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

Route::get('/test-logs', function () {
    Log::debug('Este es un mensaje de prueba');
    return 'Log registrado!';
});

