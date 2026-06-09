<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4 select-none" 
         @contextmenu.prevent 
         @copy.prevent
         @cut.prevent
         @dragstart.prevent
         @keydown.prevent="handleSecurityKeys">
        
        <div class="max-w-4xl mx-auto">
            
            <!-- Loader -->
            <div v-if="loading" class="bg-white p-12 rounded-2xl shadow text-center">
                <div class="animate-spin inline-block w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full mb-4"></div>
                <p class="text-gray-500">Preparando entorno seguro...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="bg-red-50 p-8 rounded-2xl shadow text-center">
                <div class="text-5xl mb-4">⚠️</div>
                <p class="text-red-600 font-bold">{{ error }}</p>
                <button @click="goBack" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">
                    Volver al Dashboard
                </button>
            </div>

            <!-- Expulsado -->
            <div v-else-if="isExpelled" class="bg-red-50 p-8 rounded-2xl shadow text-center">
                <div class="text-6xl mb-4">🚫</div>
                <h2 class="text-2xl font-bold text-red-600 mb-2">EXAMEN ANULADO</h2>
                <p class="text-gray-700 mb-4">{{ expulsionReason }}</p>
                <div class="bg-red-100 p-4 rounded-lg mb-4">
                    <p class="font-bold text-red-700">📋 Violaciones registradas:</p>
                    <ul class="text-sm text-left mt-2 space-y-1">
                        <li v-for="v in violations" :key="v.time">
                            • {{ v.type }} - {{ v.time }}
                        </li>
                    </ul>
                </div>
                <button @click="goBack" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold">
                    Salir del Sistema
                </button>
            </div>

            <!-- Completado -->
            <div v-else-if="completed" class="bg-white p-8 rounded-2xl shadow text-center">
                <div class="text-6xl mb-4">{{ finalScore >= 60 ? '🏆' : '📚' }}</div>
                <h2 class="text-2xl font-bold mb-2">{{ finalScore >= 60 ? '¡Examen Aprobado!' : 'Examen Completado' }}</h2>
                <div :class="finalScore >= 60 ? 'bg-green-50' : 'bg-yellow-50'" class="p-4 rounded-lg mb-4">
                    <p class="font-bold text-xl">Tu puntuación: {{ finalScore }}%</p>
                    <p class="text-lg mt-2">{{ finalScore >= 60 ? '✅ APROBADO' : '❌ REPROBADO' }}</p>
                </div>
                <button @click="goBack" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold">
                    Volver al Dashboard
                </button>
            </div>

            <!-- Examen activo -->
            <div v-else class="bg-white rounded-2xl shadow overflow-hidden">
                
                <!-- Header con timer -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold">Examen Modular</h1>
                            <p class="text-indigo-200 text-sm mt-1">
                                Módulo {{ currentIndex + 1 }} de {{ modules.length }}
                            </p>
                        </div>
                        <div :class="timeLeft < 300 ? 'bg-red-500 animate-pulse' : 'bg-indigo-500'" 
                             class="px-4 py-2 rounded-xl font-mono font-bold text-lg">
                            ⏱️ {{ formatTime(timeLeft) }}
                        </div>
                    </div>
                    <div class="mt-3 w-full bg-indigo-700 rounded-full h-2">
                        <div class="bg-white rounded-full h-2 transition-all" :style="{ width: (timeLeft / totalTime) * 100 + '%' }"></div>
                    </div>
                </div>

                <!-- Módulo actual -->
                <div v-if="currentModule" class="p-6">
                    
                    <!-- Progreso -->
                    <div class="mb-6">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm text-gray-600">Progreso:</span>
                            <span class="text-sm font-bold">{{ answeredCount }}/{{ currentModule.questions?.length || 0 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="rounded-full h-2 transition-all bg-indigo-600" 
                                 :style="{ width: (answeredCount / (currentModule.questions?.length || 1)) * 100 + '%' }">
                            </div>
                        </div>
                    </div>

                    <!-- Título y contenido -->
                    <h2 class="text-xl font-bold text-gray-800 mb-2">{{ currentModule.title }}</h2>
                    <p class="text-gray-600 mb-4">{{ currentModule.content }}</p>
                    
                    <!-- Audio (con evento play para registrar única reproducción) -->
                    <div v-if="currentModule.audio" class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <audio 
                            controls 
                            class="w-full" 
                            controlsList="nodownload"
                            @play="onAudioPlay"
                        >
                            <source :src="getUrl(currentModule.audio)" type="audio/mpeg">
                        </audio>
                        <p class="text-xs text-gray-500 mt-2">
                            🔊 El tiempo continúa corriendo mientras escuchas
                            <span v-if="isAudioPlayed" class="text-green-500 ml-2">✓ Audio ya reproducido</span>
                        </p>
                    </div>

                    <!-- Imagen (con evento load para registrar vista) -->
                    <div v-if="currentModule.picture" class="mb-4">
                        <img 
                            :src="getUrl(currentModule.picture)" 
                            class="rounded-lg max-w-full"
                            @load="onImageLoaded"
                        >
                    </div>

                    <!-- Preguntas -->
                    <div class="space-y-4">
                        <div v-for="(q, idx) in currentModule.questions" :key="q.id"
                             :class="['border rounded-lg p-4', !answers[q.id] && showValidation ? 'border-red-400 bg-red-50' : 'border-gray-200']">
                            
                            <p class="font-bold mb-3">{{ idx + 1 }}. {{ q.text }} <span class="text-red-500">*</span></p>
                            
                            <div class="space-y-2">
                                <label v-for="(opt, letter) in q.options" :key="letter"
                                       class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" :name="`q${q.id}`" :value="letter"
                                           v-model="answers[q.id]" @change="saveAnswer(q.id, letter)"
                                           class="w-4 h-4 text-indigo-600">
                                    <span><strong>{{ letter }}.</strong> {{ opt }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Botón siguiente -->
                    <div class="flex justify-end mt-6 pt-4 border-t">
                        <button @click="nextModule"
                                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold">
                            {{ isLastModule ? 'Finalizar' : 'Siguiente →' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de advertencia -->
        <div v-if="showWarning" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 max-w-md text-center">
                <div class="text-5xl mb-4">⚠️</div>
                <h3 class="text-xl font-bold mb-2">Violación de Seguridad</h3>
                <p class="text-gray-600 mb-4">{{ warningMessage }}</p>
                <p class="text-sm text-red-600 mb-4">Violación {{ violationCount }}/{{ maxViolations }}</p>
                <button @click="showWarning = false" class="bg-indigo-600 text-white px-6 py-2 rounded-lg">
                    Continuar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

// ==================== ESTADO ====================
const loading = ref(true);
const error = ref(null);
const completed = ref(false);
const isExpelled = ref(false);
const expulsionReason = ref('');
const violations = ref([]);

const modules = ref([]);
const currentIndex = ref(0);
const timeLeft = ref(0);
const totalTime = ref(2400);
const answers = ref({});
const results = ref(null);
const showValidation = ref(false);
const attemptId = ref(null);

// Estados para control de medios
const playedAudios = ref([]);
const viewedMedia = ref([]);

// Control para evitar múltiples registros
const isRegisteringAudio = ref(false);
const isRegisteringImage = ref(false);

// Seguridad
const showWarning = ref(false);
const warningMessage = ref('');
const violationCount = ref(0);
const maxViolations = ref(3);

// Refs para timers
let timerInterval = null;
let securityInterval = null;
let timerSyncInterval = null;

// ==================== COMPUTED ====================
const currentModule = computed(() => modules.value[currentIndex.value] || null);
const answeredCount = computed(() => {
    if (!currentModule.value?.questions) return 0;
    return currentModule.value.questions.filter(q => answers.value[q.id]).length;
});
const isLastModule = computed(() => currentIndex.value === modules.value.length - 1);
const finalScore = computed(() => results.value?.total_percentage || 0);

// Verificar si el audio ya fue reproducido (usando el ID del módulo)
const isAudioPlayed = computed(() => {
    if (!currentModule.value) return false;
    return playedAudios.value.includes(currentModule.value.id);
});

// ==================== FUNCIONES DE SINCRONIZACIÓN ====================

// Sincronizar timer con backend cada 5 segundos
const startTimerSync = () => {
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    
    timerSyncInterval = setInterval(async () => {
        if (!attemptId.value || isExpelled.value || completed.value) return;
        
        // Solo sincronizar si timeLeft es válido
        if (timeLeft.value >= 0) {
            try {
                await axios.post(`/api/v1/student/modular-exam/sync-timer/${attemptId.value}`, {
                    time_left: timeLeft.value
                });
            } catch (error) {
                console.error('Error sincronizando timer:', error);
            }
        }
    }, 5000);
};

// Registrar audio reproducido (solo una vez)
const registerAudioPlayed = async (moduleId) => {
    // Evitar registros múltiples
    if (isRegisteringAudio.value) return;
    if (playedAudios.value.includes(moduleId)) return;
    
    isRegisteringAudio.value = true;
    
    try {
        await axios.post(`/api/v1/student/modular-exam/audio-played/${attemptId.value}`, {
            module_id: moduleId
        });
        playedAudios.value.push(moduleId);
        console.log(`🎵 Audio del módulo ${moduleId} registrado como reproducido`);
    } catch (error) {
        console.error('Error registrando audio:', error);
    } finally {
        isRegisteringAudio.value = false;
    }
};

// Registrar imagen vista (solo una vez)
const registerMediaViewed = async (moduleId, type) => {
    const key = `${type}_${moduleId}`;
    
    if (isRegisteringImage.value) return;
    if (viewedMedia.value.includes(key)) return;
    
    isRegisteringImage.value = true;
    
    try {
        await axios.post(`/api/v1/student/modular-exam/media-viewed/${attemptId.value}`, {
            module_id: moduleId,
            media_type: type
        });
        viewedMedia.value.push(key);
        console.log(`🖼️ ${type} del módulo ${moduleId} registrado como visto`);
    } catch (error) {
        console.error('Error registrando media:', error);
    } finally {
        isRegisteringImage.value = false;
    }
};

// Evento de audio - solo permite reproducción si no se ha reproducido antes
const onAudioPlay = (event) => {
    if (!currentModule.value) return;
    
    // Si el audio ya fue reproducido, pausarlo inmediatamente
    if (isAudioPlayed.value) {
        event.preventDefault();
        event.target.pause();
        Swal.fire({
            icon: 'warning',
            title: 'Audio ya reproducido',
            text: 'Solo puedes reproducir el audio una vez.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    // Registrar que el audio se está reproduciendo
    registerAudioPlayed(currentModule.value.id);
};

// Evento de imagen cargada
const onImageLoaded = () => {
    if (currentModule.value) {
        registerMediaViewed(currentModule.value.id, 'image');
    }
};

// ==================== SEGURIDAD ====================

let devToolsConfirmations = 0;
let lastDevToolsTime = 0;
let tabSwitchCount = 0;
let lastTabTime = 0;
let blurCount = 0;
let lastBlurTime = 0;

const DEVTOOLS_THRESHOLD = 200;
const DETECTION_COOLDOWN = 10000;
const REQUIRED_CONFIRMATIONS = 2;

const detectDevTools = () => {
    if (isExpelled.value || completed.value) return;
    
    const widthDiff = window.outerWidth - window.innerWidth;
    const heightDiff = window.outerHeight - window.innerHeight;
    
    const isDevToolsReallyOpen = (widthDiff > DEVTOOLS_THRESHOLD) || (heightDiff > DEVTOOLS_THRESHOLD);
    
    if (isDevToolsReallyOpen) {
        const now = Date.now();
        
        if (now - lastDevToolsTime > DETECTION_COOLDOWN) {
            lastDevToolsTime = now;
            devToolsConfirmations++;
            
            if (devToolsConfirmations >= REQUIRED_CONFIRMATIONS) {
                registerViolation('devtools_opened', `DevTools confirmada (${widthDiff}x${heightDiff})`);
                devToolsConfirmations = 0;
            }
        }
    } else {
        devToolsConfirmations = 0;
    }
};

const detectTabSwitch = () => {
    if (isExpelled.value || completed.value) return;
    
    if (document.hidden) {
        const now = Date.now();
        if (now - lastTabTime > DETECTION_COOLDOWN) {
            lastTabTime = now;
            tabSwitchCount++;
            registerViolation('tab_switch', `Cambio de pestaña #${tabSwitchCount}`);
        }
    }
};

const detectWindowBlur = () => {
    if (isExpelled.value || completed.value) return;
    
    const now = Date.now();
    if (now - lastBlurTime > DETECTION_COOLDOWN) {
        lastBlurTime = now;
        blurCount++;
        registerViolation('window_blur', `Pérdida de foco #${blurCount}`);
    }
};

const registerViolation = async (type, detail) => {
    violationCount.value++;
    
    violations.value.unshift({
        type: type,
        detail: detail,
        time: new Date().toLocaleTimeString()
    });
    
    if (violations.value.length > 10) violations.value.pop();
    
    const remaining = maxViolations.value - violationCount.value;
    warningMessage.value = `⚠️ ${type}. Te quedan ${remaining} oportunidad(es) antes de ser expulsado.`;
    showWarning.value = true;
    
    try {
        await axios.post('/api/v1/student/log-security-event', {
            exam_attempt_id: attemptId.value,
            event_type: type,
            details: detail
        });
    } catch (e) {
        console.error('Error registrando violación:', e);
    }
    
    if (violationCount.value >= maxViolations.value) {
        await expelStudent(`Has alcanzado el límite de ${maxViolations.value} violaciones de seguridad.`);
    }
};

const expelStudent = async (reason) => {
    if (isExpelled.value) return;
    
    if (timerInterval) clearInterval(timerInterval);
    if (securityInterval) clearInterval(securityInterval);
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    
    isExpelled.value = true;
    expulsionReason.value = reason;
    
    try {
        await axios.post(`/api/v1/student/modular-exam/invalidate/${attemptId.value}`, { reason });
    } catch (e) {
        console.error('Error invalidando examen:', e);
    }
    
    showWarning.value = false;
};

const handleSecurityKeys = (e) => {
    if (isExpelled.value || completed.value) return;
    
    if (e.key === 'F12') {
        e.preventDefault();
        registerViolation('f12_pressed', 'Tecla F12 presionada');
        return;
    }
    
    if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
        e.preventDefault();
        registerViolation('devtools_shortcut', `Ctrl+Shift+${e.key} presionado`);
        return;
    }
    
    if (e.ctrlKey && e.key.toUpperCase() === 'U') {
        e.preventDefault();
        registerViolation('view_source', 'Intento de ver código fuente');
        return;
    }
    
    if (e.ctrlKey && e.key.toUpperCase() === 'R') {
        e.preventDefault();
        registerViolation('reload_attempt', 'Intento de recargar página');
        Swal.fire({
            icon: 'warning',
            title: 'Recarga bloqueada',
            text: 'No puedes recargar la página durante el examen.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    if (e.key === 'PrintScreen') {
        e.preventDefault();
        registerViolation('screenshot', 'Intento de captura de pantalla');
        return;
    }
};

// ==================== FUNCIONES PRINCIPALES ====================

const formatTime = (seconds) => {
    // 🔥 CORREGIDO: Evitar valores negativos
    if (!seconds || seconds <= 0) return '00:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const getUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage/')) return path;
    return '/storage/' + path.replace(/^.*?storage/, '').replace(/^\/+/, '');
};

const saveAnswer = async (questionId, option) => {
    if (!attemptId.value || isExpelled.value) return;
    
    try {
        await axios.post(`/api/v1/student/modular-exam/save/${attemptId.value}`, {
            module_id: currentModule.value.id,
            answers: [{ question_id: questionId, selected_option: option }]
        });
        
        if (showValidation.value && answeredCount.value === currentModule.value.questions?.length) {
            showValidation.value = false;
        }
    } catch (err) {
        console.error('Error guardando respuesta:', err);
    }
};

const loadExam = async () => {
    const assignmentId = route.params.assignmentId;
    
    if (!assignmentId) {
        error.value = 'No se encontró el examen';
        loading.value = false;
        return;
    }
    
    try {
        const res = await axios.get(`/api/v1/student/modular-exam/load/${assignmentId}`);
        
        if (res.data.success) {
            modules.value = res.data.modules || [];
            currentIndex.value = res.data.current_module || 0;
            
            // 🔥 CORREGIDO: Asegurar que timeLeft nunca sea negativo
            const rawTimeLeft = res.data.time_left || res.data.total_duration_seconds || 2400;
            timeLeft.value = Math.max(0, rawTimeLeft);
            totalTime.value = res.data.total_duration_seconds || 2400;
            attemptId.value = res.data.attempt_id;
            answers.value = res.data.saved_answers || {};
            playedAudios.value = res.data.played_audios || [];
            viewedMedia.value = res.data.viewed_media || [];
            
            console.log('✅ Examen cargado correctamente');
            console.log(`⏱️ Tiempo restante: ${timeLeft.value}s`);
            console.log(`🎵 Audios reproducidos: ${playedAudios.value.length}`);
            
            startTimer();
            startTimerSync();
            startSecurity();
        } else {
            error.value = res.data.error || 'Error al cargar el examen';
        }
    } catch (err) {
        console.error('Error loading exam:', err);
        error.value = err.response?.data?.error || 'Error de conexión';
    } finally {
        loading.value = false;
    }
};

const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval);
    
    timerInterval = setInterval(() => {
        // 🔥 CORREGIDO: Solo decrementar si es mayor a 0
        if (timeLeft.value > 0 && !isExpelled.value && !completed.value) {
            timeLeft.value--;
        } 
        // 🔥 CORREGIDO: Si llega a 0 o menos, finalizar
        else if (timeLeft.value <= 0 && !completed.value && !isExpelled.value) {
            clearInterval(timerInterval);
            finishExam();
        }
    }, 1000);
};

const startSecurity = () => {
    securityInterval = setInterval(() => {
        detectDevTools();
    }, 2000);
    
    document.addEventListener('visibilitychange', detectTabSwitch);
    window.addEventListener('blur', detectWindowBlur);
};

const stopSecurity = () => {
    if (securityInterval) clearInterval(securityInterval);
    document.removeEventListener('visibilitychange', detectTabSwitch);
    window.removeEventListener('blur', detectWindowBlur);
};

const nextModule = async () => {
    if (isExpelled.value) return;
    
    showValidation.value = true;
    
    if (answeredCount.value !== currentModule.value?.questions?.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Preguntas pendientes',
            text: `Te faltan ${currentModule.value.questions.length - answeredCount.value} pregunta(s) por responder`,
            confirmButtonText: 'Entiendo'
        });
        return;
    }
    
    showValidation.value = false;
    
    const moduleAnswers = currentModule.value.questions
        .filter(q => answers.value[q.id])
        .map(q => ({ question_id: q.id, selected_option: answers.value[q.id] }));
    
    await axios.post(`/api/v1/student/modular-exam/save/${attemptId.value}`, {
        module_id: currentModule.value.id,
        answers: moduleAnswers
    });
    
    if (isLastModule.value) {
        await finishExam();
    } else {
        await axios.post(`/api/v1/student/modular-exam/next/${attemptId.value}`);
        currentIndex.value++;
    }
};

const finishExam = async () => {
    if (isExpelled.value) return;
    
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    
    try {
        const res = await axios.post(`/api/v1/student/modular-exam/finish/${attemptId.value}`);
        
        if (res.data.success) {
            results.value = res.data.results;
            completed.value = true;
            stopSecurity();
            if (timerInterval) clearInterval(timerInterval);
        }
    } catch (err) {
        console.error('Error finalizando:', err);
    }
};

const goBack = () => {
    router.push({ name: 'student.dashboard' });
};

// ==================== LIFECYCLE ====================
onMounted(() => {
    loadExam();
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    stopSecurity();
});
</script>

<style scoped>
.select-none {
    user-select: none;
    -webkit-user-select: none;
}

audio::-webkit-media-controls-download-button {
    display: none;
}

.animate-pulse {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
</style>