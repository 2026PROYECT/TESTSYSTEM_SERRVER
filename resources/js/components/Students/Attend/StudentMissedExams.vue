<!-- resources/js/components/Students/Attend/StudentMissedExams.vue -->
<template>
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900">📋 Inasistencias a Exámenes</h1>
            <p class="text-gray-500">Exámenes a los que no asististe y sus sanciones</p>
        </div>

        <!-- Filtro por tipo de examen -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border mb-6">
            <div class="flex flex-wrap gap-3">
                <button 
                    @click="selectedType = 'all'"
                    :class="selectedType === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-xl text-sm font-bold transition"
                >
                    Todos
                </button>
                <button 
                    @click="selectedType = 'OralTest'"
                    :class="selectedType === 'OralTest' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-xl text-sm font-bold transition"
                >
                    🗣️ Exámenes Orales
                </button>
                <button 
                    @click="selectedType = 'CompTest'"
                    :class="selectedType === 'CompTest' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-xl text-sm font-bold transition"
                >
                    💻 Exámenes Computarizados
                </button>
            </div>
        </div>

        <!-- Lista de inasistencias -->
        <div v-if="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
            <p class="mt-4 text-gray-400">Cargando...</p>
        </div>

        <div v-else-if="filteredMissedExams.length === 0" class="bg-white rounded-2xl p-12 text-center shadow-sm border">
            <div class="text-6xl mb-4">✅</div>
            <p class="text-gray-500 font-medium">No hay inasistencias registradas</p>
            <p class="text-sm text-gray-400 mt-2">¡Excelente! Has asistido a todos tus exámenes programados.</p>
        </div>

        <div v-else class="space-y-4">
            <div v-for="exam in filteredMissedExams" :key="exam.id" class="bg-white rounded-2xl p-5 shadow-sm border hover:shadow-md transition">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div :class="exam.test_type === 'OralTest' ? 'bg-purple-100' : 'bg-blue-100'" class="w-12 h-12 rounded-2xl flex items-center justify-center">
                            <span class="text-2xl">{{ exam.test_type === 'OralTest' ? '🗣️' : '💻' }}</span>
                        </div>
                        <div>
                            <p class="font-black text-gray-800">
                                {{ exam.test_type === 'OralTest' ? 'Examen Oral' : 'Examen Computarizado' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                📅 {{ formatDate(exam.start_at) }}
                            </p>
                            <p v-if="exam.language" class="text-xs text-gray-400">
                                🌐 {{ exam.language }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">
                            ❌ Inasistencia
                        </span>
                        <p class="text-xs text-gray-400 mt-1">
                            Registrado: {{ formatDate(exam.updated_at) }}
                        </p>
                    </div>
                </div>

                <!-- SECCIÓN DE SANCIÓN -->
                <div v-if="getSanctionInfo(exam)" class="mt-4 p-4 rounded-xl" :class="getSanctionClass(exam)">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">⚠️</span>
                        <div class="flex-1">
                            <p class="font-bold text-sm" :class="getSanctionTextClass(exam)">
                                {{ getSanctionInfo(exam).title }}
                            </p>
                            <p class="text-xs mt-1" :class="getSanctionDescriptionClass(exam)">
                                {{ getSanctionInfo(exam).description }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-black" :class="getSanctionNumberClass(exam)">
                                {{ getSanctionInfo(exam).days }}
                            </p>
                            <p class="text-xs">días</p>
                        </div>
                    </div>
                    
                    <!-- Barra de progreso de la sanción -->
                    <div v-if="getSanctionProgress(exam)" class="mt-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span>Días transcurridos</span>
                            <span>{{ getSanctionProgress(exam).elapsed }}/{{ getSanctionProgress(exam).total }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div 
                                class="h-2 rounded-full transition-all duration-500"
                                :class="getSanctionBarClass(exam)"
                                :style="{ width: getSanctionProgress(exam).percentage + '%' }"
                            ></div>
                        </div>
                        <p class="text-xs mt-2" :class="getSanctionTextClass(exam)">
                            🔓 Reactivación: {{ formatDate(getSanctionInfo(exam).reactivationDate) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(false);
const missedExams = ref([]);
const selectedType = ref(route.query.type || 'all');

const filteredMissedExams = computed(() => {
    if (selectedType.value === 'all') {
        return missedExams.value;
    }
    return missedExams.value.filter(exam => exam.test_type === selectedType.value);
});

// Calcular información de sanción
const getSanctionInfo = (exam) => {
    if (!exam.attended === 0) return null;
    
    const startDate = new Date(exam.start_at);
    const now = new Date();
    const sanctionDays = 14; // Días de sanción por inasistencia
    const reactivationDate = new Date(exam.updated_at);
    reactivationDate.setDate(reactivationDate.getDate() + sanctionDays);
    
    const elapsedDays = Math.max(0, Math.floor((now - new Date(exam.updated_at)) / (1000 * 60 * 60 * 24)));
    const remainingDays = Math.max(0, sanctionDays - elapsedDays);
    const isActive = remainingDays > 0;
    
    return {
        title: isActive ? '⏰ Sanción Activa' : '✅ Sanción Completada',
        description: isActive 
            ? `No asististe a este examen. Tu cuenta está suspendida por ${sanctionDays} días.`
            : `La sanción por esta inasistencia ya ha finalizado.`,
        days: sanctionDays,
        remainingDays: remainingDays,
        reactivationDate: reactivationDate,
        isActive: isActive,
        elapsed: elapsedDays,
        total: sanctionDays,
        percentage: Math.min(100, Math.round((elapsedDays / sanctionDays) * 100))
    };
};

// Clases para los estilos de la sanción
const getSanctionClass = (exam) => {
    const info = getSanctionInfo(exam);
    if (!info) return 'bg-gray-50';
    return info.isActive ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200';
};

const getSanctionTextClass = (exam) => {
    const info = getSanctionInfo(exam);
    if (!info) return 'text-gray-600';
    return info.isActive ? 'text-red-700' : 'text-green-700';
};

const getSanctionDescriptionClass = (exam) => {
    const info = getSanctionInfo(exam);
    if (!info) return 'text-gray-500';
    return info.isActive ? 'text-red-600' : 'text-green-600';
};

const getSanctionNumberClass = (exam) => {
    const info = getSanctionInfo(exam);
    if (!info) return 'text-gray-600';
    return info.isActive ? 'text-red-600' : 'text-green-600';
};

const getSanctionBarClass = (exam) => {
    const info = getSanctionInfo(exam);
    if (!info) return 'bg-gray-400';
    return info.isActive ? 'bg-red-500' : 'bg-green-500';
};

const getSanctionProgress = (exam) => {
    const info = getSanctionInfo(exam);
    if (!info) return null;
    return {
        elapsed: info.elapsed,
        total: info.total,
        percentage: info.percentage
    };
};

const loadMissedExams = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/student/missed-exams');
        missedExams.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error loading missed exams:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    loadMissedExams();
});
</script>