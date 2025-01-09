<?php

namespace App\Http\Middleware;

use App\Models\SistemaExterno;
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

        $tokenAutorizado = SistemaExterno::where("apiToken", $token)->first();

        if (!$tokenAutorizado) {
            return response()->json(['error' => 'Token no valido'], 401);
        }

        return $next($request);
    }
}
