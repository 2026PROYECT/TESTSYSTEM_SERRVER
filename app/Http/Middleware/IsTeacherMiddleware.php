<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsTeacherMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }
        
        // Admin y teacher pueden acceder
        if (in_array($request->user()->role, ['admin', 'teacher'])) {
            return $next($request);
        }
        
        return response()->json([
            'message' => 'Acceso no autorizado. Se requieren permisos de profesor.'
        ], 403);
    }
}