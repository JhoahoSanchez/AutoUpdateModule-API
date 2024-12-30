<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ZIPArchivoController extends Controller
{
    public function descargarArchivos(Request $request) //$instrucciones, $elemento, $ultimaVersionElemento
    {
        $instrucciones = $request->input('instrucciones'); //TODO: MODIFICAR POR EL BODY
        $elemento  = $request->input('elemento');
        $ultimaVersion  = $request->input('ultimaVersion');

        $archivos = [];

        foreach ($instrucciones as $instruccion) {
            $archivos[] = [
                'rutaReal' => $instruccion['rutaAPI'],
                'rutaZip' => $instruccion['rutaInstalacion']
            ];
        }

        //$rutaArchivoInstrucciones = storage_path('app\\temp\\instrucciones.json');
        //file_put_contents($rutaArchivoInstrucciones, json_encode($instrucciones, JSON_PRETTY_PRINT));

        $zipFileName = "{$elemento}-{$ultimaVersion}.zip";
        $zip = new ZipArchive;
        $zipPath = storage_path("app\\temp\\{$zipFileName}");

//        $archivos[] = [
//            'rutaReal' => $rutaArchivoInstrucciones,
//            'rutaZip' => 'instrucciones.json'
//        ];

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

//        unlink($rutaArchivoInstrucciones);

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    public function descargarArchivosInstalacion(Request $request)
    {
        $elemento = $request->input('elemento');
        $ultimaVersion  = $request->input('ultimaVersion');

        // Nombre del archivo ZIP temporal
        $zipFileName = "{$elemento}-{$ultimaVersion}.zip";
        $zip = new ZipArchive;
        $zipPath = storage_path("app\\temp\\{$zipFileName}");


        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            // Agregar los archivos al ZIP
            $files = File::allFiles($directoryPath);
            foreach ($files as $file) {
                $relativePath = str_replace($directoryPath . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }
            $zip->close();
        } else {
            return response()->json(['error' => 'No se pudo crear el archivo ZIP'], 500);
        }

        // Enviar el archivo ZIP como respuesta
        return Response::download($zipFilePath)->deleteFileAfterSend(true);
    }
}
