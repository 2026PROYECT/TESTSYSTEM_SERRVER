<template>
  <div class="p-6 max-w-6xl mx-auto space-y-6">
    <header class="mb-2">
      <h1 class="text-2xl font-bold text-slate-800">Centro de Programación</h1>
      <p class="text-slate-500 text-sm">Reserva tu espacio para el examen computarizado</p>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- FORMULARIO DE PROGRAMACIÓN -->
      <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 sticky top-6">
          <h2 class="text-sm font-bold text-slate-700 uppercase mb-6 tracking-wider">Nueva Cita</h2>
          
          <!-- PROCESO COMPLETADO -->
          <div v-if="processCompleted" class="p-5 bg-green-50 border border-green-100 rounded-2xl text-center">
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <p class="text-green-700 font-bold text-sm uppercase">Proceso Completado</p>
            <p class="text-green-600 text-xs mt-2 leading-relaxed">
              ¡Felicidades! Has completado exitosamente todas las etapas de evaluación.
              <br><br>
              <strong>Etapas completadas:</strong><br>
              ✓ Examen Oral (Aprobado)<br>
              ✓ Examen {{ examType === 'ModularTest' ? 'Modular' : 'Computarizado' }} (Aprobado)
              <br><br>
              Tu Reporte está disponible en el área de descargas.
            </p>
          </div>

          <!-- BLOQUEO POR SANCIÓN -->
          <div v-else-if="sancionUntil" class="p-5 bg-red-50 border border-red-100 rounded-2xl text-center">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <p class="text-red-700 font-bold text-sm uppercase">Acceso Restringido</p>
            <p class="text-red-600 text-[11px] mt-2 leading-relaxed">
              Suspendido hasta: <br>
              <span class="text-base font-black">{{ formatDateSancion(sancionUntil) }}</span>
            </p>
          </div>

          <!-- BLOQUEO POR CITA ACTIVA PENDIENTE -->
          <div v-else-if="pendingBooking" class="p-5 bg-amber-50 border border-amber-100 rounded-2xl text-center">
            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <p class="text-amber-700 font-bold text-sm uppercase">Cita Pendiente</p>
            <p class="text-amber-600 text-xs mt-2">
              Tienes un examen programado para:
            </p>
            <p class="text-amber-800 font-bold text-sm mt-1">
              {{ formatDateTime(pendingBooking.start_at) }}
            </p>
            <p class="text-amber-600 text-[11px] mt-3">
              Debes completar este examen antes de poder reservar uno nuevo.
              <br>El resultado será validado por el administrador.
            </p>
          </div>

          <!-- FORMULARIO HABILITADO (solo cuando no hay cita pendiente Y proceso no completado) -->
          <div v-else class="space-y-5">
            <!-- Idioma fijo -->
            <div>
              <label class="text-[10px] font-extrabold text-slate-500 uppercase block mb-1">Idioma</label>
              <div class="w-full bg-slate-100 border border-slate-200 rounded-xl text-sm p-3 font-semibold text-slate-700">
                🇬🇧 {{ currentLanguage?.name || 'English' }}
              </div>
            </div>

            <!-- Tipo de Evaluación dinámico -->
            <div>
              <label class="text-[10px] font-extrabold text-slate-500 uppercase block mb-1">Tipo de Evaluación</label>
              <div class="w-full bg-slate-100 border border-slate-200 rounded-xl text-sm p-3 font-semibold text-slate-700">
                <span v-if="examType === 'ModularTest'">📚 Examen Modular</span>
                <span v-else>💻 Examen Computarizado</span>
              </div>
            </div>

            <!-- Selección de Fecha -->
            <div>
              <label class="text-[10px] font-bold text-slate-400 uppercase block mb-2">Fecha del Examen</label>
              <input 
                type="date" 
                v-model="selectedDate" 
                @change="fetchSlots" 
                :min="minDate" 
                class="w-full border-slate-200 rounded-xl text-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
              />
            </div>

            <!-- Horarios -->
            <div v-if="selectedDate">
              <div v-if="loadingSlots" class="flex justify-center py-6">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                <span class="ml-2 text-xs text-gray-500">Cargando horarios...</span>
              </div>
              
              <div v-else>
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-2">
                  Horarios Disponibles
                </label>
                
                <div v-if="availableSlots.length === 0 && !loadingSlots" class="text-center py-6 text-gray-400 text-xs bg-slate-50 rounded-xl">
                  No hay horarios disponibles para esta fecha
                </div>
                
                <div v-else class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
                  <button 
                    v-for="slot in availableSlots" 
                    :key="slot.id" 
                    @click="selectTimeSlot(slot)"
                    :disabled="!slot.is_available"
                    type="button"
                    :class="[
                      'py-2 px-2 rounded-lg text-xs font-bold transition-all border',
                      selectedTimeSlot === slot.time ? 'bg-indigo-600 border-indigo-600 text-white shadow-md' : 
                      slot.is_available ? 'bg-white border-slate-200 text-slate-600 hover:border-indigo-400 cursor-pointer' : 
                      'bg-slate-100 border-slate-100 text-slate-300 cursor-not-allowed'
                    ]">
                    {{ slot.label }}
                  </button>
                </div>
              </div>
            </div>

            <button 
              @click="submitBooking" 
              :disabled="loading || !selectedTimeSlot || !selectedDate" 
              class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold text-sm shadow-lg hover:bg-indigo-700 transition disabled:opacity-50">
              {{ loading ? 'Procesando...' : 'Reservar Cupo' }}
            </button>
          </div>
        </div>
      </div>

      <!-- TABLA DE HISTORIAL -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">
              Mi Progreso - {{ currentLanguage?.name || 'English' }}
            </h2>
            <p class="text-xs text-slate-500 mt-1">Los resultados son validados por el administrador</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50/30 text-[10px] font-bold text-slate-400 uppercase border-b border-slate-100">
                <tr>
                  <th class="px-6 py-4">Tipo de Examen</th>
                  <th class="px-6 py-4">Fecha y Hora</th>
                  <th class="px-6 py-4 text-center">Estado</th>
                  <th class="px-6 py-4 text-center">Resultado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="filteredAppointments.length === 0">
                  <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">
                    No hay exámenes registrados
                  </td>
                </tr>
                <tr v-for="item in filteredAppointments" :key="item.id">
                  <td class="px-6 py-4 font-semibold">
                    <span v-if="item.test_type === 'OralTest'">🗣️ Examen Oral</span>
                    <span v-else-if="item.test_type === 'ModularTest'">📚 Examen Modular</span>
                    <span v-else>💻 Examen Computarizado</span>
                  </td>
                  <td class="px-6 py-4 text-xs">
                    {{ formatDateTime(item.start_at || item.created_at) }}
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span :class="getStatusClass(item)" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                      {{ getStatusText(item) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span v-if="!item.active" :class="getResultClass(item)" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                      {{ getResultText(item) }}
                    </span>
                    <span v-else class="text-amber-500 text-[10px]">
                      Pendiente de validación
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Información adicional sobre el proceso -->
        <div class="mt-4 bg-blue-50 border border-blue-100 rounded-xl p-4">
          <div class="flex items-start gap-3">
            <div class="text-blue-600 mt-0.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="text-xs text-blue-800">
              <p class="font-semibold mb-1">¿Cómo funciona?</p>
              <ul class="list-disc list-inside space-y-1 text-blue-700">
                <li>Tu examen será habilitado por el administrador en la fecha y hora programada</li>
                <li>Recibirás acceso al laboratorio en el horario seleccionado</li>
                <li>El resultado será validado por el administrador después de completar el examen</li>
                <li>No puedes reservar un nuevo examen mientras tengas uno pendiente</li>
                <li><strong>Una vez aprobadas ambas etapas (Oral + {{ examType === 'ModularTest' ? 'Modular' : 'Computarizado' }}), el proceso finaliza</strong></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_BASE_URL = '/api/v1';

// Configurar axios
const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// ==================== ESTADO ====================
const loading = ref(false);
const loadingSlots = ref(false);
const appointments = ref([]);
const currentLanguage = ref(null);
const languageId = ref(null);
const pendingBooking = ref(null);
const sancionUntil = ref(null);
const examType = ref('CompTest');
const selectedQuizId = ref(null);
const processCompleted = ref(false); // NUEVO: indica si ya completó todo

const selectedDate = ref('');
const selectedTimeSlot = ref('');
const availableSlots = ref([]);
const minDate = ref(new Date().toISOString().split('T')[0]);

// ==================== COMPUTED ====================
const filteredAppointments = computed(() => {
  if (!languageId.value) return [];
  return appointments.value
    .filter(app => app.language_id === languageId.value)
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

// ==================== FUNCIONES ====================
const formatDateSancion = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('es-ES', { 
    day: '2-digit', 
    month: 'long', 
    year: 'numeric' 
  });
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusClass = (item) => {
  if (item.active) return 'bg-amber-100 text-amber-700';
  if (item.passed) return 'bg-green-100 text-green-700';
  return 'bg-red-100 text-red-700';
};

const getStatusText = (item) => {
  if (item.active) return 'Pendiente';
  if (item.passed) return 'Completado';
  return 'Reprobado';
};

const getResultClass = (item) => {
  if (item.passed) return 'bg-green-100 text-green-700';
  return 'bg-red-100 text-red-700';
};

const getResultText = (item) => {
  if (item.passed) return 'Aprobado';
  return 'No Aprobado';
};

const loadData = async () => {
  try {
    const response = await axios.get(`${API_BASE_URL}/student/my-exams`);
    const data = response.data.data || response.data;
    appointments.value = data.appointments || data || [];
    
    console.log('Exámenes cargados:', appointments.value);
    
    // Buscar el idioma donde tiene OralTest aprobado
    const oralApproved = appointments.value.find(app => 
      app.test_type === 'OralTest' && app.passed === true
    );
    
    if (!oralApproved) {
      Swal.fire('Error', 'No tienes ningún examen oral aprobado', 'error');
      return;
    }
    
    languageId.value = oralApproved.language_id;
    currentLanguage.value = oralApproved.language || { name: oralApproved.language_name || 'English' };
    console.log('Idioma encontrado:', currentLanguage.value);
    
    // Determinar qué tipo de examen necesita
    const hasCompTestApproved = appointments.value.some(app => 
      app.language_id === languageId.value && 
      app.test_type === 'CompTest' && 
      app.passed === true
    );
    
    const hasModularTestApproved = appointments.value.some(app => 
      app.language_id === languageId.value && 
      app.test_type === 'ModularTest' && 
      app.passed === true
    );
    
    // Determinar el tipo de examen según la configuración del sistema
    // Puedes cambiar esto según tu lógica de negocio
    const useModularTest = true; // o false según prefieras CompTest o ModularTest
    
    if (useModularTest) {
      examType.value = 'ModularTest';
      if (hasModularTestApproved) {
        // ✅ Ya completó el ModularTest, proceso terminado
        processCompleted.value = true;
        console.log('🎉 PROCESO COMPLETADO: Oral + Modular aprobados');
      } else {
        processCompleted.value = false;
      }
    } else {
      examType.value = 'CompTest';
      if (hasCompTestApproved) {
        // ✅ Ya completó el CompTest, proceso terminado
        processCompleted.value = true;
        console.log('🎉 PROCESO COMPLETADO: Oral + Computarizado aprobados');
      } else {
        processCompleted.value = false;
      }
    }
    
    console.log('Tipo de examen:', examType.value);
    console.log('Proceso completado:', processCompleted.value);
    
    // Buscar cita pendiente (solo si no ha completado el proceso)
    if (!processCompleted.value) {
      pendingBooking.value = appointments.value.find(app => 
        app.language_id === languageId.value && 
        app.test_type === examType.value && 
        app.active === true
      );
      console.log('Cita pendiente:', pendingBooking.value);
    }
    
    await checkSancion();
    
  } catch (error) {
    console.error('Error cargando datos:', error);
    Swal.fire('Error', 'No se pudieron cargar los datos', 'error');
  }
};

const checkSancion = async () => {
  if (!languageId.value) return;
  
  try {
    const response = await axios.get(`${API_BASE_URL}/student/check-sancion`, {
      params: { language_id: languageId.value }
    });
    const data = response.data.data || response.data;
    sancionUntil.value = data.sancion_until || null;
  } catch (error) {
    console.error('Error verificando sanción:', error);
    sancionUntil.value = null;
  }
};

const fetchSlots = async () => {
  if (!selectedDate.value || !languageId.value || processCompleted.value) {
    availableSlots.value = [];
    return;
  }
  
  loadingSlots.value = true;
  
  try {
    const testTypeForSlots = 'CompTest';
    
    const response = await axios.get(`${API_BASE_URL}/student/available-slots`, {
      params: {
        date: selectedDate.value,
        test_type: testTypeForSlots,
        language_id: languageId.value
      }
    });
    
    const data = response.data.data || response.data;
    let slots = [];
    if (data.slots) {
      slots = data.slots;
    } else if (Array.isArray(data)) {
      slots = data;
    } else if (data.data && Array.isArray(data.data)) {
      slots = data.data;
    }
    
    const now = new Date();
    const today = selectedDate.value === new Date().toISOString().split('T')[0];
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();
    
    availableSlots.value = slots.map(slot => {
      let slotHour = 0, slotMinute = 0;
      const timeStr = slot.start_time || slot.time || slot.hour;
      
      if (timeStr) {
        const [hour, minute] = timeStr.toString().split(':').map(Number);
        slotHour = hour || 0;
        slotMinute = minute || 0;
      }
      
      const isPastTime = today && (slotHour < currentHour || (slotHour === currentHour && slotMinute <= currentMinute));
      const isAvailable = !slot.blocked && !isPastTime && (slot.available > 0 || slot.occupied < slot.capacity);
      
      return {
        id: slot.id,
        time: timeStr,
        label: timeStr ? timeStr.substring(0, 5) : 'N/A',
        is_available: isAvailable,
        capacity: slot.capacity || 4,
        occupied: slot.occupied || 0,
        available: slot.available || (slot.capacity - slot.occupied) || 0
      };
    });
    
  } catch (error) {
    console.error('Error cargando horarios:', error);
    availableSlots.value = [];
    Swal.fire('Error', 'No se pudieron cargar los horarios', 'error');
  } finally {
    loadingSlots.value = false;
  }
};

const selectTimeSlot = (slot) => {
  if (slot.is_available) {
    selectedTimeSlot.value = slot.time;
  }
};

const submitBooking = async () => {
  if (processCompleted.value) {
    Swal.fire('Info', 'Ya has completado todas las etapas del proceso', 'info');
    return;
  }
  
  if (!selectedDate.value) {
    Swal.fire('Error', 'Selecciona una fecha', 'error');
    return;
  }
  
  if (!selectedTimeSlot.value) {
    Swal.fire('Error', 'Selecciona un horario', 'error');
    return;
  }
  
  if (pendingBooking.value) {
    Swal.fire('Error', 'Ya tienes una cita pendiente', 'error');
    return;
  }
  
  if (sancionUntil.value) {
    Swal.fire('Error', `Tienes sanción hasta ${formatDateSancion(sancionUntil.value)}`, 'error');
    return;
  }
  
  loading.value = true;
  
  try {
    const testTypeForCheck = 'CompTest';
    
    const availabilityCheck = await axios.get(`${API_BASE_URL}/student/available-slots`, {
      params: {
        date: selectedDate.value,
        test_type: testTypeForCheck,
        language_id: languageId.value
      }
    });
    
    const availableData = availabilityCheck.data.data || availabilityCheck.data;
    let availableSlotsList = [];
    if (availableData.slots) {
      availableSlotsList = availableData.slots;
    } else if (Array.isArray(availableData)) {
      availableSlotsList = availableData;
    }
    
    const slotStillAvailable = availableSlotsList.some(slot => {
      const timeStr = slot.start_time || slot.time || slot.hour;
      const occupied = slot.occupied || 0;
      const capacity = slot.capacity || 4;
      const available = capacity - occupied;
      
      return timeStr === selectedTimeSlot.value && 
             !slot.blocked && 
             available > 0;
    });
    
    if (!slotStillAvailable) {
      await fetchSlots();
      Swal.fire({
        icon: 'warning',
        title: 'Horario no disponible',
        text: 'El horario seleccionado ya no está disponible. Por favor, selecciona otro horario.',
        confirmButtonText: 'OK'
      });
      loading.value = false;
      return;
    }
    
    const payload = {
      language_id: languageId.value,
      test_type: examType.value,
      start_at: `${selectedDate.value} ${selectedTimeSlot.value}`
    };
    
    if (examType.value === 'ModularTest' && selectedQuizId.value) {
      payload.quiz_id = selectedQuizId.value;
    }
    
    const response = await axios.post(`${API_BASE_URL}/student/schedule`, payload);
    
    if (response.status >= 200 && response.status < 300) {
      Swal.fire({
        icon: 'success',
        title: '¡Reservado!',
        text: `Tu examen ${examType.value === 'ModularTest' ? 'Modular' : 'Computarizado'} ha sido agendado para el ${formatDateTime(payload.start_at)}`,
        timer: 2000,
        showConfirmButton: false
      });
      
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    }
    
  } catch (error) {
    console.error('Error al reservar:', error);
    
    let message = 'Error al reservar el cupo';
    if (error.response?.data?.error) {
      message = error.response.data.error;
    } else if (error.response?.data?.message) {
      message = error.response.data.message;
    }
    
    Swal.fire('Error', message, 'error');
    loading.value = false;
  }
};

// ==================== LIFECYCLE ====================
onMounted(async () => {
  await loadData();
});
</script>