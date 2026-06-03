<template>
    <div class="p-6 max-w-7xl mx-auto min-h-screen bg-gray-50/30">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Panel Administrativo</h1>
                <p class="text-emerald-600 font-bold uppercase text-[10px] tracking-[0.3em]">Historial de Exámenes Computarizados</p>
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
                    class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all flex items-center gap-2 shadow-lg active:scale-95">
                    <i class="fas fa-file-pdf"></i>
                    Exportar Lista
                </button>
            </div>
        </div>

        <div class="relative mb-8">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                <i class="fas fa-search"></i>
            </span>
            <input v-model="search" type="text" placeholder="Buscar alumno o examen..." 
                class="bg-white border-2 border-gray-100 rounded-2xl pl-12 pr-6 py-4 text-sm focus:border-emerald-500 outline-none transition-all w-full shadow-sm">
        </div>

        <div v-if="!loading" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                            <th class="px-8 py-6">Estudiante</th>
                            <th class="px-6 py-6 text-center">Examen</th>
                            <th class="px-6 py-6 text-center">Estado</th>
                            <th class="px-6 py-6 text-center">Puntaje</th>
                            <th class="px-4 py-6 text-center">Reporte</th>
                            <th class="px-8 py-6 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="row in filteredData" :key="row.id" class="group hover:bg-emerald-50/20 transition-colors">
                            
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center border border-emerald-100 text-emerald-600 font-black text-lg shadow-sm">
                                        {{ getInitials(row.student_name) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 text-sm leading-none">{{ row.student_name }}</p>
                                        <span :class="['text-[8px] font-black px-2 py-0.5 rounded-md uppercase border mt-1 inline-block', 
                                            row.score >= 60 ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100']">
                                            {{ row.score >= 60 ? 'Certificado' : 'No Certificado' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase border bg-emerald-50 text-emerald-700 border-emerald-100">
                                    {{ row.quiz_title || 'N/A' }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div :class="['inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[9px] font-black uppercase border', 
                                    row.status === 'completed' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200']">
                                    <span :class="['w-1.5 h-1.5 rounded-full', row.status === 'completed' ? 'bg-green-500' : 'bg-amber-500']"></span>
                                    {{ translateStatus(row.status) }}
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center font-black text-lg tracking-tighter">
                                <span :class="row.score >= 60 ? 'text-gray-900' : 'text-rose-500'">
                                    {{ row.score }}%
                                </span>
                            </td>

                            <td class="px-4 py-5 text-center">
                                <button @click="exportarPdfIndividual(row.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-file-pdf"></i>
                                    <span class="text-[9px] font-black uppercase">PDF</span>
                                </button>
                            </td>

                            <td class="px-8 py-5 text-right">
                                <button @click="verDetalle(row)"
                                    class="inline-block bg-gray-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-emerald-600 transition-all shadow-md active:scale-95">
                                    Ver Detalle
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else class="py-20 text-center font-black text-gray-400 uppercase text-xs tracking-widest animate-pulse">
            Sincronizando reportes computarizados...
        </div>

        <!-- ========== MODAL DE DETALLE ========== -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                    <h3 class="font-black text-lg text-gray-900">Detalle del Examen</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4" v-if="selectedAttempt">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Estudiante</p>
                            <p class="font-bold text-gray-900">{{ selectedAttempt.student_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Examen</p>
                            <p class="font-bold text-gray-900">{{ selectedAttempt.quiz_title }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Puntaje</p>
                            <p class="text-2xl font-black" :class="selectedAttempt.score >= 60 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ selectedAttempt.score }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Estado</p>
                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black', statusClass(selectedAttempt.status)]">
                                {{ translateStatus(selectedAttempt.status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Listening</p>
                            <p class="font-bold">{{ selectedAttempt.stats_l || '0 / 0' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Reading</p>
                            <p class="font-bold">{{ selectedAttempt.stats_r || '0 / 0' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400 uppercase font-bold">Fecha</p>
                            <p>{{ formatDate(selectedAttempt.completed_at) }}</p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-6 py-4 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all text-xs font-black uppercase">
                        Cerrar
                    </button>
                    <button @click="exportarPdfIndividual(selectedAttempt.id)" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition-all text-xs font-black uppercase flex items-center gap-2">
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

const records = ref([]);
const search = ref('');
const loading = ref(true);
const showModal = ref(false);
const selectedAttempt = ref(null);
const currentFilter = ref('all');

// Función para obtener iniciales
const getInitials = (name) => {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const fetchAttempts = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/v1/reports/exam-reports'); 
    records.value = response.data;
  } catch (error) {
    console.error("Error al cargar reportes:", error);
  } finally {
    loading.value = false;
  }
};

const filteredData = computed(() => {
  let data = records.value;
  if (search.value.trim()) {
    const q = search.value.toLowerCase().trim();
    data = data.filter(r => r.student_name.toLowerCase().includes(q) || r.quiz_title.toLowerCase().includes(q));
  }
  if (currentFilter.value === 'passed') data = data.filter(r => r.score >= 60);
  if (currentFilter.value === 'failed') data = data.filter(r => r.score < 60);
  return data;
});

const downloadPdfSecure = async (url) => {
  try {
    const response = await axios.get(url, { 
      responseType: 'blob',
      headers: { 'Accept': 'application/pdf' }
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const fileURL = window.URL.createObjectURL(blob);
    const reportWindow = window.open(fileURL, '_blank');
    
    if (!reportWindow) {
      alert("El navegador bloqueó la ventana emergente. Por favor, permite los pop-ups para este sitio.");
    }

    setTimeout(() => {
      window.URL.revokeObjectURL(fileURL);
    }, 10000);

  } catch (error) {
    console.error("Error detallado:", error);
    alert("No se pudo generar el PDF. Revisa la consola para más detalles.");
  }
};

const exportarPdf = (type) => {
  const filter = type === 'all' ? currentFilter.value : type;
  downloadPdfSecure(`/api/v1/reports/exam-reports/export-pdf?filter=${filter}`);
};

const exportarPdfIndividual = (id) => {
  downloadPdfSecure(`/api/v1/reports/exam-reports/${id}/pdf`);
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

const statusClass = (status) => {
  switch (status) {
    case 'completed': return 'bg-green-100 text-green-700 border border-green-200';
    case 'in_progress': return 'bg-amber-100 text-amber-700 border border-amber-200';
    default: return 'bg-slate-100 text-slate-600';
  }
};

const translateStatus = (status) => {
  const map = { 'completed': 'Finalizado', 'in_progress': 'En Curso', 'timed_out': 'Expirado' };
  return map[status] || status;
};

onMounted(fetchAttempts);
</script>