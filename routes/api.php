<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\QuizController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\QuizAssignmentController;
use App\Http\Controllers\Api\V1\QuestionResultController;
use App\Http\Controllers\Api\V1\StudentQuizController;
use App\Http\Controllers\Api\V1\CareerController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\StudentImportController;
use App\Http\Controllers\Api\V1\StudentReportController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\OralQuestionController;
use App\Http\Controllers\Api\V1\ExamReportController;
use App\Http\Controllers\Api\V1\LanguageController;
use App\Http\Controllers\Api\V1\TeacherReportController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\QuizMaintenanceController;
use App\Http\Controllers\Api\V1\PendingUserController;
use App\Http\Controllers\Api\V1\OralTestController;
use App\Http\Controllers\Api\V1\AdminReportController;
use App\Http\Controllers\Api\V1\ExamScheduleController;
use App\Http\Controllers\Api\V1\AdminLabController;
use App\Http\Controllers\Api\V1\QRCodeController;
use App\Http\Controllers\Api\V1\TimeSlotController;
use App\Http\Controllers\Api\V1\AdminPDFController;
use App\Http\Controllers\Api\V1\SecurityLogController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\SecurityReportController;
use App\Http\Controllers\Api\V1\BackupController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\QuizModuleController;
use App\Http\Controllers\Api\V1\ModularExamController;
use App\Http\Controllers\Api\V1\ModularExamPdfController; // ← PDF Controller
use App\Http\Controllers\Api\V1\ModuleMaintenanceController;
use App\Models\Setting;

// ============================================================
// RUTA DE PRUEBA PARA PDF
// ============================================================
Route::get('/test-pdf', function() {
    $html = '<html><body><h1>PDF de Prueba</h1><p>Funciona correctamente!</p></body></html>';
    $pdf = Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
    return $pdf->download('test.pdf');
});

