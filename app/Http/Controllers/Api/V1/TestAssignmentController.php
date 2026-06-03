<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TestAssignment;
use Illuminate\Http\Request;

class TestAssignmentController extends Controller
{
    /**
     * Display a listing of assignments.
     */
    public function index()
{
    return TestAssignment::with(['user', 'test'])->get();
}


    /**
     * Store a newly created assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'test_id' => 'required|exists:tests,id',
            'status'  => 'required|string',
        ]);

        $assignment = TestAssignment::create($validated);

        return response()->json($assignment->load(['user','test']), 201);
    }

    /**
     * Display a specific assignment.
     */
   public function show(TestAssignment $assignment)
{
    return $assignment->load(['user', 'test']);
}


    /**
     * Update an assignment.
     */
    public function update(Request $request, TestAssignment $assignment)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string',
            'user_id' => 'sometimes|exists:users,id',
            'test_id' => 'sometimes|exists:tests,id',
        ]);

        $assignment->update($validated);

        return response()->json($assignment->load(['user','test']));
    }

    /**
     * Remove an assignment.
     */
    public function destroy(TestAssignment $assignment)
    {
        $assignment->delete();

        return response()->json(null, 204);
    }
}
