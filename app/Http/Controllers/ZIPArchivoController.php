<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ZIPArchivoController extends Controller
{
    public function descargarArchivos(Request $request)
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

        $zipFileName = "{$elemento}-{$ultimaVersion}.zip";
        $zip = new ZipArchive;
        $zipPath = storage_path("app\\temp\\{$zipFileName}");

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

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    public function descargarArchivosInstalacion(Request $request)
    {
        $elemento = $request->input('elemento');
        $ultimaVersion  = $request->input('ultimaVersion');

        $zipFileName = "{$elemento}-{$ultimaVersion}.zip";
        $zip = new ZipArchive;
        $zipPath = storage_path("app/temp/{$zipFileName}"); //para windows cambiar por \\
        $path = storage_path("app/$elemento/{$ultimaVersion}"); //para windows cambiar por \\

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = File::allFiles($path);
            foreach ($files as $file) {
                $relativePath = str_replace($path . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }
            $zip->close();
        } else {
            return response()->json(['error' => 'No se pudo crear el archivo ZIP'], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
