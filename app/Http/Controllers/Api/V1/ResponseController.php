<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Response;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    /**
     * Store a new response for a question in an assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:test_assignments,id',
            'question_id'   => 'required|exists:questions,id',
            'response_text' => 'nullable|string',
            'recording_url' => 'nullable|string',
            'score'         => 'nullable|integer',
            'comments'      => 'nullable|string',
        ]);

        $response = Response::create($validated);

        return response()->json($response, 201);
    }

    /**
     * Update an existing response (e.g., evaluator adds score/comments).
     */
    public function update(Request $request, Response $response)
    {
        $validated = $request->validate([
            'response_text' => 'nullable|string',
            'recording_url' => 'nullable|string',
            'score'         => 'nullable|integer',
            'comments'      => 'nullable|string',
        ]);

        $response->update($validated);

        return response()->json($response);
    }

    /**
     * Show a specific response with its related question.
     */
    public function show(Response $response)
    {
        return $response->load('question', 'assignment');
    }
}

