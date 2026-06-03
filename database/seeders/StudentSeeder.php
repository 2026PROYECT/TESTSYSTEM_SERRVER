<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Career;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar la carrera (asegúrate que CareerSeeder ya corrió)
        $career = Career::where('name', 'Computer Science')->first();
        $careerId = $career ? $career->id : 1;

        // Lista de estudiantes a crear
        $students = [
            [
                'name'      => 'John',
                'lastname'  => 'Doe',
                'surname'   => 'Smith',
                'email'     => 'student1@test.com',
                'saga_code' => 'SAGA001',
                'id_number' => 'STU001',
            ],
            [
                'name'      => 'Jane',
                'lastname'  => 'Doe',
                'surname'   => 'Brown',
                'email'     => 'student2@test.com',
                'saga_code' => 'SAGA002',
                'id_number' => 'STU002',
            ],
            [
                'name'      => 'Alice',
                'lastname'  => 'Johnson',
                'surname'   => 'Taylor',
                'email'     => 'student3@test.com',
                'saga_code' => 'SAGA003',
                'id_number' => 'STU003',
            ],
            [
                'name'      => 'Bob',
                'lastname'  => 'Williams',
                'surname'   => 'Lee',
                'email'     => 'goperue@gmail.com',
                'saga_code' => 'SAGA004',
                'id_number' => 'STU004',
            ],
        ];

        foreach ($students as $data) {
            // Crear usuario
            $user = User::create([
                'name'      => $data['name'],
                'lastname'  => $data['lastname'],
                'surname'   => $data['surname'],
                'email'     => $data['email'],
                'password'  => Hash::make('pass1234'), // misma contraseña
                'role'      => 'student',
                'picture'   => null,
            ]);

            // Crear perfil de estudiante
            Student::create([
                'user_id'   => $user->id,
                'career_id' => $careerId,
                'saga_code' => $data['saga_code'],
                'id_number' => $data['id_number'],
                'semester'  => 1,
            ]);
        }
    }
}
