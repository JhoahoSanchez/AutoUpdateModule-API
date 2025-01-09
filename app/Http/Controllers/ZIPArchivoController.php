<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ZIPArchivoController extends Controller
{
    public function descargarArchivos(Request $request)
    {
        $instrucciones = $request->input('instrucciones');
        $elemento = $request->input('nombre');
        $ultimaVersion = $request->input('version');
        $rutaCarpeta = "{$elemento}/{$ultimaVersion}";

        if (!Storage::disk('simulador_s3')->exists($rutaCarpeta)) {
            return response()->json(["mensaje" => "No se ha encontrado el recurso."], 404);
        }

        $nombreZIP = "{$elemento}-{$ultimaVersion}.zip";
        $rutaZIP = storage_path("app/temp/{$nombreZIP}");

        $zip = new ZipArchive;
        if ($zip->open($rutaZIP, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($instrucciones as $instruccion) {
                $rutaRelativa = $instruccion['ruta'];
                $rutaAbsoluta = "{$rutaCarpeta}/{$rutaRelativa}";

                if (Storage::disk('simulador_s3')->exists($rutaAbsoluta)) {
                    $contenidoArchivo = Storage::disk('simulador_s3')->get($rutaAbsoluta);
                    $zip->addFromString($rutaRelativa, $contenidoArchivo);
                }
            }

            $zip->close();
        }

        return response()->download($rutaZIP)->deleteFileAfterSend();
    }


    public function descargarArchivosInstalacion(Request $request)
    {
        $elemento = $request->input('nombre');
        $ultimaVersion = $request->input('ultimaVersion');

        $nombreZIP = "{$elemento}-{$ultimaVersion}.zip";
        $rutaZIP = storage_path("app/temp/{$nombreZIP}");
        $rutaCarpeta = "{$elemento}/{$ultimaVersion}";

        if (!Storage::disk('simulador_s3')->exists($rutaCarpeta)) {
            return response()->json(["mensaje" => "No se ha encontrado el recurso."], 404);
        }

        $zip = new ZipArchive;
        if ($zip->open($rutaZIP, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $archivos = Storage::disk('simulador_s3')->allFiles($rutaCarpeta);

            foreach ($archivos as $archivo) {
                if (basename($archivo) === "{$ultimaVersion}.json") {
                    continue;
                }

                $rutaRelativa = str_replace("{$rutaCarpeta}/", '', $archivo);
                $contenidoArchivo = Storage::disk('simulador_s3')->get($archivo);
                $zip->addFromString($rutaRelativa, $contenidoArchivo);
            }

            $zip->close();
        } else {
            return response()->json(['mensaje' => 'No se pudo crear el archivo ZIP'], 500);
        }

        return response()->download($rutaZIP)->deleteFileAfterSend();
    }
}
