<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],  // Campo nuevo
            'surname' => ['nullable', 'string', 'max:255'],   // Campo nuevo (opcional)
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'], // Campo nuevo para foto
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.string' => 'El nombre debe ser texto',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            
            'lastname.required' => 'El apellido paterno es obligatorio',
            'lastname.string' => 'El apellido debe ser texto',
            'lastname.max' => 'El apellido no puede tener más de 255 caracteres',
            
            'surname.string' => 'El apellido materno debe ser texto',
            'surname.max' => 'El apellido materno no puede tener más de 255 caracteres',
            
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'Debe ingresar un correo electrónico válido',
            'email.unique' => 'Este correo electrónico ya está registrado',
            
            'picture.image' => 'El archivo debe ser una imagen',
            'picture.mimes' => 'La imagen debe ser de tipo: JPEG, PNG o JPG',
            'picture.max' => 'La imagen no debe superar los 2MB',
        ];
    }
}