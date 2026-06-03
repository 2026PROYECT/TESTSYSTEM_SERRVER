<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Career;

class CareerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $careers = [
            ['name' => 'Ingenieria en Sistemas'],
            ['name' => 'Ingenieria Industrial'],
            ['name' => 'Ingenieria Civil'],
            ['name' => 'Ingenieria Comercial'],
            ['name' => 'Ingenieria Geografica'],
            ['name' => 'Ingenieria Mecatronica'],
            ['name' => 'Ingenieria Financiera'],
            ['name' => 'Ingenieria Ambiental'],
            ['name' => 'Ingenieria Petrolera'],
            ['name' => 'Ingenieria Sistemas Electronicos'],
        ];

        foreach ($careers as $career) {
            // Using updateOrCreate prevents "Duplicate Entry" errors
            Career::updateOrCreate(
                ['name' => $career['name']], // Search criteria
                $career                      // Data to insert/update
            );
        }
    }
}