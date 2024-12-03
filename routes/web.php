<?php

use App\Http\Controllers\VersionController;
use App\Http\Controllers\ZIPArchivoController;
use Illuminate\Support\Facades\Route;

Route::middleware('verificar.acceso')->group(function () {
    Route::get('buscar-actualizacion', [VersionController::class, 'existeActualizacionDisponible']);
    Route::get('obtener-instrucciones', [VersionController::class, 'obtenerInstrucciones']);
    Route::get('descargar-actualizacion', [ZIPArchivoController::class, 'descargarArchivos']);
});
