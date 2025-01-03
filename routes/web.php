<?php

use App\Http\Controllers\ActualizacionController;
use App\Http\Controllers\InstalacionController;
use App\Http\Controllers\ZIPArchivoController;
use App\Http\Middleware\VerificarAcceso;
use Illuminate\Support\Facades\Route;

Route::middleware([VerificarAcceso::class])->group(function () {

    //Instalacion
    Route::get('buscar-recurso', [InstalacionController::class, 'buscarRecurso']);
    Route::get('descargar-archivos-instalacion', [ZIPArchivoController::class, 'descargarArchivosInstalacion']);

    //Actualizacion
    Route::get('buscar-actualizacion', [ActualizacionController::class, 'existeActualizacionDisponible']);
    Route::get('obtener-instrucciones', [ActualizacionController::class, 'obtenerInstrucciones']);
    Route::post('descargar-archivos', [ZIPArchivoController::class, 'descargarArchivos']);
});
