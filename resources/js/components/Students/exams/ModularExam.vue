<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4 select-none" 
         @contextmenu.prevent 
         @copy.prevent
         @cut.prevent
         @dragstart.prevent
         @keydown.prevent="handleSecurityKeys">
        
        <!-- Overlay de carga dinámico -->
        <div v-if="isProcessing" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl p-8 text-center shadow-2xl">
                <div class="animate-spin inline-block w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full mb-4"></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">{{ processingMessage }}</h3>
                <p class="text-gray-500">{{ processingSubMessage }}</p>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">
            
            <!-- Loader inicial -->
            <div v-if="loading" class="bg-white p-12 rounded-2xl shadow text-center">
                <div class="animate-spin inline-block w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full mb-4"></div>
                <p class="text-gray-500">Preparando entorno seguro...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="bg-red-50 p-8 rounded-2xl shadow text-center">
                <div class="text-5xl mb-4">⚠️</div>
                <p class="text-red-600 font-bold">{{ error }}</p>
                <button @click="goBack" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">Volver al Dashboard</button>
            </div>

            <!-- Expulsado -->
            <div v-else-if="isExpelled" class="bg-red-50 p-8 rounded-2xl shadow text-center">
                <div class="text-6xl mb-4">🚫</div>
                <h2 class="text-2xl font-bold text-red-600 mb-2">EXAMEN ANULADO</h2>
                <p class="text-gray-700 mb-4">{{ expulsionReason }}</p>
                <div class="bg-red-100 p-4 rounded-lg mb-4">
                    <p class="font-bold text-red-700">📋 Violaciones registradas:</p>
                    <ul class="text-sm text-left mt-2 space-y-1">
                        <li v-for="v in violations" :key="v.time">• {{ v.type }} - {{ v.time }}</li>
                    </ul>
                </div>
                <button @click="goBack" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold">Salir del Sistema</button>
            </div>

            <!-- Completado -->
            <div v-else-if="completed" class="bg-white p-8 rounded-2xl shadow text-center">
                <div class="text-6xl mb-4">{{ finalScore >= 60 ? '🏆' : '📚' }}</div>
                <h2 class="text-2xl font-bold mb-2">{{ finalScore >= 60 ? '¡Examen Aprobado!' : 'Examen Completado' }}</h2>
                <div :class="finalScore >= 60 ? 'bg-green-50' : 'bg-yellow-50'" class="p-4 rounded-lg mb-4">
                    <p class="font-bold text-xl">Tu puntuación: {{ finalScore }}%</p>
                    <p class="text-lg mt-2">{{ finalScore >= 60 ? '✅ APROBADO' : '❌ REPROBADO' }}</p>
                </div>
                <button @click="goBack" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold">Volver al Dashboard</button>
            </div>

            <!-- Examen activo -->
            <div v-else class="bg-white rounded-2xl shadow overflow-hidden">
                
                <!-- Header con timer -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold">Examen Modular</h1>
                            <p class="text-indigo-200 text-sm mt-1">Módulo {{ currentIndex + 1 }} de {{ modules.length }}</p>
                        </div>
                        <div :class="timeLeft < 300 ? 'bg-red-500 animate-pulse' : 'bg-indigo-500'" class="px-4 py-2 rounded-xl font-mono font-bold text-lg">
                            ⏱️ {{ formatTime(timeLeft) }}
                        </div>
                    </div>
                    <div class="mt-3 w-full bg-indigo-700 rounded-full h-2">
                        <div class="bg-white rounded-full h-2 transition-all" :style="{ width: (timeLeft / totalTime) * 100 + '%' }"></div>
                    </div>
                </div>

                <!-- Módulo actual -->
                <transition name="fade-slide" mode="out-in">
                    <div v-if="currentModule" :key="currentModule.id" class="p-6">
                        
                        <!-- Progreso general -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-500">Progreso del examen</span>
                                <span class="text-sm font-bold text-indigo-600">{{ currentIndex + 1 }}/{{ modules.length }} módulos</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 rounded-full h-2 transition-all duration-500" :style="{ width: ((currentIndex) / modules.length) * 100 + '%' }"></div>
                            </div>
                        </div>

                        <!-- Progreso del módulo actual -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-600">Progreso del módulo:</span>
                                <span class="text-sm font-bold" :class="answeredCount === currentModule.questions?.length ? 'text-green-600' : 'text-indigo-600'">
                                    {{ answeredCount }}/{{ currentModule.questions?.length || 0 }} preguntas
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="rounded-full h-2 transition-all duration-500 bg-indigo-600" :style="{ width: (answeredCount / (currentModule.questions?.length || 1)) * 100 + '%' }"></div>
                            </div>
                        </div>

                        <!-- Título y contenido -->
                        <transition name="fade" mode="out-in">
                            <div :key="currentModule.id">
                                <h2 class="text-xl font-bold text-gray-800 mb-2">{{ currentModule.title }}</h2>
                                <p class="text-gray-600 mb-4">{{ currentModule.content }}</p>
                            </div>
                        </transition>
                        
                        <!-- Audio -->
                        <div v-if="currentModule.audio" class="mb-4 p-4 bg-gray-50 rounded-lg">
                            <div v-if="!isAudioPlayed" class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <button @click="playAudio" :disabled="isAudioPlaying" class="w-12 h-12 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white rounded-full flex items-center justify-center transition-all transform hover:scale-105">
                                        <svg v-if="!isAudioPlaying" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                    <div>
                                        <p class="font-medium text-gray-700">🎵 Audio del módulo</p>
                                        <p class="text-xs text-gray-500">Reproducir una sola vez</p>
                                    </div>
                                </div>
                                <span class="text-xs text-indigo-500 bg-indigo-50 px-2 py-1 rounded-full">Pendiente</span>
                            </div>
                            <div v-else class="flex items-center justify-between text-green-600">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-green-700">✓ Audio completado</p>
                                        <p class="text-xs text-gray-500">Ya has reproducido este audio</p>
                                    </div>
                                </div>
                                <span class="text-xs text-green-500 bg-green-50 px-2 py-1 rounded-full">Completado</span>
                            </div>
                            
                            <!-- Elemento de audio oculto -->
                            <audio ref="audioPlayer" class="hidden" @ended="onAudioEnded" @play="onAudioPlayStart">
                                <source :src="getAudioUrl()" type="audio/mpeg">
                            </audio>
                        </div>

                        <!-- Imagen -->
                        <div v-if="currentModule.picture" class="mb-4">
                            <img :src="getUrl(currentModule.picture)" class="rounded-lg max-w-full" @load="onImageLoaded">
                        </div>

                        <!-- Preguntas -->
                        <transition-group name="list" tag="div" class="space-y-4">
                            <div v-for="(q, idx) in currentModule.questions" :key="q.id" :class="['border rounded-lg p-4 transition-all duration-300', !answers[q.id] && showValidation ? 'border-red-400 bg-red-50' : 'border-gray-200 hover:shadow-md']">
                                <p class="font-bold mb-3">{{ idx + 1 }}. {{ q.text }} <span class="text-red-500">*</span></p>
                                <div class="space-y-2">
                                    <label v-for="(opt, letter) in q.options" :key="letter" class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{'bg-indigo-50 border border-indigo-200': answers[q.id] === letter}">
                                        <input type="radio" :name="`q${q.id}`" :value="letter" v-model="answers[q.id]" @change="saveAnswer(q.id, letter)" class="w-4 h-4 text-indigo-600">
                                        <span><strong>{{ letter }}.</strong> {{ opt }}</span>
                                    </label>
                                </div>
                            </div>
                        </transition-group>

                        <!-- Botón siguiente y botón de prueba -->
                        <div class="flex justify-end mt-6 pt-4 border-t gap-2">
                            <button @click="nextModule" :disabled="isProcessing" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg font-bold transition-all transform hover:scale-105 flex items-center gap-2">
                                <span v-if="isProcessing" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                                <span>{{ isLastModule ? 'Finalizar Examen' : 'Siguiente Módulo →' }}</span>
                            </button>
                            
                            <!-- Botón de prueba TEMPORAL -->
                            <button @click="testSecurityEvent" class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-bold transition">
                                🧪 Test Violación
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Modal de advertencia -->
        <div v-if="showWarning" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 max-w-md text-center">
                <div class="text-5xl mb-4">⚠️</div>
                <h3 class="text-xl font-bold mb-2">Violación de Seguridad</h3>
                <p class="text-gray-600 mb-4">{{ warningMessage }}</p>
                <p class="text-sm text-red-600 mb-4">Violación {{ violationCount }}/{{ maxViolations }}</p>
                <button @click="showWarning = false" class="bg-indigo-600 text-white px-6 py-2 rounded-lg">Continuar</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

