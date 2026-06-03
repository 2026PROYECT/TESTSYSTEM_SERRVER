<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_id'      => 'required|exists:quizzes,id',
            'question1'    => 'nullable|string',
            'question2'    => 'nullable|string',
            'question3'    => 'nullable|string',
            'picture'      => 'nullable|image|max:4048',
            'sound'        => 'nullable|file|max:10240', // Solo tamaño, no tipo
            'option_a'     => 'required|string',
            'option_b'     => 'required|string',
            'option_c'     => 'required|string',
            'option_d'     => 'required|string',
            'right_answer' => 'required|integer|between:1,4',
            'area'         => 'required|string|in:L,R',
        ];
    }

    public function messages(): array
    {
        return [
            'quiz_id.required'   => 'El quiz es requerido',
            'option_a.required'  => 'La opción A es requerida',
            'option_b.required'  => 'La opción B es requerida',
            'option_c.required'  => 'La opción C es requerida',
            'option_d.required'  => 'La opción D es requerida',
            'right_answer.required' => 'La respuesta correcta es requerida',
            'area.required'      => 'El área es requerida',
            'sound.max'          => 'El audio no debe superar los 10MB',
        ];
    }
}