// ============================================================
// API V1
// ============================================================
Route::prefix('v1')->group(function () {

    // ============================================================
    // 1. RUTAS PÚBLICAS (sin autenticación)
    // ============================================================
    Route::get('/public/comp-quizzes', [QuizController::class, 'getCompQuizzes']); 
    Route::get('/public/modular-quizzes', [QuizController::class, 'getModularQuizzes']);
    
    // Backups públicos
    Route::get('/secure/download/{hash}', [BackupController::class, 'secureDownload']);
    Route::get('/backup-hashes', function() {
        $backupDir = storage_path('app/backups');
        $hashes = [];
        
        if (is_dir($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $filePath = $backupDir . '/' . $file;
                if (is_file($filePath)) {
                    $hashes[] = [
                        'name' => $file,
                        'hash' => md5($file . env('APP_KEY') . filemtime($filePath)),
                        'size' => round(filesize($filePath) / 1024 / 1024, 2),
                        'date' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'type' => pathinfo($file, PATHINFO_EXTENSION)
                    ];
                }
            }
        }
        return response()->json($hashes);
    });
    
    // Autenticación
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
    Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [ResetPasswordController::class, 'reset']);
    
    // Recursos públicos
    Route::get('languages', [LanguageController::class, 'index']);
    Route::get('careers', [CareerController::class, 'index']);
    
    // Verificaciones
    Route::get('verify/{uuid}', [VerificationController::class, 'show']);
    Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail']);
    
    // QR Públicos
    Route::get('/qr/{id}/show', [QRCodeController::class, 'show'])->name('api.qr.show');
    Route::get('/qr/{id}/download', [QRCodeController::class, 'download'])->name('api.qr.download');
    
    // Configuración
    Route::get('/settings/email-domain', function () {
        $setting = Setting::where('key', 'allowed_email_domain')->first();
        return response()->json([
            'domain' => $setting ? $setting->value : 'adm.emi.edu.bo'
        ]);
    });

    // ============================================================
    // 2. RUTAS PROTEGIDAS (requieren autenticación)
    // ============================================================
    Route::middleware('auth:sanctum')->group(function () {

        // --------------------------------------------------------
        // LOGOUT
        // --------------------------------------------------------
        Route::post('/logout', [AuthController::class, 'destroy']);
        
        // --------------------------------------------------------
        // SECCIÓN ESTUDIANTES
        // --------------------------------------------------------
        Route::get('students/export-pdf', [UserController::class, 'exportPdf']);
        Route::apiResource('students', UserController::class);
        Route::post('students/import', [StudentImportController::class, 'import']);

        // --------------------------------------------------------
        // PERFIL DE USUARIO
        // --------------------------------------------------------
        Route::prefix('user')->group(function () {
            Route::get('/', function (Request $request) { return $request->user(); });
            Route::post('/update-profile', [UserController::class, 'updateProfile']);
            Route::post('/update-password', [UserController::class, 'updatePassword']);
        });

// ============================================================
// PORTAL STUDENT
// ============================================================
Route::prefix('student')->group(function () {
    
    // Notificaciones
    Route::get('/notifications', function () {
        return auth()->user()->notifications()->latest()->take(50)->get();
    });
    Route::post('/notifications/{id}/read', function ($id) {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) $notification->markAsRead();
        return response()->json(['success' => true]);
    });
    Route::post('/notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });
    Route::delete('/notifications/{id}', function ($id) {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['error' => 'Notificación no encontrada'], 404);
    });
    Route::delete('/notifications/read', function () {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();
        return response()->json(['success' => true]);
    });
    
    // Módulos
    Route::get('/modules', [ModuleController::class, 'index']);
    Route::get('/modules/{id}', [ModuleController::class, 'show']);
    
    // ========== EXÁMENES MODULARES ==========
    Route::prefix('modular-exam')->group(function () {
        Route::get('/check-availability/{languageId}', [ModularExamController::class, 'checkAvailability']);
        Route::post('/create-random', [ModularExamController::class, 'createRandomModularExam']);
        Route::get('/load/{assignmentId}', [ModularExamController::class, 'loadExam']);
        Route::post('/save/{attemptId}', [ModularExamController::class, 'saveModuleAnswers']);
        Route::post('/next/{attemptId}', [ModularExamController::class, 'nextModule']);
        Route::post('/finish/{attemptId}', [ModularExamController::class, 'finishExam']);
        Route::post('/invalidate/{attemptId}', [ModularExamController::class, 'invalidateExam']);
        Route::post('/security-event', [ModularExamController::class, 'securityEvent']);
        
        // ✅ NUEVAS RUTAS para sincronización y control de medios
        Route::post('/sync-timer/{attemptId}', [ModularExamController::class, 'syncTimer']);
        Route::post('/audio-played/{attemptId}', [ModularExamController::class, 'registerAudioPlayed']);
        Route::post('/media-viewed/{attemptId}', [ModularExamController::class, 'registerMediaViewed']);
    });
    
    // Resultados modulares (JSON)
    Route::get('/modular-results/{attemptId}', [ModularExamController::class, 'getResults']);
    Route::get('/modular-history', [ModularExamController::class, 'getModularHistory']);
    
    // PDF de resultados (Estudiante)
    Route::get('/modular-results/{attemptId}/download', [ModularExamPdfController::class, 'downloadResultsPDF']);
    
    // ========== SEGURIDAD ==========
    Route::post('/log-security-event', [SecurityLogController::class, 'store']);
    Route::get('/my-violations', [SecurityLogController::class, 'myViolations']);
   
    // ========== EXÁMENES COMPTEST ==========
    Route::post('start-exam', [StudentQuizController::class, 'startRandomExam']);
    Route::get('load-exam/{id}', [StudentQuizController::class, 'loadExam']);
    Route::post('save-answer/{attemptId}', [StudentQuizController::class, 'saveAnswer']);
    Route::post('finish-exam/{id}', [StudentQuizController::class, 'finishExam']);
    Route::get('/check-status', [StudentQuizController::class, 'checkStatus']);
    Route::get('/results-history', [QuestionResultController::class, 'getAllResults']);
    Route::get('/results/{id}', [QuestionResultController::class, 'getResults']);
    
    // Exámenes orales
    Route::get('/oral-history', [OralTestController::class, 'getOralHistory']);
    Route::get('/oral-results/{id}', [OralTestController::class, 'show']);
    Route::get('/oral-results/{id}/download', [OralTestController::class, 'downloadCertificate']);
    Route::get('/missed-exams', [StudentQuizController::class, 'getMissedExams']);
    
    // Citas y programación
    Route::get('active-assignment', [ExamScheduleController::class, 'getActiveAssignment']);
    Route::get('my-exams', [ExamScheduleController::class, 'index']);
    Route::get('check-eligibility', [ExamScheduleController::class, 'checkEligibility']);
    Route::post('schedule', [ExamScheduleController::class, 'store']);
    Route::get('available-slots', [ExamScheduleController::class, 'getAvailableSlots']);
    Route::get('/check-sancion', [ExamScheduleController::class, 'checkSancion']);
    Route::get('last-missed-exam', [StudentQuizController::class, 'getLastMissedExam']);
});

        // ============================================================
        // PORTAL TEACHER
        // ============================================================
        Route::prefix('teacher')->middleware(['teacher'])->group(function () {
            Route::get('/notifications', function () {
                return auth()->user()->notifications()->latest()->take(50)->get();
            });
            Route::post('/notifications/{id}/read', function ($id) {
                $notification = auth()->user()->notifications()->find($id);
                if ($notification) $notification->markAsRead();
                return response()->json(['success' => true]);
            });
            Route::post('/notifications/read-all', function () {
                auth()->user()->unreadNotifications->markAsRead();
                return response()->json(['success' => true]);
            });
            Route::delete('/notifications/{id}', function ($id) {
                $notification = auth()->user()->notifications()->find($id);
                if ($notification) {
                    $notification->delete();
                    return response()->json(['success' => true]);
                }
                return response()->json(['error' => 'Notificación no encontrada'], 404);
            });
            Route::delete('/notifications/read', function () {
                auth()->user()->notifications()->whereNotNull('read_at')->delete();
                return response()->json(['success' => true]);
            });
            
            Route::get('/dashboard', [TeacherController::class, 'index']);
            Route::get('/exam/{id}', [TeacherController::class, 'showExam']);
            Route::get('questions/{level}', [TeacherController::class, 'getQuestionsByLevel']);
            Route::post('exam/{id}/save', [TeacherController::class, 'saveOralEvaluation']);
            Route::post('mark-absent/{id}', [TeacherController::class, 'markAsAbsent']);
            Route::get('completed-exams', [TeacherReportController::class, 'getCompletedExams']);
            Route::get('results/{id}', [TeacherReportController::class, 'showResult']);
            Route::get('report-general-pdf', [TeacherReportController::class, 'downloadGeneralReport']);
            Route::get('/report-pdf/{id}', [StudentReportController::class, 'exportIndividualPdf']);
        });

        // ============================================================
        // SECCIÓN ADMINISTRACIÓN
        // ============================================================
        Route::prefix('admin')->middleware(['admin'])->group(function () {

            // ========== NOTIFICACIONES ==========
            Route::get('/notifications', function () {  
                return auth()->user()->notifications()->latest()->take(50)->get(); 
            });
            Route::post('/notifications/{id}/read', function ($id) {
                $notification = auth()->user()->notifications()->find($id);
                if ($notification) $notification->markAsRead();
                return response()->json(['success' => true]);  
            });
            Route::post('/notifications/read-all', function () {
                auth()->user()->unreadNotifications->markAsRead();
                return response()->json(['success' => true]);    
            });
            Route::delete('/notifications/{id}', function ($id) {
                $notification = auth()->user()->notifications()->find($id);
                if ($notification) {
                    $notification->delete();
                    return response()->json(['success' => true]);
                }
                return response()->json(['error' => 'Notificación no encontrada'], 404); 
            });
            Route::delete('/notifications/read', function () {
                auth()->user()->notifications()->whereNotNull('read_at')->delete();
                return response()->json(['success' => true]);    
            });

            // ========== MANTENIMIENTO DE MÓDULOS ==========
            Route::prefix('maintenance')->group(function () {
                Route::post('/upload-sql', [ModuleMaintenanceController::class, 'uploadSql']);
                Route::post('/upload-only-modules-sql', [ModuleMaintenanceController::class, 'uploadOnlyModulesSQL']);
                Route::post('/upload-only-questions-sql', [ModuleMaintenanceController::class, 'uploadOnlyQuestionsSQL']);
                Route::post('/import-preset-modules', [ModuleMaintenanceController::class, 'importPresetModules']);
                Route::post('/truncate-modules-questions', [ModuleMaintenanceController::class, 'truncateModulesAndQuestions']);
                Route::post('/truncate-all', [ModuleMaintenanceController::class, 'truncateAllRelatedTables']);
                Route::post('/diagnose-sql', [ModuleMaintenanceController::class, 'diagnoseSQL']);
            });
            
            // ========== MANTENIMIENTO DE QUIZZES ==========
            Route::prefix('quiz-maintenance')->group(function () {
                Route::post('/truncate', [QuizMaintenanceController::class, 'truncateByLanguage']);
                Route::post('/upload-sql', [QuizMaintenanceController::class, 'uploadSql']);
            });

            // ========== REPORTES MODULARES ==========
            Route::prefix('modular-reports')->group(function () {
                // Lista de exámenes (JSON)
                Route::get('/', [ModularExamController::class, 'adminIndex']);
                // PDFs
                Route::get('/export-pdf', [ModularExamPdfController::class, 'exportPdfGeneral']);
                Route::get('/{id}/pdf', [ModularExamPdfController::class, 'exportPdfIndividual']);
            });

            // ========== LOGS DE SEGURIDAD (ADMIN) ==========
            Route::prefix('security')->group(function () {
                Route::get('/exams-with-violations', [SecurityLogController::class, 'getExamsWithViolations']);
                Route::get('/logs/{examAttemptId}', [SecurityLogController::class, 'getLogsByExam']);
                Route::get('/index/{examAttemptId}', [SecurityLogController::class, 'index']);
                Route::get('/export/{examAttemptId}', [SecurityLogController::class, 'exportPdf']);
                Route::delete('/clean-logs', [SecurityLogController::class, 'cleanOldLogs']);
                
                // Logs de eventos generales
                Route::post('/log-event', [SecurityLogController::class, 'store']);
            });

            // ========== RUTAS DE MÓDULOS Y PREGUNTAS ==========
            Route::get('/modules', [ModuleController::class, 'index']);
            Route::get('/modules/{id}', [ModuleController::class, 'show']);
            Route::post('/modules', [ModuleController::class, 'store']);
            Route::put('/modules/{id}', [ModuleController::class, 'update']);
            Route::delete('/modules/{id}', [ModuleController::class, 'destroy']);
            
            Route::get('/modules/{moduleId}/questions', [ModuleController::class, 'getQuestions']);
            Route::post('/modules/{moduleId}/questions', [ModuleController::class, 'addQuestion']);
            Route::put('/modules/{moduleId}/questions/{questionId}', [ModuleController::class, 'updateQuestion']);
            Route::delete('/modules/{moduleId}/questions/{questionId}', [ModuleController::class, 'deleteQuestion']);
            Route::post('/modules/{moduleId}/questions/{questionId}/move', [ModuleController::class, 'moveQuestion']);
            
            Route::get('/quizzes/modular', [QuizController::class, 'getModularQuizzes']);

            // ========== RUTAS PARA ASIGNAR MÓDULOS A EXÁMENES ==========
            Route::prefix('quizzes/{quizId}/modules')->group(function () {
                Route::get('/', [QuizModuleController::class, 'index']);
                Route::post('/{moduleId}', [QuizModuleController::class, 'attach']);
                Route::delete('/{moduleId}', [QuizModuleController::class, 'detach']);
                Route::put('/order', [QuizModuleController::class, 'updateOrder']);
            });

            // ========== RUTAS DE LABORATORIO Y ASIGNACIONES ==========
            Route::get('/test-modular', function() {
                return response()->json(['message' => 'test ok']);
            });
            Route::put('/assignment/{assignmentId}/datetime', [AdminLabController::class, 'updateAssignmentDateTime']);
            Route::get('get-available-slots', [TimeSlotController::class, 'getAvailableSlots']);
            Route::get('/check-student-eligibility/{studentId}/{excludeAssignmentId?}', [AdminLabController::class, 'checkStudentEligibility']);
            Route::get('/assignment/{assignmentId}/edit-eligibility/{studentId}', [QuizAssignmentController::class, 'checkEditEligibility']);
            Route::post('/assignment/{oralTestId}/create-next-phase', [QuizAssignmentController::class, 'createNextPhase']);
            Route::get('/assignment/{id}/with-edit-info', [QuizAssignmentController::class, 'getAssignmentWithEditInfo']);

            // ========== USUARIOS PENDIENTES ==========
            Route::get('pending-users', [PendingUserController::class, 'index']);
            Route::post('pending-users/{id}/approve', [PendingUserController::class, 'approve']);
            Route::delete('pending-users/{id}/reject', [PendingUserController::class, 'destroy']);

            // ========== ACCESO LABORATORIO ==========
            Route::get('/pending-lab-access', [AdminLabController::class, 'getPendingUnlocks']);
            Route::get('/available-schedules', [AdminLabController::class, 'getAvailableSchedules']);
            Route::post('/unlock-exam-by-id/{assignmentId}', [AdminLabController::class, 'toggleUnlockById']);
            Route::post('/mark-as-missed/{studentId}', [AdminLabController::class, 'markAsMissed']);
            Route::post('/remove-penalty/{studentId}', [AdminLabController::class, 'removePenalty']);
            Route::post('/clear-missed/{studentId}', [AdminLabController::class, 'clearMissed']);
            Route::post('/create-assignment', [AdminLabController::class, 'createAssignment']);
            Route::post('unlock-exam/{id}', [ExamScheduleController::class, 'unlockExam']);

            // ========== CONFIGURACIÓN DE HORARIOS ==========
            Route::get('time-slots-config', [TimeSlotController::class, 'getConfiguration']);
            Route::post('time-slots-config', [TimeSlotController::class, 'saveConfiguration']);
            Route::get('special-slots', [TimeSlotController::class, 'getSpecialSlots']);
            Route::delete('special-slots/{id}', [TimeSlotController::class, 'deleteSpecialSlot']);

            // ========== CÓDIGOS QR ==========
            Route::get('/qrs', [QRCodeController::class, 'index'])->name('api.admin.qrs.index');
            Route::post('/qrs', [QRCodeController::class, 'store'])->name('api.admin.qrs.store');
            Route::get('/qrs/{id}', [QRCodeController::class, 'show'])->name('api.admin.qrs.show');
            Route::put('/qrs/{id}', [QRCodeController::class, 'update'])->name('api.admin.qrs.update');
            Route::delete('/qrs/{id}', [QRCodeController::class, 'destroy'])->name('api.admin.qrs.destroy');
            Route::post('/qr/generate', [QRCodeController::class, 'generate'])->name('api.admin.qr.generate');

            // ========== AUDITORÍA ==========
            Route::prefix('audit')->group(function () {
                Route::get('/logs', [AuditLogController::class, 'index']);
                Route::get('/logs/{id}', [AuditLogController::class, 'show']);
                Route::get('/logs/export-pdf', [AuditLogController::class, 'exportPdf']);
                Route::get('/logs/export-csv', [AuditLogController::class, 'exportCsv']);
                Route::delete('/logs/clean', [AuditLogController::class, 'cleanOldLogs']);
            });

            // ========== REPORTES DE SEGURIDAD ==========
            Route::prefix('security-reports')->group(function () {
                Route::get('/', [SecurityReportController::class, 'index']);
                Route::get('/export-pdf', [SecurityReportController::class, 'exportPdf']);
                Route::get('/student/{studentId}', [SecurityReportController::class, 'getByStudent']);
                Route::get('/exam/{examAttemptId}', [SecurityReportController::class, 'getByExam']);
            });

            // ========== BACKUPS ==========
            Route::prefix('backups')->group(function () {
                Route::get('/', [BackupController::class, 'index']);
                Route::post('/', [BackupController::class, 'store']);
                Route::post('/restore', [BackupController::class, 'restore']);
                Route::get('/download/{filename}', [BackupController::class, 'download']);
                Route::delete('/{filename}', [BackupController::class, 'destroy']);
            });

            // ========== EXÁMENES ORALES ==========
            Route::prefix('oral-exams')->group(function () {
                Route::get('/completed', [TeacherReportController::class, 'getCompletedExams']);
                Route::get('/result/{id}', [TeacherReportController::class, 'showResult']);
                Route::get('/report-oralgeneral', [AdminReportController::class, 'exportOralGeneralPdf']);
                Route::get('/report-individual/{id}', [StudentReportController::class, 'exportIndividualPdf']);

                Route::prefix('pdf')->group(function () {
                    Route::get('/individual/{id}', [AdminPDFController::class, 'generateIndividualReport']);
                    Route::get('/preview/{id}', [AdminPDFController::class, 'previewIndividualReport']);
                    Route::get('/bulk', [AdminPDFController::class, 'generateBulkReport']);
                });
            });
        });

        // ============================================================
        // REPORTES
        // ============================================================
        Route::prefix('reports')->middleware(['admin'])->group(function () {
            // Student Reports
            Route::get('/student-reports', [StudentReportController::class, 'index']);
            Route::get('/student-reports/{id}', [StudentReportController::class, 'show']);
            Route::get('/general', [StudentReportController::class, 'exportGeneralReport']);
            Route::get('/history/{student_id}', [StudentReportController::class, 'exportHistorialPdf']);
            
            // COMP Reports (Exámenes Computarizados)
            Route::get('/comp-reports', [ExamReportController::class, 'indexComp']);
            Route::get('/comp-reports/export-pdf', [ExamReportController::class, 'exportPdfGeneralComp']);
            Route::get('/comp-reports/{id}/pdf', [ExamReportController::class, 'exportPdfIndividualComp']);
        });

        // ============================================================
        // ASIGNACIONES
        // ============================================================
        Route::prefix('quiz-assignments')->middleware(['admin'])->group(function () {
            Route::post('/bulk-disable', [QuizAssignmentController::class, 'bulkDisable']);
            Route::delete('/bulk-delete', [QuizAssignmentController::class, 'bulkDelete']);
            Route::get('/export-pdf', [QuizAssignmentController::class, 'exportPdf']);
            Route::get('/report-individual/{userId}', [QuizAssignmentController::class, 'exportIndividualPdf']);
        });
        Route::apiResource('quiz-assignments', QuizAssignmentController::class)->middleware(['admin']);

        // ============================================================
        // CONTENIDO Y OTROS RECURSOS
        // ============================================================
        Route::apiResource('admins', AdminController::class)->middleware(['admin']);
        Route::get('quizzes/types', [QuizController::class, 'getTypes'])->middleware(['admin']);
        Route::get('quizzes/all', [QuizController::class, 'all'])->middleware(['admin']);
        Route::apiResource('quizzes', QuizController::class)->middleware(['admin']);
        Route::get('questions/all', [QuestionController::class, 'all'])->middleware(['admin']);
        Route::apiResource('questions', QuestionController::class)->middleware(['admin']);
        Route::apiResource('oral-questions', OralQuestionController::class)->middleware(['admin']);
    });
});