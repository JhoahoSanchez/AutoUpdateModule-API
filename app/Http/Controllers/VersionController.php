<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class VersionController extends Controller
{
    public function existeActualizacionDisponible(Request $request)
    {
        //Implementar logica para coneccion a S3
        $elemento = $request->input("nombre");
        $versionActualElemento = $request->input('version');

        $ultimaVersionElemento = $this->obtenerUltimaVersion(storage_path("app") . DIRECTORY_SEPARATOR, $elemento);

        if ($versionActualElemento == $ultimaVersionElemento) {
            return response()->json([
                'actualizacionDisponible' => false
            ]);
        }

        if (isset($this->versionUpdates[$versionActualElemento])) { //TODO: CAMBIAR ESTO para que extraiga del properties la nueva version
            $nuevaVersion = $this->versionUpdates[$versionActualElemento];
            $cambios = $this->obtenerElementosAActualizar($versionActualElemento, $nuevaVersion);

            if ($cambios !== null) {
                $archivoController = new ArchivoController();
                return $archivoController->descargarArchivos($cambios);
            }

            return response()->json([
                'actualizacionDisponible' => true,
                'nuevaVersion' => $nuevaVersion,
                'cambios' => $cambios
            ]);
        }

        return response()->json(['updateAvailable' => false]);
    }

    private function obtenerElementosAActualizar($currentVersion, $nextVersion)
    {
        // Ruta a los archivos de hashes de versiones
        $path = storage_path("app/versions");

        // Cargar archivos de hashes de las versiones
        $archivoHashesVersionActual = "{$path}/{$currentVersion}.json";
        $archivoHashesVersionNueva = "{$path}/{$nextVersion}.json";

        try {
            if (!file_exists($archivoHashesVersionActual)) {
                $hashes = $this->generarArchivoHashes("{$path}/{$currentVersion}");
                file_put_contents($archivoHashesVersionActual, json_encode($hashes, JSON_PRETTY_PRINT));
                Log::debug("Hashes generados y guardados en: {$archivoHashesVersionActual}");
            }

            if (!file_exists($archivoHashesVersionNueva)) {
                $hashes = $this->generarArchivoHashes("{$path}/{$nextVersion}");
                file_put_contents($archivoHashesVersionNueva, json_encode($hashes, JSON_PRETTY_PRINT));
                Log::debug("Hashes generados y guardados en: {$archivoHashesVersionNueva}");
            }

        } catch (Exception $e) {
            Log::debug("Error al generar archivo hash: " . $e->getMessage());
        }

        $hashesVersionActual = json_decode(file_get_contents($archivoHashesVersionActual), true);
        $hashesNuevaVersion = json_decode(file_get_contents($archivoHashesVersionNueva), true);

        $cambios = [];

        foreach ($hashesNuevaVersion as $archivo => $hash) {
            if (!isset($hashesVersionActual[$archivo])) {
                // Archivo nuevo en la próxima versión
                $cambios[] = [
                    "elemento" => $archivo,
                    "hash" => $hash,
                    "accion" => "AGREGAR"
                ];
            } elseif ($hashesVersionActual[$archivo] !== $hash) {
                // Archivo modificado en la próxima versión
                $cambios[] = [
                    "elemento" => $archivo,
                    "hash" => $hash,
                    "accion" => "MODIFICAR"
                ];
            }
        }

        foreach ($hashesVersionActual as $archivo => $hash) {
            if (!isset($hashesNuevaVersion[$archivo])) {
                $cambios[] = [
                    "elemento" => $archivo,
                    "hash" => $hash,
                    "accion" => "ELIMINAR"
                ];
            }
        }

        return $cambios;
    }

    // Ruta para descargar archivos de actualización específicos
    public function descargarActualizacion($version, $filename)
    {
        $filePath = "updates/{$version}/{$filename}"; // Suponiendo que los archivos están organizados por versión
        if (Storage::exists($filePath)) {
            return Storage::download($filePath);
        } else {
            return response()->json(['error' => 'File not found'], 404);
        }
    }


    function generarArchivoHashes($directory, $hashAlgorithm = 'sha256')
    {
        $hashes = [];

        // Verifica si el directorio existe
        if (!is_dir($directory)) {
            throw new Exception("El directorio especificado no existe: {$directory}");
        }

        // Recorre los archivos y subcarpetas recursivamente
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            // Ignorar directorios
            if ($file->isDir()) {
                continue;
            }

            // Obtener la ruta relativa del archivo
            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());

            // Calcular el hash del archivo
            $hash = hash_file($hashAlgorithm, $file->getPathname());
            $hashes[$relativePath] = $hash;
        }

        return $hashes;
    }


    /**
     * @throws Exception
     */
    function obtenerUltimaVersion($basePath, $nombreAplicacion)
    {
        $appPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nombreAplicacion;

        if (!is_dir($appPath)) {
            throw new Exception("La carpeta de la aplicación no existe: {$appPath}");
        }

        $subcarpetas = array_filter(glob($appPath . DIRECTORY_SEPARATOR . '*'), 'is_dir');
        $versiones = array_map('basename', $subcarpetas);
        $versionesValidas = array_filter($versiones, function ($version) {
            return preg_match('/^\d+(\.\d+)*$/', $version);
        });

        usort($versionesValidas, function ($a, $b) {
            return version_compare($b, $a);
        });

        return $versionesValidas[0] ?? null;
    }

}
