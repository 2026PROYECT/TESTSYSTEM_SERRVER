<template>
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else class="bg-gray-50/50 min-h-screen p-6">
        
        <div v-if="showCertificate" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/95 backdrop-blur-md">
            <div class="bg-white rounded-[3rem] p-10 max-w-4xl w-full shadow-2xl border-[12px] border-indigo-50 overflow-y-auto max-h-[90vh] custom-scrollbar">
                
                <div :class="[finalResult.status === 'Aprobado' ? 'bg-green-600' : 'bg-red-600', 'rounded-[2.5rem] p-8 mb-8 text-white transition-colors']">
                    <span class="text-[9px] font-black opacity-60 uppercase tracking-widest">{{ finalResult.status }}</span>
                    <h3 class="text-7xl font-black my-2 tracking-tighter">{{ finalResult.level }}</h3>
                    <p class="text-[11px] font-bold opacity-80 uppercase tracking-widest">Puntaje Final: {{ finalResult.score }}%</p>
                </div>

                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2">
                            Sugerencias para {{ getRecommendationsForLevel().title }}
                        </label>
                        <div class="flex gap-2">
                            <button @click="clearSuggestions" 
                                class="text-[9px] text-red-500 hover:text-red-700 font-bold uppercase tracking-wider">
                                Limpiar todo
                            </button>
                            <button @click="removeLastSuggestion" 
                                class="text-[9px] text-gray-500 hover:text-gray-700 font-bold uppercase tracking-wider">
                                Deshacer
                            </button>
                        </div>
                    </div>

                    <!-- Sugerencias Positivas -->
                    <div class="mb-4">
                        <h4 class="text-[9px] font-black text-green-600 uppercase tracking-widest mb-2 ml-2">✓ Aspectos Positivos</h4>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <button v-for="sug in getRecommendationsForLevel().positive" :key="sug.text"
                                @click="addSuggestion(sug.text, 'good')"
                                class="text-[9px] font-black px-3 py-1.5 rounded-xl border bg-green-50 text-green-700 border-green-100 hover:bg-green-600 hover:text-white transition-all">
                                + {{ sug.text }}
                            </button>
                            <button v-if="getRecommendationsForLevel().positive.length > 1"
                                @click="addAllSuggestions(getRecommendationsForLevel().positive, 'good')"
                                class="text-[9px] font-black px-3 py-1.5 rounded-xl border bg-green-100 text-green-700 border-green-200 hover:bg-green-600 hover:text-white transition-all">
                                + Añadir todas
                            </button>
                        </div>
                    </div>

                    <!-- Sugerencias de Mejora -->
                    <div class="mb-6">
                        <h4 class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-2 ml-2">📝 Áreas de Mejora</h4>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="sug in getRecommendationsForLevel().improvement" :key="sug.text"
                                @click="addSuggestion(sug.text, 'improve')"
                                class="text-[9px] font-black px-3 py-1.5 rounded-xl border bg-amber-50 text-amber-700 border-amber-100 hover:bg-amber-600 hover:text-white transition-all">
                                + {{ sug.text }}
                            </button>
                            <button v-if="getRecommendationsForLevel().improvement.length > 1"
                                @click="addAllSuggestions(getRecommendationsForLevel().improvement, 'improve')"
                                class="text-[9px] font-black px-3 py-1.5 rounded-xl border bg-amber-100 text-amber-700 border-amber-200 hover:bg-amber-600 hover:text-white transition-all">
                                + Añadir todas
                            </button>
                        </div>
                    </div>

                    <!-- Feedback del Profesor -->
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2">
                        Feedback del Profesor
                    </label>
                    <textarea v-model="teacherNote" 
                        class="w-full bg-gray-50 border border-gray-100 rounded-[2rem] p-6 text-sm text-gray-600 focus:ring-2 focus:ring-indigo-500 outline-none h-48 resize-none font-medium leading-relaxed"
                        placeholder="Escribe tus observaciones aquí...">
                    </textarea>
                    
                    <div class="text-right mt-2">
                        <span class="text-[9px] text-gray-400">{{ teacherNote.split('\n').filter(l => l.trim()).length }} sugerencias añadidas</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button @click="submitEvaluation" class="w-full bg-indigo-600 text-white py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl active:scale-95">
                        Confirmar y Guardar
                    </button>
                    <button @click="showCertificate = false" class="text-[10px] font-black text-gray-400 uppercase hover:text-red-500 transition-colors">
                        Revisar notas
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-4">
                <div class="bg-gray-900 rounded-[2.5rem] p-8 text-white shadow-2xl sticky top-6">
                    <div class="flex items-center gap-4 mb-8 border-b border-gray-800 pb-8">
                        <div class="w-16 h-16 rounded-2xl bg-gray-800 border border-gray-700 overflow-hidden">
                            <img v-if="exam?.picture" :src="'/storage/' + exam.picture" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h2 class="font-black text-lg truncate">{{ exam?.name }} {{ exam?.lastname }}</h2>
                            <span class="text-indigo-400 text-[10px] font-black uppercase tracking-widest">Nivel Evaluando: {{ currentLevel }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Preguntas {{ currentLevel }}</h3>
                        <div class="space-y-3 max-h-[40vh] overflow-y-auto pr-2 custom-scrollbar text-xs text-gray-400 italic">
                            <div v-for="(q, i) in questions" :key="q.id" 
                                 class="bg-gray-800/50 p-4 rounded-2xl border border-gray-700/30 flex justify-between items-start group">
                                <div class="flex-1">
                                    <span class="text-indigo-400 font-black mr-1">{{ i + 1 }}.</span> {{ q.question_text }}
                                </div>
                                <button @click="read(q.question_text, getLangCode(q.language_id))"
                                    class="ml-3 p-2 rounded-full hover:bg-gray-700 text-gray-500 hover:text-indigo-400 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8 bg-white rounded-[2.5rem] p-10 border border-gray-100 shadow-sm">
                <div class="mb-8 flex items-center gap-3 bg-gray-50 p-4 rounded-3xl border border-gray-100">
                    <div v-for="(lvl) in levels" :key="lvl" class="flex-1 flex flex-col gap-2">
                        <div :class="[
                            'h-2 rounded-full transition-all duration-500',
                            currentLevel === lvl ? 'bg-indigo-600 shadow-[0_0_10px_rgba(79,70,229,0.4)]' : 
                            (evaluation[lvl]?.completed ? 'bg-green-500' : 'bg-gray-200')
                        ]"></div>
                        <span :class="['text-[9px] font-black text-center transition-colors', currentLevel === lvl ? 'text-indigo-600' : 'text-gray-400']">
                            {{ lvl }}
                        </span>
                    </div>
                </div>

                <div class="mb-10">
                    <h2 class="text-4xl font-black text-gray-900">Rúbrica {{ currentLevel }}</h2>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mt-1">Calificación en base 100%</p>
                </div>

                <div class="space-y-8">
                    <div v-for="crit in ['vocabulario', 'gramatica', 'fluidez', 'pronunciacion', 'contenido']" :key="crit">
                        <div class="flex justify-between mb-2">
                            <label class="font-black text-gray-500 uppercase text-[9px] tracking-widest">{{ crit }}</label>
                            <span :class="['text-xl font-black', evaluation[currentLevel][crit] >= 60 ? 'text-green-600' : 'text-red-500']">
                                {{ evaluation[currentLevel][crit] }}%
                            </span>
                        </div>
                        <input type="range" min="0" max="100" v-model.number="evaluation[currentLevel][crit]" 
                            :class="['w-full h-2.5 rounded-xl appearance-none bg-gray-100 cursor-pointer', evaluation[currentLevel][crit] >= 60 ? 'accent-green-600' : 'accent-red-500']">
                    </div>

                    <div :class="['rounded-[2.5rem] p-8 mt-12 flex flex-col md:flex-row justify-between items-center gap-6 border-2 transition-all', parseFloat(levelTotal) >= 60 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100']">
                        <div>
                            <p class="text-[10px] font-black uppercase opacity-50 mb-1 text-gray-500">Promedio Nivel {{ currentLevel }}</p>
                            <h4 :class="['text-6xl font-black', parseFloat(levelTotal) >= 60 ? 'text-green-900' : 'text-red-900']">
                                {{ levelTotal }} <span class="text-xl opacity-30">%</span>
                            </h4>
                        </div>
                        <button @click="handleAction" 
                            :class="['px-12 py-6 rounded-2xl font-black uppercase text-xs text-white shadow-xl transition-all active:scale-95', parseFloat(levelTotal) >= 60 ? 'bg-green-600 hover:bg-black' : 'bg-red-600 hover:bg-black']">
                            {{ parseFloat(levelTotal) < 60 ? 'Terminar Evaluación' : (currentLevel === 'C2' ? 'Finalizar Todo' : 'Siguiente Nivel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useReader } from '@/composables/useReader';

const { read, isReading } = useReader();

const languageMap = {
    1: 'en-US',
    2: 'fr-FR',
    3: 'pt-PT'
};

const getLangCode = (id) => languageMap[id] || 'en-US';

const route = useRoute();
const router = useRouter();

// --- ESTADOS ---
const loading = ref(true);
const showCertificate = ref(false);
const exam = ref(null);
const questions = ref([]);
const currentLevel = ref('A1');
const levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
const teacherNote = ref('');
const selectedSuggestions = ref([]);

// --- RECOMENDACIONES MEJORADAS POR NIVEL Y CATEGORÍA ---
const recommendations = {
    'A1': {
        title: 'Nivel Principiante',
        positive: [
            { text: 'Comprende expresiones básicas del día a día.', type: 'good' },
            { text: 'Puede presentarse a sí mismo y a otros.', type: 'good' },
            { text: 'Responde preguntas sencillas sobre datos personales.', type: 'good' },
            { text: 'Maneja vocabulario elemental.', type: 'good' }
        ],
        improvement: [
            { text: 'Practicar saludos y presentaciones básicas.', type: 'improve' },
            { text: 'Ampliar vocabulario de objetos cotidianos.', type: 'improve' },
            { text: 'Escuchar diálogos simples en el idioma.', type: 'improve' },
            { text: 'Repasar números, colores y fechas.', type: 'improve' }
        ]
    },
    'A2': {
        title: 'Nivel Básico',
        positive: [
            { text: 'Comprende frases y expresiones de uso frecuente.', type: 'good' },
            { text: 'Puede comunicarse en tareas simples y rutinarias.', type: 'good' },
            { text: 'Describe aspectos de su pasado y entorno.', type: 'good' },
            { text: 'Manejo adecuado de tiempos presentes.', type: 'good' }
        ],
        improvement: [
            { text: 'Practicar conversaciones sobre temas familiares.', type: 'improve' },
            { text: 'Ampliar vocabulario de viajes y trabajo.', type: 'improve' },
            { text: 'Introducir tiempos pasados simples.', type: 'improve' },
            { text: 'Leer textos cortos y sencillos.', type: 'improve' }
        ]
    },
    'B1': {
        title: 'Nivel Intermedio',
        positive: [
            { text: 'Comprende los puntos principales de textos claros.', type: 'good' },
            { text: 'Se desenvuelve en viajes y situaciones laborales.', type: 'good' },
            { text: 'Produce textos sencillos coherentes.', type: 'good' },
            { text: 'Describe experiencias y eventos.', type: 'good' }
        ],
        improvement: [
            { text: 'Practicar la expresión oral en debates sencillos.', type: 'improve' },
            { text: 'Ampliar vocabulario técnico y profesional.', type: 'improve' },
            { text: 'Mejorar la coherencia en textos escritos.', type: 'improve' },
            { text: 'Practicar comprensión auditiva con noticias.', type: 'improve' }
        ]
    },
    'B2': {
        title: 'Nivel Intermedio Alto',
        positive: [
            { text: 'Comprende ideas principales de textos complejos.', type: 'good' },
            { text: 'Se comunica con fluidez espontánea.', type: 'good' },
            { text: 'Produce textos claros y detallados.', type: 'good' },
            { text: 'Buen dominio de conectores y cohesión.', type: 'good' }
        ],
        improvement: [
            { text: 'Practicar debates y argumentaciones.', type: 'improve' },
            { text: 'Ampliar vocabulario académico.', type: 'improve' },
            { text: 'Perfeccionar estructuras gramaticales complejas.', type: 'improve' },
            { text: 'Exponerse a contenido nativo (series, podcasts).', type: 'improve' }
        ]
    },
    'C1': {
        title: 'Nivel Avanzado',
        positive: [
            { text: 'Comprende textos extensos y con sentido implícito.', type: 'good' },
            { text: 'Se expresa con fluidez casi nativa.', type: 'good' },
            { text: 'Usa el idioma de manera flexible y efectiva.', type: 'good' },
            { text: 'Excelente manejo de matices y registros.', type: 'good' }
        ],
        improvement: [
            { text: 'Perfeccionar la escritura académica.', type: 'improve' },
            { text: 'Ampliar vocabulario especializado.', type: 'improve' },
            { text: 'Practicar la expresión en contextos formales.', type: 'improve' },
            { text: 'Trabajar en la precisión gramatical.', type: 'improve' }
        ]
    },
    'C2': {
        title: 'Nivel Maestría',
        positive: [
            { text: 'Comprende con facilidad prácticamente todo lo que oye o lee.', type: 'good' },
            { text: 'Se expresa espontáneamente con gran fluidez.', type: 'good' },
            { text: 'Dominio completo del idioma.', type: 'good' },
            { text: 'Capacidad de síntesis y paráfrasis.', type: 'good' }
        ],
        improvement: [
            { text: 'Mantener inmersión total en el idioma.', type: 'improve' },
            { text: 'Perfeccionar la escritura creativa y técnica.', type: 'improve' },
            { text: 'Participar en debates de alto nivel.', type: 'improve' },
            { text: 'Certificación internacional (TOEFL, IELTS, DELF).', type: 'improve' }
        ]
    }
};

// Recomendaciones para nivel Pre-A1 (reprobado)
const preA1Recommendations = {
    title: 'No alcanza el nivel mínimo',
    positive: [
        { text: 'Muestra interés por aprender el idioma.', type: 'good' },
        { text: 'Asistió regularmente a clases.', type: 'good' },
        { text: 'Participa en actividades grupales.', type: 'good' }
    ],
    improvement: [
        { text: 'Reforzar vocabulario básico (saludos, números, colores).', type: 'improve' },
        { text: 'Practicar 30 minutos diarios de listening.', type: 'improve' },
        { text: 'Utilizar aplicaciones móviles (Duolingo, Memrise).', type: 'improve' },
        { text: 'Tomar clases de nivelación.', type: 'improve' },
        { text: 'Ver videos con subtítulos en el idioma.', type: 'improve' },
        { text: 'Repetir frases cortas en voz alta.', type: 'improve' },
        { text: 'Crear tarjetas de vocabulario visual.', type: 'improve' },
        { text: 'Practicar la escritura de palabras básicas.', type: 'improve' }
    ]
};

// --- RÚBRICA ---
const evaluation = ref({
    A1: { vocabulario: 0, gramatica: 0, fluidez: 0, pronunciacion: 0, contenido: 0, completed: false },
    A2: { vocabulario: 0, gramatica: 0, fluidez: 0, pronunciacion: 0, contenido: 0, completed: false },
    B1: { vocabulario: 0, gramatica: 0, fluidez: 0, pronunciacion: 0, contenido: 0, completed: false },
    B2: { vocabulario: 0, gramatica: 0, fluidez: 0, pronunciacion: 0, contenido: 0, completed: false },
    C1: { vocabulario: 0, gramatica: 0, fluidez: 0, pronunciacion: 0, contenido: 0, completed: false },
    C2: { vocabulario: 0, gramatica: 0, fluidez: 0, pronunciacion: 0, contenido: 0, completed: false },
});

// --- MÉTODOS DE SUGERENCIAS ---
const addSuggestion = (text, type) => {
    let suggestionText = text.trim();
    if (!suggestionText.endsWith('.')) {
        suggestionText += '.';
    }
    
    if (!teacherNote.value.includes(suggestionText)) {
        if (teacherNote.value.length > 0) {
            teacherNote.value = teacherNote.value + ' ' + suggestionText;
        } else {
            teacherNote.value = suggestionText;
        }
        selectedSuggestions.value.push(suggestionText);
    }
};

const addAllSuggestions = (suggestions, type) => {
    suggestions.forEach(sug => {
        addSuggestion(sug.text, type);
    });
};

const clearSuggestions = () => {
    teacherNote.value = '';
    selectedSuggestions.value = [];
};

const removeLastSuggestion = () => {
    if (selectedSuggestions.value.length > 0) {
        selectedSuggestions.value.pop();
        const lines = teacherNote.value.split('\n');
        lines.pop();
        teacherNote.value = lines.join('\n');
    }
};

// --- MÉTODOS DE CÁLCULO ---
const calculateScore = (lvl) => {
    const s = evaluation.value[lvl];
    const sum = (Number(s.vocabulario) + Number(s.gramatica) + Number(s.fluidez) + Number(s.pronunciacion) + Number(s.contenido));
    return (sum / 5).toFixed(2);
};

const levelTotal = computed(() => calculateScore(currentLevel.value));

const finalResult = computed(() => {
    let lastPassed = { level: 'Pre-A1', score: '0.00', status: 'Reprobado' };
    for (const lvl of levels) {
        const scoreStr = calculateScore(lvl);
        if (parseFloat(scoreStr) >= 60.0) {
            lastPassed = { level: lvl, score: scoreStr, status: 'Aprobado' };
        } else { break; }
    }
    return lastPassed;
});

const getRecommendationsForLevel = () => {
    if (finalResult.value.status === 'Reprobado') {
        return preA1Recommendations;
    }
    return recommendations[finalResult.value.level] || recommendations['B1'];
};

// --- ACCIONES DEL EXAMEN ---
const handleAction = async () => {
    if (parseFloat(levelTotal.value) < 60) {
        showCertificate.value = true;
    } else {
        const currentIndex = levels.indexOf(currentLevel.value);
        if (currentIndex < levels.length - 1) {
            evaluation.value[currentLevel.value].completed = true;
            currentLevel.value = levels[currentIndex + 1];
            await fetchQuestions(currentLevel.value);
            Swal.fire({ title: '¡Nivel superado!', icon: 'success', timer: 800, showConfirmButton: false });
        } else {
            showCertificate.value = true;
        }
    }
};

const submitEvaluation = async () => {
    loading.value = true;
    try {
        const cert = finalResult.value;
        const payload = {
            quiz_assignment_id: exam.value.assignment_id,
            student_id: exam.value.student_id,
            final_level: String(cert.level),
            final_score: parseFloat(cert.score),
            detailed_scores: evaluation.value,
            teacher_feedback: teacherNote.value
        };
        await axios.post(`/api/v1/teacher/exam/${route.params.id}/save`, payload);
        await Swal.fire('¡Éxito!', `Guardado como ${cert.level}`, 'success');
        router.push({ name: 'teacher.dashboard' });
    } catch (error) {
        Swal.fire('Error', 'Fallo al guardar', 'error');
    } finally { loading.value = false; }
};

const loadExamData = async () => {
    try {
        const resExam = await axios.get(`/api/v1/teacher/exam/${route.params.id}`);
        exam.value = resExam.data;
        await fetchQuestions(currentLevel.value);
    } catch (e) { 
        router.push({ name: 'teacher.dashboard' }); 
    } finally { 
        loading.value = false; 
    }
};

const fetchQuestions = async (lvl) => {
    const langId = exam.value?.language_id || localStorage.getItem('active_lang_id') || 1;
    const res = await axios.get(`/api/v1/teacher/questions/${lvl}`, {
        headers: { 'X-Language-Id': langId }
    });
    questions.value = res.data;
};

// --- LIFECYCLE ---
onMounted(loadExamData);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
input[type='range']::-webkit-slider-thumb { appearance: none; width: 22px; height: 22px; background: #fff; border: 5px solid currentColor; border-radius: 50%; cursor: pointer; }
</style>