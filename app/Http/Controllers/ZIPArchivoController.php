<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ZIPArchivoController extends Controller
{
    public function descargarArchivos(Request $request)
    {
        $instrucciones = $request->input('instrucciones');
        $elemento = $request->input('nombre');
        $ultimaVersion = $request->input('version');

        $archivos = [];

        foreach ($instrucciones as $instruccion) {
            $archivos[] = [
                'rutaReal' => $instruccion['rutaAPI'],
                'rutaZip' => $instruccion['rutaInstalacion']
            ];
        }

        $zipFileName = "{$elemento}-{$ultimaVersion}.zip";
        $zip = new ZipArchive;
        $zipPath = storage_path("app"
            . DIRECTORY_SEPARATOR
            . "temp"
            . DIRECTORY_SEPARATOR
            . $zipFileName
        );

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
        $elemento = $request->input('nombre');
        $ultimaVersion = $request->input('ultimaVersion');

        $zipFileName = "{$elemento}-{$ultimaVersion}.zip";
        $zip = new ZipArchive;
        $zipPath = storage_path("app" . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR . $zipFileName);
        $path = storage_path("app" . DIRECTORY_SEPARATOR . $elemento . DIRECTORY_SEPARATOR . $ultimaVersion);

        if (!is_dir($path)) {
            return response()->json(["mensaje" => "No se ha encontrado el recurso."], 404);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = File::allFiles($path);
            foreach ($files as $file) {
                $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }
            $zip->close();
        } else {
            return response()->json(['error' => 'No se pudo crear el archivo ZIP'], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
