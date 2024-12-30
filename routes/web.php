<?php

use App\Http\Controllers\ActualizacionController;
use App\Http\Controllers\InstalacionController;
use App\Http\Controllers\ZIPArchivoController;
use Illuminate\Support\Facades\Route;

Route::middleware('verificar.acceso')->group(function () {

    //Instalacion
    Route::get('buscar-recurso', [InstalacionController::class, 'buscarRecurso']);
    Route::get('obtener-instruciones-instalacion', [InstalacionController::class, 'obtenerInstruccionesInstalacion']);

    //Actualizacion
    Route::get('buscar-actualizacion', [ActualizacionController::class, 'existeActualizacionDisponible']);
    Route::get('obtener-instrucciones', [ActualizacionController::class, 'obtenerInstrucciones']);
    Route::post('descargar-archivos', [ZIPArchivoController::class, 'descargarArchivos']);
});
