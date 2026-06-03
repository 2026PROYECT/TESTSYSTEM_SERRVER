<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Language;
use App\Models\Quiz;
use App\Models\QuestionBank;
use App\Models\QuizAssignment;
use App\Models\ExamAttempt;
use Carbon\Carbon;

class SecurityLogsSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('=========================================');
        $this->command->info('🔧 CREANDO DATOS DE PRUEBA');
        $this->command->info('=========================================');

        // ==========================================
        // 1. Crear estudiante
        // ==========================================
        $student = User::updateOrCreate(
            ['email' => 'juan.perez@test.com'],
            [
                'name' => 'Juan',
                'lastname' => 'Pérez',
                'password' => Hash::make('password123'),
                'role' => 'student'
            ]
        );
        $this->command->info('✅ Estudiante: ' . $student->name . ' ' . $student->lastname . ' (ID: ' . $student->id . ')');

        // ==========================================
        // 2. Crear idioma si no existe
        // ==========================================
        $language = Language::first();
        if (!$language) {
            $language = Language::create([
                'name' => 'Inglés',
                'code' => 'EN'
            ]);
            $this->command->info('✅ Idioma creado: ' . $language->name . ' (ID: ' . $language->id . ')');
        } else {
            $this->command->info('✅ Idioma existente: ' . $language->name . ' (ID: ' . $language->id . ')');
        }

        // ==========================================
        // 3. Crear quiz/examen (sin description)
        // ==========================================
        $quiz = Quiz::create([
            'title' => 'Examen de Prueba - Seguridad',
            'language_id' => $language->id,
            'duration_minutes' => 60,
            'total_questions' => 10,
            'status' => 'active'
        ]);
        $this->command->info('✅ Examen creado: ' . $quiz->title . ' (ID: ' . $quiz->id . ')');

        // ==========================================
        // 4. Crear preguntas
        // ==========================================
        for ($i = 1; $i <= 10; $i++) {
            QuestionBank::create([
                'quiz_id' => $quiz->id,
                'question1' => "¿Esta es la pregunta de prueba número {$i}?",
                'option_a' => "Opción A correcta",
                'option_b' => "Opción B",
                'option_c' => "Opción C",
                'option_d' => "Opción D",
                'right_answer' => 1,
                'status' => 'active'
            ]);
        }
        $this->command->info('✅ 10 preguntas creadas');

        // ==========================================
        // 5. Crear asignación (cita)
        // ==========================================
        $assignment = QuizAssignment::create([
            'student_id' => $student->id,
            'language_id' => $language->id,
            'quiz_id' => $quiz->id,
            'test_type' => 'CompTest',
            'start_at' => Carbon::now()->addHours(1),
            'active' => true,
            'attended' => false,
            'passed' => false,
            'is_unlocked' => true
        ]);
        $this->command->info('✅ Asignación creada (ID: ' . $assignment->id . ')');

        // ==========================================
        // 6. Crear intento de examen
        // ==========================================
        $attempt = ExamAttempt::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'language_id' => $language->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addHours(1),
            'current_index' => 0
        ]);
        $this->command->info('✅ Intento de examen creado (ID: ' . $attempt->id . ')');

        // ==========================================
        // 7. Crear logs de seguridad
        // ==========================================
        $logs = [
            [
                'event_type' => 'tab_switch',
                'details' => 'Cambio de pestaña detectado durante el examen'
            ],
            [
                'event_type' => 'devtools_opened',
                'details' => 'Intento de abrir herramientas de desarrollo (F12)'
            ],
            [
                'event_type' => 'screenshot_attempt',
                'details' => 'Intento de captura de pantalla con PrintScreen'
            ],
            [
                'event_type' => 'mouse_leave',
                'details' => 'Mouse salió del área del examen'
            ],
            [
                'event_type' => 'tab_switch',
                'details' => 'Segundo cambio de pestaña (advertencia)'
            ],
            [
                'event_type' => 'copy_attempt',
                'details' => 'Intento de copiar texto del examen'
            ],
            [
                'event_type' => 'tab_switch',
                'details' => 'Tercer cambio de pestaña - EXAMEN INVALIDADO'
            ],
            [
                'event_type' => 'exam_invalidated',
                'details' => 'Examen invalidado por múltiples violaciones de seguridad (3/3)'
            ]
        ];

        foreach ($logs as $log) {
            DB::table('security_logs')->insert([
                'user_id' => $student->id,
                'exam_attempt_id' => $attempt->id,
                'event_type' => $log['event_type'],
                'details' => $log['details'],
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
                'created_at' => now()->subMinutes(rand(1, 60)),
                'updated_at' => now()
            ]);
        }

        $this->command->info('✅ ' . count($logs) . ' logs de seguridad creados');

        // ==========================================
        // 8. Actualizar el examen como invalidado
        // ==========================================
        $attempt->update([
            'status' => 'invalidated',
            'security_alert' => true,
            'invalidated_at' => now(),
            'invalidation_reason' => 'Múltiples violaciones de seguridad (cambios de pestaña)'
        ]);

        $this->command->info('✅ Examen marcado como INVALIDADO');

        // ==========================================
        // Resumen final
        // ==========================================
        $this->command->info('=========================================');
        $this->command->info('🎉 DATOS DE PRUEBA CREADOS EXITOSAMENTE!');
        $this->command->info('=========================================');
        $this->command->info('👤 Estudiante: juan.perez@test.com');
        $this->command->info('🔑 Contraseña: password123');
        $this->command->info('📝 Examen ID: ' . $quiz->id);
        $this->command->info('🎯 Intento ID: ' . $attempt->id);
        $this->command->info('🔒 Logs creados: ' . count($logs));
        $this->command->info('=========================================');
    }
}