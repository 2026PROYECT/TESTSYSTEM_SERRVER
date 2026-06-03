<template>
    <div class="p-6 max-w-7xl mx-auto min-h-screen bg-gray-50/30">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Panel Administrativo</h1>
                <p class="text-indigo-600 font-bold uppercase text-[10px] tracking-[0.3em]">Historial de Exámenes Modulares</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-gray-100 p-1 rounded-2xl flex gap-1 shadow-inner border border-gray-200">
                    <button @click="currentFilter = 'all'" 
                        :class="['px-4 py-1.5 text-[10px] font-black uppercase rounded-xl transition-all', currentFilter === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        Todos
                    </button>
                    <button @click="currentFilter = 'passed'" 
                        :class="['px-4 py-1.5 text-[10px] font-black uppercase rounded-xl transition-all', currentFilter === 'passed' ? 'bg-emerald-500 text-white shadow-md' : 'text-gray-500 hover:text-gray-700']">
                        Aprobados
                    </button>
                    <button @click="currentFilter = 'failed'" 
                        :class="['px-4 py-1.5 text-[10px] font-black uppercase rounded-xl transition-all', currentFilter === 'failed' ? 'bg-rose-500 text-white shadow-md' : 'text-gray-500 hover:text-gray-700']">
                        Reprobados
                    </button>
                </div>

                <button @click="exportarPdf('all')"
                    class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all flex items-center gap-2 shadow-lg active:scale-95">
                    <i class="fas fa-file-pdf"></i>
                    Exportar Lista
                </button>
            </div>
        </div>

        <div class="relative mb-8">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                <i class="fas fa-search"></i>
            </span>
            <input v-model="search" type="text" placeholder="Buscar alumno..." 
                class="bg-white border-2 border-gray-100 rounded-2xl pl-12 pr-6 py-4 text-sm focus:border-indigo-500 outline-none transition-all w-full shadow-sm">
        </div>

        <div v-if="!loading" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                            <th class="px-8 py-6">Estudiante</th>
                            <th class="px-6 py-6 text-center">Idioma</th>
                            <th class="px-6 py-6 text-center">Fecha</th>
                            <th class="px-6 py-6 text-center">Puntaje</th>
                            <th class="px-6 py-6 text-center">Niveles Aprobados</th>
                            <th class="px-6 py-6 text-center">Estado</th>
                            <th class="px-8 py-6 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="row in filteredData" :key="row.id" class="group hover:bg-indigo-50/20 transition-colors">
                            
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center border border-indigo-100 text-indigo-600 font-black text-lg shadow-sm">
                                        {{ getInitials(row.student_name) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 text-sm leading-none">{{ row.student_name }}</p>
                                        <span class="text-[8px] font-black px-2 py-0.5 rounded-md uppercase border mt-1 inline-block bg-gray-50 text-gray-600 border-gray-200">
                                            {{ row.student_email }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase border bg-indigo-50 text-indigo-700 border-indigo-100">
                                    {{ row.language_name }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="text-xs font-medium text-gray-600">
                                    {{ formatDate(row.completed_at) }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center font-black text-lg tracking-tighter">
                                <span :class="row.total_percentage >= 60 ? 'text-gray-900' : 'text-rose-500'">
                                    {{ row.total_percentage }}%
                                </span>
                                <div class="text-[8px] text-gray-400">
                                    {{ row.total_score }}/{{ row.total_points }} pts
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex flex-wrap justify-center gap-1">
                                    <span v-for="level in getApprovedLevels(row)" 
                                          :key="level.name"
                                          class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase"
                                          :class="level.percentage >= 60 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400'">
                                        {{ level.name }}: {{ level.percentage }}%
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div :class="['inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[9px] font-black uppercase border', 
                                    row.total_percentage >= 60 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
                                    <span :class="['w-1.5 h-1.5 rounded-full', row.total_percentage >= 60 ? 'bg-emerald-500' : 'bg-rose-500']"></span>
                                    {{ row.total_percentage >= 60 ? 'Aprobado' : 'Reprobado' }}
                                </div>
                            </td>

                            <td class="px-8 py-5 text-right">
                                <button @click="verDetalle(row)"
                                    class="inline-block bg-gray-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-indigo-600 transition-all shadow-md active:scale-95">
                                    Ver Detalle
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else class="py-20 text-center font-black text-gray-400 uppercase text-xs tracking-widest animate-pulse">
            Cargando reportes modulares...
        </div>

        <!-- MODAL DE DETALLE -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[85vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                    <h3 class="font-black text-lg text-gray-900">Detalle del Examen Modular</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-6" v-if="selectedAttempt">
                    
                    <!-- Información General -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-[9px] text-gray-400 uppercase font-bold">Estudiante</p>
                            <p class="font-bold text-gray-900">{{ selectedAttempt.student_name }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-gray-400 uppercase font-bold">Idioma</p>
                            <p class="font-bold text-gray-900">{{ selectedAttempt.language_name }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-gray-400 uppercase font-bold">Puntaje Total</p>
                            <p class="text-2xl font-black" :class="selectedAttempt.total_percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ selectedAttempt.total_percentage }}%
                            </p>
                            <p class="text-xs text-gray-500">{{ selectedAttempt.total_score }}/{{ selectedAttempt.total_points }} pts</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-gray-400 uppercase font-bold">Fecha</p>
                            <p class="text-sm">{{ formatDate(selectedAttempt.completed_at) }}</p>
                        </div>
                    </div>

                    <!-- Resultados por Nivel -->
                    <div v-if="selectedAttempt.results_data?.by_level">
                        <h4 class="font-bold text-sm text-gray-700 mb-3">📊 Resultados por Nivel</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div v-for="(level, name) in selectedAttempt.results_data.by_level" :key="name" 
                                 class="bg-gray-50 p-3 rounded-xl text-center">
                                <div class="font-black text-lg">{{ name }}</div>
                                <div class="text-sm font-bold" :class="level.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ level.percentage }}%
                                </div>
                                <div class="text-[10px] text-gray-500">{{ level.score }}/{{ level.total }}</div>
                                <div class="w-full bg-gray-200 h-1 rounded-full mt-2">
                                    <div class="h-full rounded-full transition-all" 
                                         :class="level.percentage >= 60 ? 'bg-emerald-500' : 'bg-rose-500'"
                                         :style="{ width: level.percentage + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen por Tipo -->
                    <div v-if="selectedAttempt.results_data?.by_type">
                        <h4 class="font-bold text-sm text-gray-700 mb-3">🎯 Resumen por Habilidad</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-xl">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-bold">🎧 Listening</span>
                                    <span class="text-sm font-bold" :class="selectedAttempt.results_data.by_type.listening?.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ selectedAttempt.results_data.by_type.listening?.percentage || 0 }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 h-1.5 rounded-full">
                                    <div class="h-full rounded-full transition-all" 
                                         :class="selectedAttempt.results_data.by_type.listening?.percentage >= 60 ? 'bg-emerald-500' : 'bg-rose-500'"
                                         :style="{ width: (selectedAttempt.results_data.by_type.listening?.percentage || 0) + '%' }"></div>
                                </div>
                                <div class="text-[10px] text-gray-500 mt-1">
                                    {{ selectedAttempt.results_data.by_type.listening?.score || 0 }}/{{ selectedAttempt.results_data.by_type.listening?.total || 0 }} pts
                                </div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-bold">📖 Reading</span>
                                    <span class="text-sm font-bold" :class="selectedAttempt.results_data.by_type.reading?.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ selectedAttempt.results_data.by_type.reading?.percentage || 0 }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 h-1.5 rounded-full">
                                    <div class="h-full rounded-full transition-all" 
                                         :class="selectedAttempt.results_data.by_type.reading?.percentage >= 60 ? 'bg-emerald-500' : 'bg-rose-500'"
                                         :style="{ width: (selectedAttempt.results_data.by_type.reading?.percentage || 0) + '%' }"></div>
                                </div>
                                <div class="text-[10px] text-gray-500 mt-1">
                                    {{ selectedAttempt.results_data.by_type.reading?.score || 0 }}/{{ selectedAttempt.results_data.by_type.reading?.total || 0 }} pts
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle por Módulo -->
                    <div v-if="selectedAttempt.results_data?.details && selectedAttempt.results_data.details.length > 0">
                        <h4 class="font-bold text-sm text-gray-700 mb-3">📋 Detalle por Módulo</h4>
                        <div class="max-h-60 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-[9px] font-black">Módulo</th>
                                        <th class="px-3 py-2 text-center text-[9px] font-black">Nivel</th>
                                        <th class="px-3 py-2 text-center text-[9px] font-black">Tipo</th>
                                        <th class="px-3 py-2 text-center text-[9px] font-black">Puntaje</th>
                                        <th class="px-3 py-2 text-center text-[9px] font-black">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="detail in selectedAttempt.results_data.details" :key="detail.module_id" class="border-b border-gray-100">
                                        <td class="px-3 py-2 text-xs">{{ truncate(detail.title, 25) }}</td>
                                        <td class="px-3 py-2 text-center text-xs">{{ detail.level }}</td>
                                        <td class="px-3 py-2 text-center text-xs">{{ detail.type }}</td>
                                        <td class="px-3 py-2 text-center text-xs">{{ detail.score }}/{{ detail.total }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <span :class="detail.percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'" class="font-bold">
                                                {{ detail.percentage }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-6 py-4 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all text-xs font-black uppercase">
                        Cerrar
                    </button>
                    <button @click="exportarPdfIndividual(selectedAttempt?.id)" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-all text-xs font-black uppercase flex items-center gap-2">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const records = ref([]);
const search = ref('');
const loading = ref(true);
const showModal = ref(false);
const selectedAttempt = ref(null);
const currentFilter = ref('all');

const getInitials = (name) => {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const truncate = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

const getApprovedLevels = (row) => {
    if (!row.results_data?.by_level) return [];
    return Object.entries(row.results_data.by_level).map(([name, data]) => ({
        name,
        percentage: data.percentage
    }));
};

const fetchModularReports = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/v1/admin/modular-reports');
        records.value = response.data;
    } catch (error) {
        console.error("Error al cargar reportes modulares:", error);
        Swal.fire('Error', 'No se pudieron cargar los reportes', 'error');
    } finally {
        loading.value = false;
    }
};

const filteredData = computed(() => {
    let data = records.value;
    if (search.value.trim()) {
        const q = search.value.toLowerCase().trim();
        data = data.filter(r => r.student_name.toLowerCase().includes(q) || r.language_name.toLowerCase().includes(q));
    }
    if (currentFilter.value === 'passed') data = data.filter(r => r.total_percentage >= 60);
    if (currentFilter.value === 'failed') data = data.filter(r => r.total_percentage < 60);
    return data;
});

const exportarPdfIndividual = async (id) => {
    try {
        const response = await axios.get(`/api/v1/admin/modular-reports/${id}/pdf`, {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Reporte_Modular_${id}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
    } catch (error) {
        console.error('Error:', error);
        if (error.response?.status === 401) {
            Swal.fire('Error', 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.', 'error').then(() => {
                window.location.href = '/login';
            });
        } else {
            Swal.fire('Error', 'No se pudo descargar el PDF', 'error');
        }
    }
};

const exportarPdf = async (type) => {
    try {
        const filter = type === 'all' ? currentFilter.value : type;
        const response = await axios.get(`/api/v1/admin/modular-reports/export-pdf?filter=${filter}`, {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Reporte_Modular_${filter}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
    } catch (error) {
        console.error('Error:', error);
        if (error.response?.status === 401) {
            Swal.fire('Error', 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.', 'error').then(() => {
                window.location.href = '/login';
            });
        } else {
            Swal.fire('Error', 'No se pudo descargar el PDF', 'error');
        }
    }
};


const verDetalle = (attempt) => {
    selectedAttempt.value = attempt;
    showModal.value = true;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
};

onMounted(() => {
    fetchModularReports();
});
</script>