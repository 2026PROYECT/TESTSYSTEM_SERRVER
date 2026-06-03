<template>
    <div class="max-w-4xl mx-auto p-6 space-y-10 bg-gray-50/50 min-h-screen pb-20 text-slate-800">
        
        <!-- CABECERA -->
        <div class="flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black italic tracking-tighter text-slate-900">EmiSystem</h1>
                <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest">Panel de Estudiante</p>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Usuario activo</span>
                <p class="text-sm font-bold text-indigo-600">{{ user.name || 'Usuario' }} {{ user.lastname || '' }}</p>
            </div>
        </div>

        <!-- BANNER DE SANCIÓN GENERAL -->
        <transition name="slide-down">
            <div v-if="isBanned" class="mb-10 overflow-hidden bg-gradient-to-r from-slate-700 to-slate-800 rounded-[2.5rem] border border-slate-600 shadow-xl flex flex-col md:flex-row">
                <div class="p-8 flex-1 flex gap-6">
                    <div :class="penaltyType === 'failed' ? 'bg-amber-600' : 'bg-slate-600'" class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0">
                        <i :class="penaltyType === 'failed' ? 'fas fa-exclamation-triangle text-2xl' : 'fas fa-gavel text-2xl'" class="text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black uppercase tracking-tighter mb-1 text-white">
                            {{ penaltyType === 'failed' ? 'EXAMEN REPROBADO' : 'SUSPENSIÓN TEMPORAL' }}
                        </h3>
                        <p class="text-slate-300 font-bold text-[11px] leading-relaxed max-w-md">
                            <template v-if="penaltyType === 'failed'">
                                Reprobase el examen del día 
                                <span class="text-amber-400 font-black">{{ getMissedExamDate() }}</span>. 
                                Deberás esperar 7 días para poder programar un nuevo examen.
                            </template>
                            <template v-else>
                                No asististe a tu examen programado el día 
                                <span class="text-amber-400 font-black">{{ getMissedExamDate() }}</span>. 
                                Tu cuenta ha sido suspendida como medida administrativa.
                            </template>
                        </p>
                        <p class="text-xs text-slate-400 mt-2">
                            ⏰ Reactivación programada: <span class="font-bold text-amber-400">{{ getReactivationDate() }}</span>
                        </p>
                    </div>
                </div>
                <div class="bg-black/20 p-8 flex items-center gap-6 border-l border-white/10">
                    <div class="text-center bg-white/10 px-5 py-3 rounded-2xl backdrop-blur-sm">
                        <p class="text-[9px] font-black text-amber-400 uppercase tracking-widest mb-1">Días Restantes</p>
                        <p class="text-3xl font-black text-white leading-none">{{ daysRemaining }}</p>
                    </div>
                    <div class="text-left">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Reactivación</p>
                        <p class="text-xs font-black text-amber-400">{{ getReactivationDate() }}</p>
                    </div>
                </div>
            </div>
        </transition>

        <!-- SECCIÓN PASO 1: EVALUACIÓN ORAL -->
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-1 h-4 bg-indigo-600 rounded-full"></div>
                <h2 class="text-[11px] font-black uppercase text-slate-400 tracking-widest">Paso 1: Evaluación Oral</h2>
                <span v-if="oralData.status === 'passed'" class="ml-auto text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">
                    ✅ Completado
                </span>
                <span v-else-if="oralData.status === 'failed'" class="ml-auto text-[10px] font-black text-rose-600 bg-rose-50 px-3 py-1 rounded-full">
                    ❌ Reprobado
                </span>
            </div>
            
            <div 
                :class="[
                    oralCardColor === 'green' ? 'bg-white border-emerald-200 shadow-emerald-100' : 
                    oralCardColor === 'banned' ? 'bg-slate-100 border-slate-300 shadow-slate-200' :
                    'bg-white border-rose-200 shadow-rose-100'
                ]"
                class="rounded-[2.5rem] p-8 border shadow-sm flex flex-col md:flex-row items-center gap-8 relative overflow-hidden"
            >
                <div 
                    :class="[
                        oralCardColor === 'green' ? 'bg-emerald-500' : 
                        oralCardColor === 'banned' ? 'bg-slate-500' :
                        'bg-rose-500'
                    ]" 
                    class="absolute left-0 top-0 bottom-0 w-1.5"
                ></div>
                
                <div 
                    :class="[
                        oralCardColor === 'green' ? 'bg-emerald-50 text-emerald-600' : 
                        oralCardColor === 'banned' ? 'bg-slate-100 text-slate-500' :
                        'bg-rose-50 text-rose-600'
                    ]" 
                    class="w-20 h-20 rounded-[2rem] flex items-center justify-center shrink-0 transition-colors"
                >
                    <i 
                        :class="[
                            oralCardColor === 'green' ? 'fas fa-check-circle' : 
                            oralCardColor === 'banned' ? 'fas fa-ban' :
                            'fas fa-times-circle'
                        ]" 
                        class="text-3xl"
                    ></i>
                </div>

                <div class="flex-1 text-center md:text-left">
                    <h3 
                        :class="[
                            oralCardColor === 'green' ? 'text-emerald-700' : 
                            oralCardColor === 'banned' ? 'text-slate-700' :
                            'text-rose-700'
                        ]" 
                        class="text-xl font-black leading-tight"
                    >
                        {{ getOralStatusText() }}
                    </h3>
                    <p 
                        :class="[
                            oralCardColor === 'green' ? 'text-emerald-600' : 
                            oralCardColor === 'banned' ? 'text-slate-500' :
                            'text-rose-600'
                        ]" 
                        class="text-sm mt-1 font-medium"
                    >
                        {{ getOralMessage() }}
                    </p>
                </div>

                <div v-if="oralData.status !== 'none' && oralData.status !== 'absent'" class="bg-slate-50 px-6 py-3 rounded-2xl border border-gray-100 text-center">
                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-tighter">Calificación Oral</span>
                    <span class="text-xl font-black text-slate-800">{{ oralData.score }}%</span>
                </div>
            </div>
        </section>

        <!-- SECCIÓN PASO 2: EXAMEN (CompTest o ModularTest) -->
        <section>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-1 h-4 bg-indigo-600 rounded-full"></div>
                <h2 class="text-[11px] font-black uppercase text-slate-400 tracking-widest">
                    Paso 2: {{ isModularExam ? 'Examen Modular' : 'Examen CompTest' }}
                </h2>
                <span v-if="compData.status === 'passed'" class="ml-auto text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">
                    ✅ Certificado
                </span>
            </div>

            <div 
                @click="isExamEnabled && handleStartExam()" 
                :class="[
                    examCardColor === 'green' ? 'bg-emerald-600 border-emerald-600 shadow-emerald-100' : 
                    examCardColor === 'blue' ? 'bg-blue-600 border-blue-600 shadow-blue-100' : 
                    examCardColor === 'orange' ? 'bg-amber-600 border-amber-600 shadow-amber-100' : 
                    examCardColor === 'banned' ? 'bg-slate-600 border-slate-600 shadow-slate-100 opacity-75' :
                    'bg-rose-600 border-rose-600 shadow-rose-100',
                    isExamEnabled ? 'cursor-pointer' : 'cursor-not-allowed',
                    'relative p-10 rounded-[3rem] border-4 transition-all duration-500 group overflow-hidden shadow-2xl'
                ]"
            >
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8 text-white">
                    <div class="text-center md:text-left">
                        <h3 class="text-4xl font-black italic tracking-tighter leading-none mb-4 uppercase">
                            {{ examTitle }}
                        </h3>
                        <p class="text-sm font-bold opacity-90 max-w-sm">
                            {{ examMessage }}
                        </p>
                    </div>
                    
                    <div 
                        :class="[
                            examCardColor === 'green' ? 'text-emerald-600' : 
                            examCardColor === 'blue' ? 'text-blue-600' : 
                            examCardColor === 'orange' ? 'text-amber-600' : 
                            examCardColor === 'banned' ? 'text-slate-400' :
                            'text-rose-600',
                            'w-24 h-24 bg-white rounded-[2rem] flex items-center justify-center shadow-2xl group-hover:scale-110 transition-all'
                        ]"
                    >
                        <i :class="examIcon" class="text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Botón para solicitar nueva cita -->
            <div v-if="examState === 'no_appointment' && compData.status !== 'passed'" class="mt-4">
                <button 
                    @click="router.push({ name: 'student.appointments' })"
                    class="w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold py-4 rounded-2xl transition-all flex items-center justify-center gap-3 border border-indigo-200"
                >
                    <i class="fas fa-calendar-plus"></i>
                    Solicitar Nueva Cita
                </button>
            </div>

            <!-- Botón para ver reporte -->
            <div v-if="compData.status === 'passed' || compData.status === 'failed'" class="mt-6">
                <router-link :to="{ name: 'student.history' }" class="flex items-center justify-between bg-white p-6 rounded-[2rem] border border-gray-100 hover:border-indigo-200 transition-all group shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <i class="fas fa-file-alt text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900 uppercase tracking-tighter">Ver Reporte Detallado</p>
                            <p class="text-xs text-slate-400 font-medium">Revisa el desglose de tus respuestas y resultados.</p>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right text-slate-300 group-hover:translate-x-2 transition-transform"></i>
                </router-link>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'; 
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const user = ref(JSON.parse(localStorage.getItem('user_data')) || {});
const loading = ref(true);

