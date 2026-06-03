<template>
    <div class="max-w-2xl mx-auto p-4 md:p-6 min-h-screen bg-gray-50/50 select-none" 
         translate="no" 
         @contextmenu.prevent 
         @copy.prevent
         @cut.prevent
         @dragstart.prevent
         @keydown.prevent="handleKeyDown">
        
        <div v-if="!loading && examData" class="sticky top-4 z-20 bg-white shadow-xl rounded-3xl p-5 mb-6 border border-gray-100 flex flex-col gap-3">
            <div class="flex justify-between items-center">
                <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">
                    {{ examData.quiz_title }} — Pregunta {{ currentIndex + 1 }} de {{ totalQuestions }}
                </span>
                <div :class="timeLeft < 300 ? 'text-red-600 bg-red-50 animate-pulse' : 'text-indigo-600 bg-indigo-50'" 
                     class="px-4 py-1 rounded-xl font-mono font-black text-xl border border-current/10">
                    {{ formatTime(timeLeft) }}
                </div>
            </div>
            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-indigo-600 h-full transition-all duration-700 ease-out" 
                     :style="{ width: ((currentIndex + 1) / totalQuestions) * 100 + '%' }">
                </div>
            </div>
        </div>

        <!-- Overlay de advertencia por salir de la ventana -->
        <div v-if="showWarning" class="fixed inset-0 bg-red-600/90 backdrop-blur-md z-50 flex items-center justify-center p-6">
            <div class="bg-white rounded-3xl p-8 max-w-md text-center shadow-2xl">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-black text-gray-900 mb-2">⚠️ ADVERTENCIA</h3>
                <p class="text-gray-600 mb-4">Has intentado salir de la ventana del examen. Esto ha sido registrado. Si continúas, el examen podría ser invalidado.</p>
                <button @click="showWarning = false" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold">
                    Continuar con el examen
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
            <p class="mt-4 text-gray-400 font-bold text-xs uppercase tracking-tighter">Preparando entorno seguro...</p>
        </div>

        <div v-else-if="examData">
            <div v-for="(question, index) in examData.questions" :key="question.id">
                <Transition name="slide-fade">
                    <div v-if="index === currentIndex" class="space-y-6">
                        
                        <div class="bg-white p-6 md:p-10 rounded-[2.5rem] shadow-sm border border-gray-100 relative overflow-hidden">
                            
                            <!-- Marca de agua invisible -->
                            <div class="absolute inset-0 pointer-events-none opacity-5 flex items-center justify-center">
                                <span class="text-6xl font-black rotate-12">{{ userData?.name }} - {{ userData?.email }}</span>
                            </div>
                            
                            <div v-if="question.picture" class="mb-6 rounded-3xl overflow-hidden border-4 border-gray-50 shadow-inner">
                                <img :src="'/storage/' + question.picture" class="w-full h-auto max-h-72 object-contain bg-gray-50" alt="Recurso Visual" draggable="false">
                            </div>

                            <div v-if="question.sound" class="mb-6 p-5 bg-indigo-600 rounded-3xl flex items-center gap-4 text-white shadow-lg shadow-indigo-100">
                                <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                                <audio controls class="w-full h-8 brightness-110" :src="'/storage/' + question.sound" controlsList="nodownload"></audio>
                            </div>

                            <h2 class="text-xl md:text-2xl font-black text-gray-800 leading-tight mb-8">
                                {{ question.prompt }}
                            </h2>

                            <div class="grid grid-cols-1 gap-3">
                                <button v-for="option in question.options" :key="option.id"
                                    @click="tempSelection = option.id"
                                    :disabled="isSaving"
                                    :class="[
                                        tempSelection === option.id 
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg scale-[1.01]' 
                                        : 'bg-gray-50 text-gray-600 border-transparent hover:border-indigo-200 hover:bg-white',
                                    ]"
                                    class="w-full text-left p-5 rounded-2xl border-2 transition-all duration-200 font-bold flex justify-between items-center group"
                                >
                                    <span><b class="mr-4 opacity-30">{{ getOptionLetter(option.id) }}.</b> {{ option.option_text }}</span>
                                    <div v-if="tempSelection === option.id" class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <button v-if="tempSelection" 
                                @click="handleSaveAndNext"
                                :disabled="isSaving"
                                class="w-full h-20 rounded-3xl bg-gray-900 text-white font-black text-lg shadow-2xl hover:bg-black transition-all flex items-center justify-center gap-3 active:scale-95 disabled:opacity-50"
                            >
                                <span v-if="!isSaving">
                                    {{ isLastQuestion ? 'CONFIRMAR Y FINALIZAR EXAMEN' : 'GUARDAR Y SIGUIENTE PREGUNTA' }}
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    SINCRONIZANDO...
                                </span>
                                <svg v-if="!isSaving" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </button>
                            <p v-else class="text-center text-[10px] font-black text-gray-400 uppercase tracking-widest animate-pulse">
                                Selecciona una opción para habilitar el botón
                            </p>
                        </div>

                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps(['id']);
