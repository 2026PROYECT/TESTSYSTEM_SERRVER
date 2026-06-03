<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-6">
    <!-- Loading -->
    <div v-if="loading" class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
      <p class="mt-4 text-slate-600 font-medium tracking-tight">Verificando en EmiSystem...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="max-w-sm w-full bg-white rounded-[2.5rem] shadow-2xl p-10 text-center border border-red-100">
      <div class="text-6xl mb-6">⚠️</div>
      <h2 class="text-xl font-black text-slate-800 uppercase italic">Código no válido</h2>
      <p class="text-slate-500 mt-2 font-medium">Este registro no existe en nuestra base de datos oficial o ha sido revocado.</p>
      <button @click="reintentar" class="mt-6 px-6 py-2 bg-slate-800 text-white rounded-full text-xs font-bold uppercase tracking-widest">Reintentar</button>
    </div>

    <!-- Datos -->
    <div v-else-if="data" class="max-w-sm w-full bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
      
      <!-- Header -->
      <div :class="[colorClasses[data.header?.color] || 'bg-slate-600', 'p-8 text-center relative']">
        <div class="absolute top-4 right-6 opacity-20 text-white text-4xl">
          <i :class="['fas', data.header?.icon || 'fa-check-circle']"></i>
        </div>
        <div class="bg-white p-3 rounded-2xl shadow-xl inline-block mb-4">
          <img src="/logo.png" alt="Logo EMI" class="h-10 w-auto">
        </div>
        <h1 class="text-white font-black uppercase tracking-tighter text-lg leading-tight">
          {{ data.header?.title || 'Documento Verificado' }}
        </h1>
      </div>

      <!-- Cuerpo -->
      <div class="p-8">
        <!-- Tipo de documento -->
        <div class="flex justify-center mb-6">
          <span :class="['px-4 py-1.5 rounded-full text-[10px] font-black uppercase border tracking-widest shadow-sm', typeLabels[data.type]?.class || 'bg-gray-100']">
            {{ typeLabels[data.type]?.text || 'Documento Oficial' }}
          </span>
        </div>

        <!-- ==================== REPORTES GENERALES ==================== -->
        
        <!-- REPORTE GENERAL MODULAR -->
        <div v-if="data.type === 'MODULAR_GENERAL_REPORT'">
          <div v-if="data.students_list && data.students_list.length > 0">
            <h3 class="text-sm font-black text-slate-800 uppercase mb-4 border-b border-slate-200 pb-2">
              📋 Estudiantes ({{ data.students_list.length }})
            </h3>
            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
              <div v-for="(student, idx) in data.students_list" :key="idx"
                   class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <p class="font-black text-slate-800 text-sm">{{ student.full_name }}</p>
                <p class="text-xs text-slate-500 mt-1">
                  <span class="font-bold">Carnet:</span> {{ student.id_number || 'N/D' }} | 
                  <span class="font-bold">Idioma:</span> {{ student.language || 'N/A' }} |
                  <span class="font-bold">Porcentaje:</span> {{ student.percentage }}%
                </p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <p class="text-slate-500">No hay estudiantes en este reporte</p>
          </div>
        </div>

        <!-- REPORTE GENERAL COMP (COMP_GENERAL_REPORT) -->
        <div v-else-if="data.type === 'COMP_GENERAL_REPORT'">
          <div v-if="data.students_list && data.students_list.length > 0">
            <h3 class="text-sm font-black text-slate-800 uppercase mb-4 border-b border-slate-200 pb-2">
              📋 Estudiantes ({{ data.students_list.length }})
            </h3>
            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
              <div v-for="(student, idx) in data.students_list" :key="idx"
                   class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <p class="font-black text-slate-800 text-sm">{{ student.full_name }}</p>
                <p class="text-xs text-slate-500 mt-1">
                  <span class="font-bold">Carnet:</span> {{ student.id_number || 'N/D' }} | 
                  <span class="font-bold">Examen:</span> {{ student.quiz_title || 'N/A' }} |
                  <span class="font-bold">Puntaje:</span> {{ student.score }}%
                </p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <p class="text-slate-500">No hay estudiantes en este reporte</p>
          </div>
        </div>

        <!-- REPORTE GRUPAL GLOBAL / ORAL -->
        <div v-else-if="data.type === 'GLOBAL_REPORT' || data.type === 'ORAL_ONLY_REPORT'">
          <div v-if="data.students_list && data.students_list.length > 0">
            <h3 class="text-sm font-black text-slate-800 uppercase mb-4 border-b border-slate-200 pb-2">
              📋 Estudiantes ({{ data.students_list.length }})
            </h3>
            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
              <div v-for="(student, idx) in data.students_list" :key="idx"
                   class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <p class="font-black text-slate-800 text-sm">{{ student.full_name || student.name + ' ' + student.lastname }}</p>
                <p class="text-xs text-slate-500 mt-1">
                  <span class="font-bold">Carnet:</span> {{ student.id_card || 'N/D' }} | 
                  <span class="font-bold">Carrera:</span> {{ student.career || 'N/A' }}
                </p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <p class="text-slate-500">No hay estudiantes en este reporte</p>
          </div>
        </div>

        <!-- ==================== EXÁMENES INDIVIDUALES ==================== -->

        <!-- COMP_EXAM (individual) -->
        <div v-else-if="data.type === 'COMP_EXAM'" class="text-center mb-8">
          <h2 class="text-xl font-black text-slate-800 uppercase leading-tight tracking-tight">
            {{ data.student?.full_name }}
          </h2>
          <p class="text-indigo-600 text-[11px] font-bold uppercase tracking-widest mt-2 px-4 py-1 bg-indigo-50 rounded-lg inline-block">
            {{ data.student?.career }}
          </p>
        </div>

        <!-- MODULAR_EXAM (individual) -->
        <div v-else-if="data.type === 'MODULAR_EXAM'" class="text-center mb-8">
          <h2 class="text-xl font-black text-slate-800 uppercase leading-tight tracking-tight">
            {{ data.student?.full_name }}
          </h2>
          <p class="text-indigo-600 text-[11px] font-bold uppercase tracking-widest mt-2 px-4 py-1 bg-indigo-50 rounded-lg inline-block">
            {{ data.student?.career }}
          </p>
        </div>

        <!-- ORAL_EXAM (individual) -->
        <div v-else-if="data.type === 'ORAL_EXAM'" class="text-center mb-8">
          <div class="mb-3 text-indigo-100">
            <i class="fas fa-microphone-alt text-5xl text-indigo-300"></i>
          </div>
          <h2 class="text-xl font-black text-slate-800 uppercase leading-tight tracking-tight">
            {{ data.student?.full_name }}
          </h2>
          <p class="text-indigo-600 text-[11px] font-bold uppercase tracking-widest mt-2 px-4 py-1 bg-indigo-50 rounded-lg inline-block">
            {{ data.student?.career }}
          </p>
        </div>

        <!-- Stats (notas, niveles, etc) -->
        <div class="space-y-3 mb-8" v-if="data.stats && data.stats.length">
          <div v-for="(stat, idx) in data.stats" :key="idx" 
               class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl border border-slate-100 group hover:bg-white hover:border-indigo-200 transition-colors shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ stat.label }}</span>
            <span class="text-slate-800 font-black text-sm tracking-tight">{{ stat.value }}</span>
          </div>
        </div>

        <!-- Meta información -->
        <div class="pt-6 border-t border-dashed border-slate-200">
          <div v-for="(meta, mIdx) in data.meta" :key="mIdx" 
               class="flex justify-between text-[9px] mb-2 last:mb-0 uppercase tracking-widest">
            <span class="text-slate-400 font-bold">{{ meta.label }}</span>
            <span class="text-slate-700 font-black">{{ meta.value }}</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-5 bg-slate-900 text-center">
        <p class="text-[8px] text-slate-400 font-black uppercase tracking-[0.2em]">
          {{ data.footer || 'EmiSystem • Verificación de Integridad Académica' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const error = ref(false);
const data = ref(null);

const colorClasses = {
  indigo:  'bg-indigo-600',
  blue:    'bg-blue-700',
  emerald: 'bg-emerald-600',
  rose:    'bg-rose-600'
};

const typeLabels = {
  // Exámenes individuales
  'COMP_EXAM': { 
    text: 'Examen de Competencia Computarizado', 
    class: 'bg-blue-50 text-blue-700 border-blue-200' 
  },
  'MODULAR_EXAM': { 
    text: 'Examen Modular', 
    class: 'bg-blue-50 text-blue-700 border-blue-200' 
  },
  'ORAL_EXAM': { 
    text: 'Evaluación de Suficiencia Oral', 
    class: 'bg-emerald-50 text-emerald-700 border-emerald-200' 
  },
  // Reportes generales
  'COMP_GENERAL_REPORT': { 
    text: 'Reporte Consolidado Computarizado', 
    class: 'bg-indigo-50 text-indigo-700 border-indigo-200' 
  },
  'MODULAR_GENERAL_REPORT': { 
    text: 'Reporte Consolidado Modular', 
    class: 'bg-indigo-50 text-indigo-700 border-indigo-200' 
  },
  'GLOBAL_REPORT': { 
    text: 'Consolidado Histórico Grupal', 
    class: 'bg-indigo-50 text-indigo-700 border-indigo-200' 
  },
  'ORAL_ONLY_REPORT': { 
    text: 'Consolidado de Evaluaciones Orales', 
    class: 'bg-purple-50 text-purple-700 border-purple-200' 
  }
};

const fetchData = async () => {
  try {
    loading.value = true;
    error.value = false;
    const uuid = route.params.uuid;
    const response = await axios.get(`/api/v1/verify/${uuid}`);
    data.value = response.data;
    console.log('Datos recibidos:', data.value);
  } catch (err) {
    console.error("Error al verificar QR:", err);
    error.value = true;
  } finally {
    loading.value = false;
  }
};

const reintentar = () => {
  fetchData();
};

onMounted(fetchData);
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>