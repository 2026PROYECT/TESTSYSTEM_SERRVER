<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Language;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $language = Language::where('code', 'en')->first();

        $modules = [
            // ========== READING ==========
            [
                'title' => 'Reading A1 - My Family',
                'content' => 'This is my family. My father is a doctor. My mother is a teacher. I have a sister. Her name is Anna.',
                'type' => 'reading',
                'level' => 'A1'
            ],
            [
                'title' => 'Reading A1 - My House',
                'content' => 'I live in a small house. It has two bedrooms, a kitchen, and a garden. I like my house.',
                'type' => 'reading',
                'level' => 'A1'
            ],
            [
                'title' => 'Reading A2 - Daily Routine',
                'content' => 'I wake up at 7 AM. I have breakfast at 7:30. I go to work at 8 AM. I finish work at 5 PM.',
                'type' => 'reading',
                'level' => 'A2'
            ],
            [
                'title' => 'Reading A2 - My Job',
                'content' => 'I work in an office. I start at 9 AM and finish at 5 PM. I like my job because my colleagues are nice.',
                'type' => 'reading',
                'level' => 'A2'
            ],
            [
                'title' => 'Reading B1 - Travel Experiences',
                'content' => 'Last summer I traveled to Paris. The Eiffel Tower was amazing. I tried French food.',
                'type' => 'reading',
                'level' => 'B1'
            ],
            [
                'title' => 'Reading B1 - Technology',
                'content' => 'Smartphones have changed the way we communicate. Now we can talk with people from anywhere.',
                'type' => 'reading',
                'level' => 'B1'
            ],
            [
                'title' => 'Reading B2 - Climate Change',
                'content' => 'Global warming is a serious issue that requires immediate action. Carbon emissions must be reduced.',
                'type' => 'reading',
                'level' => 'B2'
            ],
            [
                'title' => 'Reading B2 - Economy',
                'content' => 'Inflation rates have increased due to global market conditions. Experts predict recovery next year.',
                'type' => 'reading',
                'level' => 'B2'
            ],

            // ========== LISTENING ==========
            [
                'title' => 'Listening A1 - Introducing Yourself',
                'content' => 'Hello, my name is John. I am from Spain. I like football and music.',
                'type' => 'listening',
                'level' => 'A1'
            ],
            [
                'title' => 'Listening A1 - My School',
                'content' => 'I go to Greenfield School. I have many friends there. My favorite subject is science.',
                'type' => 'listening',
                'level' => 'A1'
            ],
            [
                'title' => 'Listening A2 - At the Restaurant',
                'content' => 'Welcome to our restaurant. Would you like to see the menu? Our special today is pasta.',
                'type' => 'listening',
                'level' => 'A2'
            ],
            [
                'title' => 'Listening A2 - Shopping',
                'content' => 'The store opens at 9 AM. There is a sale on clothes today. Everything is 20% off.',
                'type' => 'listening',
                'level' => 'A2'
            ],
            [
                'title' => 'Listening B1 - News Report',
                'content' => 'A new study shows that exercise improves mental health. Experts recommend 30 minutes daily.',
                'type' => 'listening',
                'level' => 'B1'
            ],
            [
                'title' => 'Listening B1 - Job Interview',
                'content' => 'Tell me about your experience. I have worked in sales for five years. Why do you want this job?',
                'type' => 'listening',
                'level' => 'B1'
            ],
            [
                'title' => 'Listening B2 - Business Meeting',
                'content' => 'The quarterly report shows a 15% increase in profits. We need to expand to new markets.',
                'type' => 'listening',
                'level' => 'B2'
            ],
            [
                'title' => 'Listening B2 - Academic Lecture',
                'content' => 'The Industrial Revolution began in the 18th century. It transformed manufacturing and society.',
                'type' => 'listening',
                'level' => 'B2'
            ],
        ];

        foreach ($modules as $module) {
            Module::create([
                'language_id' => $language->id,
                'title' => $module['title'],
                'content' => $module['content'],
                'type' => $module['type'],
                'level' => $module['level']
            ]);
        }

        $this->command->info('✅ Módulos creados: ' . count($modules));
        $this->command->info('   - Reading: 8 módulos (2 por nivel A1, A2, B1, B2)');
        $this->command->info('   - Listening: 8 módulos (2 por nivel A1, A2, B1, B2)');
    }
}