const router = useRouter();

// Estado del Examen
const examData = ref(null);
const loading = ref(true);
const isSaving = ref(false);
const timeLeft = ref(0);
const currentIndex = ref(0);
const tempSelection = ref(null); 
const showWarning = ref(false);
let timer = null;
let warningTimeout = null;
let devToolsInterval = null;

// Contadores de violaciones
let visibilityWarningCount = 0;
let devToolsDetectionCount = 0;

// Datos del usuario
const userData = ref(null);

const totalQuestions = computed(() => {
    return examData.value?.total_questions || examData.value?.questions?.length || 0;
});

const isLastQuestion = computed(() => {
    return examData.value && currentIndex.value >= totalQuestions.value - 1;
});

// ========== FUNCIÓN PARA REGISTRAR EVENTOS EN BACKEND ==========
const logSecurityEvent = async (eventType, details = null) => {
    try {
        const response = await axios.post('/api/v1/student/log-security-event', {
            exam_attempt_id: props.id,
            event_type: eventType,
            details: details
        });
        
        // Si el backend invalida el examen, mostrar mensaje
        if (response.data.status === 'exam_invalidated') {
            Swal.fire({
                icon: 'error',
                title: 'EXAMEN INVALIDADO',
                text: response.data.message,
                confirmButtonColor: '#ef4444',
                allowOutsideClick: false
            }).then(() => {
                processFinalize(true);
            });
        }
        
        // Mostrar advertencia si se acerca al límite
        if (response.data.warning) {
            Swal.fire({
                icon: 'warning',
                title: '⚠️ Atención',
                text: response.data.warning,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });
        }
        
        return response.data;
    } catch (error) {
        console.error('Error logging security event:', error);
    }
};

// ========== MEDIDAS DE SEGURIDAD ==========

