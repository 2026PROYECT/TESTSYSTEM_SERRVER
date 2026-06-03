<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleController extends Controller
{
    /**
     * Listar todos los módulos con paginación y filtros
     */
    public function index(Request $request)
{
    try {
        $query = Module::with('language')
            ->withCount('questions');
        
        // 🔥 FILTRO POR IDIOMA (IMPORTANTE)
        if ($request->language_id) {
            $query->where('language_id', $request->language_id);
        } else {
            // Si no se especifica, usar el idioma activo del usuario
            $activeLangId = $request->user()->active_language_id ?? 1;
            $query->where('language_id', $activeLangId);
        }
        
        // Filtro por tipo
        if ($request->type) {
            $query->where('type', $request->type);
        }
        
        // Filtro por nivel
        if ($request->level) {
            $query->where('level', $request->level);
        }
        
        // 🔥 ORDENAMIENTO CORRECTO
        $sortField = $request->sort_by ?? 'id'; // id, title, type, level, created_at
        $sortOrder = $request->sort_order ?? 'asc'; // asc o desc
        
        $query->orderBy($sortField, $sortOrder);
        
        // Paginación
        $perPage = $request->per_page ?? 15;
        $modules = $query->paginate($perPage);
        
        return response()->json($modules);
        
    } catch (\Exception $e) {
        Log::error('Error en ModuleController@index: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar los módulos: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Mostrar un módulo específico con sus preguntas
     */
    public function show($id)
    {
        try {
            $module = Module::with(['language', 'questions' => function($q) {
                $q->orderBy('order_position', 'asc');
            }])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $module
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Módulo no encontrado'
            ], 404);
        }
    }

    /**
     * OBTENER PREGUNTAS DE UN MÓDULO (Método nuevo)
     */
    public function getQuestions($moduleId)
    {
        try {
            $module = Module::findOrFail($moduleId);
            $questions = $module->questions()
                                ->orderBy('order_position', 'asc')
                                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $questions,
                'total' => $questions->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@getQuestions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las preguntas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo módulo
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'language_id' => 'required|exists:languages,id',
            'content' => 'nullable|string',
            'type' => 'required|in:reading,listening,mixed',
            'level' => 'nullable|string|max:5',
            'duration_seconds' => 'nullable|integer',
            'audio' => 'nullable|string|max:255',
            'picture' => 'nullable|string|max:255',
        ]);

        try {
            $module = Module::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Módulo creado correctamente',
                'data' => $module
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un módulo
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string',
            'type' => 'sometimes|in:reading,listening,mixed',
            'level' => 'nullable|string|max:5',
            'duration_seconds' => 'nullable|integer',
            'audio' => 'nullable|string|max:255',
            'picture' => 'nullable|string|max:255',
        ]);

        try {
            $module = Module::findOrFail($id);
            $module->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Módulo actualizado correctamente',
                'data' => $module
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un módulo (las preguntas se eliminan por CASCADE)
     */
    public function destroy($id)
    {
        try {
            $module = Module::findOrFail($id);
            $module->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Módulo eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar pregunta a un módulo
     */
    public function addQuestion(Request $request, $moduleId)
    {
        $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'nullable|string',  // Permitir nulo
            'option_d' => 'nullable|string',  // Permitir nulo
            'right_answer' => 'required|integer|min:1|max:4',
            'points' => 'sometimes|integer|min:1|max:100',
        ]);

        try {
            $module = Module::findOrFail($moduleId);
            
            // Obtener el último orden
            $lastOrder = ModuleQuestion::where('module_id', $moduleId)->max('order_position') ?? 0;
            
            $question = $module->questions()->create([
                'question_text' => $request->question_text,
                'option_a' => $request->option_a,
                'option_b' => $request->option_b,
                'option_c' => $request->option_c,
                'option_d' => $request->option_d,
                'right_answer' => $request->right_answer,
                'points' => $request->points ?? 10,
                'order_position' => $lastOrder + 1,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pregunta agregada correctamente',
                'data' => $question
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@addQuestion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una pregunta
     */
    public function updateQuestion(Request $request, $moduleId, $questionId)
    {
        $request->validate([
            'question_text' => 'sometimes|string',
            'option_a' => 'sometimes|string',
            'option_b' => 'sometimes|string',
            'option_c' => 'nullable|string',
            'option_d' => 'nullable|string',
            'right_answer' => 'sometimes|integer|min:1|max:4',
            'points' => 'sometimes|integer|min:1|max:100',
        ]);

        try {
            $question = ModuleQuestion::where('module_id', $moduleId)
                                      ->where('id', $questionId)
                                      ->firstOrFail();
            $question->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Pregunta actualizada correctamente',
                'data' => $question
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@updateQuestion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una pregunta
     */
    public function deleteQuestion($moduleId, $questionId)
    {
        try {
            $question = ModuleQuestion::where('module_id', $moduleId)
                                      ->where('id', $questionId)
                                      ->firstOrFail();
            $question->delete();
            
            // Reordenar las preguntas restantes
            $this->reorderQuestions($moduleId);
            
            return response()->json([
                'success' => true,
                'message' => 'Pregunta eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@deleteQuestion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mover una pregunta (cambiar orden)
     */
    public function moveQuestion(Request $request, $moduleId, $questionId)
    {
        $request->validate([
            'direction' => 'required|in:up,down'
        ]);

        try {
            $question = ModuleQuestion::where('module_id', $moduleId)
                                      ->where('id', $questionId)
                                      ->firstOrFail();
            
            $currentOrder = $question->order_position;
            
            if ($request->direction === 'up') {
                // Mover hacia arriba (disminuir orden)
                $swapQuestion = ModuleQuestion::where('module_id', $moduleId)
                    ->where('order_position', '<', $currentOrder)
                    ->orderBy('order_position', 'desc')
                    ->first();
                
                if ($swapQuestion) {
                    $question->update(['order_position' => $swapQuestion->order_position]);
                    $swapQuestion->update(['order_position' => $currentOrder]);
                }
            } else {
                // Mover hacia abajo (aumentar orden)
                $swapQuestion = ModuleQuestion::where('module_id', $moduleId)
                    ->where('order_position', '>', $currentOrder)
                    ->orderBy('order_position', 'asc')
                    ->first();
                
                if ($swapQuestion) {
                    $question->update(['order_position' => $swapQuestion->order_position]);
                    $swapQuestion->update(['order_position' => $currentOrder]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en ModuleController@moveQuestion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reordenar preguntas después de eliminar una
     */
    private function reorderQuestions($moduleId)
    {
        $questions = ModuleQuestion::where('module_id', $moduleId)
            ->orderBy('order_position')
            ->get();
        
        foreach ($questions as $index => $question) {
            $question->update(['order_position' => $index + 1]);
        }
    }
}