// Estados de la evaluación
const oralData = ref({ status: 'none', score: 0, level: 'N/E', sanction_until: null, sanction_type: null });
const compData = ref({ status: 'none', sanction_until: null, sanction_type: null });
const modularData = ref({ status: 'none', score: 0, sanction_until: null, sanction_type: null }); // 👈 NUEVO: para ModularTest
const authorized = ref(false);
const isUnlocked = ref(false); 
const isReconnecting = ref(false); 
const lastResult = ref(null);
const hasActiveAppointment = ref(false);
const activeAppointment = ref(null);

// Sanción activa general
const penaltyUntil = ref(null);
const penaltyType = ref(null);
const missedExamDate = ref(null);

// Computed para saber si está baneado/sancionado
const isBanned = computed(() => {
    if (!penaltyUntil.value) return false;
    const penaltyDate = new Date(penaltyUntil.value);
    const now = new Date();
    return penaltyDate > now;
});

// Determinar si es ModularTest
const isModularExam = computed(() => {
    return activeAppointment.value?.test_type === 'ModularTest';
});

// Estado del examen (CompTest o ModularTest)
const examState = computed(() => {
    // Determinar qué examen está activo
    const activeExam = activeAppointment.value?.test_type;
    const isModularActive = activeExam === 'ModularTest';
    
    // 1. Verificar si el ModularTest ya está aprobado (y no es el activo)
    if (!isModularActive && modularData.value.status === 'passed') {
        console.log('✅ ModularTest ya aprobado, mostrar como certificado');
        return 'passed';
    }
    
    // 2. Verificar si el CompTest ya está aprobado
    if (!isModularActive && compData.value.status === 'passed') {
        return 'passed';
    }
    
    // 3. Si el examen activo es ModularTest
    if (isModularActive) {
        // Sancionado/Baneado
        if (isBanned.value && !hasActiveAppointment.value) return 'banned';
        
        // Ya aprobó el ModularTest (caso de que el activo sea ModularTest pero ya pasó)
        if (modularData.value.status === 'passed') return 'passed';
        
        // Tiene cita activa
        if (hasActiveAppointment.value && activeAppointment.value) {
            const appointmentIsUnlocked = activeAppointment.value.is_unlocked === 1;
            
            if (isReconnecting.value) return 'reconnecting';
            if (appointmentIsUnlocked) return 'ready';
            return 'waiting_admin';
        }
        
        // No tiene cita activa
        if (!authorized.value) return 'need_oral';
        if (modularData.value.status === 'failed') return 'failed';
        return 'no_appointment';
    }
    
    // 4. Para CompTest (código original)
    if (isBanned.value && !hasActiveAppointment.value) return 'banned';
    if (compData.value.status === 'passed') return 'passed';
    
    if (hasActiveAppointment.value && activeAppointment.value) {
        const appointmentIsUnlocked = activeAppointment.value.is_unlocked === 1;
        
        if (isReconnecting.value) return 'reconnecting';
        if (authorized.value && appointmentIsUnlocked) return 'ready';
        if (authorized.value && !appointmentIsUnlocked) return 'waiting_admin';
        if (!authorized.value) return 'need_oral';
        return 'blocked';
    }
    
    if (!hasActiveAppointment.value) {
        if (!authorized.value) return 'need_oral';
        if (compData.value.status === 'failed') return 'failed';
        return 'no_appointment';
    }
    
    return 'blocked';
});

