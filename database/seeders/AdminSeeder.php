<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Crear Administrador
        User::create([
            'name'      => 'System',
            'lastname'  => 'Administrator',
            'surname'   => null,
            'email'     => 'admin@test.com',
            'password'  => Hash::make('pass1234'),
            'role'      => 'admin',
            'picture'   => null,
        ]);

        // Crear Profesor (Añadido aquí mismo)
        User::create([
            'name'      => 'John',
            'lastname'  => 'Doe',
            'surname'   => 'Teacher',
            'email'     => 'admin@gmail.com',
            'password'  => Hash::make('pass1234'),
            'role'      => 'teacher',
            'picture'   => null,
        ]);
    }
}