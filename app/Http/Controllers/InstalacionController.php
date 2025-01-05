<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstalacionController extends Controller
{
    public function buscarRecurso(Request $request)
    {
        $elemento = $request->input("nombre");

        $ultimaVersionElemento = $this->obtenerUltimaVersion($elemento);

        if (!$ultimaVersionElemento) {
            return response()->json(["mensaje" => "No se ha encontrado el recurso"], 404);
        }

        return response()->json(["mensaje" => "Se ha encontrado el recurso", "version" => $ultimaVersionElemento]);
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
