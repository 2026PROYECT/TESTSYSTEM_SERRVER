<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionBank;
use Carbon\Carbon;

class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        // Definimos el ID del idioma (1 = Inglés)
        $langId = 1;
        $now = Carbon::now();

        $questionsByQuiz = [
            1 => [
                // [Pregunta, Opt A, Opt B, Opt C, Opt D, Respuesta(1-4), Area(L/R)]
                ['What is the capital of France?', 'Madrid', 'Paris', 'Rome', 'Berlin', 2, 'R'],
                ['Who wrote Don Quixote?', 'Cervantes', 'Shakespeare', 'Goethe', 'Neruda', 1, 'R'],
                ['What is the result of 5x6?', '30', '25', '20', '35', 1, 'R'],
                ['Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 2, 'L'],
                ['Which is the longest river in the world?', 'Nile', 'Amazon', 'Yangtze', 'Mississippi', 2, 'L'],
                ['In which year did World War II begin?', '1939', '1945', '1914', '1929', 1, 'R'],
                ['What is the chemical symbol for gold?', 'Ag', 'Au', 'Fe', 'Pb', 2, 'L'],
                ['Who painted the Mona Lisa?', 'Picasso', 'Van Gogh', 'Da Vinci', 'Dali', 3, 'R'],
                ['Which is the largest ocean?', 'Atlantic', 'Indian', 'Pacific', 'Arctic', 3, 'L'],
                ['Which country has the shape of a boot?', 'Spain', 'Italy', 'Greece', 'Portugal', 2, 'R'],
                ['What is the official language of Brazil?', 'Spanish', 'Portuguese', 'French', 'English', 2, 'L'],
                ['Which is the lightest metal?', 'Aluminum', 'Lithium', 'Iron', 'Copper', 2, 'L'],
                ['Which continent has the most countries?', 'Asia', 'Africa', 'Europe', 'America', 2, 'R'],
                ['Which is the fastest land animal?', 'Lion', 'Cheetah', 'Horse', 'Eagle', 2, 'L'],
                ['Who discovered America?', 'Magellan', 'Columbus', 'Vespucci', 'Cook', 2, 'R'],
                ['Which is the highest mountain in the world?', 'K2', 'Everest', 'Makalu', 'Kilimanjaro', 2, 'L'],
                ['Which is the most populated country?', 'India', 'China', 'USA', 'Indonesia', 2, 'R'],
                ['Which gas do we mainly breathe?', 'Oxygen', 'Nitrogen', 'CO2', 'Hydrogen', 2, 'L'],
                ['Which is the most popular sport in the world?', 'Basketball', 'Football', 'Tennis', 'Cricket', 2, 'R'],
                ['Which is the coldest continent?', 'Europe', 'Asia', 'Antarctica', 'America', 3, 'L'],
            ],
            2 => [
                ['What is the capital of Germany?', 'Berlin', 'Munich', 'Hamburg', 'Frankfurt', 1, 'R'],
                ['Who developed the theory of relativity?', 'Newton', 'Einstein', 'Galileo', 'Tesla', 2, 'L'],
                ['What is the result of 12x12?', '124', '144', '132', '120', 2, 'R'],
                ['Which planet has rings?', 'Mars', 'Saturn', 'Venus', 'Neptune', 2, 'L'],
                ['Which is the most powerful river by flow?', 'Nile', 'Amazon', 'Yangtze', 'Mississippi', 2, 'L'],
                ['In which year did World War II end?', '1939', '1945', '1918', '1950', 2, 'R'],
                ['What is the chemical symbol for silver?', 'Ag', 'Au', 'Fe', 'Pb', 1, 'L'],
                ['Who painted Las Meninas?', 'Velazquez', 'Goya', 'Picasso', 'Dali', 1, 'R'],
                ['Which is the smallest ocean?', 'Atlantic', 'Indian', 'Arctic', 'Pacific', 3, 'L'],
                ['Which country is called the Land of the Rising Sun?', 'China', 'Japan', 'Korea', 'India', 2, 'R'],
                ['What is the capital of Russia?', 'Moscow', 'St. Petersburg', 'Kiev', 'Warsaw', 1, 'R'],
                ['Who wrote The Divine Comedy?', 'Dante', 'Boccaccio', 'Petrarch', 'Virgil', 1, 'L'],
                ['What is the result of 7x8?', '54', '56', '64', '48', 2, 'R'],
                ['Which planet is closest to the sun?', 'Venus', 'Mercury', 'Mars', 'Earth', 2, 'L'],
                ['Which is the largest lake in the world?', 'Victoria', 'Superior', 'Caspian', 'Tanganyika', 3, 'L'],
                ['In which year did the Western Roman Empire fall?', '476', '1492', '1453', '800', 1, 'R'],
                ['What is the chemical symbol for iron?', 'Fe', 'Au', 'Ag', 'Pb', 1, 'L'],
                ['Who painted The Last Supper?', 'Da Vinci', 'Michelangelo', 'Raphael', 'Botticelli', 1, 'R'],
                ['Which is the saltiest sea?', 'Dead Sea', 'Red Sea', 'Mediterranean', 'Black Sea', 1, 'L'],
                ['Which country is famous for tulips?', 'Spain', 'Netherlands', 'Belgium', 'Switzerland', 2, 'R'],
            ],
            3 => [
                ['What is the capital of Japan?', 'Tokyo', 'Osaka', 'Kyoto', 'Nagoya', 1, 'R'],
                ['Who wrote Hamlet?', 'Shakespeare', 'Cervantes', 'Goethe', 'Moliere', 1, 'L'],
                ['What is the result of 15x2?', '25', '30', '35', '40', 2, 'R'],
                ['Which is the largest planet?', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 1, 'L'],
                ['Which is the deepest lake in the world?', 'Victoria', 'Baikal', 'Tanganyika', 'Superior', 2, 'L'],
                ['In which year was America discovered?', '1492', '1500', '1485', '1510', 1, 'R'],
                ['What is the chemical symbol for copper?', 'Cu', 'Co', 'Ag', 'Au', 1, 'L'],
                ['Who painted Guernica?', 'Picasso', 'Dali', 'Miro', 'Velazquez', 1, 'R'],
                ['Which is the largest sea?', 'Caribbean', 'Mediterranean', 'Red', 'Black', 2, 'L'],
                ['Which country is famous for sushi?', 'China', 'Japan', 'Korea', 'Thailand', 2, 'R'],
                ['What is the capital of Egypt?', 'Cairo', 'Luxor', 'Alexandria', 'Giza', 1, 'R'],
                ['Who wrote The Iliad?', 'Homer', 'Virgil', 'Plato', 'Aristotle', 1, 'L'],
                ['What is the result of 20x5?', '100', '120', '80', '90', 1, 'R'],
                ['Which planet is known as a gas giant?', 'Mars', 'Jupiter', 'Venus', 'Mercury', 2, 'L'],
                ['Which is the highest navigable lake?', 'Titicaca', 'Victoria', 'Superior', 'Baikal', 1, 'L'],
                ['In which year did World War I begin?', '1914', '1939', '1929', '1945', 1, 'R'],
                ['What is the chemical symbol for lead?', 'Pb', 'Fe', 'Au', 'Ag', 1, 'L'],
                ['Who painted The School of Athens?', 'Raphael', 'Michelangelo', 'Da Vinci', 'Velazquez', 1, 'R'],
                ['Which is the coldest sea?', 'Arctic', 'Antarctic', 'Mediterranean', 'Caribbean', 1, 'L'],
                ['Which country is famous for pyramids?', 'Mexico', 'Egypt', 'Peru', 'India', 2, 'R'],
            ],
            4 => [
                ['What is the capital of Italy?', 'Rome', 'Milan', 'Venice', 'Florence', 1, 'R'],
                ['Who wrote The Odyssey?', 'Homer', 'Virgil', 'Plato', 'Aristotle', 1, 'L'],
                ['What is the result of 9x9?', '81', '72', '99', '90', 1, 'R'],
                ['Which is the largest planet?', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 1, 'L'],
                ['Which is the largest lake?', 'Victoria', 'Superior', 'Caspian', 'Tanganyika', 3, 'L'],
                ['In which year did the Western Roman Empire fall?', '476', '1492', '1453', '800', 1, 'R'],
                ['What is the chemical symbol for iron?', 'Fe', 'Au', 'Ag', 'Pb', 1, 'L'],
                ['Who painted The Last Supper?', 'Da Vinci', 'Michelangelo', 'Raphael', 'Botticelli', 1, 'R'],
                ['Which is the saltiest sea?', 'Dead Sea', 'Red Sea', 'Mediterranean', 'Black Sea', 1, 'L'],
                ['Which country is famous for tulips?', 'Spain', 'Netherlands', 'Belgium', 'Switzerland', 2, 'R'],
                ['What is the capital of Canada?', 'Toronto', 'Ottawa', 'Vancouver', 'Montreal', 2, 'R'],
                ['Who wrote Pride and Prejudice?', 'Jane Austen', 'Emily Brontë', 'Charles Dickens', 'Mark Twain', 1, 'L'],
                ['What is the result of 8x7?', '54', '56', '64', '48', 2, 'R'],
                ['Which planet is known as the Morning Star?', 'Venus', 'Mars', 'Mercury', 'Jupiter', 1, 'L'],
                ['Which is the largest desert in the world?', 'Sahara', 'Gobi', 'Kalahari', 'Arctic', 1, 'L'],
                ['In which year did the American Civil War begin?', '1861', '1776', '1812', '1865', 1, 'R'],
                ['What is the chemical symbol for sodium?', 'Na', 'So', 'Sn', 'Nd', 1, 'L'],
                ['Who painted Starry Night?', 'Van Gogh', 'Monet', 'Cezanne', 'Rembrandt', 1, 'R'],
                ['Which is the hottest planet in the solar system?', 'Mercury', 'Venus', 'Mars', 'Jupiter', 2, 'L'],
                ['Which country is famous for kangaroos?', 'New Zealand', 'Australia', 'South Africa', 'Argentina', 2, 'R'],
            ],
        ];

        foreach ($questionsByQuiz as $quizId => $questions) {
            foreach ($questions as $q) {
                QuestionBank::create([
                    'quiz_id'      => $quizId,
                    'language_id'  => $langId, // <--- Inyectado aquí
                    'question1'    => $q[0],
                    'option_a'     => $q[1],
                    'option_b'     => $q[2],
                    'option_c'     => $q[3],
                    'option_d'     => $q[4],
                    'right_answer' => $q[5],
                    'area'         => $q[6],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }
    }
}