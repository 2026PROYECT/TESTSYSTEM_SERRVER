<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language; // <--- ESTA LÍNEA ES LA QUE FALTA O ESTÁ MAL

class LanguageController extends Controller
{
    public function index()
{
    // Solo devolvemos los idiomas que estén marcados como activos
    return response()->json(Language::where('is_active', true)->get());
}
}
