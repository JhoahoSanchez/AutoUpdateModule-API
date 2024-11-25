<?php

namespace App\Http\Controllers;

use ZipArchive;

class ArchivoController extends Controller
{
    public function descargarArchivos($instrucciones, $elemento, $ultimaVersionElemento)
    {
        $archivos = [];

        foreach ($instrucciones as $instruccion) {
            $archivos[] = [
                'rutaReal' => $instruccion['rutaAPI'],
                'rutaZip' => $instruccion['rutaInstalacion']
            ];
        }

        $rutaArchivoInstrucciones = storage_path('app\\temp\\instrucciones.json');
        file_put_contents($rutaArchivoInstrucciones, json_encode($instrucciones, JSON_PRETTY_PRINT));

        $zipFileName = "{$elemento}-{$ultimaVersionElemento}.zip";
        $zip = new ZipArchive;
        $zipPath = storage_path("app\\temp\\{$zipFileName}");

        $archivos[] = [
            'rutaReal' => $rutaArchivoInstrucciones,
            'rutaZip' => 'instrucciones.json'
        ];

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($archivos as $archivo) {
                $rutaReal = $archivo['rutaReal'];
                $rutaZip = $archivo['rutaZip'];

                if (file_exists($rutaReal)) {
                    $zip->addFile($rutaReal, $rutaZip);
                }
            }
            $zip->close();
        }

        unlink($rutaArchivoInstrucciones);

        return response()->download($zipPath)->deleteFileAfterSend();
    }
}