// ==================== ESTADO ====================
const loading = ref(true);
const isProcessing = ref(false);
const processingMessage = ref('');
const processingSubMessage = ref('');
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

// Estados de audio
const playedAudios = ref([]);
const viewedMedia = ref([]);
const audioPlayer = ref(null);
const isAudioPlaying = ref(false);

// Seguridad
const showWarning = ref(false);
const warningMessage = ref('');
const violationCount = ref(0);
const maxViolations = ref(3);

// Timers
let timerInterval = null;
let securityInterval = null;
let timerSyncInterval = null;

// Variables de seguridad
let devToolsConfirmations = 0;
let lastDevToolsTime = 0;
let tabSwitchCount = 0;
let lastTabTime = 0;
let blurCount = 0;
let lastBlurTime = 0;

const DEVTOOLS_THRESHOLD = 200;
const DETECTION_COOLDOWN = 10000;
const REQUIRED_CONFIRMATIONS = 2;

// ==================== FUNCIÓN PARA SCROLL AL INICIO ====================
const scrollToTop = () => {
    nextTick(() => {
        window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
        const mainContainer = document.querySelector('.max-w-4xl');
        if (mainContainer) mainContainer.scrollTop = 0;
    });
};

// ==================== COMPUTED ====================
const currentModule = computed(() => modules.value[currentIndex.value] || null);
const answeredCount = computed(() => {
    if (!currentModule.value?.questions) return 0;
    return currentModule.value.questions.filter(q => answers.value[q.id]).length;
});
const isLastModule = computed(() => currentIndex.value === modules.value.length - 1);
const finalScore = computed(() => results.value?.total_percentage || 0);
const isAudioPlayed = computed(() => {
    if (!currentModule.value) return false;
    return playedAudios.value.includes(currentModule.value.id);
});

