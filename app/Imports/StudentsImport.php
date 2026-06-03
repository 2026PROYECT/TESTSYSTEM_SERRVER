<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string'],
            'lastname'  => ['required', 'string'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'career_id' => ['required', 'exists:careers,id'],
            'id_number' => ['required', 'unique:students,id_number'],
            'semester'  => ['nullable', 'integer'],
        ];
    }

    public function model(array $row)
{
    return DB::transaction(function () use ($row) {
        // 1. Crear el Usuario
        $user = User::create([
            'name'     => $row['name'],
            'lastname' => $row['lastname'],
            'surname'  => $row['surname'] ?? null,
            'email'    => $row['email'],
            
            // CAMBIO CLAVE: Usamos el saga_code como contraseña. 
            // Si por alguna razón viene vacío, usamos 'Emi123456' como respaldo.
            'password' => Hash::make($row['saga_code'] ?? 'Emi123456'),
            
            'role'     => 'student',
        ]);

        // 2. Crear el perfil de Estudiante (vínculo automático)
        $user->student()->create([
            'career_id' => $row['career_id'],
            'saga_code' => $row['saga_code'] ?? null,
            'id_number' => $row['id_number'],
            'semester'  => $row['semester'] ?? 1,
        ]);

        return $user;
    });
}
}