// Colores según el estado
const examCardColor = computed(() => {
    switch (examState.value) {
        case 'passed': return 'green';
        case 'ready': return 'blue';
        case 'reconnecting': return 'orange';
        case 'waiting_admin': return 'orange';
        case 'need_oral': return 'red';
        case 'banned': return 'banned';
        case 'failed': return 'red';
        case 'no_appointment': return 'red';
        default: return 'red';
    }
});

// Iconos según el estado
const examIcon = computed(() => {
    // Si ya aprobó ModularTest y no es el activo
    if (!isModularExam.value && modularData.value.status === 'passed') {
        return 'fas fa-certificate';
    }
    
    switch (examState.value) {
        case 'passed': return 'fas fa-medal';
        case 'ready': return isModularExam.value ? 'fas fa-layer-group' : 'fas fa-rocket';
        case 'reconnecting': return 'fas fa-pause-circle';
        case 'waiting_admin': return 'fas fa-hourglass-half';
        case 'need_oral': return 'fas fa-microphone-alt';
        case 'banned': return 'fas fa-ban';
        case 'failed': return 'fas fa-exclamation-triangle';
        case 'no_appointment': return 'fas fa-calendar-times';
        default: return 'fas fa-lock';
    }
});

// Títulos según el estado
const examTitle = computed(() => {
    const examType = isModularExam.value ? 'Modular' : 'CompTest';
    
    // Si ya aprobó ModularTest (y no es el activo)
    if (!isModularExam.value && modularData.value.status === 'passed') {
        return 'Certificación Modular';
    }
    
    switch (examState.value) {
        case 'passed': 
            return isModularExam.value ? 'Módulo Certificado' : 'Certificado';
        case 'ready': 
            return isModularExam.value ? 'Iniciar Módulo' : 'Iniciar Examen';
        case 'reconnecting': 
            return 'Examen en Pausa';
        case 'waiting_admin': 
            return 'Esperando Autorización';
        case 'need_oral': 
            return 'Evaluación Oral Pendiente';
        case 'banned': 
            return 'Cuenta Suspendida';
        case 'failed': 
            return `${examType} Reprobado`;
        case 'no_appointment': 
            return 'Sin Cita Activa';
        default: 
            return 'Acceso Restringido';
    }
});

