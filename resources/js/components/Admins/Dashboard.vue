<template>
  <div class="min-h-screen bg-gray-50 p-8 transition-opacity duration-500 antialiased" :class="{ 'opacity-50 pointer-events-none': isLoading }">
    <header class="mb-8 flex justify-between items-end">
      <div>
        <h1 class="text-4xl font-bold text-indigo-950 tracking-tight">Panel de Administración</h1>
        <p class="text-gray-500 font-medium mt-1">Datos actualizados en tiempo real.</p>
      </div>
      <button @click="refreshData" 
              class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors uppercase tracking-widest">
        Refrescar Datos ↻
      </button>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div v-for="(stat, index) in stats" :key="index" 
           class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition-all duration-300">
        <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">{{ stat.label }}</span>
        <div class="flex items-end justify-between mt-2">
          <h2 class="text-3xl font-bold text-gray-800">{{ stat.value }}</h2>
          <span :class="stat.trend > 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50'" 
                class="text-xs font-bold px-2 py-1 rounded-lg">
            {{ stat.trend > 0 ? '↑' : '↓' }} {{ Math.abs(stat.trend) }}%
          </span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-8">Exámenes por Día</h3>
        <div class="relative flex items-end justify-between h-56 w-full px-4 border-b border-gray-50">
          <div v-for="(day, index) in weeklyData" :key="index"
               class="relative w-10 bg-indigo-500 rounded-t-xl hover:bg-indigo-400 transition-all duration-300 group cursor-pointer"
               :style="{ height: day.percentage + '%' }">
            <span class="absolute -top-8 left-1/2 -translate-x-1/2 text-xs font-bold text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity">
              {{ day.count }}
            </span>
          </div>
        </div>
        <div class="flex justify-between mt-4 px-4 text-[11px] font-bold text-gray-400 uppercase tracking-tighter">
          <span v-for="day in weeklyData" :key="day.name">{{ day.name }}</span>
        </div>
      </div>

      <div class="bg-indigo-900 p-8 rounded-3xl shadow-xl text-white">
        <h3 class="text-xl font-bold mb-6 italic border-b border-indigo-800 pb-4">Acciones Rápidas</h3>
        <div class="space-y-4">
          <button @click="generateExam" 
                  class="w-full bg-white text-indigo-900 font-bold py-4 rounded-2xl hover:bg-indigo-50 active:scale-95 transition-all shadow-lg uppercase text-sm tracking-wide">
            Nuevo Examen +
          </button>
          
          <div class="mt-8 pt-8 border-t border-indigo-800/50">
            <h4 class="text-[10px] font-bold uppercase text-indigo-300 mb-4 tracking-widest">Logs del Sistema</h4>
            <ul class="text-sm space-y-3">
              <li v-for="(log, i) in logs" :key="i" class="flex items-center gap-3 opacity-90">
                <span class="w-2 h-2 rounded-full shrink-0" :class="log.type === 'success' ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                <span class="font-medium text-indigo-100">{{ log.msg }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const isLoading = ref(true);
const stats = ref([]);
const weeklyData = ref([]);
const logs = ref([]);

const loadDashboard = async () => {
    isLoading.value = true;
    try {
        const res = await axios.get('/api/v1/teacher/dashboard');
        
        // Sincronizamos con los datos reales o defaults
        stats.value = res.data.stats || [
            { label: 'Total Estudiantes', value: '120', trend: 12 },
            { label: 'Exámenes Hoy', value: '5', trend: -2 },
            { label: 'Aprobados', value: '88%', trend: 4 },
            { label: 'Pendientes', value: '14', trend: 0 }
        ];

        weeklyData.value = res.data.weeklyData || [
            { name: 'Lun', percentage: 40, count: 12 },
            { name: 'Mar', percentage: 70, count: 25 },
            { name: 'Mie', percentage: 50, count: 18 },
            { name: 'Jue', percentage: 90, count: 32 },
            { name: 'Vie', percentage: 60, count: 20 }
        ];

        logs.value = res.data.logs || [
            { msg: "Sistema iniciado correctamente", type: "success" }
        ];
        
    } catch (error) {
        console.error("Error al cargar dashboard:", error);
        logs.value.push({ msg: "Fallo de conexión con la API", type: "error" });
    } finally {
        setTimeout(() => { isLoading.value = false; }, 300); // Pequeño delay para suavidad
    }
};

const refreshData = () => loadDashboard();
const generateExam = () => console.log("Navegando a nuevo examen...");

onMounted(loadDashboard);
</script>

<style scoped>
/* Esto obliga al navegador a renderizar las fuentes con un trazo más fino */
div {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
}

/* Opcional: Si quieres que todo el dashboard use una fuente más limpia */
:deep(h1), :deep(h2), :deep(h3), :deep(span) {
  letter-spacing: -0.02em; /* Hace que los títulos se vean más compactos y modernos */
}
</style>