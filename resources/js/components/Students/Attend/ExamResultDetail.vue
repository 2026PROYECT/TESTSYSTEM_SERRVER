<template>
    <div class="container mx-auto p-4 max-w-2xl min-h-screen bg-gray-50/30 select-none" 
         @contextmenu.prevent
         @copy.prevent
         @cut.prevent
         @dragstart.prevent
         @keydown.prevent="handleKeyDown">
        
        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-500 font-medium italic">Cargando resultados...</p>
        </div>

        <div v-else class="animate-fade-in">
            <!-- Marca de agua con datos del estudiante -->
            <div class="fixed inset-0 pointer-events-none flex items-center justify-center z-50 opacity-5">
                <div class="text-8xl font-black rotate-12 text-gray-900 whitespace-nowrap">
                    {{ userData?.name }} - {{ userData?.email }}
                </div>
            </div>

            <!-- Resumen de resultados -->
            <div class="bg-white shadow-xl rounded-3xl p-6 mb-6 flex justify-between items-center border border-gray-100 relative">
                <div>
                    <h1 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Puntaje Final</h1>
                    <span class="text-4xl font-black" :class="results.score >= 60 ? 'text-green-600' : 'text-red-600'">
                        {{ results.score }}%
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Estado</p>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase"
                          :class="results.score >= 60 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                        {{ results.score >= 60 ? 'Aprobado' : 'Reprobado' }}
                    </span>
                </div>
            </div>

            <!-- Estadísticas de errores -->
            <div class="bg-white p-5 rounded-[2rem] shadow-sm border border-gray-100 mb-6">
                <div class="flex justify-between items-center mb-4 px-2">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        📋 Resumen de Errores
                    </p>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                          :class="wrongAnswers.length > 0 ? 'text-red-600 bg-red-50' : 'text-green-600 bg-green-50'">
                        {{ wrongAnswers.length }} pregunta(s) incorrecta(s)
                    </span>
                </div>
                
                <!-- Navegación de errores -->
                <div v-if="wrongAnswers.length > 0" class="mb-4">
                    <div class="flex flex-wrap gap-2 justify-center">
                        <button 
                            v-for="(wrong, idx) in wrongAnswers" 
                            :key="'error-nav-' + idx"
                            @click="currentErrorIndex = idx"
                            :class="[
                                currentErrorIndex === idx ? 'ring-2 ring-black ring-offset-2 scale-110 z-10 bg-red-600' : 'bg-red-400',
                                'w-8 h-8 rounded-full text-white font-bold transition-all shadow-sm flex items-center justify-center text-xs'
                            ]"
                        >
                            {{ idx + 1 }}
                        </button>
                    </div>
                </div>
                
                <div v-else class="text-center py-8">
                    <div class="text-6xl mb-2">🎉</div>
                    <p class="text-green-600 font-bold">¡Excelente! No tuviste errores</p>
                    <p class="text-gray-400 text-sm mt-1">Todas las respuestas fueron correctas</p>
                </div>
            </div>

            <!-- Detalle del error actual -->
            <Transition name="slide-up" mode="out-in">
                <div v-if="currentError" class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center gap-2 mb-6">
                            <span class="bg-red-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase">
                                Error {{ currentErrorIndex + 1 }} de {{ wrongAnswers.length }}
                            </span>
                        </div>

                        <div class="space-y-6">
                            <!-- Tu respuesta (incorrecta) -->
                            <div class="space-y-2">
                                <span class="text-[9px] font-black text-red-600 uppercase tracking-widest ml-1 flex items-center gap-1">
                                    ❌ Tu Respuesta
                                </span>
                                <div class="p-4 rounded-2xl border-2 border-red-400 bg-red-50 font-bold text-sm text-red-800">
                                    <span class="mr-2">({{ getLetter(currentError.selected_option_id) }})</span>
                                    {{ currentError.userAnswer }}
                                </div>
                            </div>

                            <!-- Respuesta correcta -->
                            <div class="space-y-2">
                                <span class="text-[9px] font-black text-green-600 uppercase tracking-widest ml-1 flex items-center gap-1">
                                    ✅ Respuesta Correcta
                                </span>
                                <div class="p-4 rounded-2xl border-2 border-dashed border-green-200 bg-green-50/30 font-bold text-sm text-green-700">
                                    <span class="mr-2">({{ getLetter(currentError.correct_option) }})</span>
                                    {{ currentError.correctAnswer }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navegación entre errores -->
                    <div class="flex border-t border-gray-100 h-16">
                        <button @click="prevError" :disabled="currentErrorIndex === 0"
                                class="flex-1 font-black text-[10px] uppercase tracking-widest disabled:opacity-20 hover:bg-gray-50 transition-colors">
                            ← Error Anterior
                        </button>
                        <div class="w-px bg-gray-100"></div>
                        <button @click="nextError" :disabled="currentErrorIndex === wrongAnswers.length - 1"
                                class="flex-1 font-black text-[10px] uppercase tracking-widest disabled:opacity-20 hover:bg-gray-50 transition-colors text-indigo-600">
                            Siguiente Error →
                        </button>
                    </div>
                </div>
            </Transition>

            <button @click="goBack" class="w-full mt-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-colors">
                ← Volver al historial
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const results = ref({ score: 0, status: '', details: [] });
const loading = ref(true);
const currentErrorIndex = ref(0);
const userData = ref(null);

// ========== MEDIDAS DE SEGURIDAD ==========

// 1. Detectar cambio de pestaña
let visibilityWarningCount = 0;

const handleVisibilityChange = () => {
    if (document.hidden) {
        visibilityWarningCount++;
        
        if (visibilityWarningCount >= 3) {
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: 'Has cambiado de pestaña múltiples veces. Serás redirigido al dashboard.',
                confirmButtonColor: '#ef4444',
                allowOutsideClick: false
            }).then(() => {
                router.push({ name: 'student.dashboard' });
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: '⚠️ Atención',
                text: `No está permitido cambiar de pestaña. ${3 - visibilityWarningCount} intentos restantes.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
};

// 2. Teclas prohibidas
const forbiddenKeys = ['PrintScreen', 'F12', 'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'Tab', 'Alt', 'Control', 'Meta', 'Escape'];

const handleKeyDown = (e) => {
    // Prevenir teclas de función
    if (forbiddenKeys.includes(e.key)) {
        e.preventDefault();
        e.stopPropagation();
        
        if (e.key === 'F12' || e.key === 'PrintScreen') {
            Swal.fire({
                icon: 'warning',
                title: 'Acción Bloqueada',
                text: 'Esta acción no está permitida.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        }
        return;
    }
    
    // Prevenir Ctrl+C, Ctrl+X, Ctrl+V
    if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'x' || e.key === 'v')) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Copiar/pegar bloqueado',
            text: 'No está permitido copiar el contenido.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    // Prevenir Ctrl+P (imprimir)
    if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Impresión bloqueada',
            text: 'No está permitido imprimir esta página.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    // Prevenir Ctrl+S (guardar)
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Guardado bloqueado',
            text: 'No está permitido guardar esta página.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    // Prevenir Ctrl+U (ver código fuente)
    if ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U')) {
        e.preventDefault();
        return;
    }
    
    // Prevenir Ctrl+Shift+I (DevTools)
    if ((e.ctrlKey || e.metaKey) && (e.shiftKey) && (e.key === 'i' || e.key === 'I')) {
        e.preventDefault();
        return;
    }
};

// 3. Bloquear selección de texto
const disableTextSelection = (e) => {
    if (e.detail > 1) {
        e.preventDefault();
    }
};

// 4. Detectar DevTools abiertas
let devToolsInterval = null;

const detectDevTools = () => {
    const threshold = 160;
    const widthDiff = window.outerWidth - window.innerWidth;
    const heightDiff = window.outerHeight - window.innerHeight;
    
    if (widthDiff > threshold || heightDiff > threshold) {
        Swal.fire({
            icon: 'warning',
            title: 'Herramientas detectadas',
            text: 'Por favor, cierra las herramientas de desarrollo.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
};

// 5. Bloquear arrastrar imágenes
const preventDrag = (e) => {
    e.preventDefault();
    return false;
};

// 6. Marca de agua dinámica
const loadUserData = () => {
    const stored = localStorage.getItem('user_data');
    if (stored) {
        try {
            userData.value = JSON.parse(stored);
        } catch(e) {}
    }
};

// 7. Función segura para volver atrás
const goBack = () => {
    router.push({ name: 'student.history' });
};

// ========== LÓGICA DEL COMPONENTE ==========

const numberToLetter = { 1: 'A', 2: 'B', 3: 'C', 4: 'D', '1': 'A', '2': 'B', '3': 'C', '4': 'D' };
const numberToField = { 1: 'option_a', 2: 'option_b', 3: 'option_c', 4: 'option_d' };

const wrongAnswers = computed(() => {
    const wrong = [];
    results.value.details.forEach((q, index) => {
        if (Number(q.selected_option_id) !== Number(q.correct_option)) {
            wrong.push({
                selected_option_id: q.selected_option_id,
                correct_option: q.correct_option,
                userAnswer: getOptionText(q, q.selected_option_id),
                correctAnswer: getOptionText(q, q.correct_option)
            });
        }
    });
    return wrong;
});

const currentError = computed(() => wrongAnswers.value[currentErrorIndex.value]);

const loadDetails = async () => {
    try {
        const response = await axios.get(`/api/v1/student/results/${route.params.id}`);
        results.value = response.data.data;
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'No se pudieron cargar los resultados', 'error');
    } finally {
        loading.value = false;
    }
};

const getLetter = (id) => numberToLetter[id] || 'N/A';
const getOptionText = (q, id) => q[numberToField[id]] || 'Opción no encontrada';

const prevError = () => {
    if (currentErrorIndex.value > 0) {
        currentErrorIndex.value--;
    }
};

const nextError = () => {
    if (currentErrorIndex.value < wrongAnswers.value.length - 1) {
        currentErrorIndex.value++;
    }
};

// ========== EVENT LISTENERS ==========
onMounted(() => {
    loadDetails();
    loadUserData();
    
    // Eventos de seguridad
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('dblclick', disableTextSelection);
    document.addEventListener('dragstart', preventDrag);
    
    // Detectar DevTools cada 3 segundos
    devToolsInterval = setInterval(detectDevTools, 3000);
    
    // Deshabilitar menú contextual en imágenes específicas
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('dragstart', preventDrag);
        img.setAttribute('draggable', 'false');
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('dblclick', disableTextSelection);
    document.removeEventListener('dragstart', preventDrag);
    
    if (devToolsInterval) {
        clearInterval(devToolsInterval);
    }
});
</script>

<style scoped>
/* Prevenir selección de texto */
.select-none { 
    user-select: none; 
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

* {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s ease-out; }
.slide-up-enter-from { transform: translateY(20px); opacity: 0; }
.slide-up-leave-to { transform: translateY(-20px); opacity: 0; }

.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>