<template>
    <div class="p-6 max-w-7xl mx-auto min-h-screen bg-gray-50/30">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Panel Administrativo</h1>
                <p class="text-indigo-600 font-bold uppercase text-[10px] tracking-[0.3em]">Reportes de Exámenes Orales</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex bg-gray-100/50 p-1.5 rounded-2xl border border-gray-200">
                    <button @click="statusFilter = 'all'" 
                        :class="['px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', 
                        statusFilter === 'all' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        Todos
                    </button>
                    
                    <button @click="statusFilter = 'passed'" 
                        :class="['px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', 
                        statusFilter === 'passed' ? 'bg-white text-green-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        Aprobados
                    </button>

                    <button @click="statusFilter = 'failed'" 
                        :class="['px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', 
                        statusFilter === 'failed' ? 'bg-white text-red-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        Reprobados
                    </button>

                    <button @click="statusFilter = 'absent'" 
                        :class="['px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', 
                        statusFilter === 'absent' ? 'bg-white text-gray-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        Inasistencias
                    </button>
                </div>

                <!-- BOTÓN REPORTE GENERAL - SOLO AÑADIDO, NADA ELIMINADO -->
                <button @click="downloadGeneralReport" :disabled="downloading"
                    class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all flex items-center gap-2 shadow-lg disabled:opacity-50">
                    <i v-if="!downloading" class="fas fa-file-pdf"></i>
                    <i v-else class="fas fa-spinner animate-spin"></i>
                    Exportar General
                </button>
            </div>
        </div>

        <div class="relative mb-8">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                <i class="fas fa-search"></i>
            </span>
            <input v-model="search" type="text" placeholder="Buscar por nombre o nivel..." 
                class="bg-white border-2 border-gray-100 rounded-2xl pl-12 pr-6 py-4 text-sm focus:border-indigo-500 outline-none transition-all w-full shadow-sm">
        </div>

        <div v-if="!loading" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                            <th class="px-8 py-6">Estudiante</th>
                            <th class="px-6 py-6 text-center">Idioma</th>
                            <th class="px-6 py-6 text-center">Asistencia</th>
                            <th class="px-6 py-6 text-center">Nivel</th>
                            <th class="px-6 py-6 text-center">Puntaje</th>
                            <th class="px-4 py-6 text-center">Informe</th>
                            <th class="px-8 py-6 text-right">Vista previa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="res in filteredResults" :key="res.result_id" 
                            :class="['transition-colors group', (res.attended == 0) ? 'bg-rose-50/20 italic' : 'hover:bg-gray-50']">
                            
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <img :src="res.picture ? '/storage/' + res.picture : '/images/default-avatar.png'" 
                                         class="w-12 h-12 rounded-2xl object-cover border-2 border-white shadow-sm">
                                    <div>
                                        <p class="font-bold text-gray-900 text-sm mb-1 leading-none">{{ res.name }} {{ res.lastname }}</p>
                                        <span :class="['text-[8px] font-black px-2 py-0.5 rounded-md uppercase border', 
                                            checkPassed(res.final_level, res.final_score, res.attended) ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100']">
                                            {{ checkPassed(res.final_level, res.final_score, res.attended) ? 'Certificado' : 'No Certificado' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span :class="['px-3 py-1 rounded-full text-[9px] font-black uppercase border', langBadgeClass(res.language_name)]">
                                    {{ res.language_name || 'N/A' }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div :class="['inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[9px] font-black uppercase border', 
                                    res.attended == 1 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
                                    <span :class="['w-1.5 h-1.5 rounded-full', res.attended == 1 ? 'bg-green-500' : 'bg-rose-500']"></span>
                                    {{ res.attended == 1 ? 'Presente' : 'Inasistencia' }}
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center font-black text-gray-700">
                                {{ res.final_level || 'N/E' }}
                            </td>

                            <td class="px-6 py-5 text-center font-black text-lg">
                                <span :class="res.attended == 1 ? 'text-gray-900' : 'text-rose-400'">
                                    {{ formatScore(res.final_score, res.attended) }}%
                                </span>
                            </td>

                            <td class="px-4 py-5 text-center">
                                <button v-if="res.attended == 1"
                                    @click="downloadAdminReport(res.result_id, res.attended)" 
                                    :disabled="downloading"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                    <i v-if="!downloading" class="fas fa-file-pdf"></i>
                                    <i v-else class="fas fa-spinner animate-spin"></i>
                                    <span class="text-[9px] font-black uppercase">PDF</span>
                                </button>

                                <div v-else 
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 text-gray-300 border border-gray-100 cursor-not-allowed">
                                    <i class="fas fa-file-pdf"></i>
                                    <span class="text-[9px] font-black uppercase">N/A</span>
                                </div>
                            </td>

                            <td class="px-8 py-5 text-right">
                                <router-link :to="{ name: 'teacher.showresult', params: { id: res.result_id } }"
                                    class="inline-block bg-gray-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-indigo-600 transition-all shadow-md active:scale-95">
                                    Ver Detalles
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else class="py-20 text-center font-black text-gray-400 uppercase text-xs tracking-widest animate-pulse">
            Cargando registros administrativos...
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// --- ESTADOS (TODO IGUAL, NADA ELIMINADO) ---
const loading = ref(true);
const results = ref([]);
const search = ref('');
const downloading = ref(false);
const statusFilter = ref('all');

// --- LÓGICA DE DATOS (TODO IGUAL, NADA ELIMINADO) ---
const fetchResults = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/v1/admin/oral-exams/completed');
        results.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
    } catch (e) {
        console.error("Error cargando historial:", e);
    } finally {
        loading.value = false;
    }
};

const checkPassed = (level, score, attended) => {
    if (attended != 1) return false;
    const passedLevels = ['B2', 'C1', 'C2'];
    return passedLevels.includes(level?.toUpperCase()) && parseFloat(score) >= 60;
};

// --- FUNCIÓN REPORTE INDIVIDUAL (TODO IGUAL, NADA ELIMINADO) ---
const downloadAdminReport = async (resultId, attended) => {
    if (attended != 1) return;
    
    downloading.value = true;
    try {
        const response = await axios.get(`/api/v1/admin/oral-exams/report-individual/${resultId}`, {
            responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Reporte_Admin_${resultId}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        alert("Error al generar el PDF del reporte.");
    } finally {
        downloading.value = false;
    }
};

// --- FUNCIÓN REPORTE GENERAL (SOLO AÑADIDA, NADA ELIMINADO) ---
const downloadGeneralReport = async () => {
    downloading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/oral-exams/report-oralgeneral', {
            responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Reporte_General_Admin.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error("Error reporte general:", error);
        alert("Error al generar el reporte general.");
    } finally {
        downloading.value = false;
    }
};

// --- COMPUTED (TODO IGUAL, NADA ELIMINADO) ---
const filteredResults = computed(() => {
    let filtered = results.value;

    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(res => {
            const isPassed = checkPassed(res.final_level, res.final_score, res.attended);
            const isAbsent = res.attended == 0;

            if (statusFilter.value === 'passed') return isPassed;
            if (statusFilter.value === 'failed') return !isPassed && !isAbsent;
            if (statusFilter.value === 'absent') return isAbsent;
            return true;
        });
    }

    if (search.value) {
        const s = search.value.toLowerCase();
        filtered = filtered.filter(res => 
            res.name.toLowerCase().includes(s) || 
            res.lastname.toLowerCase().includes(s)
        );
    }

    return filtered;
});

const langBadgeClass = (lang) => {
    const name = lang?.toLowerCase() || '';
    if (name.includes('ing')) return 'bg-blue-50 text-blue-700 border-blue-100';
    if (name.includes('fra')) return 'bg-rose-50 text-rose-700 border-rose-100';
    if (name.includes('por')) return 'bg-emerald-50 text-emerald-700 border-emerald-100';
    return 'bg-gray-50 text-gray-700 border-gray-100';
};

const formatScore = (score, attended) => (attended == 1) ? parseFloat(score).toFixed(2) : "0.00";

// --- LIFECYCLE (TODO IGUAL, NADA ELIMINADO) ---
onMounted(() => {
    fetchResults();
});
</script>