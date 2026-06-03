<template>
  <div class="p-6 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Consolidado Histórico</h1>
        <p class="text-slate-500 text-sm">Registro vertical con fechas. Aprobación: ≥ 60 + B2/C1/C2.</p>
      </div>
      
      <div class="flex flex-wrap items-center gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-2">
          <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Desde:</label>
          <input type="date" v-model="dateRange.since" class="text-xs border-none focus:ring-0 text-slate-600 bg-transparent">
        </div>
        <div class="h-4 w-px bg-slate-200"></div>
        <div class="flex items-center gap-2">
          <label class="text-[10px] font-bold text-slate-400 uppercase">Hasta:</label>
          <input type="date" v-model="dateRange.until" class="text-xs border-none focus:ring-0 text-slate-600 bg-transparent">
        </div>
        
        <button @click="descargarPdfGeneral" 
                :disabled="loading"
                class="flex items-center gap-2 px-4 py-2 bg-rose-600 text-white rounded-xl text-sm font-bold shadow-lg active:scale-95 disabled:opacity-50 transition-all">
          <i v-if="!loading" class="fas fa-file-pdf"></i>
          <i v-else class="fas fa-spinner animate-spin"></i>
          {{ loading ? 'Generando...' : 'Generar Reporte Sellado' }}
        </button>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
            <i class="fas fa-search"></i>
          </span>
          <input v-model="search" type="text" placeholder="Buscar estudiante..." class="pl-10 pr-4 py-2 border border-slate-200 rounded-xl w-full md:w-80 shadow-sm outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
    </div>
      
    <div class="max-w-7xl mx-auto bg-white shadow-xl rounded-2xl overflow-hidden border border-slate-200">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-800 text-white text-[11px] uppercase tracking-widest font-bold">
              <th class="p-5 font-bold">Estudiante / Carrera</th>
              <th class="p-5 text-center border-l border-slate-700">Idioma</th>
              <th class="p-5 text-center border-l border-slate-700 w-48">Historial Oral</th>
              <th class="p-5 text-center border-l border-slate-700 w-48">Historial Comp</th>
              <th class="p-5 text-center border-l border-slate-700 w-48">Historial Modular</th>
              <th class="p-5 text-center border-l border-slate-700">Estado</th>
              <th class="p-5 text-center">Acciones</th>
             </tr>
          </thead>
          
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="7" class="p-10 text-center text-slate-400 animate-pulse font-medium">Cargando base de datos...</td>
             </tr>

            <tr v-for="row in filteredData" :key="`${row.student_id}-${row.language_id}`" class="hover:bg-slate-50/50 transition-colors">
              <td class="p-5 align-top">
                <div class="font-bold text-slate-900 text-base leading-tight">{{ row.full_name }}</div>
                <div class="text-[10px] text-indigo-600 font-black uppercase mt-1">{{ row.career_name || 'Sin Carrera' }}</div>
              </td>

              <td class="p-5 text-center border-l border-slate-50 align-top">
                <span :class="langBadgeClass(row.language_name)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase border">
                  {{ row.language_name }}
                </span>
              </td>

              <td class="p-3 border-l border-slate-50 bg-slate-50/10 align-top">
                <div class="flex flex-col gap-3 items-center">
                  <div v-for="(nota, i) in parseHistory(row.oral_history)" :key="i"
                       :class="isOralItemPassed(nota, parseHistory(row.level_history)[i]) ? 'text-emerald-600' : 'text-rose-600'"
                       class="flex flex-col items-center leading-none">
                    <span class="text-[11px] font-black uppercase tracking-widest">{{ parseHistory(row.level_history)[i] || '--' }} - {{ nota }}</span>
                    <span class="text-[9px] text-slate-400 font-bold mt-1">{{ parseHistory(row.oral_dates)[i] }}</span>
                  </div>
                  <span v-if="!row.oral_history" class="text-slate-300 text-[10px] font-bold italic">Sin registros</span>
                </div>
              </td>

              <td class="p-3 border-l border-slate-50 bg-slate-50/10 align-top">
                <div class="flex flex-col gap-3 items-center">
                  <div v-for="(nota, i) in parseHistory(row.comp_history)" :key="i"
                       :class="nota >= 60 ? 'text-emerald-600' : 'text-rose-600'"
                       class="flex flex-col items-center leading-none">
                    <span class="text-[11px] font-black uppercase tracking-widest">{{ nota }}%</span>
                    <span class="text-[9px] text-slate-400 font-bold mt-1">{{ parseHistory(row.comp_dates)[i] }}</span>
                  </div>
                  <span v-if="!row.comp_history" class="text-slate-300 text-[10px] font-bold italic">Sin registros</span>
                </div>
              </td>

              <!-- 🔥 NUEVA COLUMNA: HISTORIAL MODULAR -->
              <td class="p-3 border-l border-slate-50 bg-slate-50/10 align-top">
                <div class="flex flex-col gap-3 items-center">
                  <div v-for="(nota, i) in parseHistory(row.modular_history)" :key="i"
                       :class="nota >= 60 ? 'text-emerald-600' : 'text-rose-600'"
                       class="flex flex-col items-center leading-none">
                    <span class="text-[11px] font-black uppercase tracking-widest">{{ nota }}%</span>
                    <span class="text-[9px] text-slate-400 font-bold mt-1">{{ parseHistory(row.modular_dates)[i] }}</span>
                  </div>
                  <span v-if="!row.modular_history" class="text-slate-300 text-[10px] font-bold italic">Sin registros</span>
                </div>
              </td>

              <td class="p-5 text-center border-l border-slate-50 align-top">
                <div v-if="isApproved(row)" class="text-emerald-500 flex flex-col items-center gap-1">
                  <i class="fas fa-certificate text-xl"></i>
                  <span class="text-[9px] font-black uppercase">Certificable</span>
                </div>
                <div v-else class="text-slate-300 flex flex-col items-center gap-1">
                  <i class="fas fa-clock text-xl opacity-30"></i>
                  <span class="text-[9px] font-black uppercase">Pendiente</span>
                </div>
              </td>

              <td class="p-5 text-center align-top">
                <button @click="verDetalle(row)" class="px-4 py-2 bg-white text-indigo-700 border border-slate-200 rounded-xl text-[10px] font-black uppercase hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                  DETALLES
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL DE DETALLE -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <div>
            <h2 class="text-xl font-black text-slate-800">{{ selectedStudent?.full_name }}</h2>
            <p class="text-xs text-indigo-600 font-bold uppercase tracking-widest">{{ selectedStudent?.career_name }}</p>
          </div>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-800 text-2xl p-2 transition-colors">&times;</button>
        </div>
        
        <div class="p-6 overflow-y-auto grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Historial Oral -->
          <div>
            <h3 class="font-black text-blue-700 text-[11px] uppercase mb-4 border-b-2 border-blue-50 pb-2">Historial Oral</h3>
            <div v-for="item in detailData.oral" :key="item.id" class="mb-3 p-4 bg-slate-50 rounded-2xl flex justify-between items-center">
              <div class="text-xs font-bold text-slate-700">{{ item.final_level }} - {{ item.created_at }}</div>
              <div :class="item.final_score >= 60 ? 'text-emerald-600' : 'text-rose-600'" class="text-lg font-black">{{ item.final_score }}</div>
            </div>
          </div>

          <!-- Historial CompTest -->
          <div>
            <h3 class="font-black text-emerald-700 text-[11px] uppercase mb-4 border-b-2 border-emerald-50 pb-2">Historial Computarizado</h3>
            <div v-for="item in detailData.comp" :key="item.id" class="mb-3 p-4 bg-slate-50 rounded-2xl flex justify-between items-center">
              <div class="text-xs font-bold text-slate-700">{{ item.created_at }}</div>
              <div :class="item.score >= 60 ? 'text-emerald-600' : 'text-rose-600'" class="text-lg font-black">{{ item.score }}</div>
            </div>
          </div>

          <!-- 🔥 NUEVA COLUMNA: Historial Modular -->
          <div>
            <h3 class="font-black text-purple-700 text-[11px] uppercase mb-4 border-b-2 border-purple-50 pb-2">Historial Modular</h3>
            <div v-for="item in detailData.modular" :key="item.id" class="mb-3 p-4 bg-slate-50 rounded-2xl flex justify-between items-center">
              <div class="text-xs font-bold text-slate-700">{{ item.completed_at }}</div>
              <div :class="item.total_percentage >= 60 ? 'text-emerald-600' : 'text-rose-600'" class="text-lg font-black">{{ item.total_percentage }}%</div>
            </div>
          </div>
        </div>

        <div class="p-6 bg-slate-50 border-t flex justify-center">
          <button @click="showModal = false" class="px-10 py-3 bg-slate-900 text-white rounded-2xl font-black text-sm hover:scale-95 transition-transform">
            CERRAR VENTANA
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

