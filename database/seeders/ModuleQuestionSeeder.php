<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\ModuleQuestion;

class ModuleQuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener módulos por título
        $modules = Module::get()->keyBy('title');

        // ========== PREGUNTAS READING A1 - My Family ==========
        $moduleId = $modules['Reading A1 - My Family']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What does the father do?', 'options' => ['Teacher', 'Doctor', 'Engineer', 'Lawyer'], 'answer' => 2],
            ['text' => 'What is the mother?', 'options' => ['Doctor', 'Nurse', 'Teacher', 'Chef'], 'answer' => 3],
            ['text' => 'What is the sister\'s name?', 'options' => ['Maria', 'Anna', 'Lisa', 'Emma'], 'answer' => 2],
        ]);

        // ========== PREGUNTAS READING A1 - My House ==========
        $moduleId = $modules['Reading A1 - My House']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'How many bedrooms does the house have?', 'options' => ['One', 'Two', 'Three', 'Four'], 'answer' => 2],
            ['text' => 'Does the person like their house?', 'options' => ['Yes', 'No', 'Not specified', 'Maybe'], 'answer' => 1],
        ]);

        // ========== PREGUNTAS READING A2 - Daily Routine ==========
        $moduleId = $modules['Reading A2 - Daily Routine']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What time does the person wake up?', 'options' => ['6:00 AM', '6:30 AM', '7:00 AM', '7:30 AM'], 'answer' => 3],
            ['text' => 'What time does the person finish work?', 'options' => ['4:00 PM', '5:00 PM', '6:00 PM', '7:00 PM'], 'answer' => 2],
        ]);

        // ========== PREGUNTAS READING A2 - My Job ==========
        $moduleId = $modules['Reading A2 - My Job']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'Where does the person work?', 'options' => ['School', 'Hospital', 'Office', 'Factory'], 'answer' => 3],
            ['text' => 'Why does the person like their job?', 'options' => ['Good salary', 'Nice colleagues', 'Short hours', 'Easy work'], 'answer' => 2],
        ]);

        // ========== PREGUNTAS READING B1 - Travel Experiences ==========
        $moduleId = $modules['Reading B1 - Travel Experiences']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'Where did the person travel?', 'options' => ['London', 'Paris', 'Rome', 'Berlin'], 'answer' => 2],
            ['text' => 'What landmark is mentioned?', 'options' => ['Big Ben', 'Colosseum', 'Eiffel Tower', 'Brandenburg Gate'], 'answer' => 3],
        ]);

        // ========== PREGUNTAS READING B1 - Technology ==========
        $moduleId = $modules['Reading B1 - Technology']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What has changed communication?', 'options' => ['Computers', 'Smartphones', 'Tablets', 'Laptops'], 'answer' => 2],
            ['text' => 'Who can we talk with?', 'options' => ['Family only', 'Friends only', 'People anywhere', 'Colleagues only'], 'answer' => 3],
        ]);

        // ========== PREGUNTAS READING B2 - Climate Change ==========
        $moduleId = $modules['Reading B2 - Climate Change']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What is the main topic?', 'options' => ['Pollution', 'Global warming', 'Deforestation', 'Recycling'], 'answer' => 2],
            ['text' => 'What must be reduced?', 'options' => ['Water', 'Energy', 'Carbon emissions', 'Waste'], 'answer' => 3],
        ]);

        // ========== PREGUNTAS READING B2 - Economy ==========
        $moduleId = $modules['Reading B2 - Economy']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What has increased?', 'options' => ['Employment', 'Production', 'Inflation rates', 'Exports'], 'answer' => 3],
            ['text' => 'What do experts predict?', 'options' => ['Recession', 'Recovery', 'Stagnation', 'Crisis'], 'answer' => 2],
        ]);

        // ========== PREGUNTAS LISTENING A1 - Introducing Yourself ==========
        $moduleId = $modules['Listening A1 - Introducing Yourself']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What is the person\'s name?', 'options' => ['Peter', 'John', 'Mike', 'David'], 'answer' => 2],
            ['text' => 'Where is the person from?', 'options' => ['France', 'Italy', 'Spain', 'Portugal'], 'answer' => 3],
        ]);

        // ========== PREGUNTAS LISTENING A1 - My School ==========
        $moduleId = $modules['Listening A1 - My School']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What is the name of the school?', 'options' => ['Greenfield', 'Sunset', 'Riverside', 'Hilltop'], 'answer' => 1],
            ['text' => 'What is the favorite subject?', 'options' => ['Math', 'Science', 'History', 'Art'], 'answer' => 2],
        ]);

        // ========== PREGUNTAS LISTENING A2 - At the Restaurant ==========
        $moduleId = $modules['Listening A2 - At the Restaurant']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What is the special dish today?', 'options' => ['Pizza', 'Pasta', 'Salad', 'Soup'], 'answer' => 2],
            ['text' => 'What does the waiter offer?', 'options' => ['Menu', 'Water', 'Bread', 'Wine'], 'answer' => 1],
        ]);

        // ========== PREGUNTAS LISTENING A2 - Shopping ==========
        $moduleId = $modules['Listening A2 - Shopping']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What time does the store open?', 'options' => ['8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM'], 'answer' => 2],
            ['text' => 'What is the discount?', 'options' => ['10%', '15%', '20%', '25%'], 'answer' => 3],
        ]);

        // ========== PREGUNTAS LISTENING B1 - News Report ==========
        $moduleId = $modules['Listening B1 - News Report']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What improves mental health?', 'options' => ['Diet', 'Exercise', 'Sleep', 'Meditation'], 'answer' => 2],
            ['text' => 'How many minutes are recommended?', 'options' => ['15', '30', '45', '60'], 'answer' => 2],
        ]);

        // ========== PREGUNTAS LISTENING B1 - Job Interview ==========
        $moduleId = $modules['Listening B1 - Job Interview']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'How many years of experience?', 'options' => ['Three', 'Four', 'Five', 'Six'], 'answer' => 3],
            ['text' => 'What area is the experience in?', 'options' => ['Marketing', 'Sales', 'Finance', 'HR'], 'answer' => 2],
        ]);

        // ========== PREGUNTAS LISTENING B2 - Business Meeting ==========
        $moduleId = $modules['Listening B2 - Business Meeting']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'What is the profit increase?', 'options' => ['10%', '12%', '15%', '18%'], 'answer' => 3],
            ['text' => 'Where does the company need to expand?', 'options' => ['New markets', 'New products', 'New offices', 'New employees'], 'answer' => 1],
        ]);

        // ========== PREGUNTAS LISTENING B2 - Academic Lecture ==========
        $moduleId = $modules['Listening B2 - Academic Lecture']->id;
        $this->createQuestions($moduleId, [
            ['text' => 'When did the Industrial Revolution begin?', 'options' => ['17th', '18th', '19th', '20th'], 'answer' => 2],
            ['text' => 'What did it transform?', 'options' => ['Agriculture', 'Manufacturing', 'Transportation', 'Communication'], 'answer' => 2],
        ]);

        $this->command->info('✅ Preguntas creadas exitosamente');
    }

    private function createQuestions($moduleId, $questions)
    {
        foreach ($questions as $index => $q) {
            ModuleQuestion::create([
                'module_id' => $moduleId,
                'question_text' => $q['text'],
                'option_a' => $q['options'][0],
                'option_b' => $q['options'][1],
                'option_c' => $q['options'][2],
                'option_d' => $q['options'][3],
                'right_answer' => $q['answer'],
                'points' => 5,
                'order_position' => $index + 1
            ]);
        }
    }
}