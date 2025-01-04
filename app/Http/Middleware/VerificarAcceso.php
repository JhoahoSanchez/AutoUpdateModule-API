<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarAcceso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $tokensAutorizados = ['b5c60c71ad91d11573d3333de94be2af3cbdb1492908337040ceeb72722faa0c'];

        if (!$token || !in_array($token, $tokensAutorizados)) {
            return response()->json(['error' => 'Token no valido'], 401);
        }

        //logica para obtener tokesn desde s3

//        if (!$request->user() || !$request->user()->tienePermiso('acceso_actualizaciones')) {
//            // Redirigir o devolver un error si no tiene permiso
//            abort(403, 'No tienes permiso para acceder a esta ruta.');
//        }

        return $next($request);
    }
}
