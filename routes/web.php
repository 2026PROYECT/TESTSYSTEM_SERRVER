<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\StudentReportController;
use App\Http\Controllers\Api\V1\OralTestController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| 1. RUTAS DE AUTENTICACIÓN
|--------------------------------------------------------------------------
*/
Route::get('/login', function () {
    return view('dashboard'); 
})->name('login');

Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| 2. REPORTES Y VALIDACIÓN
|--------------------------------------------------------------------------
*/


// NUEVA RUTA: Asegura que cualquier UUID de verificación cargue el dashboard de Vue
Route::get('/verify/{uuid}', function () {
    return view('dashboard');
});

/*
|--------------------------------------------------------------------------
| 3. COMODÍN DE VUE (SPA)
|--------------------------------------------------------------------------
*/
Route::get('/{any?}', function () {
    return view('dashboard');
})->where('any', '^(?!api|export|validar|login).*$');