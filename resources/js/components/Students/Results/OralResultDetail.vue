<template>
    <div class="container mx-auto p-4 max-w-2xl min-h-screen bg-gray-50/30 font-sans pb-20 select-none relative overflow-hidden">
        
        <div class="flex justify-end mb-4">
    <button 
        @click="downloadPDF" 
        class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-emerald-100"
    >
        <span>📥</span> Descargar PDF
    </button>
</div>

        <div v-if="loading" class="flex flex-col items-center justify-center py-20 relative z-10">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-500 font-bold italic text-xs uppercase tracking-widest text-center">Generando reporte protegido...</p>
        </div>

        <div v-else class="animate-fade-in space-y-6 relative z-10">
            <div class="bg-white shadow-xl rounded-3xl p-8 border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4">
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase rounded-lg border border-indigo-100">
                        {{ languageName }}
                    </span>
                </div>
                <div class="text-center">
                    <h1 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nivel Certificado</h1>
                    <div class="text-7xl font-black text-indigo-600 italic tracking-tighter">{{ result.final_level }}</div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6 ml-2">Explorar Niveles</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div v-for="item in allLevelsSummary" :key="item.level" 
                         @click="selectedLevel = item.level"
                         :class="[
                            item.level === selectedLevel ? 'bg-indigo-600 text-white border-indigo-600 ring-4 ring-indigo-100 scale-[1.03]' : 'border',
                            (!item.completed || item.average < 60) ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100'
                         ]"
                         class="flex flex-col p-4 rounded-2xl cursor-pointer transition-all duration-300">
                        <span class="text-xs font-black">{{ item.level }}</span>
                        <span class="text-[10px] font-mono font-bold">{{ item.average }}%</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-8 ml-2">Notas en {{ selectedLevel }}</p>
                <div class="space-y-6">
                    <div v-for="(score, category) in detailedScores" :key="category">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[11px] font-black text-gray-600 uppercase tracking-wide">{{ formatCategory(category) }}</span>
                            <span class="text-xs font-black" :class="score >= 60 ? 'text-emerald-600' : 'text-rose-600'">{{ score }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden border border-gray-50">
                            <div class="h-full rounded-full transition-all duration-1000 ease-out shadow-sm" 
                                 :class="score >= 60 ? 'bg-emerald-500' : 'bg-rose-500'"
                                 :style="{ width: score + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-600 p-8 rounded-[2.5rem] shadow-xl text-white">
                <h3 class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-4 flex items-center gap-2">
                    <span>📚</span> Recomendaciones de Estudio
                </h3>
                <ul class="space-y-3">
                    <li v-for="(suggest, index) in studySuggestions" :key="index" class="text-sm font-medium flex gap-2">
                        <span class="opacity-50">•</span> {{ suggest }}
                    </li>
                </ul>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Feedback del Evaluador</p>
                <p class="text-gray-700 italic text-sm border-l-4 border-indigo-200 pl-4 py-3 leading-relaxed bg-gray-50/50 rounded-r-xl">
                    "{{ result.teacher_feedback || 'Sin observaciones adicionales.' }}"
                </p>
            </div>

            <button @click="$router.back()" class="w-full py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-indigo-600 transition-all">
                ← Volver al Historial
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2'; // Asegúrate de tenerlo instalado

const route = useRoute();
const result = ref({});
const teacherName = ref('');
const languageName = ref('');
const loading = ref(true);
const selectedLevel = ref('');

/**
 * CAPA 3: Bloqueo de Acciones y Alertas (SweetAlert2)
 */
const preventAction = (e) => {
    e.preventDefault();
    Swal.fire({
        title: 'Acción Protegida',
        text: 'Por seguridad de EmiSystem, no se permite copiar o usar el menú contextual en este reporte.',
        icon: 'warning',
        confirmButtonColor: '#6366f1',
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false
    });
};

const blockKeyboard = (e) => {
    // Solo bloqueamos copiado (c), ver código fuente (u) y guardar (s)
    if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'u' || e.key === 's')) {
        preventAction(e);
    }
};
const downloadPDF = async () => {
    try {
        // Usamos axios porque él sí conoce la dirección del servidor y tiene el Token
        const response = await axios.get(`/api/v1/student/oral-results/${route.params.id}/download`, {
            responseType: 'blob' // Indicamos que recibiremos un archivo
        });

        // Creamos un link temporal para disparar la descarga en el navegador
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Reporte_Oral_${route.params.id}.pdf`);
        document.body.appendChild(link);
        link.click();
        
        // Limpiamos
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

    } catch (error) {
        Swal.fire('Error', 'No se pudo generar el PDF. Verifica tu conexión.', 'error');
        console.error("Error en descarga:", error);
    }
};
onMounted(() => {
    document.addEventListener('contextmenu', preventAction); // Bloquea clic derecho
    document.addEventListener('copy', preventAction);        // Bloquea Ctrl+C
    document.addEventListener('keydown', blockKeyboard);     // Bloquea atajos
    loadResult();
});

onUnmounted(() => {
    document.removeEventListener('contextmenu', preventAction);
    document.removeEventListener('copy', preventAction);
    document.removeEventListener('keydown', blockKeyboard);
});

/**
 * LÓGICA DE DATOS (Mantenida de versiones anteriores)
 */
const studySuggestions = computed(() => {
    const scores = Object.entries(detailedScores.value);
    if (!scores.length) return [];
    const sorted = [...scores].sort((a, b) => a[1] - b[1]);
    const weakest = sorted[0][0];
    const map = {
        vocabulario: ["Practicar con lecturas avanzadas.", "Usar flashcards para nuevos términos."],
        gramatica: ["Repasar estructuras complejas.", "Realizar ejercicios de transformación."],
        fluidez: ["Grabar monólogos cortos.", "Practicar shadowing con audios."],
        pronunciacion: ["Trabajar fonemas específicos.", "Escuchar y repetir podcasts."],
        contenido: ["Estructurar mejor el discurso.", "Ampliar argumentos con ejemplos."]
    };
    return map[weakest] || ["Continuar con la práctica constante."];
});

const allLevelsSummary = computed(() => {
    const allScores = result.value.detailed_scores;
    if (!allScores) return [];
    const parsed = typeof allScores === 'string' ? JSON.parse(allScores) : allScores;
    return Object.entries(parsed).map(([level, data]) => {
        const scores = Object.values(data).filter(v => typeof v === 'number');
        const avg = scores.length ? (scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1) : 0;
        return { level, average: parseFloat(avg), completed: data.completed, isFinal: level === result.value.final_level };
    }).filter(item => item.average > 0 || item.isFinal);
});

const detailedScores = computed(() => {
    const allScores = result.value.detailed_scores;
    if (!allScores || !selectedLevel.value) return {};
    const parsed = typeof allScores === 'string' ? JSON.parse(allScores) : allScores;
    const levelData = parsed[selectedLevel.value] || {};
    return Object.fromEntries(Object.entries(levelData).filter(([k, v]) => typeof v === 'number'));
});

const formatCategory = (cat) => {
    const map = { vocabulario: 'Vocabulario', gramatica: 'Gramática', fluidez: 'Fluidez', pronunciacion: 'Pronunciación', contenido: 'Contenido' };
    return map[cat] || cat;
};

const loadResult = async () => {
    try {
        const response = await axios.get(`/api/v1/student/oral-results/${route.params.id}`);
        result.value = response.data.data;
        teacherName.value = response.data.teacher_name;
        languageName.value = response.data.language_name;
        selectedLevel.value = result.value.final_level;
    } catch (error) {
        console.error("Error cargando detalle protegido:", error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' }) : '---';
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>