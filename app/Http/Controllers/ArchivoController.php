<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;

class ArchivoController extends Controller
{
    public function descargarArchivos($instrucciones)
    {
        $archivos = [];

        foreach ($instrucciones as $instruccion) {
            $archivos[] = $instruccion["elemento"]; //TODO: AGREGAR RUTA DE CARPETA
        }

        $rutaArchivoInstrucciones = storage_path('app/temp/instrucciones.json');
        file_put_contents($rutaArchivoInstrucciones, json_encode($instrucciones, JSON_PRETTY_PRINT));

        $zipFileName = 'archivos.zip';
        $zip = new ZipArchive;

        $archivos[] = $rutaArchivoInstrucciones;

        // Crear el archivo zip
        $zipPath = storage_path("app/temp/{$zipFileName}");
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($archivos as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }

            $zip->close();
        }

        unlink($rutaArchivoInstrucciones);

        // Respuesta para descargar el archivo zip
        return response()->download($zipPath)->deleteFileAfterSend();
    }
}
