<template>
  <div class="p-6 bg-white rounded-xl shadow-sm">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Configuración de Horarios</h2>
        <p class="text-sm text-gray-500 mt-1">Gestiona los horarios disponibles para exámenes Oral y Comp</p>
      </div>
      <div class="flex gap-3">
        <button @click="loadConfigs" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
          🔄 Refrescar
        </button>
        <button @click="saveConfigs" :disabled="saving" 
          class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 disabled:opacity-50">
          {{ saving ? 'Guardando...' : '💾 Guardar Cambios' }}
        </button>
      </div>
    </div>

    <!-- Selector de Tipo de Examen -->
    <div class="mb-8 flex gap-4 border-b pb-4">
      <button 
        @click="changeTestType('OralTest')"
        :class="[
          'px-6 py-3 rounded-xl font-bold transition-all text-sm',
          selectedTestType === 'OralTest' 
            ? 'bg-amber-100 text-amber-700 border-2 border-amber-500' 
            : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
        ]">
        🗣️ Oral Test
      </button>
      <button 
        @click="changeTestType('CompTest')"
        :class="[
          'px-6 py-3 rounded-xl font-bold transition-all text-sm',
          selectedTestType === 'CompTest' 
            ? 'bg-indigo-100 text-indigo-700 border-2 border-indigo-500' 
            : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
        ]">
        💻 Comp Test
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      <p class="ml-3 text-gray-500">Cargando configuración...</p>
    </div>

    <!-- Configuración por Día - SOLO SE MUESTRA CUANDO ESTÁ CARGADO -->
    <div v-else-if="Object.keys(configs).length > 0" class="space-y-4">
      <div v-for="day in daysOfWeek" :key="day.value" 
        class="border rounded-xl p-5 hover:shadow-md transition-all">
        
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <span>{{ day.icon }}</span>
            {{ day.label }}
          </h3>
          <label class="flex items-center gap-2 cursor-pointer">
            <span class="text-sm text-gray-500">Activo</span>
            <input type="checkbox" v-model="configs[day.value].is_active" 
              class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
          </label>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hora Inicio</label>
            <input type="time" v-model="configs[day.value].start_time" 
              :disabled="!configs[day.value].is_active"
              class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-100">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hora Fin</label>
            <input type="time" v-model="configs[day.value].end_time" 
              :disabled="!configs[day.value].is_active"
              class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-100">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Duración (min)</label>
            <input type="number" v-model="configs[day.value].slot_duration" 
              :disabled="!configs[day.value].is_active"
              :step="selectedTestType === 'OralTest' ? 15 : 30"
              min="15"
              class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-100">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Capacidad</label>
            <input type="number" v-model="configs[day.value].capacity" 
              :disabled="!configs[day.value].is_active"
              min="1"
              :max="selectedTestType === 'OralTest' ? 4 : 10"
              class="w-full border rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-100">
          </div>
        </div>

        <!-- Configuración de Pausas/Almuerzo -->
        <div class="mt-5 pt-4 border-t">
          <div class="flex justify-between items-center mb-3">
            <h4 class="text-sm font-bold text-gray-600 flex items-center gap-2">
              🍽️ Pausas / Lunch
            </h4>
            <button 
              @click="addBreak(day.value)" 
              :disabled="!configs[day.value].is_active"
              class="text-indigo-600 text-sm font-bold hover:text-indigo-800 disabled:opacity-50">
              + Agregar Pausa
            </button>
          </div>
          
          <div v-if="!breaks[day.value] || breaks[day.value].length === 0" 
            class="text-center py-3 text-gray-400 text-sm">
            No hay pausas configuradas para este día
          </div>
          
          <div v-for="(breakItem, idx) in breaks[day.value]" :key="idx" 
            class="flex gap-2 items-center mt-2">
            <input type="time" v-model="breakItem.break_start" 
              :disabled="!configs[day.value].is_active"
              class="border rounded-lg p-2 text-sm w-32 focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-100">
            <span class="text-gray-400">→</span>
            <input type="time" v-model="breakItem.break_end" 
              :disabled="!configs[day.value].is_active"
              class="border rounded-lg p-2 text-sm w-32 focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-100">
            <input type="text" v-model="breakItem.description" 
              placeholder="Ej: Almuerzo" 
              :disabled="!configs[day.value].is_active"
              class="border rounded-lg p-2 text-sm flex-1 focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-100">
            <button 
              @click="removeBreak(day.value, idx)" 
              :disabled="!configs[day.value].is_active"
              class="text-red-500 hover:text-red-700 font-bold text-xl disabled:opacity-50">
              ×
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensaje de éxito/error -->
    <div v-if="message" 
      :class="[
        'mt-6 p-4 rounded-xl text-sm font-bold',
        messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
      ]">
      {{ message }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const daysOfWeek = [
  { value: 1, label: 'Lunes', icon: '🌙' },
  { value: 2, label: 'Martes', icon: '🔥' },
  { value: 3, label: 'Miércoles', icon: '💧' },
  { value: 4, label: 'Jueves', icon: '⚡' },
  { value: 5, label: 'Viernes', icon: '⭐' },
  { value: 6, label: 'Sábado', icon: '🎉' },
  { value: 0, label: 'Domingo', icon: '☀️' }
];

const selectedTestType = ref('OralTest');
const configs = ref({});
const breaks = ref({});
const saving = ref(false);
const loading = ref(true);
const message = ref('');
const messageType = ref('success');

const initializeConfigs = () => {
  const initialConfigs = {};
  daysOfWeek.forEach(day => {
    initialConfigs[day.value] = {
      day_of_week: day.value,
      test_type: selectedTestType.value,
      start_time: '08:00',
      end_time: '17:00',
      slot_duration: selectedTestType.value === 'OralTest' ? 30 : 60,
      capacity: selectedTestType.value === 'OralTest' ? 1 : 4,
      is_active: false
    };
  });
  configs.value = initialConfigs;
  
  const initialBreaks = {};
  daysOfWeek.forEach(day => {
    initialBreaks[day.value] = [];
  });
  breaks.value = initialBreaks;
};

const loadConfigs = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/time-slots-config', {
      params: { test_type: selectedTestType.value }
    });
    
    // Asegurar que configs tenga valores iniciales
    if (Object.keys(configs.value).length === 0) {
      initializeConfigs();
    }
    
    // Actualizar configs con los datos del servidor
    daysOfWeek.forEach(day => {
      const existing = response.data.configs?.find(c => c.day_of_week === day.value);
      if (existing) {
        configs.value[day.value] = {
          ...configs.value[day.value],
          ...existing,
          start_time: existing.start_time?.substring(0, 5) || '08:00',
          end_time: existing.end_time?.substring(0, 5) || '17:00'
        };
      }
    });
    
    // Actualizar breaks
    daysOfWeek.forEach(day => {
      const dayBreaks = response.data.breaks?.filter(b => b.day_of_week === day.value) || [];
      breaks.value[day.value] = dayBreaks;
    });
    
  } catch (error) {
    console.error('Error loading configs:', error);
    Swal.fire('Error', 'No se pudo cargar la configuración', 'error');
  } finally {
    loading.value = false;
  }
};