// ESTADOS
const records = ref([]);
const search = ref('');
const loading = ref(true);
const showModal = ref(false);
const selectedStudent = ref(null);
const detailData = ref({ oral: [], comp: [], modular: [] });

const dateRange = ref({
  since: '',
  until: ''
});

// REGLAS DE NEGOCIO
const parseHistory = (h) => h ? h.split(',') : [];

const isOralItemPassed = (nota, nivel) => {
  const n = Number(nota);
  const approvedLevels = ['B2', 'C1', 'C2'];
  return n >= 60 && approvedLevels.includes(nivel);
};

const isApproved = (row) => {
  const orales = parseHistory(row.oral_history).map(Number);
  const niveles = parseHistory(row.level_history);
  const comps = parseHistory(row.comp_history).map(Number);
  const modulares = parseHistory(row.modular_history).map(Number);
  const topLevels = ['B2', 'C1', 'C2'];
  
  const hasPassedOral = orales.some((n, i) => n >= 60 && topLevels.includes(niveles[i]));
  const hasPassedComp = comps.some(n => n >= 60);
  const hasPassedModular = modulares.some(n => n >= 60);

  // Puedes ajustar la lógica: requiere oral + (comp O modular)
  return hasPassedOral && (hasPassedComp || hasPassedModular);
};

