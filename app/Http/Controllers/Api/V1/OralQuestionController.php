<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OralQuestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class OralQuestionController extends Controller
{
    /**
     * Lista de preguntas filtradas por idioma, nivel y búsqueda
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        $questions = OralQuestion::query()
            // FILTRO CRÍTICO: Solo mostrar preguntas del idioma seleccionado
            ->when($request->language_id, function ($q, $languageId) {
                $q->where('language_id', $languageId);
            })
            ->when($request->search, function ($q, $search) {
                $q->where('question_text', 'like', "%{$search}%");
            })
            ->when($request->level && $request->level !== 'all', function ($q) use ($request) {
                $q->where('level', $request->level);
            })
            // Filtro por estado activo (opcional, según tus filtros de Vue)
            ->when($request->active !== 'all' && $request->has('active'), function ($q) use ($request) {
                $q->where('active', $request->active);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json($questions);
    }

    /**
     * Almacenar una nueva pregunta asignándole su departamento
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'level'         => ['required', Rule::in(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])],
            'category'      => 'nullable|string|max:255',
            'active'        => 'boolean',
            'language_id'   => 'required|integer' // <--- Obligatorio para EmiSystem
        ]);

        $question = OralQuestion::create($validated);

        return response()->json([
            'message' => 'Pregunta creada con éxito en el departamento correspondiente',
            'data' => $question
        ], 201);
    }

    /**
     * Actualizar pregunta manteniendo la integridad del idioma
     */
    public function update(Request $request, $id)
    {
        $question = OralQuestion::findOrFail($id);
        
        $validated = $request->validate([
            'question_text' => 'sometimes|required|string',
            'level'         => ['sometimes', 'required', Rule::in(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])],
            'category'      => 'nullable|string',
            'active'        => 'boolean',
            'language_id'   => 'sometimes|required|integer' // <--- Permitir actualizar el idioma si es necesario
        ]);

        $question->update($validated);

        return response()->json([
            'message' => 'Pregunta actualizada con éxito',
            'data' => $question
        ]);
    }

    public function show(OralQuestion $question)
    {
        return response()->json($question);
    }

    public function destroy($id)
    {
        $question = OralQuestion::findOrFail($id);
        $question->delete();

        return response()->json([
            'message' => 'Pregunta eliminada correctamente'
        ]);
    }
}