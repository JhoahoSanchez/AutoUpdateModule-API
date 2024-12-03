<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class VersionController extends Controller
{
    /**
     * @throws Exception
     */
    public function existeActualizacionDisponible(Request $request)
    {
        $elemento = $request->input("nombre");
        $versionActualElemento = $request->input('version');

        $ultimaVersionElemento = $this->obtenerUltimaVersion(storage_path("app") . DIRECTORY_SEPARATOR, $elemento);

        if ($versionActualElemento == $ultimaVersionElemento) {
            return response()->json(["mensaje" => "No existen nuevas versiones disponibles", "actualizable" => false], 204);
        }

        return response()->json(["mensaje" => "Existe una nueva version", "actualizable" => true, "version" => $ultimaVersionElemento]);
    }

    private function obtenerInstrucciones(Request $request) //$currentVersion, $nextVersion, $elemento
    {
        $elemento = $request->input("nombre");
        $versionActualElemento = $request->input('versionActual');
        $versionActualizable = $request->input("versionActualizable");

        $path = storage_path("app\\$elemento");

        $archivoHashesVersionActual = "{$path}\\{$versionActualElemento}\\{$versionActualElemento}.json";
        $archivoHashesVersionNueva = "{$path}\\{$versionActualizable}\\{$versionActualizable}.json";

        try {
            if (!file_exists($archivoHashesVersionActual)) {
                $hashes = $this->generarArchivoHashes("{$path}\\{$versionActualElemento}");
                file_put_contents($archivoHashesVersionActual, json_encode($hashes, JSON_PRETTY_PRINT));
                Log::debug("Hashes generados y guardados en: {$archivoHashesVersionActual}");
            }

            if (!file_exists($archivoHashesVersionNueva)) {
                $hashes = $this->generarArchivoHashes("{$path}\\{$versionActualizable}");
                file_put_contents($archivoHashesVersionNueva, json_encode($hashes, JSON_PRETTY_PRINT));
                Log::debug("Hashes generados y guardados en: {$archivoHashesVersionNueva}");
            }

        } catch (Exception $e) {
            Log::debug("Error al generar archivo hash: " . $e->getMessage());
            return null;
        }

        $hashesVersionActual = json_decode(file_get_contents($archivoHashesVersionActual), true);
        $hashesNuevaVersion = json_decode(file_get_contents($archivoHashesVersionNueva), true);

        $cambios = [];

        foreach ($hashesNuevaVersion as $archivo => $hash) {
            if (!isset($hashesVersionActual[$archivo])) {
                $cambios[] = [
                    "elemento" => basename($archivo),
                    "rutaInstalacion" => $archivo,
                    "rutaAPI" => "{$path}\\{$versionActualizable}\\{$archivo}",
                    "hash" => $hash,
                    "accion" => "AGREGAR"
                ];
            } elseif ($hashesVersionActual[$archivo] !== $hash) {
                $cambios[] = [
                    "elemento" => basename($archivo),
                    "rutaInstalacion" => $archivo,
                    "rutaAPI" => "{$path}\\{$versionActualizable}\\{$archivo}",
                    "hash" => $hash,
                    "accion" => "MODIFICAR"
                ];
            }
        }

        foreach ($hashesVersionActual as $archivo => $hash) {
            if (!isset($hashesNuevaVersion[$archivo])) {
                $cambios[] = [
                    "elemento" => basename($archivo),
                    "rutaInstalacion" => $archivo,
                    "hash" => $hash,
                    "accion" => "ELIMINAR"
                ];
            }
        }

        return response()->json($cambios);
    }

    /**
     * @throws Exception
     */
    private function generarArchivoHashes($directory, $hashAlgorithm = 'sha256')
    {
        $hashes = [];

        if (!is_dir($directory)) {
            throw new Exception("El directorio especificado no existe: {$directory}");
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $hash = hash_file($hashAlgorithm, $file->getPathname());
            $hashes[$relativePath] = $hash;
        }

        return $hashes;
    }


    /**
     * @throws Exception
     */
    private function obtenerUltimaVersion($basePath, $nombreAplicacion)
    {
        $appPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nombreAplicacion;

        if (!is_dir($appPath)) {
            throw new Exception("La carpeta de la aplicación no existe: {$appPath}");
        }

        $subcarpetas = array_filter(glob($appPath . DIRECTORY_SEPARATOR . '*'), 'is_dir');
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
