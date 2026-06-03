<template>
    <div class="container mx-auto p-4 max-w-2xl min-h-screen bg-gray-50/30 font-sans pb-20">
        
        <div class="flex justify-end mb-4">
            <button @click="downloadPDF" 
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest">
                📥 Descargar PDF
            </button>
        </div>

        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-500">Cargando resultados...</p>
        </div>

        <div v-else class="space-y-6">
            <!-- Resumen General -->
            <div class="bg-white shadow-xl rounded-3xl p-8 border border-gray-100 text-center">
                <h1 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Resultado del Examen Modular</h1>
                <div class="text-6xl font-black" :class="totalPercentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                    {{ totalPercentage }}%
                </div>
                <div class="mt-2 text-sm font-bold" :class="totalPercentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                    {{ totalPercentage >= 60 ? '✅ APROBADO' : '❌ REPROBADO' }}
                </div>
                <div class="mt-4 text-xs text-gray-500">
                    Puntaje: {{ totalScore }} / {{ totalPoints }}
                </div>
            </div>

            <!-- Resumen por Nivel (AGRUPADO) -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Resultados por Nivel</h2>
                <div class="space-y-4">
                    <div v-for="level in groupedByLevel" :key="level.name" class="border-b border-gray-100 pb-3 last:border-0">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-lg">{{ level.name }}</span>
                            <span class="text-sm font-bold" :class="level.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ level.percentage }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" 
                                 :class="level.percentage >= 60 ? 'bg-emerald-500' : 'bg-rose-500'"
                                 :style="{ width: level.percentage + '%' }"></div>
                        </div>
                        <!-- Subtítulos por habilidad dentro del nivel -->
                        <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                            <div v-if="level.listening" class="text-gray-500">
                                Escucha: <span :class="level.listening.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ level.listening.percentage }}%
                                </span>
                            </div>
                            <div v-if="level.reading" class="text-gray-500">
                                Lectura: <span :class="level.reading.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ level.reading.percentage }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen por Habilidad (GLOBAL) -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Resumen por Habilidad</h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold">🎧 Escucha (Listening)</span>
                            <span class="text-sm font-bold" :class="listeningPercentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ listeningPercentage }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" 
                                 :class="listeningPercentage >= 60 ? 'bg-emerald-500' : 'bg-rose-500'"
                                 :style="{ width: listeningPercentage + '%' }"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold">📖 Lectura (Reading)</span>
                            <span class="text-sm font-bold" :class="readingPercentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ readingPercentage }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" 
                                 :class="readingPercentage >= 60 ? 'bg-emerald-500' : 'bg-rose-500'"
                                 :style="{ width: readingPercentage + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalle por Módulo (con nombres estandarizados) -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Detalle por Módulo</h2>
                <div class="space-y-3">
                    <div v-for="detail in formattedDetails" :key="detail.module_id" class="border-b border-gray-100 pb-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-bold">{{ detail.displayName }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ detail.level }} - {{ detail.typeLabel }}</span>
                            </div>
                            <span class="text-sm font-bold" :class="detail.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ detail.percentage }}%
                            </span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ detail.score }} / {{ detail.total }} puntos
                        </div>
                    </div>
                </div>
            </div>

            <button @click="$router.back()" class="w-full py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-indigo-600 transition-all">
                ← Volver al Historial
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const results = ref(null);

const totalPercentage = computed(() => results.value?.total_percentage || 0);
const totalScore = computed(() => results.value?.total_score || 0);
const totalPoints = computed(() => results.value?.total_points || 0);

// Función para obtener nombre estandarizado del módulo
const getModuleDisplayName = (level, type, title) => {
    // Si el título ya tiene el formato esperado, lo usamos
    if (title && (title.includes(level) || title.includes('A1') || title.includes('A2') || title.includes('B1') || title.includes('B2'))) {
        return title;
    }
    
    // Formato estandarizado
    const typeName = type === 'listening' ? 'Escucha' : 'Lectura';
    return `${level} - ${typeName}`;
};

