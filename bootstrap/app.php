<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\IsAdminMiddleware;
use App\Http\Middleware\IsTeacherMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;  // 👈 IMPORTAR Request

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();
        
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'admin' => IsAdminMiddleware::class,
            'teacher' => IsTeacherMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn () => null); 
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ✅ CORREGIDO: Quitar el tipo Request del parámetro
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => true
                ], 500);
            }
        });
    })->create();