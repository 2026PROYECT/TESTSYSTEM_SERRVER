<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\QuizType;           // 👈 Tu Enum para los tipos
use Illuminate\Validation\Rule;   // 👈 ¡ESTA ES LA LÍNEA QUE FALTA!

class StoreQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:1|max:100',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'subject_name' => 'required|string|min:1|max:100',
            'quiz_date' => 'required|date',
            'total_mark' => 'nullable|numeric',
            'pass_mark' => 'nullable|numeric',
            'language_id' => 'required|integer',
            // Añadimos la validación del Enum aquí:
            'type' => ['required', Rule::enum(QuizType::class)],
            'total_questions' => 'required|integer|min:5|max:100', // 👈 AÑADE
        ];
    }
}