// 1. Prevenir captura de pantalla
const preventScreenshot = async (e) => {
    if ((e.key === 'PrintScreen') || 
        (e.ctrlKey && e.key === 'p') ||
        (e.ctrlKey && e.shiftKey && e.key === 's') ||
        (e.metaKey && e.shiftKey && e.key === 's')) {
        
        e.preventDefault();
        e.stopPropagation();
        
        // Registrar en backend
        await logSecurityEvent('screenshot_attempt', `Tecla: ${e.key}`);
        
        Swal.fire({
            icon: 'warning',
            title: 'Captura de pantalla bloqueada',
            text: 'No está permitido capturar pantalla durante el examen.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return false;
    }
    return true;
};

// 2. Detectar cambio de pestaña
const handleVisibilityChange = async () => {
    if (document.hidden) {
        visibilityWarningCount++;
        
        // Registrar en backend
        const result = await logSecurityEvent('tab_switch', `Cambio de pestaña detectado (${visibilityWarningCount})`);
        
        if (result?.status === 'exam_invalidated') {
            return;
        }
        
        if (visibilityWarningCount >= 3) {
            Swal.fire({
                icon: 'error',
                title: 'EXAMEN INVALIDADO',
                text: 'Has cambiado de pestaña múltiples veces. El examen ha sido invalidado.',
                confirmButtonColor: '#ef4444',
                allowOutsideClick: false
            }).then(() => {
                processFinalize(true);
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: '⚠️ Atención',
                text: `Has cambiado de pestaña. ${3 - visibilityWarningCount} intentos restantes antes de invalidar el examen.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000
            });
        }
    }
};

// 3. Detectar salida del área
const handleMouseLeave = async (e) => {
    if (e.clientY <= 0 || e.clientX <= 0 || e.clientX >= window.innerWidth || e.clientY >= window.innerHeight) {
        if (!showWarning.value) {
            showWarning.value = true;
            
            // Registrar en backend
            await logSecurityEvent('mouse_leave', 'Intento de salir del área del examen');
            
            warningTimeout = setTimeout(() => {
                showWarning.value = false;
            }, 5000);
        }
    }
};

// 4. Teclas prohibidas
const forbiddenKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'F5', 'F12', 'F1', 'F2', 'F3', 'F4', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'Tab', 'Alt', 'Control', 'Meta', 'Escape'];

const handleKeyDown = async (e) => {
    // Prevenir teclas de navegación
    if (forbiddenKeys.includes(e.key)) {
        e.preventDefault();
        e.stopPropagation();
        
        if (e.key === 'F12') {
            await logSecurityEvent('devtools_opened', 'Intento de abrir DevTools con F12');
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
    
    // Prevenir Ctrl+Shift+I
    if ((e.ctrlKey || e.metaKey) && (e.shiftKey) && (e.key === 'i' || e.key === 'I')) {
        e.preventDefault();
        await logSecurityEvent('devtools_opened', 'Intento de abrir DevTools con Ctrl+Shift+I');
        return;
    }
    
    // Prevenir Ctrl+U
    if ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U')) {
        e.preventDefault();
        await logSecurityEvent('view_source_attempt', 'Intento de ver código fuente');
        return;
    }
};

// 5. Detectar DevTools
const detectDevTools = async () => {
    const threshold = 160;
    const widthDiff = window.outerWidth - window.innerWidth;
    const heightDiff = window.outerHeight - window.innerHeight;
    
    if (widthDiff > threshold || heightDiff > threshold) {
        devToolsDetectionCount++;
        
        if (devToolsDetectionCount === 1) {
            await logSecurityEvent('devtools_opened', `DevTools detectado (${devToolsDetectionCount})`);
        }
        
        if (devToolsDetectionCount >= 3) {
            Swal.fire({
                icon: 'error',
                title: 'Herramientas de desarrollo detectadas',
                text: 'El uso de herramientas de desarrollo durante el examen no está permitido.',
                confirmButtonColor: '#ef4444'
            });
        }
    }
};

// 6. Bloquear copia
const blockCopy = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    await logSecurityEvent('copy_attempt', 'Intento de copiar texto');
    return false;
};

// 7. Prevenir arrastrar
const preventDrag = async (e) => {
    e.preventDefault();
    await logSecurityEvent('drag_attempt', 'Intento de arrastrar elemento');
    return false;
};

// ========== LÓGICA DEL EXAMEN ==========

const loadExam = async () => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/v1/student/load-exam/${props.id}`);
        
        const storedUser = localStorage.getItem('user_data');
        if (storedUser) {
            userData.value = JSON.parse(storedUser);
        }
        
        examData.value = response.data;
        timeLeft.value = response.data.seconds_left;
        currentIndex.value = parseInt(response.data.current_index) || 0;
        
        if (timeLeft.value <= 0) {
            Swal.fire('Tiempo Agotado', 'El tiempo para este examen ha finalizado.', 'warning')
                .then(() => processFinalize(true));
            return;
        }

        syncPreviousSelection();
        startTimer();
        
        // Iniciar detección de DevTools
        devToolsInterval = setInterval(detectDevTools, 3000);
        
    } catch (error) {
        console.error("Error al cargar:", error);
        Swal.fire('Error', 'No se pudo acceder al examen.', 'error')
            .then(() => router.push({ name: 'student.dashboard' }));
    } finally {
        loading.value = false;
    }
};

const syncPreviousSelection = () => {
    if (!examData.value) return;
    const currentQuestion = examData.value.questions[currentIndex.value];
    tempSelection.value = currentQuestion?.selected_option_id || null;
};

const handleSaveAndNext = async () => {
    if (isSaving.value || !tempSelection.value) return;

    const currentQuestion = examData.value.questions[currentIndex.value];
    isSaving.value = true;
    
    try {
        const isActuallyLast = currentIndex.value >= totalQuestions.value - 1;
        const nextIndexForServer = isActuallyLast ? currentIndex.value : currentIndex.value + 1;
        
        await axios.post(`/api/v1/student/save-answer/${props.id}`, {
            question_id: currentQuestion.id,
            option_id: tempSelection.value,
            current_index: nextIndexForServer 
        });

        currentQuestion.selected_option_id = tempSelection.value;

        if (isActuallyLast) {
            processFinalize(false);
        } else {
            currentIndex.value++;
            tempSelection.value = null;
            syncPreviousSelection();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    } catch (error) {
        if (error.response && error.response.status === 403) {
            Swal.fire('Tiempo Agotado', 'El tiempo ha terminado. Se guardarán las respuestas enviadas hasta ahora.', 'warning')
                .then(() => processFinalize(true));
            return;
        }
        console.error("Error al guardar:", error);
        Swal.fire('Error de Conexión', 'Tu respuesta no se guardó. Intenta de nuevo.', 'error');
    } finally {
        isSaving.value = false;
    }
};

const processFinalize = async (isForced = false) => {
    try {
        clearInterval(timer);
        if (devToolsInterval) clearInterval(devToolsInterval);
        
        Swal.fire({ 
            title: 'Finalizando examen...', 
            text: isForced ? 'El examen ha sido finalizado por medidas de seguridad.' : 'Calculando tus resultados y cerrando sesión de examen.',
            allowOutsideClick: false, 
            didOpen: () => Swal.showLoading() 
        });
        
        const resp = await axios.post(`/api/v1/student/finish-exam/${props.id}`);
        
        await Swal.fire({
            title: '¡Examen Completado!',
            html: `
                <div class="py-4 text-center">
                    <div class="text-6xl font-black text-indigo-600">${resp.data.score}%</div>
                    <p class="mt-2 text-sm text-gray-500 font-bold uppercase tracking-widest">Puntaje Final</p>
                    <div class="mt-4 bg-gray-50 p-3 rounded-2xl border border-gray-100">
                        <span class="text-gray-600 font-medium">Respuestas correctas: </span>
                        <span class="text-indigo-600 font-black">${resp.data.correct_count} de ${resp.data.total_questions}</span>
                    </div>
                </div>`,
            icon: 'success',
            confirmButtonText: 'Volver al Panel Principal',
            confirmButtonColor: '#4f46e5',
            allowOutsideClick: false,
            customClass: { popup: 'rounded-[2.5rem]' }
        });
        
        router.push({ name: 'student.dashboard' }); 

    } catch (e) {
        console.error("Error al finalizar:", e);
        Swal.fire('Atención', 'El examen se cerró correctamente. Volviendo al panel.', 'info')
            .then(() => router.push({ name: 'student.dashboard' }));
    }
};

const startTimer = () => {
    if (timer) clearInterval(timer);
    timer = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            clearInterval(timer);
            processFinalize(true);
        }
    }, 1000);
};

const formatTime = (seconds) => {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    
    if (h > 0) {
        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    return `${m}:${s.toString().padStart(2, '0')}`;
};

const getOptionLetter = (id) => {
    const letters = { 1: 'A', 2: 'B', 3: 'C', 4: 'D' };
    return letters[id] || id;
};

// ========== EVENT LISTENERS ==========
onMounted(() => {
    loadExam();
    
    document.addEventListener('keydown', preventScreenshot);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('mouseleave', handleMouseLeave);
    document.addEventListener('copy', blockCopy);
    document.addEventListener('cut', blockCopy);
    document.addEventListener('dragstart', preventDrag);
});

onBeforeUnmount(() => {
    clearInterval(timer);
    if (devToolsInterval) clearInterval(devToolsInterval);
    if (warningTimeout) clearTimeout(warningTimeout);
    
    document.removeEventListener('keydown', preventScreenshot);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('mouseleave', handleMouseLeave);
    document.removeEventListener('copy', blockCopy);
    document.removeEventListener('cut', blockCopy);
    document.removeEventListener('dragstart', preventDrag);
});

watch(() => props.id, (newId) => { if (newId) loadExam(); });
</script>
<style scoped>
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

.slide-fade-enter-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-fade-leave-active { transition: all 0.3s cubic-bezier(0.7, 0, 0.84, 0); position: absolute; width: 100%; }
.slide-fade-enter-from { transform: translateX(50px); opacity: 0; }
.slide-fade-leave-to { transform: translateX(-50px); opacity: 0; }

.max-w-2xl { 
    overflow-x: hidden; 
    position: relative;
}

/* Prevenir selección de audio */
audio {
    pointer-events: auto;
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
</style>