// Mensajes según el estado
const examMessage = computed(() => {
    const examType = isModularExam.value ? 'módulo' : 'examen escrito';
    
    // Si ya aprobó ModularTest (y no es el activo)
    if (!isModularExam.value && modularData.value.status === 'passed') {
        return '¡Felicidades! Has completado exitosamente el examen modular. Ahora puedes realizar el examen computarizado.';
    }
    
    switch (examState.value) {
        case 'passed': 
            return 'Has superado todas las pruebas satisfactoriamente.';
        case 'ready': 
            return isModularExam.value 
                ? 'Tu módulo está listo para comenzar. El tiempo iniciará al entrar.'
                : 'Tu examen escrito está listo. El tiempo iniciará al entrar.';
        case 'reconnecting': 
            return 'Tienes un intento en pausa. Haz clic aquí para reanudarlo.';
        case 'waiting_admin': 
            return 'Acceso autorizado. El docente debe habilitar tu equipo para comenzar.';
        case 'need_oral': 
            return 'Debes aprobar la evaluación oral primero. Coordina con tu docente.';
        case 'banned':
            if (penaltyType.value === 'failed') {
                return `Reprobaste el ${examType}. Deberás esperar 7 días para programar uno nuevo. Tu cuenta se reactivará el ${getReactivationDate()}.`;
            }
            return `No puedes realizar el ${examType} debido a una sanción por inasistencia. Tu cuenta se reactivará el ${getReactivationDate()}.`;
        case 'failed':
            const sanctionData = isModularExam.value ? modularData.value : compData.value;
            if (sanctionData.value?.sanction_until) {
                const date = new Date(sanctionData.value.sanction_until);
                return `Reprobaste el ${examType}. Deberás esperar 7 días. Podrás intentar nuevamente a partir del ${date.toLocaleDateString('es-ES')}.`;
            }
            return `Reprobaste el ${examType}. Deberás esperar 7 días para programar uno nuevo.`;
        case 'no_appointment': 
            return 'No tienes una cita activa. Solicita una nueva cita con el administrador.';
        default: 
            return 'Acceso restringido. No cumples con los requisitos.';
    }
});

