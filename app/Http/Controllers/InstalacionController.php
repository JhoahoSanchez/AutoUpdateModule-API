<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstalacionController extends Controller
{
    public function buscarRecurso(Request $request)
    {
        $nombre = $request->input("nombre");
        $tipo = $request->input("tipo");

        $ultimaVersionElemento = $this->obtenerUltimaVersion("{$tipo}/{$nombre}");

        if (!$ultimaVersionElemento) {
            return response()->json(["mensaje" => "No se ha encontrado el recurso"], 404);
        }

        if ($request->input("incluir")) {
            if ($request->input("incluir") == "procesos") {
                $elemento = Elemento::where("nombre", $nombre)->first();

                if (!$elemento) {
                    return response()->json(['mensaje' => 'Elemento no encontrado'], 404);
                }

                return response()
                    ->json([
                        "mensaje" => "Se ha encontrado el recurso",
                        "version" => $ultimaVersionElemento,
                        "procesos" => json_decode($elemento->procesos)
                    ]);
            }
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
