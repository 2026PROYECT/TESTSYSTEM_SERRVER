<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4 select-none" 
         @contextmenu.prevent 
         @copy.prevent
         @cut.prevent
         @dragstart.prevent
         @keydown.prevent="handleSecurityKeyDown">
        
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
                <p v-if="errorDetails" class="text-sm text-gray-500 mt-2">{{ errorDetails }}</p>
                <button @click="goBack" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">
                    Volver al Dashboard
                </button>
            </div>

            <!-- Examen invalidado por seguridad -->
            <div v-else-if="isInvalidated" class="bg-red-50 p-8 rounded-2xl shadow text-center">
                <div class="text-6xl mb-4">🚫</div>
                <h2 class="text-2xl font-bold text-red-600 mb-2">Examen Invalidado</h2>
                <p class="text-gray-700 mb-4">{{ invalidationReason || 'Se han detectado múltiples violaciones de seguridad durante el examen.' }}</p>
                <button @click="goBack" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold">
                    Volver al Dashboard
                </button>
            </div>

            <!-- Examen completado -->
            <div v-else-if="completed" class="bg-white p-8 rounded-2xl shadow text-center">
                <div :class="getIconClass()" class="text-6xl mb-4">
                    {{ getIcon() }}
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    {{ getTitle() }}
                </h2>
                <div :class="getCardClass()" class="p-4 rounded-lg mb-4">
                    <p class="font-bold text-xl">
                        Tu puntuación: {{ results?.total_percentage || 0 }}%
                    </p>
                    <p :class="getResultClass()" class="text-lg mt-2">
                        {{ getResultText() }}
                    </p>
                    <p v-if="!isApproved" class="text-sm text-gray-600 mt-2">
                        El mínimo para aprobar es 60%
                    </p>
                </div>
                <button @click="goBack" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold">
                    Volver al Dashboard
                </button>
            </div>

            <!-- Contenido del examen -->
            <div v-else class="bg-white rounded-2xl shadow overflow-hidden">
                
                <!-- Cabecera con timer y advertencia -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold">{{ examData?.title || 'Examen Modular' }}</h1>
                            <p class="text-indigo-200 text-sm mt-1">
                                Módulo {{ currentModuleIndex + 1 }} de {{ modules.length }}
                            </p>
                        </div>
                        <div :class="timeLeft < 300 ? 'bg-red-500 animate-pulse' : 'bg-indigo-500'" 
                             class="px-4 py-2 rounded-xl font-mono font-bold text-lg">
                            ⏱️ {{ formatTime(timeLeft) }}
                        </div>
                    </div>
                    
                    <!-- Barra de tiempo -->
                    <div class="mt-3 w-full bg-indigo-700 rounded-full h-2">
                        <div class="bg-white rounded-full h-2 transition-all duration-1000" 
                             :style="{ width: timerPercentage + '%' }">
                        </div>
                    </div>
                    
                    <!-- Advertencia de tiempo bajo -->
                    <p v-if="timeLeft < 300 && timeLeft > 0" class="text-red-200 text-xs mt-2 animate-pulse">
                        ⚠️ ¡Tiempo bajo! Asegúrate de responder todas las preguntas.
                    </p>
                </div>

                <!-- Módulo actual -->
                <div v-if="currentModule" class="p-6">
                    
                    <!-- Barra de progreso del módulo -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Progreso del módulo:</span>
                            <span class="text-sm font-bold" :class="getProgressColorClass()">
                                {{ getAnsweredCount() }}/{{ currentModule?.questions?.length || 0 }} preguntas
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="rounded-full h-2 transition-all duration-300" 
                                 :class="getProgressBarClass()"
                                 :style="{ width: `${getAnsweredPercentage()}%` }">
                            </div>
                        </div>
                    </div>

                    <!-- Título del módulo -->
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-2">{{ currentModule.title }}</h2>
                        <p class="text-gray-600" v-if="currentModule.content">{{ currentModule.content }}</p>
                        
                        <!-- Audio -->
                        <div v-if="currentModule.audio" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <audio controls class="w-full" controlsList="nodownload" @error="handleAudioError">
                                <source :src="getMediaUrl(currentModule.audio, 'audio')" type="audio/mpeg">
                                <source :src="getMediaUrl(currentModule.audio, 'audio')" type="audio/wav">
                                Tu navegador no soporta el elemento de audio.
                            </audio>
                            <p v-if="audioError" class="text-red-500 text-xs mt-2">
                                ⚠️ Error al cargar el audio. Por favor, contacta al administrador.
                            </p>
                        </div>
                        
                        <!-- Imagen -->
                        <div v-if="currentModule.picture" class="mt-4">
                            <img :src="getMediaUrl(currentModule.picture, 'image')" 
                                 :alt="currentModule.title" 
                                 class="rounded-lg max-w-full"
                                 @error="handleImageError">
                            <p v-if="imageError" class="text-red-500 text-xs mt-2">
                                ⚠️ Error al cargar la imagen.
                            </p>
                        </div>
                    </div>

                    <!-- Preguntas del módulo -->
                    <div class="space-y-6">
                        <div v-for="(question, qIndex) in currentModule.questions" 
                             :key="question.id" 
                             :ref="el => { if (el && !answers[question.id] && showValidation) setQuestionRef(el, qIndex) }"
                             :class="[
                                 'border rounded-lg p-4 transition-all duration-300',
                                 getQuestionClass(question)
                             ]">
                            
                            <div class="flex justify-between items-start mb-3">
                                <p class="font-bold">
                                    {{ qIndex + 1 }}. {{ question.text }}
                                    <span class="text-red-500 text-sm ml-1">*</span>
                                </p>
                                <div v-if="!answers[question.id] && showValidation" class="text-red-500 text-xs flex items-center gap-1">
                                    <span>⚠️</span> Requerida
                                </div>
                                <div v-else-if="answers[question.id]" class="text-green-500 text-xs flex items-center gap-1">
                                    <span>✓</span> Respondida
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label v-for="(option, letter) in question.options" 
                                       :key="letter" 
                                       :class="[
                                           'flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-all',
                                           getOptionClass(question, letter)
                                       ]">
                                    <input type="radio" 
                                           :name="`q_${question.id}`"
                                           :value="letter"
                                           v-model="answers[question.id]"
                                           @change="saveAnswer(question.id, letter)"
                                           class="w-4 h-4 text-indigo-600">
                                    <span class="text-gray-700">
                                        <strong>{{ letter }}.</strong> {{ option }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                   <!-- Botones de navegación -->
<div class="flex justify-end mt-8 pt-4 border-t border-gray-200">
    <!-- ❌ ELIMINAR ESTE BLOQUE COMPLETO
    <button @click="prevModule" 
            v-if="currentModuleIndex > 0"
            class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold transition">
        ← Anterior
    </button>
    -->
    
    <button @click="nextModule" 
            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold transition ml-auto">
        {{ currentModuleIndex === modules.length - 1 ? 'Finalizar Examen' : 'Siguiente Módulo →' }}
    </button>
</div>
                </div>
            </div>
        </div>

        <!-- Modal de advertencia por salida de ventana -->
        <div v-if="showSecurityWarning" class="fixed inset-0 bg-red-600/90 backdrop-blur-md z-50 flex items-center justify-center p-6">
            <div class="bg-white rounded-3xl p-8 max-w-md text-center shadow-2xl">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-black text-gray-900 mb-2">⚠️ ADVERTENCIA DE SEGURIDAD</h3>
                <p class="text-gray-600 mb-4">{{ securityWarningMessage }}</p>
                <p class="text-sm text-red-600 mb-4 font-bold">Violación {{ violationCount }} de {{ maxViolations }}</p>
                <button @click="closeSecurityWarning" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold w-full">
                    Continuar con el examen
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

// ==================== ESTADO ====================
const loading = ref(true);
const error = ref(null);
const errorDetails = ref(null);
const completed = ref(false);
const isInvalidated = ref(false);
const invalidationReason = ref(null);
const examData = ref(null);
const modules = ref([]);
const currentModuleIndex = ref(0);
const timeLeft = ref(0);
const totalTimeSeconds = ref(2400); // 40 minutos
const answers = ref({});
const results = ref(null);
const timerInterval = ref(null);
const attemptId = ref(null);
const showValidation = ref(false);
const questionRefs = ref([]);
const audioError = ref(false);
const imageError = ref(false);

// ==================== SEGURIDAD ====================
const showSecurityWarning = ref(false);
const securityWarningMessage = ref('');
const violationCount = ref(0);
const maxViolations = ref(3);
let visibilityWarningCount = 0;
let devToolsInterval = null;

// ==================== COMPUTED ====================
const currentModule = computed(() => {
    return modules.value[currentModuleIndex.value] || null;
});

const isApproved = computed(() => {
    return (results.value?.total_percentage || 0) >= 60;
});

const timerPercentage = computed(() => {
    if (totalTimeSeconds.value <= 0) return 0;
    return (timeLeft.value / totalTimeSeconds.value) * 100;
});

// ==================== FUNCIONES DE SEGURIDAD ====================

// Registrar evento de seguridad en backend
const logSecurityEvent = async (eventType, details = null) => {
    if (!attemptId.value) return;
    
    try {
        const response = await axios.post('/api/v1/student/log-security-event', {
            exam_attempt_id: null,  // 🔥 Enviar null
            event_type: eventType,
            details: details
        });
        
        if (response.data.status === 'exam_invalidated') {
            isInvalidated.value = true;
            invalidationReason.value = response.data.message;
            if (timerInterval.value) clearInterval(timerInterval.value);
            if (devToolsInterval) clearInterval(devToolsInterval);
            return;
        }
        
        violationCount.value = response.data.violation_count || 0;
        maxViolations.value = response.data.limit || 3;
        
        if (response.data.warning) {
            securityWarningMessage.value = response.data.warning;
            showSecurityWarning.value = true;
        }
        
        return response.data;
    } catch (error) {
        console.error('Error logging security event:', error);
    }
};

// Manejar teclas prohibidas
const forbiddenKeys = ['F12', 'F5', 'F1', 'F2', 'F3', 'F4', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'Tab', 'Alt', 'Control', 'Meta', 'Escape'];

const handleSecurityKeyDown = async (e) => {
    // Prevenir teclas prohibidas
    if (forbiddenKeys.includes(e.key)) {
        e.preventDefault();
        e.stopPropagation();
        
        if (e.key === 'F12') {
            await logSecurityEvent('devtools_opened', 'Intento de abrir DevTools');
            Swal.fire({
                icon: 'warning',
                title: 'Herramientas de desarrollo bloqueadas',
                text: 'No está permitido abrir herramientas de desarrollo durante el examen.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
        return;
    }
    
    // Prevenir Ctrl+R, Ctrl+Shift+R
    if ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R')) {
        e.preventDefault();
        await logSecurityEvent('reload_attempt', 'Intento de recargar página');
        Swal.fire({
            icon: 'warning',
            title: 'Recarga bloqueada',
            text: 'No está permitido recargar la página durante el examen.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }
    
    // Prevenir Ctrl+W
    if ((e.ctrlKey || e.metaKey) && (e.key === 'w' || e.key === 'W')) {
        e.preventDefault();
        await logSecurityEvent('tab_close_attempt', 'Intento de cerrar pestaña');
        return;
    }
    
    // Prevenir Ctrl+Shift+I (DevTools)
    if ((e.ctrlKey || e.metaKey) && (e.shiftKey) && (e.key === 'i' || e.key === 'I')) {
        e.preventDefault();
        await logSecurityEvent('devtools_opened', 'Intento de abrir DevTools con Ctrl+Shift+I');
        return;
    }
    
    // Prevenir Ctrl+U (ver fuente)
    if ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U')) {
        e.preventDefault();
        await logSecurityEvent('view_source_attempt', 'Intento de ver código fuente');
        return;
    }
    
    // Prevenir PrintScreen
    if (e.key === 'PrintScreen') {
        e.preventDefault();
        await logSecurityEvent('screenshot_attempt', 'Intento de captura de pantalla');
        Swal.fire({
            icon: 'warning',
            title: 'Captura de pantalla bloqueada',
            text: 'No está permitido capturar pantalla durante el examen.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }
};

// Detectar cambio de pestaña
const handleVisibilityChange = async () => {
    if (document.hidden) {
        visibilityWarningCount++;
        await logSecurityEvent('tab_switch', `Cambio de pestaña detectado (${visibilityWarningCount})`);
        
        if (!showSecurityWarning.value) {
            securityWarningMessage.value = `Has cambiado de pestaña. Te quedan ${maxViolations.value - violationCount} intentos antes de invalidar el examen.`;
            showSecurityWarning.value = true;
        }
    }
};

// Detectar salida del área del examen
const handleMouseLeave = async (e) => {
    if (e.clientY <= 0 || e.clientX <= 0 || e.clientX >= window.innerWidth || e.clientY >= window.innerHeight) {
        await logSecurityEvent('mouse_leave', 'Intento de salir del área del examen');
    }
};

// Detectar DevTools
const detectDevTools = async () => {
    const threshold = 160;
    const widthDiff = window.outerWidth - window.innerWidth;
    const heightDiff = window.outerHeight - window.innerHeight;
    
    if (widthDiff > threshold || heightDiff > threshold) {
        await logSecurityEvent('devtools_opened', 'Herramientas de desarrollo detectadas');
    }
};

// Cerrar advertencia de seguridad
const closeSecurityWarning = () => {
    showSecurityWarning.value = false;
};

// ==================== FUNCIONES DE MEDIOS ====================

const getMediaUrl = (path, type = 'audio') => {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    if (path.startsWith('/storage/')) return path;
    
    let cleanPath = path.replace(/\\/g, '/');
    cleanPath = cleanPath.replace(/^storage\/app\/public\//, '');
    cleanPath = cleanPath.replace(/^public\//, '');
    
    return '/storage/' + cleanPath;
};

const handleAudioError = async (event) => {
    console.error('Error cargando audio:', event.target.src);
    audioError.value = true;
    await logSecurityEvent('audio_error', `Error cargando audio: ${event.target.src}`);
};

const handleImageError = (event) => {
    console.error('Error cargando imagen:', event.target.src);
    imageError.value = true;
    event.target.src = '/placeholder-image.jpg';
};

// ==================== FUNCIONES DE VALIDACIÓN ====================

const getAnsweredCount = () => {
    if (!currentModule.value || !currentModule.value.questions) return 0;
    let count = 0;
    for (const question of currentModule.value.questions) {
        if (answers.value[question.id]) count++;
    }
    return count;
};

const getAnsweredPercentage = () => {
    const total = currentModule.value?.questions?.length || 1;
    const answered = getAnsweredCount();
    return (answered / total) * 100;
};

const isCurrentModuleComplete = () => {
    if (!currentModule.value || !currentModule.value.questions) return false;
    for (const question of currentModule.value.questions) {
        if (!answers.value[question.id]) return false;
    }
    return true;
};

const getUnansweredQuestions = () => {
    if (!currentModule.value || !currentModule.value.questions) return [];
    const unanswered = [];
    for (const question of currentModule.value.questions) {
        if (!answers.value[question.id]) unanswered.push(question);
    }
    return unanswered;
};

const getQuestionClass = (question) => {
    const classes = [];
    if (!answers.value[question.id] && showValidation.value) {
        classes.push('border-red-400 bg-red-50');
    } else if (answers.value[question.id]) {
        classes.push('border-green-200 bg-green-50/30');
    } else {
        classes.push('border-gray-200');
    }
    return classes;
};

const getOptionClass = (question, letter) => {
    const classes = ['hover:bg-gray-50'];
    if (answers.value[question.id] === letter && showValidation.value) {
        classes.push('bg-green-100 border border-green-300');
    }
    return classes;
};

const getProgressBarClass = () => {
    const percentage = getAnsweredPercentage();
    if (percentage === 100) return 'bg-green-500';
    if (percentage >= 60) return 'bg-yellow-500';
    return 'bg-indigo-600';
};

const getProgressColorClass = () => {
    const percentage = getAnsweredPercentage();
    if (percentage === 100) return 'text-green-600';
    if (percentage >= 60) return 'text-yellow-600';
    return 'text-indigo-600';
};

const setQuestionRef = (el, index) => {
    if (el && !questionRefs.value[index]) {
        questionRefs.value[index] = el;
    }
};

const scrollToFirstUnanswered = () => {
    if (!currentModule.value) return;
    
    for (let i = 0; i < currentModule.value.questions.length; i++) {
        const question = currentModule.value.questions[i];
        if (!answers.value[question.id]) {
            const element = questionRefs.value[i];
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.classList.add('ring-2', 'ring-red-400', 'shadow-lg');
                setTimeout(() => {
                    element.classList.remove('ring-2', 'ring-red-400', 'shadow-lg');
                }, 2000);
                break;
            }
        }
    }
};

const showUnansweredAlert = () => {
    const unanswered = getUnansweredQuestions();
    const totalQuestions = currentModule.value?.questions?.length || 0;
    
    let message = '';
    if (unanswered.length === totalQuestions) {
        message = 'No has respondido ninguna pregunta. ¡Debes responder al menos una pregunta para continuar!';
    } else {
        const questionNumbers = unanswered.map((_, idx) => {
            const originalIndex = currentModule.value.questions.findIndex(q => q.id === unanswered[idx].id);
            return originalIndex + 1;
        }).join(', ');
        
        message = `
            <div class="text-left">
                <p class="font-bold mb-2">⚠️ Te faltan ${unanswered.length} pregunta(s) por responder:</p>
                <p class="text-red-600 mb-3">Preguntas: ${questionNumbers}</p>
                <p class="text-sm text-gray-600">Las preguntas marcadas en <span class="text-red-600">rojo</span> son obligatorias.</p>
            </div>
        `;
    }
    
    Swal.fire({
        icon: 'warning',
        title: 'Preguntas sin contestar',
        html: message,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Ir a las preguntas faltantes',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            scrollToFirstUnanswered();
        }
    });
};

// ==================== FUNCIONES PRINCIPALES ====================

const assignmentId = route.params.assignmentId;

const formatTime = (seconds) => {
    if (!seconds || seconds <= 0) return '00:00';
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const saveAnswer = async (questionId, selectedOption) => {
    if (!attemptId.value) return;
    
    try {
        await axios.post(`/api/v1/student/modular-exam/save/${attemptId.value}`, {
            module_id: currentModule.value.id,
            answers: [{
                question_id: questionId,
                selected_option: selectedOption
            }]
        });
        
        if (showValidation.value && isCurrentModuleComplete()) {
            showValidation.value = false;
        }
        
        audioError.value = false;
        imageError.value = false;
        
    } catch (error) {
        console.error('Error guardando respuesta:', error);
        if (error.response?.status === 403) {
            error.value = error.response?.data?.error || 'Tiempo de examen agotado';
            if (timerInterval.value) clearInterval(timerInterval.value);
        }
    }
};

const loadExam = async () => {
    if (!assignmentId) {
        error.value = 'No se encontró el ID del examen';
        loading.value = false;
        return;
    }
    
    try {
        const response = await axios.get(`/api/v1/student/modular-exam/load/${assignmentId}`);
        
        if (response.data.success) {
            examData.value = response.data.exam;
            modules.value = response.data.modules || [];
            currentModuleIndex.value = response.data.current_module || 0;
            timeLeft.value = response.data.time_left || 0;
            totalTimeSeconds.value = response.data.total_time_seconds || 2400;
            attemptId.value = response.data.attempt_id;
            
            if (response.data.saved_answers) {
                answers.value = response.data.saved_answers;
            }
            
            startTimer();
            startSecurityMonitoring();
        } else {
            error.value = response.data.error || 'Error al cargar el examen';
        }
    } catch (err) {
        console.error('Error:', err);
        error.value = err.response?.data?.error || 'No se pudo cargar el examen';
        errorDetails.value = err.response?.data?.message;
    } finally {
        loading.value = false;
    }
};

const startTimer = () => {
    if (timerInterval.value) clearInterval(timerInterval.value);
    
    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else if (timeLeft.value === 0) {
            clearInterval(timerInterval.value);
            finishExam();
        }
    }, 1000);
};

const startSecurityMonitoring = () => {
    devToolsInterval = setInterval(detectDevTools, 3000);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('mouseleave', handleMouseLeave);
};

const saveCurrentModuleAnswers = async () => {
    if (!currentModule.value || !attemptId.value) return;
    
    const moduleAnswers = [];
    for (const question of currentModule.value.questions) {
        if (answers.value[question.id]) {
            moduleAnswers.push({
                question_id: question.id,
                selected_option: answers.value[question.id]
            });
        }
    }
    
    if (moduleAnswers.length > 0) {
        await axios.post(`/api/v1/student/modular-exam/save/${attemptId.value}`, {
            module_id: currentModule.value.id,
            answers: moduleAnswers
        });
    }
};

const nextModule = async () => {
    showValidation.value = true;
    
    if (!isCurrentModuleComplete()) {
        showUnansweredAlert();
        return;
    }
    
    showValidation.value = false;
    questionRefs.value = [];
    await saveCurrentModuleAnswers();
    
    const isLast = currentModuleIndex.value === modules.value.length - 1;
    
    if (isLast) {
        await finishExam();
    } else {
        try {
            await axios.post(`/api/v1/student/modular-exam/next/${attemptId.value}`);
            currentModuleIndex.value++;
            audioError.value = false;
            imageError.value = false;
        } catch (error) {
            console.error('Error avanzando módulo:', error);
            await Swal.fire('Error', 'No se pudo avanzar al siguiente módulo', 'error');
        }
    }
};


const finishExam = async () => {
    showValidation.value = true;
    
    if (!isCurrentModuleComplete()) {
        showUnansweredAlert();
        return;
    }
    
    try {
        await saveCurrentModuleAnswers();
        
        const response = await axios.post(`/api/v1/student/modular-exam/finish/${attemptId.value}`);
        
        if (response.data.success) {
            results.value = response.data.results;
            completed.value = true;
            if (timerInterval.value) clearInterval(timerInterval.value);
            if (devToolsInterval) clearInterval(devToolsInterval);
            
            const percentage = results.value?.total_percentage || 0;
            const passed = percentage >= 60;
            
            await Swal.fire({
                icon: passed ? 'success' : 'info',
                title: passed ? '¡Examen Aprobado!' : 'Examen Completado',
                html: `
                    <div class="text-center">
                        <p class="text-lg font-bold mb-2">Tu puntuación: ${percentage}%</p>
                        <p class="text-sm text-gray-600 mt-3">${passed ? '🎉 ¡Felicidades! Has aprobado el examen.' : '📚 No alcanzaste el mínimo requerido del 60%. ¡Sigue practicando!'}</p>
                    </div>
                `,
                confirmButtonColor: passed ? '#10b981' : '#3b82f6',
                confirmButtonText: 'Ver resultados'
            });
        }
    } catch (error) {
        console.error('Error finalizando examen:', error);
        await Swal.fire('Error', 'No se pudo finalizar el examen', 'error');
    }
};

const goBack = () => {
    router.push({ name: 'student.dashboard' });
};

// Funciones para resultados
const getIcon = () => isApproved.value ? '🏆' : '📚';
const getIconClass = () => isApproved.value ? 'text-green-500' : 'text-yellow-500';
const getTitle = () => isApproved.value ? '¡Examen Aprobado!' : 'Examen Completado';
const getCardClass = () => isApproved.value ? 'bg-green-50' : 'bg-yellow-50';
const getResultText = () => isApproved.value ? '✅ APROBADO' : '❌ REPROBADO';
const getResultClass = () => isApproved.value ? 'text-green-600 font-bold text-xl' : 'text-red-600 font-bold text-xl';

// ==================== LIFECYCLE ====================
onMounted(() => {
    loadExam();
});

onUnmounted(() => {
    if (timerInterval.value) clearInterval(timerInterval.value);
    if (devToolsInterval) clearInterval(devToolsInterval);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('mouseleave', handleMouseLeave);
});
</script>

<style scoped>
/* Animaciones */
.ring-2 {
    transition: all 0.3s ease;
}

.bg-green-100 {
    background-color: rgb(220 252 231);
}

.border-green-300 {
    border-color: rgb(134 239 172);
}

html {
    scroll-behavior: smooth;
}

.select-none {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

/* Prevenir selección en móviles */
* {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
}

/* Ocultar controles de descarga en audio */
audio::-internal-media-controls-download-button {
    display: none;
}

audio::-webkit-media-controls-enclosure {
    overflow: hidden;
}

audio::-webkit-media-controls-panel {
    width: calc(100% + 30px);
}

/* Animación de pulso para tiempo bajo */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.animate-pulse {
    animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>