// Determinar si el botón debe estar habilitado
const isExamEnabled = computed(() => {
    return examState.value === 'ready' || examState.value === 'reconnecting';
});

// Días restantes de sanción
const daysRemaining = computed(() => {
    if (!penaltyUntil.value) return 0;
    const penaltyDate = new Date(penaltyUntil.value);
    const now = new Date();
    const diffTime = penaltyDate - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
});

// Computed para determinar colores del ORAL
const oralCardColor = computed(() => {
    if (isBanned.value && oralData.value.status !== 'passed') return 'banned';
    if (oralData.value.status === 'passed') return 'green';
    if (oralData.value.status === 'failed') return 'red';
    if (oralData.value.status === 'absent') return 'banned';
    return 'red';
});

const getOralStatusText = () => {
    if (oralData.value.status === 'passed') return 'Aprobado ✅';
    if (oralData.value.status === 'failed') return 'Reprobado ❌';
    if (oralData.value.status === 'absent') return 'Inasistencia 🚫';
    return 'Pendiente ⏳';
};

const getOralMessage = () => {
    if (oralData.value.status === 'passed') {
        return '¡Felicidades! Has aprobado la evaluación oral.';
    }
    if (oralData.value.status === 'failed') {
        if (oralData.value.sanction_until) {
            const date = new Date(oralData.value.sanction_until);
            return `Reprobaste el examen oral. Deberás esperar 7 días. Podrás reagendar a partir del ${date.toLocaleDateString('es-ES')}.`;
        }
        return 'Reprobaste el examen oral. Deberás esperar 7 días para volver a programar.';
    }
    if (oralData.value.status === 'absent') {
        if (oralData.value.sanction_until) {
            const date = new Date(oralData.value.sanction_until);
            return `No asististe a tu examen oral. Tu cuenta ha sido suspendida por 14 días. Reactivación: ${date.toLocaleDateString('es-ES')}.`;
        }
        return 'No asististe a tu examen oral. Deberás esperar 14 días para volver a programar.';
    }
    return 'Aún no has completado la evaluación oral. Coordina con tu docente.';
};

// Formatear fecha
const formatDate = (dateStr) => {
    if (!dateStr) return '---';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-ES', {
            day: 'numeric', 
            month: 'long', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return 'fecha inválida';
    }
};

const getMissedExamDate = () => {
    if (missedExamDate.value) {
        return formatDate(missedExamDate.value);
    }
    return 'fecha no registrada';
};

const getReactivationDate = () => {
    if (penaltyUntil.value) {
        return formatDate(penaltyUntil.value);
    }
    return 'fecha no disponible';
};

