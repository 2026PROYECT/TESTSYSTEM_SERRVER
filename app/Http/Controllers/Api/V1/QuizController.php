<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Quiz;
use App\Models\AuditLog; // ← Agregar
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\QuizService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Models\Question;
use App\Models\QuizAssignment;
use App\Models\QuestionResult;
use App\Enums\QuizType;

class QuizController extends Controller
{
    use ApiResponse;

    public function __construct(
        private QuizService $quizService
    ) {}

    /**
     * Helper para registrar logs de auditoría
     */
    private function logAudit($action, $data = [])
    {
        if (!auth()->check()) {
            return;
        }
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_data' => $data['old_data'] ?? null,
            'new_data' => $data['new_data'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'severity' => $data['severity'] ?? 'info'
        ]);
    }

    /**
     * Método para obtener todos los quices (usado por selectores o listas rápidas)
     * Responde a: GET /api/v1/quizzes/all
     */
    public function all(Request $request)
    {
        try {
            $data = Quiz::withCount('questions')->get(); 
            
            return $this->ResponseSuccess($data);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCompQuizzes(Request $request)
{
    try {
        $languageId = $request->query('language_id');
        
        $query = Quiz::where('type', 'normal'); // o 'comp'
        
        if ($languageId) {
            $query->where('language_id', $languageId);
        }
        
        $quizzes = $query->select('id', 'title', 'duration_minutes', 'total_questions')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/**
 * Obtener lista de exámenes modulares para estudiantes
 */
public function getModularQuizzes(Request $request)
{
    try {
        $quizzes = Quiz::where('type', 'modular')
            ->select('id', 'title', 'duration_minutes', 'total_questions')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Listado paginado principal para la tabla de administración
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $data = $this->quizService->allPaginate($request, $perPage);
        
        if ($data->isEmpty()) {
            return $this->ResponseSuccess([], ['count' => 0], 'No Data Found!');
        }

        $metadata['count'] = $data->total();
        return $this->ResponseSuccess($data, $metadata);
    }

    /**
     * Almacenar un nuevo Quiz
     */
    public function store(StoreQuizRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->quizService->store($request);
            DB::commit();
            
            // ✅ LOG DE AUDITORÍA: Creación de examen
            $this->logAudit('Creación de examen', [
                'entity_type' => 'quiz',
                'entity_id' => $data->id ?? null,
                'new_data' => [
                    'title' => $data->title ?? 'N/A',
                    'type' => $data->type ?? 'N/A',
                    'language_id' => $data->language_id ?? 'N/A'
                ],
                'severity' => 'info'
            ]);
            
            return $this->ResponseSuccess($data, null, 'Quiz created successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->ResponseError($e->getMessage(), null, 'Data Process Error!');
        }
    }

    /**
     * Mostrar un Quiz específico
     */
    public function show(string $id)
    {
        $data = $this->quizService->show($id);
        if (!$data) {
            return $this->ResponseError('Quiz not found', null, 404);
        }
        return $this->ResponseSuccess($data);
    }

    /**
     * Actualizar un Quiz
     */
    public function update(StoreQuizRequest $request, string $id)
    {
        // Obtener datos antiguos antes de actualizar
        $oldQuiz = Quiz::find($id);
        $oldData = $oldQuiz ? [
            'title' => $oldQuiz->title,
            'type' => $oldQuiz->type,
            'language_id' => $oldQuiz->language_id,
            'duration_minutes' => $oldQuiz->duration_minutes,
            'total_questions' => $oldQuiz->total_questions,
        ] : null;
        
        DB::beginTransaction();
        try {
            $data = $this->quizService->update($request, $id);
            DB::commit();
            
            // ✅ LOG DE AUDITORÍA: Actualización de examen (solo si hubo cambios)
            $newData = $data ? [
                'title' => $data->title ?? 'N/A',
                'type' => $data->type ?? 'N/A',
                'language_id' => $data->language_id ?? 'N/A',
                'duration_minutes' => $data->duration_minutes ?? 'N/A',
                'total_questions' => $data->total_questions ?? 'N/A',
            ] : null;
            
            if ($oldData && $newData && $oldData != $newData) {
                $this->logAudit('Actualización de examen', [
                    'entity_type' => 'quiz',
                    'entity_id' => (int)$id,
                    'old_data' => $oldData,
                    'new_data' => $newData,
                    'severity' => 'warning'
                ]);
            }
            
            return $this->ResponseSuccess($data, null, 'Quiz updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->ResponseError($e->getMessage(), null, 'Update Error!');
        }
    }

    /**
     * Eliminar un Quiz
     */
    public function destroy(string $id)
    {
        // Obtener datos antes de eliminar
        $quiz = Quiz::find($id);
        $quizData = $quiz ? [
            'title' => $quiz->title,
            'type' => $quiz->type,
            'language_id' => $quiz->language_id,
        ] : null;
        
        DB::beginTransaction();
        try {
            $result = $this->quizService->delete($id);
            
            if (!$result) {
                DB::rollback();
                return $this->ResponseError('Data Not Found!', null, 404);
            }

            $result->delete();
            DB::commit();
            
            // ✅ LOG DE AUDITORÍA: Eliminación de examen
            $this->logAudit('Eliminación de examen', [
                'entity_type' => 'quiz',
                'entity_id' => (int)$id,
                'old_data' => $quizData,
                'severity' => 'danger'
            ]);
            
            return $this->ResponseSuccess(null, null, 'Quiz deleted successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->ResponseError($e->getMessage(), null, 'Delete Error!');
        }
    }
    
    public function getTypes()
    {
        try {
            $types = collect(QuizType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]);

            return $this->ResponseSuccess($types);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}