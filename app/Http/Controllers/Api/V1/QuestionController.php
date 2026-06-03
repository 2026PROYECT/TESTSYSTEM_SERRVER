<?php

namespace App\Http\Controllers\Api\V1;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\QuizService;
use App\Services\QuestionService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;

class QuestionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private QuizService $quizService,
        private QuestionService $questionService
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 30);
        $data = $this->questionService->allPaginate($request, $perPage);
        $metadata['count'] = $data->total(); 
        return $this->ResponseSuccess($data, $metadata);
    }

    public function all(Request $request)
    {
        $data = $this->questionService->all($request);
        if ($data->isEmpty()) {
            return $this->ResponseSuccess([], ['count' => 0], 'No Data Found!');
        }
        return $this->ResponseSuccess($data, ['count' => $data->count()]);
    }

    public function store(StoreQuestionRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->questionService->store($request);
            DB::commit();
            return $this->ResponseSuccess($data, null, 'Question created successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->ResponseError($e->getMessage(), null, 'Data Process Error!');
        }
    }

    public function show(string $id)
    {
        $data = $this->questionService->show($id);
        if (!$data) {
            return $this->ResponseError('Question not found', null, 404);
        }
        return $this->ResponseSuccess($data);
    }

    public function update(StoreQuestionRequest $request, string $id)
    {
        $quiz = $this->quizService->show($request->quiz_id);
        if (!$quiz) {
            return $this->ResponseError('Selected Quiz not found', null, 422);
        }

        DB::beginTransaction();
        try {
            $data = $this->questionService->update($request, $id);
            DB::commit();
            return $this->ResponseSuccess($data, null, 'Question updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->ResponseError($e->getMessage(), null, 'Update Error!');
        }
    }

    public function destroy(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $question = $this->questionService->delete($request, $id);
            if (!$question) {
                return $this->ResponseError('Question not found', null, 404);
            }
            $question->delete();
            DB::commit();
            return $this->ResponseSuccess(null, null, 'Question deleted successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->ResponseError($e->getMessage(), null, 'Delete Error!');
        }
    }
}