// Carga y actualiza los datos del estudiante desde el servidor
const refreshData = async () => {
    loading.value = true;
    try {
        const [historyResp, statusResp] = await Promise.all([
            axios.get('/api/v1/student/results-history'),
            axios.get('/api/v1/student/check-status')
        ]);

        if (historyResp && historyResp.data) {
            const history = historyResp.data.data || [];
            isReconnecting.value = history.some(attempt => 
                attempt.status === 'in_progress' || attempt.status === 'paused'
            );
            const completedAttempt = history.find(attempt => attempt.status === 'completed');
            lastResult.value = completedAttempt || null;
        }

        if (statusResp && statusResp.data) {
            console.log('📡 Datos recibidos:', statusResp.data);
            
            // Datos orales
            if (statusResp.data.oral_assignment) {
                oralData.value = statusResp.data.oral_assignment;
            }
            
            // 👉 Datos del ModularTest
            if (statusResp.data.modular_test) {
                modularData.value = statusResp.data.modular_test;
                console.log('📚 ModularTest data:', modularData.value);
            }
            
            // Asignar active_appointment correctamente
            if (statusResp.data.active_appointment) {
                activeAppointment.value = statusResp.data.active_appointment;
                console.log('✅ activeAppointment asignado:', {
                    id: activeAppointment.value.id,
                    test_type: activeAppointment.value.test_type,
                    is_unlocked: activeAppointment.value.is_unlocked
                });
            } else {
                activeAppointment.value = null;
                console.log('⚠️ No hay cita activa');
            }
            
            // Datos del examen CompTest
            if (statusResp.data.comp_test) {
                compData.value = statusResp.data.comp_test;
            }
            
            authorized.value = statusResp.data.authorized || false;
            isUnlocked.value = statusResp.data.is_unlocked || false;
            hasActiveAppointment.value = statusResp.data.has_active_appointment || false;
            
            // Sanción activa
            if (statusResp.data.penalty_until) {
                penaltyUntil.value = statusResp.data.penalty_until;
            }
            if (statusResp.data.penalty_type) {
                penaltyType.value = statusResp.data.penalty_type;
            }
            if (statusResp.data.missed_exam_date) {
                missedExamDate.value = statusResp.data.missed_exam_date;
            }
            
            // Guardar en localStorage
            const currentUser = JSON.parse(localStorage.getItem('user_data')) || {};
            if (penaltyUntil.value) currentUser.penalty_until = penaltyUntil.value;
            if (missedExamDate.value) currentUser.missed_exam_date = missedExamDate.value;
            if (hasActiveAppointment.value) currentUser.has_active_appointment = hasActiveAppointment.value;
            if (penaltyType.value) currentUser.penalty_type = penaltyType.value;
            localStorage.setItem('user_data', JSON.stringify(currentUser));
        }

    } catch (error) {
        console.error("Error en Dashboard:", error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudieron cargar todos los datos. Por favor, recarga la página.',
            confirmButtonColor: '#ef4444'
        });
    } finally {
        loading.value = false;
    }
};

