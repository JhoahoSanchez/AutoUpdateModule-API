<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ActualizacionController extends Controller
{
    public function existeActualizacionDisponible(Request $request)
    {
        $elemento = $request->input('nombre');
        $versionActualElemento = $request->input('version');
        $tipo = $request->input('tipo');

        $rutaVersionActual = $tipo . DIRECTORY_SEPARATOR . $elemento . DIRECTORY_SEPARATOR . $versionActualElemento;

        if (!Storage::disk('simulador_s3')->exists($rutaVersionActual)) {
            return response()->json(['mensaje' => 'No existe la version especificada'], 404);
        }

        $ultimaVersionElemento = $this->obtenerUltimaVersion("{$tipo}/{$elemento}");

        if ($versionActualElemento == $ultimaVersionElemento) {
            return response()->json(['mensaje' => 'No existen nuevas versiones disponibles', 'actualizable' => false]);
        }

        return response()->json(['mensaje' => 'Existe una nueva version', 'actualizable' => true, 'version' => $ultimaVersionElemento]);
    }

    public function obtenerInstrucciones(Request $request)
    {
        $elemento = $request->input('nombre');
        $versionActualElemento = $request->input('versionActual');
        $versionActualizable = $request->input('versionActualizable');
        $tipo = $request->input('tipo');

        if (!Storage::disk('simulador_s3')->exists("{$tipo}/{$elemento}/{$versionActualElemento}")) {
            return response()->json(['mensaje' => 'No existe la version especificada'], 404);
        }

        if (!Storage::disk('simulador_s3')->exists("{$tipo}/{$elemento}/{$versionActualizable}")) {
            return response()->json(['mensaje' => 'No existe la version especificada'], 404);
        }

        $archivoHashesVersionActual = $tipo
            . DIRECTORY_SEPARATOR
            . $elemento
            . DIRECTORY_SEPARATOR
            . $versionActualElemento
            . DIRECTORY_SEPARATOR
            . $versionActualElemento
            . '.json';
        $archivoHashesVersionNueva = $tipo
            . DIRECTORY_SEPARATOR
            . $elemento
            . DIRECTORY_SEPARATOR
            . $versionActualizable
            . DIRECTORY_SEPARATOR
            . $versionActualizable
            . '.json';

        try {
            if (!Storage::disk('simulador_s3')->exists($archivoHashesVersionActual)) {
                $hashes = $this->generarArchivoHashes("{$tipo}/{$elemento}/{$versionActualElemento}");
                Storage::disk('simulador_s3')->put($archivoHashesVersionActual, json_encode($hashes, JSON_PRETTY_PRINT));
                Log::debug("Hashes generados y guardados en: {$archivoHashesVersionActual}");
            }

            if (!Storage::disk('simulador_s3')->exists($archivoHashesVersionNueva)) {
                $hashes = $this->generarArchivoHashes("{$tipo}/{$elemento}/{$versionActualizable}");
                Storage::disk('simulador_s3')->put($archivoHashesVersionNueva, json_encode($hashes, JSON_PRETTY_PRINT));
                Log::debug("Hashes generados y guardados en: {$archivoHashesVersionNueva}");
            }

        } catch (Exception $e) {
            Log::debug('Error al generar archivo hash: ' . $e->getMessage());
            return null;
        }

        $hashesVersionActual = json_decode(Storage::disk('simulador_s3')->get($archivoHashesVersionActual), true);
        $hashesNuevaVersion = json_decode(Storage::disk('simulador_s3')->get($archivoHashesVersionNueva), true);

        $cambios = [];

        foreach ($hashesNuevaVersion as $archivo => $hash) {
            if (!isset($hashesVersionActual[$archivo])) {
                if (basename($archivo) === "{$versionActualizable}.json") {
                    continue;
                }
                $cambios[] = [
                    'elemento' => basename($archivo),
                    'ruta' => $archivo,
                    'accion' => 'AGREGAR'
                ];
            } elseif ($hashesVersionActual[$archivo] !== $hash) {
                $cambios[] = [
                    'elemento' => basename($archivo),
                    'ruta' => $archivo,
                    'accion' => 'MODIFICAR'
                ];
            }
        }

        foreach ($hashesVersionActual as $archivo => $hash) {
            if (!isset($hashesNuevaVersion[$archivo])) {
                $cambios[] = [
                    'elemento' => basename($archivo),
                    'ruta' => $archivo,
                    'accion' => 'ELIMINAR'
                ];
            }
        }

        return response()->json($cambios);
    }

    /**
     * @throws Exception
     */
    private function generarArchivoHashes($directorio, $hashAlgorithm = 'sha256')
    {
        $hashes = [];

        if (!Storage::disk('simulador_s3')->exists($directorio)) {
            throw new Exception("El directorio especificado no existe: {$directorio}");
        }

        $archivos = Storage::disk('simulador_s3')->allFiles($directorio);

        foreach ($archivos as $archivo) {
            $rutaRelativa = str_replace("{$directorio}/", '', $archivo);
            $contanidoArchivo = Storage::disk('simulador_s3')->get($archivo);
            $hash = hash($hashAlgorithm, $contanidoArchivo);
            $hashes[$rutaRelativa] = $hash;
        }

        return $hashes;
    }

    private function obtenerUltimaVersion($nombreAplicacion)
    {
        if (!Storage::disk('simulador_s3')->exists($nombreAplicacion)) {
            return null;
        }

        $subcarpetas = Storage::disk('simulador_s3')->directories($nombreAplicacion);

        $versiones = array_map('basename', $subcarpetas);

        $versionesValidas = array_filter($versiones, function ($version) {
            return preg_match('/\d+(\.\d+)*$/', $version);
        });

        usort($versionesValidas, function ($a, $b) {
            return version_compare($b, $a);
        });

        return $versionesValidas[0] ?? null;
    }
}