// Función para obtener etiqueta del tipo
const getTypeLabel = (type) => {
    return type === 'listening' ? 'Escucha' : 'Lectura';
};

// Agrupar resultados por nivel (A1, A2, B1, B2)
const groupedByLevel = computed(() => {
    if (!results.value?.details) return [];
    
    const levelsMap = new Map();
    
    results.value.details.forEach(detail => {
        const level = detail.level;
        const type = detail.type;
        const percentage = detail.percentage;
        const score = detail.score;
        const total = detail.total;
        
        if (!levelsMap.has(level)) {
            levelsMap.set(level, {
                name: level,
                listening: null,
                reading: null,
                totalScore: 0,
                totalPoints: 0,
                percentage: 0
            });
        }
        
        const levelData = levelsMap.get(level);
        
        if (type === 'listening') {
            levelData.listening = { percentage, score, total };
        } else if (type === 'reading') {
            levelData.reading = { percentage, score, total };
        }
        
        // Acumular para el promedio del nivel
        levelData.totalScore += score;
        levelData.totalPoints += total;
    });
    
    // Calcular porcentaje total por nivel
    const result = Array.from(levelsMap.values()).map(level => ({
        ...level,
        percentage: level.totalPoints > 0 
            ? Math.round((level.totalScore / level.totalPoints) * 100) 
            : 0
    }));
    
    // Ordenar niveles: A1, A2, B1, B2
    const order = { 'A1': 1, 'A2': 2, 'B1': 3, 'B2': 4 };
    return result.sort((a, b) => (order[a.name] || 999) - (order[b.name] || 999));
});

// Formatear detalles con nombres estandarizados
const formattedDetails = computed(() => {
    if (!results.value?.details) return [];
    
    return results.value.details.map(detail => ({
        ...detail,
        displayName: getModuleDisplayName(detail.level, detail.type, detail.title),
        typeLabel: getTypeLabel(detail.type)
    }));
});

// Filtrar niveles que tienen total > 0 (para compatibilidad)
const filteredLevels = computed(() => {
    if (!results.value?.by_level) return [];
    return Object.entries(results.value.by_level)
        .filter(([_, data]) => data.total > 0)
        .map(([name, data]) => ({
            name,
            percentage: data.percentage,
            score: data.score,
            total: data.total
        }));
});

const listeningPercentage = computed(() => {
    if (!results.value?.by_type) return 0;
    return results.value.by_type?.listening?.percentage || 0;
});

const readingPercentage = computed(() => {
    if (!results.value?.by_type) return 0;
    return results.value.by_type?.reading?.percentage || 0;
});

const details = computed(() => {
    if (!results.value?.details) return [];
    return results.value.details;
});

const loadResult = async () => {
    try {
        const response = await axios.get(`/api/v1/student/modular-results/${route.params.id}`);
        results.value = response.data;
        console.log('Resultados cargados:', results.value);
        loading.value = false;
    } catch (error) {
        console.error("Error cargando resultados:", error);
        Swal.fire('Error', 'No se pudieron cargar los resultados', 'error');
        loading.value = false;
    }
};

const downloadPDF = async () => {
    try {
        const response = await axios.get(`/api/v1/student/modular-results/${route.params.id}/download`, {
            responseType: 'blob'
        });
        
        // Si la respuesta es un blob JSON, significa que hubo error
        if (response.headers['content-type'] && response.headers['content-type'].includes('application/json')) {
            const text = await response.data.text();
            const error = JSON.parse(text);
            console.error('Error del servidor:', error);
            Swal.fire('Error', error.error || 'Error al generar el PDF', 'error');
            return;
        }
        
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Reporte_Modular_${route.params.id}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        Swal.fire('Éxito', 'PDF descargado correctamente', 'success');
        
    } catch (error) {
        console.error('Error al descargar:', error);
        Swal.fire('Error', 'No se pudo generar el PDF', 'error');
    }
};

onMounted(() => {
    loadResult();
});
</script>