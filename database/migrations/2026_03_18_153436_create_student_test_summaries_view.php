<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // We use DB::statement to run the raw SQL that builds the view
        DB::statement("
            CREATE OR REPLACE VIEW student_test_summaries AS
            SELECT 
                u.id AS student_id,
                CONCAT(u.lastname, ' ', u.name) AS full_name,
                c.name AS career_name,
                
                -- Oral Score & Attempts
                (SELECT orl.final_score FROM oral_results orl 
                 WHERE orl.student_id = u.id ORDER BY orl.created_at DESC LIMIT 1) AS oral_score,
                (SELECT COUNT(*) FROM oral_results orl WHERE orl.student_id = u.id) AS oral_attempts,

                -- Comp Score & Attempts
                (SELECT exa.score FROM exam_attempts exa 
                 WHERE exa.student_id = u.id ORDER BY exa.created_at DESC LIMIT 1) AS comp_score,
                (SELECT COUNT(*) FROM exam_attempts exa WHERE exa.student_id = u.id) AS comp_attempts

            FROM users u
            LEFT JOIN students s ON u.id = s.user_id
            LEFT JOIN careers c ON s.career_id = c.id
            WHERE LOWER(u.role) LIKE 'student%';
        ");
    }

    public function down(): void
    {
        // If we roll back, we drop the view
        DB::statement("DROP VIEW IF EXISTS student_test_summaries");
    }
};