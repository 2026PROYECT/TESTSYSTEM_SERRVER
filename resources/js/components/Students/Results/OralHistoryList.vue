<template>
  <div class="main-history-container min-h-screen bg-gray-50/50 p-6 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">
      
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-gray-800 tracking-tight">Mis Intentos Orales</h1>
          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Historial acumulado de evaluaciones</p>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="isLoading" class="p-20 text-center text-gray-400">
          <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-4"></div>
          <p class="font-bold text-xs uppercase tracking-widest">Consultando base de datos...</p>
        </div>

        <div v-else-if="attempts.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
              <tr>
                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Idioma</th>
                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha del Examen</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Nivel Certificado</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Puntaje (%)</th>
                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Estado de Asistencia</th>
                <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 bg-white">
              <tr v-for="test in attempts" :key="test.id" class="hover:bg-gray-50/50 transition-colors group">
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm font-bold text-gray-700">
                    {{ test.language_name }}
                  </span>
                 </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600">
                  {{ test.date }}
                 </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <span class="text-sm font-black text-gray-900 bg-gray-100 px-3 py-1 rounded-lg">
                    {{ test.level }}
                  </span>
                 </td>
                <td class="px-6 py-4 text-center whitespace-nowrap font-mono font-bold text-indigo-600">
                  {{ test.score ? test.score + '%' : '--' }}
                 </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <span 
                    :class="statusClass(test)" 
                    class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all shadow-sm"
                  >
                    {{ test.attended ? 'Presentado' : 'No Presentado' }}
                  </span>
                 </td>
                <td class="px-6 py-4 text-right whitespace-nowrap">
                  <router-link 
                    v-if="test.attended"
                    :to="{ name: 'student.results.oral', params: { id: test.id } }" 
                    class="bg-indigo-600 text-white px-5 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100"
                  >
                    Ver Detalles
                  </router-link>
                  <span v-else class="text-[10px] font-bold text-gray-300 uppercase italic">Falta Injustificada</span>
                 </td>
               </tr>
            </tbody>
           </table>
        </div>

        <div v-else class="p-20 text-center">
          <div class="text-6xl mb-4">📂</div>
          <h3 class="text-xl font-black text-gray-800 tracking-tight">Sin registros de exámenes orales</h3>
          <p class="text-gray-400 text-sm font-medium">Tus intentos orales aparecerán aquí una vez que el docente registre tu evaluación.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const attempts = ref([]);
const isLoading = ref(true);

/**
 * Define los colores basados en la asistencia
 */
const statusClass = (test) => {
  if (test.attended) return 'bg-emerald-50 text-emerald-600 border-emerald-100';
  return 'bg-rose-50 text-rose-600 border-rose-100'; 
};

/**
 * Obtiene el historial completo (todos los idiomas)
 */
const fetchAttempts = async () => {
  isLoading.value = true;
  
  try {
    const response = await axios.get('/api/v1/student/oral-history');
    attempts.value = response.data.attempts || [];
    console.log('Historial cargado:', attempts.value);
  } catch (error) {
    console.error("Error al cargar historial:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchAttempts);
</script>

<style scoped>
.main-history-container {
  animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>