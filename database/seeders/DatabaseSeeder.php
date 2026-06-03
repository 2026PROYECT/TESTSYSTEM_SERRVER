<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            CareerSeeder::class,
            AdminSeeder::class,
            LanguageSeeder::class,      // 1. Idiomas
            //QuizSeeder::class,          // 2. Exámenes (normales + modular)
            //ModuleSeeder::class,        // 3. Módulos (con niveles)
            //ModuleQuestionSeeder::class, // 4. Preguntas de módulos
            StudentSeeder::class,     
            //QuestionBankSeeder::class,
            OralQuestionSeeder::class,
            SettingSeeder::class,
            TimeSlotConfigSeeder::class,
            TimeSlotExceptionSeeder::class,
            TimeSlotBreaksSeeder::class,
        ]);
    }
}