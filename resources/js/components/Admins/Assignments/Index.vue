<template>
  <div class="min-h-screen bg-gray-50/50 p-6 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">Quiz Access Control</h1>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">View test authorizations</p>
          </div>
          
          <div :class="langColorClass" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-sm">
            <span class="flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
              {{ currentLanguageName }}
            </span>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input v-model="search" type="text" placeholder="Filter student..." 
              class="bg-white border-none rounded-2xl px-4 py-2 pl-10 shadow-sm focus:ring-2 focus:ring-indigo-400 w-full md:w-64 text-sm font-medium" />
          </div>

          <button @click="downloadReport" class="flex items-center gap-2 bg-white text-gray-600 px-4 py-2 rounded-2xl font-bold shadow-sm border border-gray-100 hover:bg-gray-50 transition-all text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>PDF Report</span>
          </button>

          <router-link :to="{ name: 'assignments.create' }" class="bg-indigo-600 text-white px-6 py-2 rounded-2xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all whitespace-nowrap">
            + Activate Student
          </router-link>
        </div>
      </div>

      <!-- Loader -->
      <div v-if="isLoading && !assignments?.data?.length" class="bg-white rounded-3xl shadow-sm border border-gray-100 p-20 text-center text-gray-400">
        <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-4"></div>
        <p class="font-bold text-xs uppercase tracking-widest">Loading data...</p>
      </div>

      <!-- Tabla de datos -->
      <div v-else-if="assignments?.data?.length > 0" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
              <tr>
                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Student Info</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Test Modality</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Access Status</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Scheduled Date & Time</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 bg-white">
              <tr v-for="asig in assignments.data" :key="asig.id" class="hover:bg-gray-50/50 transition-colors">
                
                <!-- Student Info -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-black text-xs shadow-sm">
                      {{ getInitials(asig.student?.name, asig.student?.lastname, asig.student?.surname) }}
                    </div>
                    <div>
                      <div class="text-sm font-bold text-gray-900 leading-none mb-1">
                        {{ getFullName(asig.student) }}
                      </div>
                      <div class="text-[10px] text-gray-400 font-mono tracking-tighter">{{ asig.student?.email }}</div>
                    </div>
                  </div>
                </td>

                <!-- Test Modality (SOLO LECTURA) -->
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <div class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border"
                    :class="asig.test_type === 'OralTest' ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-indigo-50 text-indigo-600 border-indigo-100'">
                    <span class="mr-1.5 text-sm">{{ asig.test_type === 'OralTest' ? '🗣️' : '💻' }}</span>
                    {{ asig.test_type === 'OralTest' ? 'Oral Exam' : (asig.test_type === 'ModularTest' ? 'Modular Test' : 'Comp Exam') }}
                  </div>
                </td>

                <!-- Access Status (SOLO LECTURA) -->
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <div class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border-2 inline-block"
                    :class="asig.is_unlocked ? 'bg-emerald-500 text-white border-emerald-100' : 'bg-gray-200 text-gray-500 border-gray-100'">
                    {{ asig.is_unlocked ? 'Authorized' : 'Locked' }}
                  </div>
                </td>

                <!-- Scheduled Date & Time -->
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <div class="inline-flex flex-col items-center bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                    <span class="text-[10px] font-bold text-indigo-500">
                      {{ formatDateTime(asig.start_at) }}
                    </span>
                  </div>
                </td>

                <!-- Oral Status (Aprobado/Pendiente) -->
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <div class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase inline-block"
                    :class="asig.test_type === 'OralTest' ? (asig.passed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') : 'bg-gray-100 text-gray-500'">
                    <span v-if="asig.test_type === 'OralTest'">
                      {{ asig.passed ? '✅ Approved' : '📝 Pending' }}
                    </span>
                    <span v-else>
                      {{ asig.passed ? '✅ Passed' : '⏳ Waiting' }}
                    </span>
                  </div>
                </td>

                <!-- Actions (SOLO VER Y ELIMINAR - sin edición de tipo ni status) -->
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <div class="flex justify-center gap-2">
                    <router-link :to="{ name: 'assignments.edit', params: { id: asig.id } }" 
                                 class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all"
                                 title="View/Edit Details">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    </router-link>
                    <button @click="handleDelete(asig.id)" 
                            class="p-2 text-gray-300 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all"
                            title="Delete">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Paginación -->
          <div class="p-6 bg-gray-50/30 border-t border-gray-100 flex justify-center">
            <TailwindPagination :data="assignments" @pagination-change-page="getAssignments" :limit="1" class="shadow-sm rounded-xl overflow-hidden" />
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white rounded-3xl shadow-sm border border-gray-100 p-20 text-center">
        <div class="text-6xl mb-4">🎫</div>
        <h3 class="text-xl font-black text-gray-800 tracking-tight">Access List is Empty</h3>
        <p class="text-gray-400 text-sm font-medium">Authorizations will appear here once you activate a student.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, computed } from 'vue';
