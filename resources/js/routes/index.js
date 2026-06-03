import { createRouter, createWebHistory } from "vue-router";
import AuthenticatedLayout from "@/layouts/Authenticated.vue";
import Login from "@/components/Auth/Login.vue";
import ForgotPassword from "@/components/Auth/ForgotPassword.vue";
import ResetPassword from "@/components/Auth/ResetPassword.vue";

// Función de protección de rutas (Guard)
function auth(to, from, next) {
    const isLoggedIn = JSON.parse(localStorage.getItem("loggedIn"));
    if (!isLoggedIn) return next("/login");
    next();
}

const routes = [
    { path: "/login", name: "login", component: Login },
    { 
        path: "/forgot-password", 
        name: "ForgotPassword", 
        component: ForgotPassword 
    },
    { 
        path: "/reset-password", 
        name: "ResetPassword", 
        component: ResetPassword 
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/auth/Register.vue'),
        meta: { guest: true }
    },
    {
        path: '/verify-email/:token',
        name: 'verify.email',
        component: () => import('@/pages/auth/VerifyEmail.vue'),
        meta: { guest: true }
    },
    {
        path: '/verify/:uuid',
        name: 'verify-qr',
        component: () => import('@/pages/VerifyQr.vue')
    },
    
    {
        path: "/",
        component: AuthenticatedLayout,
        beforeEnter: auth,
        children: [
            { 
                path: '', 
                name: 'home',
                redirect: () => {
                    const userData = JSON.parse(localStorage.getItem("user_data"));
                    if (!userData || !userData.role) return { name: 'login' };
                    if (userData.role === 'admin') return { name: 'admin.dashboard' };
                    if (userData.role === 'teacher') return { name: 'teacher.dashboard' };
                    return { name: 'student.dashboard' };
                }
            },
            
            // --- PERFIL ---
            {
                path: 'profile',
                name: 'profile',
                component: () => import('@/components/Profile/Profile.vue'),
                meta: { requiresAuth: true }
            },

            // --- GESTIÓN DE PREGUNTAS ORALES ---
            {
                path: "admin/oral-questions",
                name: "admin.oral_questions.index",
                component: () => import("@/components/Admins/OralQuestions/OralQuestionsAdmin.vue"),
                meta: { role: 'admin' }
            },

            // --- REPORTES (ADMIN) ---
            {
                path: 'admin/reports',
                name: 'admin.reports',
                component: () => import('@/components/Admins/Reports/Index.vue'),
                meta: { title: 'Academic Records', role: 'admin' }
            },
            {
                path: 'admin/reports/oral',
                name: 'admin.oral.reports',
                component: () => import('@/components/Admins/Reports/AdminOralResults.vue'),
                meta: { requiresAuth: true, role: 'admin' }
            },
            {
                path: 'admin/reports/computerized',
                name: 'admin.reports.computerized',
                component: () => import('@/components/Admins/Reports/CompReportsIndex.vue'),
                meta: { requiresAuth: true, role: 'admin' }
            },
            
            // ========== NUEVAS RUTAS DE SEGURIDAD Y AUDITORÍA ==========
            // Logs de Seguridad por examen
            {
    path: 'admin/backups',
    name: 'admin.backups',
    component: () => import('@/components/Admins/Backup/BackupManager.vue'),
    meta: { role: 'admin', title: 'Gestión de Backups' }
},
            {
                path: 'admin/security-logs/:examId?',
                name: 'admin.security-logs',
                component: () => import('@/components/Admins/Reports/AdminSecurityLogs.vue'),
                props: true,
                meta: { role: 'admin', title: 'Registros de Seguridad' }
            },
            // Auditoría de Actividad del Sistema
            {
                path: 'admin/audit-logs',
                name: 'admin.audit.logs',
                component: () => import('@/components/Admins/Reports/AuditLogs.vue'),
                meta: { role: 'admin', title: 'Auditoría de Actividad' }
            },
            // Reporte de Violaciones de Seguridad
            {
                path: 'admin/security-reports',
                name: 'admin.security.reports',
                component: () => import('@/components/Admins/Reports/SecurityReports.vue'),
                meta: { role: 'admin', title: 'Reporte de Violaciones' }
            },

            // --- DASHBOARDS ---
            {
                path: "admin/dashboard",
                name: "admin.dashboard",
                component: () => import("@/components/Admins/Dashboard.vue"),
                meta: { role: 'admin' }
            },
            {
                path: 'teacher/dashboard',
                name: 'teacher.dashboard',
                component: () => import('@/components/Teachers/Dashboard.vue'),
                meta: { role: 'teacher' }
            },
            {
                path: "student/dashboard",
                name: "student.dashboard",
                component: () => import("@/components/Students/Dashboard.vue"),
                meta: { role: 'student' }
            },

            // --- GESTIÓN DE ESTUDIANTES ---
            {
                path: "admin/students/index",
                name: "students.index",
                component: () => import("@/components/Admins/Students/Index.vue"),
            },
            {
                path: "admin/students/create",
                name: "students.create",
                component: () => import("@/components/Admins/Students/Create.vue"),
            },
            {
                path: 'admin/students/:id/edit',
                name: 'students.edit',
                component: () => import('@/components/Admins/Students/Edit.vue'),
                props: true
            },

            // --- GESTIÓN DE EXÁMENES (QUIZZES) ---
            {
                path: "admin/quizzes/index",
                name: "quiz.index",
                component: () => import("@/components/Admins/Quizzes/Index.vue"),
            },
            {
                path: "admin/quizzes/create",
                name: "quiz.create",
                component: () => import("@/components/Admins/Quizzes/Create.vue"),
            },
            {
                path: "admin/quizzes/edit/:id",
                name: "quiz.edit",
                component: () => import("@/components/Admins/Quizzes/Edit.vue"),
                meta: { role: 'admin' }
            },

            // --- PREGUNTAS ---
            {
                path: "admin/questions/index",
                name: "question.index",
                component: () => import("@/components/Admins/Question/Index.vue"),
            },
            {
                path: "admin/questions/create",
                name: "question.create",
                component: () => import("@/components/Admins/Question/Create.vue"),
            },
            {
                path: "admin/questions/edit/:id",
                name: "question.edit",
                component: () => import("@/components/Admins/Question/Edit.vue"),
                props: true
            },

            // --- ASIGNACIONES ---
            {
                path: "admin/assignments/index",
                name: "assignments.index",
                component: () => import("@/components/Admins/Assignments/Index.vue"),
            },
            {
                path: "admin/assignments/create",
                name: "assignments.create",
                component: () => import("@/components/Admins/Assignments/Create.vue"),
            },
            {
                path: "admin/assignments/edit/:id",
                name: "assignments.edit",
                component: () => import("@/components/Admins/Assignments/Edit.vue"),
                props: true
            },
            {
                path: "admin/assignments/slots-config",
                name: "assignments.slots-config",
                component: () => import("@/components/Admins/Assignments/TimeSlotManager.vue"),
                meta: { role: 'admin' }
            },
            {
                path: "admin/assignments/special-slots",
                name: "assignments.special-slots",
                component: () => import("@/components/Admins/Assignments/SpecialSlotManager.vue"),
                meta: { role: 'admin' }
            },
            {
                path: '/admin/pending-users',
                name: 'admin.pending-users',
                component: () => import('@/components/Admins/PendingUsers/Index.vue'),
                meta: { isAdmin: true }
            },

            // --- ADMINISTRADORES ---
            {
                path: 'admin/users',
                name: 'admins.index',
                component: () => import('@/components/Admins/Users/AdminIndex.vue'),
                meta: { role: 'admin' } 
            },
            {
                path: 'admin/users/create',
                name: 'admins.create',
                component: () => import('@/components/Admins/Users/AdminCreate.vue'),
                meta: { role: 'admin' }
            },
            {
                path: 'admin/users/edit/:id',
                name: 'admins.edit',
                component: () => import('@/components/Admins/Users/AdminEdit.vue'),
                props: true,
                meta: { role: 'admin' }
            },
            {
                path: 'admin/lab-control',
                name: 'admin.lab-control',
                component: () => import('@/components/Admins/Assignments/AdminLabControl.vue'),
                meta: { role: 'admin' }
            },
            {
                path: 'admin/qr-manager',
                name: 'admin.qr-manager',
                component: () => import('@/pages/admin/QrManager.vue'),
                meta: { role: 'admin' }
            },
            {
                path: 'admin/qr-statistics',
                name: 'admin.qr-statistics',
                component: () => import('@/pages/admin/QrStatistics.vue'),
                meta: { role: 'admin' }
            },
         {
    path: 'admin/modular-reports',
    name: 'admin.modular.reports',
    component: () => import('@/components/Admins/Reports/AdminModularReports.vue'),
    meta: { requiresAuth: true, role: 'admin' }
},

           // ========== GESTIÓN DE MÓDULOS ==========
{
    path: 'admin/modules',
    name: 'admin.modules.index',
    component: () => import('@/components/Admins/Modules/ModulesIndex.vue'),
    meta: { role: 'admin', title: 'Gestión de Módulos' }
},
{
    path: 'admin/modules/create',
    name: 'admin.modules.create',
    component: () => import('@/components/Admins/Modules/ModuleForm.vue'),
    meta: { role: 'admin', title: 'Crear Módulo' }
},
{
    path: 'admin/modules/:id/edit',
    name: 'admin.modules.edit',
    component: () => import('@/components/Admins/Modules/ModuleForm.vue'),
    props: true,
    meta: { role: 'admin', title: 'Editar Módulo' }
},
{
    path: 'admin/modules/:id/questions',
    name: 'admin.modules.questions',
    component: () => import('@/components/Admins/Modules/ModuleQuestions.vue'),
    props: true,
    meta: { role: 'admin', title: 'Preguntas del Módulo' }
},
{
    path: 'admin/quiz-modules',
    name: 'admin.quiz-modules',
    component: () => import('@/components/Admins/Modules/QuizModules.vue'),
    meta: { role: 'admin', title: 'Asignar Módulos a Exámenes' }
},
            // --- DOCENTES (TEACHERS) ---
            {
                path: 'teacher/evaluate/:id',
                name: 'teacher.evaluate',
                component: () => import('@/components/Teachers/EvaluateOral.vue'),
                meta: { role: 'teacher' } 
            },
            {
                path: 'teacher/results/:id',
                name: 'teacher.showresult',
                component: () => import('@/components/Teachers/ShowResult.vue'),
                meta: { role: 'teacher' }
            },
            {
                path: 'teacher/results',
                name: 'teacher.results.index',
                component: () => import('@/components/Teachers/ResultsIndex.vue'),
                meta: { role: 'teacher' }
            },

            // --- EXÁMENES Y RESULTADOS ESTUDIANTES ---
            {
                path: '/student/results/oral', 
                name: 'student.oral.index',
                component: () => import('@/components/Students/Results/OralHistoryList.vue')
            },
            {
                path: '/student/results/oral/:id',
                name: 'student.results.oral',
                component: () => import('@/components/Students/Results/OralResultDetail.vue')
            },
            {
                path: 'student/results/comp/:id',
                name: 'student.results.comp',
                component: () => import('@/components/Students/Attend/ExamResultDetail.vue'),
                meta: { requiresAuth: true, role: 'student' }
            },
            {
                path: 'student/exam/:id', 
                name: 'student.exam', 
                component: () => import('@/components/Students/Exams/StudentExam.vue'),
                props: true
            },
            // En router/index.js
// En tu archivo de rutas
{
    path: 'student/modular-exam/:assignmentId',  // ✅ debe ser assignmentId
    name: 'student.modular-exam',
    component: () => import('@/components/Students/exams/ModularExam.vue'),
    meta: { requiresAuth: true, role: 'student' }
},
            {
    path: 'student/missed-exams',
    name: 'student.missed-exams',
    component: () => import('@/components/Students/Attend/StudentMissedExams.vue'),
    meta: { requiresAuth: true, role: 'student' }
},
// En la sección de rutas de estudiante
{
    path: 'student/modular-history',
    name: 'student.modular.index',
    component: () => import('@/components/Students/Results/ModularHistoryList.vue'),
    meta: { requiresAuth: true, role: 'student' }
},
{
    path: 'student/modular-results/:id',
    name: 'student.modular.results',
    component: () => import('@/components/Students/Results/ModularResults.vue'),
    meta: { requiresAuth: true, role: 'student' }
},
            {
                path: 'student/results-history',
                name: 'student.history',
                component: () => import("@/components/Students/Attend/Result.vue"),
                props: true
            },
            {
                path: '/student/schedule',
                name: 'student.schedule',
                component: () => import('@/components/Students/Schedule/StudentSchedule.vue'),
                meta: { requiresAuth: true, role: 'student' }
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;