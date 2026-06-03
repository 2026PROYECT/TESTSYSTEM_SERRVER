<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Verificar autenticación y rol de admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'No autorizado. Se requieren permisos de administrador.'
            ], 403);
        }
        
        return $next($request);
    }
}