const addBreak = (day) => {
  if (!breaks.value[day]) breaks.value[day] = [];
  breaks.value[day].push({
    break_start: '12:00',
    break_end: '13:00',
    description: 'Almuerzo'
  });
};

const removeBreak = (day, idx) => {
  breaks.value[day].splice(idx, 1);
};

const saveConfigs = async () => {
  saving.value = true;
  message.value = '';
  
  try {
    const configsToSave = Object.values(configs.value).map(config => ({
      day_of_week: config.day_of_week,
      start_time: config.start_time,
      end_time: config.end_time,
      slot_duration: parseInt(config.slot_duration),
      capacity: parseInt(config.capacity),
      is_active: config.is_active
    }));
    
    const breaksToSave = {};
    for (const [day, dayBreaks] of Object.entries(breaks.value)) {
      breaksToSave[day] = dayBreaks.map(b => ({
        break_start: b.break_start,
        break_end: b.break_end,
        description: b.description
      }));
    }
    
    await axios.post('/api/v1/admin/time-slots-config', {
      test_type: selectedTestType.value,
      configs: configsToSave,
      breaks: breaksToSave,
      special_slots: []
    });
    
    message.value = '✅ Configuración guardada exitosamente';
    messageType.value = 'success';
    
    setTimeout(() => {
      message.value = '';
    }, 3000);
    
  } catch (error) {
    console.error('Error saving configs:', error);
    message.value = '❌ Error al guardar: ' + (error.response?.data?.message || 'Error desconocido');
    messageType.value = 'error';
  } finally {
    saving.value = false;
  }
};

const changeTestType = async (type) => {
  selectedTestType.value = type;
  initializeConfigs();
  await loadConfigs();
};

onMounted(() => {
  initializeConfigs();
  loadConfigs();
});
</script>