// LLAMADAS API
const fetchReports = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/v1/reports/student-reports');
    records.value = response.data;
  } catch (error) {
    console.error("Error al cargar registros:", error);
  } finally {
    loading.value = false;
  }
};

const verDetalle = async (row) => {
  selectedStudent.value = row;
  try {
    const response = await axios.get(`/api/v1/reports/student-reports/${row.student_id}`, {
      params: { language_id: row.language_id }
    });
    detailData.value = response.data;
    showModal.value = true;
  } catch (error) {
    alert("No se pudo cargar el historial del estudiante. Verifica que la ruta /student-reports/{id} exista en el controlador.");
  }
};

const descargarPdfGeneral = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/v1/reports/general', { 
      params: {
        since: dateRange.value.since,
        until: dateRange.value.until
      },
      responseType: 'blob' 
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    const fileName = `Reporte_EMI_${dateRange.value.since || 'completo'}.pdf`;
    link.setAttribute('download', fileName);
    
    link.click();
    window.URL.revokeObjectURL(url);
 } catch (error) {
    console.error("Error capturado:", error);

    if (error.response && error.response.data instanceof Blob) {
      const reader = new FileReader();
      reader.onload = () => {
        const errorDetail = JSON.parse(reader.result);
        Swal.fire({
          icon: 'error',
          title: 'Detalle del Error',
          text: errorDetail.message || 'Error desconocido en el servidor'
        });
      };
      reader.readAsText(error.response.data);
    } else {
      Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
    }
  } finally {
    loading.value = false;
  }
};

// FILTRO Y ESTILOS
const filteredData = computed(() => {
  const q = search.value.toLowerCase().trim();
  if (!q) return records.value;
  return records.value.filter(r => 
    r.full_name.toLowerCase().includes(q) || 
    (r.career_name && r.career_name.toLowerCase().includes(q))
  );
});

const langBadgeClass = (lang) => {
  const name = lang?.toLowerCase() || '';
  if (name.includes('ing')) return 'bg-blue-50 text-blue-700 border-blue-100';
  if (name.includes('fra')) return 'bg-rose-50 text-rose-700 border-rose-100';
  if (name.includes('por')) return 'bg-emerald-50 text-emerald-700 border-emerald-100';
  if (name.includes('port')) return 'bg-emerald-50 text-emerald-700 border-emerald-100';
  return 'bg-slate-50 text-slate-700 border-slate-100';
};

onMounted(fetchReports);
</script>

<style scoped>
.flex-col { align-items: center; }
</style>