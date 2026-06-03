<?php

namespace App\Http\Controllers\Api\V1; // <-- Verifica que esto sea idéntico

use App\Http\Controllers\Controller;
use App\Models\Career;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    public function index()
    {
        return response()->json(Career::all());
    }
}