import useQuizAssignment from '@/composables/quizassignment';
import { TailwindPagination } from 'laravel-vue-pagination';
import Swal from 'sweetalert2';
import axios from 'axios';

// ==================== ESTADOS ====================
const { assignments, getAssignments, destroyAssignment, isLoading } = useQuizAssignment();
const search = ref('');
const languages = ref([]);

// ==================== FUNCIONES AUXILIARES ====================
const getFullName = (student) => {
  if (!student) return 'N/A';
  const parts = [
    student.name || '',
    student.lastname || '',
    student.surname || ''
  ];
  return parts.filter(p => p).join(' ').trim() || 'N/A';
};

const getInitials = (name, lastname, surname) => {
  const first = name?.charAt(0) || '';
  const second = lastname?.charAt(0) || '';
  const third = surname?.charAt(0) || '';
  return (first + second + third).toUpperCase().substring(0, 2);
};

const formatDateTime = (dateString) => {
  if (!dateString) return '--/--/---- --:--';
  const date = new Date(dateString);
  const day = date.getDate().toString().padStart(2, '0');
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const year = date.getFullYear();
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');
  return `${day}/${month}/${year} ${hours}:${minutes}`;
};

// ==================== IDIOMA ====================
const currentLanguageName = computed(() => {
  const langId = localStorage.getItem('active_lang_id') || '1';
  if (languages.value.length === 0) return langId == '1' ? 'Inglés' : 'Cargando...';
  const lang = languages.value.find(l => Number(l.id) === Number(langId));
  return lang ? lang.name : 'Inglés';
});

const langColorClass = computed(() => {
  const name = currentLanguageName.value.toLowerCase();
  if (name.includes('ing')) return 'bg-blue-100 text-blue-700 border-blue-200';
  if (name.includes('fra')) return 'bg-rose-100 text-rose-700 border-rose-200';
  if (name.includes('por')) return 'bg-emerald-100 text-emerald-700 border-emerald-200';
  return 'bg-gray-100 text-gray-700 border-gray-200';
});

// ==================== ELIMINAR ====================
const handleDelete = async (id) => {
  const result = await Swal.fire({
    title: '¿Eliminar acceso?',
    text: "Esta acción es irreversible para este registro.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (result.isConfirmed) {
    Swal.fire({
      title: 'Eliminando...',
      text: 'Por favor espere',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    try {
      await axios.delete(`/api/v1/quiz-assignments/${id}`);
      const activeLangId = localStorage.getItem('active_lang_id') || '1';
      await getAssignments(1, search.value, activeLangId);
      
      Swal.fire({
        icon: 'success',
        title: 'Eliminado',
        text: 'Registro eliminado correctamente',
        toast: true,
        position: 'top-end',
        timer: 2000,
        showConfirmButton: false
      });
    } catch (error) {
      console.error('Error al eliminar:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.response?.data?.message || 'No se pudo eliminar el registro',
        confirmButtonColor: '#ef4444'
      });
    }
  }
};

// ==================== REPORTE PDF ====================
const downloadReport = async () => {
  try {
    toast('Generando PDF...');
    const response = await axios.get('/api/v1/quiz-assignments/export-pdf', { 
      responseType: 'blob',
      params: { language_id: localStorage.getItem('active_lang_id') }
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Accesos_${currentLanguageName.value}_${new Date().toISOString().slice(0,10)}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    console.error('Error:', e);
    Swal.fire('Error', e.response?.data?.message || 'Fallo al conectar con el servidor de reportes.', 'error');
  }
};

// ==================== TOAST ====================
const toast = (msg) => {
  Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
  }).fire({ icon: 'success', title: msg });
};

// ==================== LOADERS Y WATCHERS ====================
const loadData = async () => {
  const activeLangId = localStorage.getItem('active_lang_id') || '1';
  await getAssignments(1, search.value, activeLangId);
};

onMounted(async () => {
  if (!localStorage.getItem('active_lang_id')) {
    localStorage.setItem('active_lang_id', '1');
  }
  
  await loadData();

  try {
    const res = await axios.get('/api/v1/languages');
    languages.value = res.data || [];
  } catch (e) {
    console.error("Error al cargar departamentos:", e);
  }
});

watch(search, () => {
  loadData();
});
</script>