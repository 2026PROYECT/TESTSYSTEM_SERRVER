<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const result = ref(null);
const loading = ref(true);
const assignmentId = route.params.id; 

onMounted(async () => {
    try {
        const res = await axios.get(`/api/v1/teacher/results/${assignmentId}`);
        let data = res.data;

        // CRITICAL: Parse the JSON string if the backend hasn't casted it to an object
        if (typeof data.detailed_scores === 'string') {
            try {
                data.detailed_scores = JSON.parse(data.detailed_scores);
            } catch (e) {
                console.error("Error parsing detailed_scores", e);
                data.detailed_scores = {};
            }
        }
        
        result.value = data;
    } catch (e) {
        console.error("Error al cargar resultados", e);
    } finally {
        loading.value = false;
    }
});

// Helper function to calculate level average (Sum / 50)
const getLevelAverage = (lvlData) => {
    const sum = (Number(lvlData.vocabulario || 0) + 
                 Number(lvlData.gramatica || 0) + 
                 Number(lvlData.fluidez || 0) + 
                 Number(lvlData.pronunciacion || 0) + 
                 Number(lvlData.contenido || 0));
    return (sum / 5).toFixed(2);
};
</script>

<template>
    <div v-if="loading" class="text-center py-20 font-black text-gray-400 uppercase tracking-widest animate-pulse">
        Cargando Reporte...
    </div>

    <div v-else-if="result" class="max-w-5xl mx-auto space-y-8 p-4">
        <div class="bg-gray-900 rounded-[3rem] p-10 text-white flex flex-col md:flex-row items-center gap-8 shadow-2xl">
            <img v-if="result.picture" :src="'/storage/' + result.picture" class="w-24 h-24 rounded-3xl object-cover border-4 border-indigo-500">
            <div v-else class="w-24 h-24 rounded-3xl bg-gray-800 border-4 border-gray-700 flex items-center justify-center text-4xl font-black text-gray-600 uppercase">
                {{ result.name[0] }}
            </div>

            <div class="text-center md:text-left flex-1">
                <p class="text-indigo-400 font-black uppercase text-[10px] tracking-[0.3em] mb-1">Reporte de Evaluación Oral</p>
                <h1 class="text-4xl font-black tracking-tighter">{{ result.name }} {{ result.lastname }}</h1>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-4">
                    <span class="bg-white/10 px-4 py-2 rounded-xl text-xs font-black border border-white/10 uppercase tracking-widest">
                        Nivel Certificado: <span class="text-indigo-400 ml-1">{{ result.final_level }}</span>
                    </span>
                    <span class="bg-indigo-600 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20">
                        Score Final: {{ result.final_score }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="(levelData, levelKey) in result.detailed_scores" :key="levelKey" 
                 v-show="levelData.completed || getLevelAverage(levelData) > 0"
                 class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm">
                
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 leading-none">Nivel {{ levelKey }}</h3>
                        <p class="text-[9px] font-bold text-gray-400 uppercase mt-1 tracking-widest">Desempeño Individual</p>
                    </div>
                    <span :class="parseFloat(getLevelAverage(levelData)) >= 60 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'" 
                          class="font-black px-5 py-2 rounded-2xl text-xl tabular-nums">
                        {{ getLevelAverage(levelData) }}
                    </span>
                </div>

                <div class="space-y-5">
                    <div v-for="crit in ['vocabulario', 'gramatica', 'fluidez', 'pronunciacion', 'contenido']" :key="crit">
                        <div class="flex justify-between text-[9px] font-black uppercase text-gray-400 mb-2 tracking-widest">
                            <span>{{ crit }}</span>
                            <span class="text-gray-900 font-black text-xs">{{ levelData[crit] }}%</span>
                        </div>
                        <div class="w-full bg-gray-50 h-2 rounded-full overflow-hidden border border-gray-100">
                            <div :class="[
                                    'h-full transition-all duration-1000 ease-out',
                                    levelData[crit] >= 60 ? 'bg-green-500' : 'bg-red-500'
                                 ]" 
                                 :style="{ width: levelData[crit] + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="result.teacher_feedback" class="bg-indigo-50 rounded-[2.5rem] p-10 border-2 border-dashed border-indigo-200">
            <h4 class="font-black text-indigo-900 uppercase text-xs tracking-widest mb-4">Comentarios del Evaluador</h4>
            <p class="text-indigo-800 text-lg leading-relaxed italic font-medium">"{{ result.teacher_feedback }}"</p>
        </div>
    </div>

    <div v-else class="text-center py-20">
        <p class="text-gray-400 font-bold uppercase tracking-widest">No se encontraron resultados.</p>
    </div>
</template>

<style scoped>
/* Optional: Adding a smooth slide-in for the progress bars */
@keyframes slideIn {
  from { width: 0; }
}
.bg-green-500, .bg-red-500 {
  animation: slideIn 1.5s ease-out;
}
</style>