// Iniciar o reanudar examen (CompTest o ModularTest)
const handleStartExam = async () => {
    // Para ModularTest
    if (isModularExam.value) {
        const isUnlocked = activeAppointment.value?.is_unlocked === 1;
        
        if (!isUnlocked) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'Esperando Autorización', 
                text: 'Tu examen está listo, pero el docente debe habilitar tu equipo para comenzar.', 
                confirmButtonColor: '#f59e0b' 
            });
            return;
        }
        
        if (examState.value === 'banned') {
            Swal.fire({ 
                icon: 'error', 
                title: 'Acceso Suspendido', 
                text: `No puedes realizar el examen debido a una sanción. Tu cuenta se reactivará el ${getReactivationDate()}.`, 
                confirmButtonColor: '#6b7280' 
            });
            return;
        }
        
        if (examState.value === 'passed') {
            Swal.fire({ 
                icon: 'success', 
                title: 'Módulo Certificado', 
                text: 'Ya has completado y aprobado este módulo.', 
                confirmButtonColor: '#10b981' 
            });
            return;
        }
        
        // Iniciar examen modular
        try {
            Swal.fire({ 
                title: 'Preparando módulo...', 
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });
            
            const response = await axios.post('/api/v1/student/modular-exam/create-random');
            
            if (response.data.assignment_id) {
                Swal.close();
                router.push({ 
                    name: 'student.modular-exam', 
                    params: { assignmentId: response.data.assignment_id } 
                });
            } else {
                throw new Error('No se pudo crear el examen modular');
            }
        } catch (error) {
            Swal.close();
            console.error('Error:', error);
            Swal.fire({ 
                icon: 'error', 
                title: 'Error', 
                text: error.response?.data?.message || 'No se pudo iniciar el examen modular.' 
            });
        }
        return;
    }
    
    // Para CompTest (código original)
    if (examState.value === 'banned') {
        if (penaltyType.value === 'failed') {
            Swal.fire({ 
                icon: 'warning', 
                title: 'Examen Reprobado', 
                text: `Reprobaste el examen. Deberás esperar 7 días para programar uno nuevo. Podrás intentar nuevamente a partir del ${getReactivationDate()}.`, 
                confirmButtonColor: '#f59e0b' 
            });
        } else {
            Swal.fire({ 
                icon: 'error', 
                title: 'Acceso Suspendido', 
                text: `No puedes realizar el examen debido a una sanción por inasistencia. Tu cuenta se reactivará el ${getReactivationDate()}.`, 
                confirmButtonColor: '#6b7280' 
            });
        }
        return;
    }

    if (examState.value === 'passed') {
        Swal.fire({ 
            icon: 'success', 
            title: 'Certificación Completa', 
            text: 'Ya aprobaste esta evaluación.', 
            confirmButtonColor: '#10b981' 
        });
        return;
    }

    if (examState.value === 'failed') {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Examen Reprobado', 
            text: 'Reprobaste el examen. Deberás esperar 7 días para programar uno nuevo.', 
            confirmButtonColor: '#f59e0b' 
        });
        return;
    }

    if (examState.value === 'need_oral') {
        Swal.fire({ 
            icon: 'error', 
            title: 'Oral Pendiente', 
            text: 'Debes aprobar la evaluación oral primero.', 
            confirmButtonColor: '#ef4444' 
        });
        return;
    }

    if (examState.value === 'no_appointment') {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Sin Cita Activa', 
            text: 'No tienes una cita activa. Solicita una nueva cita al administrador.', 
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    if (examState.value === 'waiting_admin') {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Esperando Habilitación', 
            text: 'Tu acceso está autorizado, pero el encargado del laboratorio debe habilitar tu equipo.', 
            confirmButtonColor: '#f59e0b' 
        });
        return;
    }

    try {
        Swal.fire({ 
            title: 'Preparando examen...', 
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });
        
        const response = await axios.post('/api/v1/student/start-exam');
        if (response.data.attempt_id) {
            Swal.close();
            router.push({ name: 'student.exam', params: { id: response.data.attempt_id } });
        } else {
            throw new Error('No se pudo iniciar el examen');
        }
    } catch (error) {
        Swal.close();
        console.error('Error iniciando examen:', error);
        Swal.fire({ 
            icon: 'error', 
            title: 'Error', 
            text: error.response?.data?.message || 'No se pudo iniciar la sesión de examen.' 
        });
    }
};

const loadInitialData = () => {
    try {
        const userData = JSON.parse(localStorage.getItem('user_data')) || {};
        
        if (userData.penalty_until) penaltyUntil.value = userData.penalty_until;
        if (userData.missed_exam_date) missedExamDate.value = userData.missed_exam_date;
        if (userData.has_active_appointment) hasActiveAppointment.value = userData.has_active_appointment;
        if (userData.penalty_type) penaltyType.value = userData.penalty_type;
        if (userData.name) user.value = userData;
    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
    }
};

// Watchers para depuración
watch(activeAppointment, (newVal) => {
    console.log('🔄 activeAppointment actualizado:', {
        id: newVal?.id,
        test_type: newVal?.test_type,
        is_unlocked: newVal?.is_unlocked,
        active: newVal?.active
    });
}, { deep: true });

watch(examState, (newVal) => {
    console.log('🔄 examState actualizado a:', newVal);
});

watch(isExamEnabled, (newVal) => {
    console.log('🔄 isExamEnabled actualizado a:', newVal);
});

watch(modularData, (newVal) => {
    console.log('📚 modularData actualizado:', newVal);
}, { deep: true });

onMounted(() => {
    loadInitialData();
    refreshData();
});
</script>