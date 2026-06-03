<?php

namespace App\Services;

use App\Models\QuestionBank;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QuestionService
{
    public function allPaginate($request, $perPage = 10)
    {
        return QuestionBank::query() 
            ->with('quiz:id,title')
            ->when($request->quiz_id, function ($query, $quizId) {
                $query->where('quiz_id', $quizId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('question1', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function all($request)
    {
        return QuestionBank::query()
            ->when($request->quiz_id, function ($query, $quizId) {
                $query->where('quiz_id', $quizId);
            })
            ->select('id', 'question1', 'question2', 'question3')
            ->get();
    }

    public function store($request)
    {
        try {
            $data = $request->validated();
            $quizId = $data['quiz_id'];
            
            $questionData = [
                'quiz_id'      => $quizId,
                'question1'    => $data['question1'] ?? null,
                'question2'    => $data['question2'] ?? null,
                'question3'    => $data['question3'] ?? null,
                'option_a'     => $data['option_a'],
                'option_b'     => $data['option_b'],
                'option_c'     => $data['option_c'],
                'option_d'     => $data['option_d'],
                'right_answer' => $data['right_answer'],
                'area'         => $data['area'],
            ];
            
            // Procesar picture - Guardar en questions/pictures/quiz_id/
            if ($request->hasFile('picture')) {
                $file = $request->file('picture');
                $extension = $file->getClientOriginalExtension();
                $name = time() . '_' . uniqid() . '.' . $extension;
                $path = "questions/pictures/{$quizId}/" . $name;
                Storage::disk('public')->put($path, file_get_contents($file));
                $questionData['picture'] = $path;
                Log::info('Imagen guardada en: ' . $path);
            }
            
            // Procesar sound - Guardar en questions/sounds/quiz_id/
            if ($request->hasFile('sound')) {
                $file = $request->file('sound');
                $name = time() . '_' . uniqid() . '.mp3';
                $path = "questions/sounds/{$quizId}/" . $name;
                Storage::disk('public')->put($path, file_get_contents($file));
                $questionData['sound'] = $path;
                Log::info('Audio guardado en: ' . $path);
            }
            
            return QuestionBank::create($questionData);
            
        } catch (\Exception $e) {
            throw new \Exception('Error al guardar: ' . $e->getMessage());
        }
    }

    public function update($request, $id)
    {
        try {
            $data = $request->validated();
            $question = $this->show($id);
            $quizId = $data['quiz_id'];

            $questionData = [
                'quiz_id'      => $quizId,
                'question1'    => $data['question1'] ?? null,
                'question2'    => $data['question2'] ?? null,
                'question3'    => $data['question3'] ?? null,
                'option_a'     => $data['option_a'],
                'option_b'     => $data['option_b'],
                'option_c'     => $data['option_c'],
                'option_d'     => $data['option_d'],
                'right_answer' => $data['right_answer'],
                'area'         => $data['area'],
            ];

            // Procesar picture - Guardar en questions/pictures/quiz_id/
            if ($request->hasFile('picture')) {
                // Eliminar imagen anterior si existe
                if ($question->picture && Storage::disk('public')->exists($question->picture)) {
                    Storage::disk('public')->delete($question->picture);
                    Log::info('Imagen anterior eliminada: ' . $question->picture);
                }
                
                $file = $request->file('picture');
                $extension = $file->getClientOriginalExtension();
                $name = time() . '_' . uniqid() . '.' . $extension;
                $path = "questions/pictures/{$quizId}/" . $name;
                Storage::disk('public')->put($path, file_get_contents($file));
                $questionData['picture'] = $path;
                Log::info('Imagen actualizada en: ' . $path);
            }

            // Procesar sound - Guardar en questions/sounds/quiz_id/
            if ($request->hasFile('sound')) {
                // Eliminar audio anterior si existe
                if ($question->sound && Storage::disk('public')->exists($question->sound)) {
                    Storage::disk('public')->delete($question->sound);
                    Log::info('Audio anterior eliminado: ' . $question->sound);
                }
                
                $file = $request->file('sound');
                $name = time() . '_' . uniqid() . '.mp3';
                $path = "questions/sounds/{$quizId}/" . $name;
                Storage::disk('public')->put($path, file_get_contents($file));
                $questionData['sound'] = $path;
                Log::info('Audio actualizado en: ' . $path);
            }

            $question->update($questionData);
            return $question;
            
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        return QuestionBank::findOrFail($id);
    }

    public function delete($request, $id)
    {
        try {
            $question = $this->show($id);
            
            // Eliminar imagen asociada si existe
            if ($question->picture && Storage::disk('public')->exists($question->picture)) {
                Storage::disk('public')->delete($question->picture);
                Log::info('Imagen eliminada: ' . $question->picture);
            }
            
            // Eliminar audio asociado si existe
            if ($question->sound && Storage::disk('public')->exists($question->sound)) {
                Storage::disk('public')->delete($question->sound);
                Log::info('Audio eliminado: ' . $question->sound);
            }
            
            return $question;
            
        } catch (\Exception $e) {
            throw new \Exception('Error al preparar eliminación: ' . $e->getMessage());
        }
    }
}