// ==================== BLOQUEO DE F12 Y HERRAMIENTAS ====================
const blockDevToolsCompletely = () => {
    const preventDevTools = (e) => {
        if (e.key === 'F12') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            registerViolation('f12_blocked', 'Intento de abrir DevTools con F12');
            return false;
        }
        if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i')) {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('devtools_blocked', 'Intento de abrir DevTools con Ctrl+Shift+I');
            return false;
        }
        if (e.ctrlKey && e.shiftKey && (e.key === 'J' || e.key === 'j')) {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('devtools_blocked', 'Intento de abrir consola con Ctrl+Shift+J');
            return false;
        }
        if (e.ctrlKey && e.shiftKey && (e.key === 'C' || e.key === 'c')) {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('devtools_blocked', 'Intento de abrir inspector con Ctrl+Shift+C');
            return false;
        }
        if (e.ctrlKey && (e.key === 'U' || e.key === 'u')) {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('view_source_blocked', 'Intento de ver código fuente');
            return false;
        }
        if (e.ctrlKey && (e.key === 'R' || e.key === 'r')) {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('reload_blocked', 'Intento de recargar página');
            Swal.fire({ icon: 'warning', title: 'Recarga bloqueada', text: 'No puedes recargar la página durante el examen.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            return false;
        }
        if (e.key === 'F5') {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('reload_blocked', 'Intento de recargar con F5');
            return false;
        }
        if (e.key === 'PrintScreen') {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('screenshot_blocked', 'Intento de captura de pantalla');
            return false;
        }
        if (e.ctrlKey && (e.key === 'P' || e.key === 'p')) {
            e.preventDefault();
            e.stopPropagation();
            registerViolation('print_blocked', 'Intento de imprimir');
            return false;
        }
        return true;
    };
    
    document.addEventListener('keydown', preventDevTools, true);
    
    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        registerViolation('right_click_blocked', 'Intento de clic derecho');
        return false;
    }, true);
    
    const style = document.createElement('style');
    style.innerHTML = `
        audio::-webkit-media-controls-download-button { display: none !important; }
        audio::-webkit-media-controls-enclosure { overflow: hidden !important; }
        audio::-webkit-media-controls-panel { width: calc(100% + 30px) !important; }
        audio::-webkit-media-controls-playback-rate-button { display: none !important; }
    `;
    document.head.appendChild(style);
};

