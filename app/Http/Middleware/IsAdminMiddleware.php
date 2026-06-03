<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }
        
        // Verificar rol admin (usando la columna 'role')
        if ($request->user()->role === 'admin') {
            return $next($request);
        }
        
        return response()->json([
            'message' => 'Acceso no autorizado. Se requieren permisos de administrador.'
        ], 403);
    }
}