<?php

use App\Http\Controllers\VersionController;
use Illuminate\Support\Facades\Route;

Route::post('buscar-actualizacion', [VersionController::class, 'existeActualizacionDisponible']);

