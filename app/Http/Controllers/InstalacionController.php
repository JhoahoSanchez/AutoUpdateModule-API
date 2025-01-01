<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstalacionController extends Controller
{
    public function buscarRecurso(Request $request) {
        $elemento = $request->input("nombre");

        $ultimaVersionElemento = $this->obtenerUltimaVersion(storage_path("app") . DIRECTORY_SEPARATOR, $elemento);

        return response()->json(["mensaje" => "Existe una nueva version", "version" => $ultimaVersionElemento]);
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