// ==================== UTILIDADES ====================
const formatTime = (seconds) => {
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

const getAudioUrl = () => {
    if (!currentModule.value?.audio) return '';
    return getUrl(currentModule.value.audio);
};

// ==================== AUDIO ====================
const playAudio = async () => {
    if (!audioPlayer.value) return;
    if (isAudioPlayed.value) {
        Swal.fire({ icon: 'warning', title: 'Audio ya reproducido', text: 'Solo puedes reproducir este audio una vez.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
        return;
    }
    try {
        isAudioPlaying.value = true;
        await audioPlayer.value.play();
    } catch (error) {
        console.error('Error reproduciendo audio:', error);
        isAudioPlaying.value = false;
    }
};

const onAudioPlayStart = () => {
    isAudioPlaying.value = true;
    registerAudioPlayed(currentModule.value.id);
};

const onAudioEnded = () => {
    isAudioPlaying.value = false;
};

const registerAudioPlayed = async (moduleId) => {
    if (playedAudios.value.includes(moduleId)) return;
    try {
        await axios.post(`/api/v1/student/modular-exam/audio-played/${attemptId.value}`, { module_id: moduleId });
        playedAudios.value.push(moduleId);
    } catch (error) {
        console.error('Error registrando audio:', error);
    }
};

const registerMediaViewed = async (moduleId, type) => {
    const key = `${type}_${moduleId}`;
    if (viewedMedia.value.includes(key)) return;
    try {
        await axios.post(`/api/v1/student/modular-exam/media-viewed/${attemptId.value}`, { module_id: moduleId, media_type: type });
        viewedMedia.value.push(key);
    } catch (error) {
        console.error('Error registrando media:', error);
    }
};

const onImageLoaded = () => {
    if (currentModule.value) registerMediaViewed(currentModule.value.id, 'image');
};

// ==================== RESPUESTAS ====================
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

// ==================== SEGURIDAD ====================
const registerViolation = async (type, detail) => {
    console.log('🔴 Violación:', type, detail);
    
    if (isExpelled.value || completed.value) return;
    
    const currentViolationNumber = violationCount.value + 1;
    
    try {
        const response = await axios.post('/api/v1/student/log-security-event', {
            exam_attempt_id: attemptId.value || null,
            event_type: type,
            details: detail
        });
        
        if (response.data.success) {
            violationCount.value = response.data.violation_count || currentViolationNumber;
            maxViolations.value = response.data.limit || 3;
            
            violations.value.unshift({
                type: type,
                detail: detail,
                time: new Date().toLocaleTimeString(),
                count: response.data.violation_count
            });
            if (violations.value.length > 10) violations.value.pop();
            
            if (response.data.status === 'exam_invalidated') {
                await expelStudent(response.data.message || 'El examen ha sido invalidado por violaciones de seguridad.');
                return;
            }
            
            if (response.data.warning) {
                warningMessage.value = response.data.warning;
                showWarning.value = true;
                setTimeout(() => { if (showWarning.value) showWarning.value = false; }, 5000);
            } else if (response.data.remaining_warnings <= 2) {
                Swal.fire({ icon: 'warning', title: '⚠️ Violación de seguridad', text: `Se ha registrado una violación (${violationCount.value}/${maxViolations.value})`, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        }
    } catch (error) {
        console.error('Error registrando violación:', error);
        violationCount.value = currentViolationNumber;
        violations.value.unshift({ type: type, detail: detail, time: new Date().toLocaleTimeString(), count: currentViolationNumber });
        
        if (violationCount.value >= maxViolations.value) {
            await expelStudent(`Has alcanzado el límite de ${maxViolations.value} violaciones de seguridad.`);
        } else {
            const remaining = maxViolations.value - violationCount.value;
            warningMessage.value = `⚠️ ${type}. Te quedan ${remaining} oportunidad(es) antes de ser expulsado.`;
            showWarning.value = true;
            setTimeout(() => { if (showWarning.value) showWarning.value = false; }, 5000);
        }
    }
};

const expelStudent = async (reason) => {
    if (isExpelled.value) return;
    
    console.log('🚫 Expulsando estudiante:', reason);
    
    if (timerInterval) clearInterval(timerInterval);
    if (securityInterval) clearInterval(securityInterval);
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    
    isExpelled.value = true;
    expulsionReason.value = reason;
    showWarning.value = false;
    
    if (attemptId.value) {
        try {
            await axios.post(`/api/v1/student/modular-exam/invalidate/${attemptId.value}`, { reason });
        } catch (e) {}
    }
    
    Swal.fire({
        icon: 'error',
        title: '🚫 EXAMEN ANULADO',
        html: `<p class="text-lg font-bold text-red-600">Has sido expulsado</p><p class="text-sm mt-2">${reason}</p><hr class="my-3"><p class="text-xs text-gray-500">Total de violaciones: ${violationCount.value}/${maxViolations.value}</p>`,
        confirmButtonText: 'Salir',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then(() => { goBack(); });
};

const testSecurityEvent = async () => {
    console.log('🧪 Test de violación manual');
    try {
        const response = await axios.post('/api/v1/student/log-security-event', {
            exam_attempt_id: attemptId.value || null,
            event_type: 'test_button',
            details: 'Prueba manual desde botón de prueba'
        });
        console.log('✅ Respuesta:', response.data);
        Swal.fire({ icon: 'success', title: 'Evento registrado', text: `Violación #${response.data.violation_count} de ${response.data.limit}`, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
    } catch (error) {
        console.error('❌ Error:', error.response?.data);
        Swal.fire({ icon: 'error', title: 'Error', text: error.response?.data?.message || error.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
    }
};

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

const handleSecurityKeys = (e) => {
    if (isExpelled.value || completed.value) return;
    const prohibitedKeys = ['F12', 'F5', 'F1', 'F2', 'F3', 'F4', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11'];
    if (prohibitedKeys.includes(e.key)) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && ['I', 'J', 'C', 'i', 'j', 'c'].includes(e.key)) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    if ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U' || e.key === 'r' || e.key === 'R')) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
};

const startSecurity = () => {
    securityInterval = setInterval(detectDevTools, 2000);
    document.addEventListener('visibilitychange', detectTabSwitch);
    window.addEventListener('blur', detectWindowBlur);
    blockDevToolsCompletely();
};

const stopSecurity = () => {
    if (securityInterval) clearInterval(securityInterval);
    document.removeEventListener('visibilitychange', detectTabSwitch);
    window.removeEventListener('blur', detectWindowBlur);
};

// ==================== TIMERS ====================
const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (timeLeft.value > 0 && !isExpelled.value && !completed.value) {
            timeLeft.value--;
        } else if (timeLeft.value <= 0 && !completed.value && !isExpelled.value) {
            clearInterval(timerInterval);
            finishExam();
        }
    }, 1000);
};

const startTimerSync = () => {
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    timerSyncInterval = setInterval(async () => {
        if (!attemptId.value || isExpelled.value || completed.value) return;
        if (timeLeft.value >= 0) {
            try {
                await axios.post(`/api/v1/student/modular-exam/sync-timer/${attemptId.value}`, { time_left: timeLeft.value });
            } catch (error) {
                console.error('Error sincronizando timer:', error);
            }
        }
    }, 5000);
};

// ==================== NAVEGACIÓN ====================
const nextModule = async () => {
    if (isExpelled.value || isProcessing.value) return;
    showValidation.value = true;
    if (answeredCount.value !== currentModule.value?.questions?.length) {
        Swal.fire({ icon: 'warning', title: 'Preguntas pendientes', text: `Te faltan ${currentModule.value.questions.length - answeredCount.value} pregunta(s) por responder`, confirmButtonText: 'Entiendo' });
        return;
    }
    if (isLastModule.value) {
        processingMessage.value = 'Finalizando examen';
        processingSubMessage.value = 'Calculando tu puntuación...';
    } else {
        processingMessage.value = 'Cargando siguiente módulo';
        processingSubMessage.value = 'Preparando el siguiente módulo...';
    }
    isProcessing.value = true;
    showValidation.value = false;
    try {
        const moduleAnswers = currentModule.value.questions.filter(q => answers.value[q.id]).map(q => ({ question_id: q.id, selected_option: answers.value[q.id] }));
        await axios.post(`/api/v1/student/modular-exam/save/${attemptId.value}`, { module_id: currentModule.value.id, answers: moduleAnswers });
        if (isLastModule.value) {
            await finishExam();
        } else {
            await axios.post(`/api/v1/student/modular-exam/next/${attemptId.value}`);
            currentIndex.value++;
            scrollToTop();
            setTimeout(() => { isProcessing.value = false; }, 300);
        }
    } catch (error) {
        console.error('Error en nextModule:', error);
        isProcessing.value = false;
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo avanzar al siguiente módulo', confirmButtonText: 'Intentar de nuevo' });
    }
};

const finishExam = async () => {
    if (isExpelled.value) return;
    processingMessage.value = 'Procesando resultados';
    processingSubMessage.value = 'Guardando tu progreso...';
    isProcessing.value = true;
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    try {
        const res = await axios.post(`/api/v1/student/modular-exam/finish/${attemptId.value}`);
        if (res.data.success) {
            results.value = res.data.results;
            processingMessage.value = '¡Examen completado!';
            processingSubMessage.value = 'Redirigiendo a resultados...';
            setTimeout(() => {
                completed.value = true;
                isProcessing.value = false;
                stopSecurity();
                if (timerInterval) clearInterval(timerInterval);
            }, 1000);
        }
    } catch (err) {
        console.error('Error finalizando:', err);
        isProcessing.value = false;
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo finalizar el examen', confirmButtonText: 'Intentar de nuevo' });
    }
};

const goBack = () => {
    router.push({ name: 'student.dashboard' });
};

// ==================== CARGA INICIAL ====================
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
            timeLeft.value = Math.max(0, res.data.time_left || 2400);
            totalTime.value = res.data.total_duration_seconds || 2400;
            attemptId.value = res.data.attempt_id;
            answers.value = res.data.saved_answers || {};
            playedAudios.value = res.data.played_audios || [];
            viewedMedia.value = res.data.viewed_media || [];
            startTimer();
            startTimerSync();
            startSecurity();
            scrollToTop();
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

// ==================== WATCHERS ====================
watch(currentModule, (newModule, oldModule) => {
    if (newModule && newModule.id !== oldModule?.id) {
        isAudioPlaying.value = false;
        scrollToTop();
    }
});

// ==================== LIFECYCLE ====================
onMounted(() => {
    loadExam();
    window.addEventListener('keydown', (e) => {
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            e.preventDefault();
            return false;
        }
    });
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    if (timerSyncInterval) clearInterval(timerSyncInterval);
    stopSecurity();
    if (audioPlayer.value) audioPlayer.value.pause();
});
</script>

<style scoped>
.select-none { user-select: none; -webkit-user-select: none; }
audio::-webkit-media-controls-download-button { display: none; }
.animate-pulse { animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease; }
.fade-slide-enter-from { opacity: 0; transform: translateX(30px); }
.fade-slide-leave-to { opacity: 0; transform: translateX(-30px); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.list-enter-active, .list-leave-active { transition: all 0.3s ease; }
.list-enter-from { opacity: 0; transform: translateY(20px); }
.list-leave-to { opacity: 0; transform: translateY(-20px); }
.list-move { transition: transform 0.3s ease; }
</style>