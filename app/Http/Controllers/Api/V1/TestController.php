<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        return Test::with('sections.questions', 'criteria')->get();
    }

    public function show(Test $test)
    {
        return $test->load('sections.questions', 'criteria');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        return Test::create($validated);
    }
}

