<template>
  <div class="min-h-screen bg-gray-50 py-10 px-4">
    <div class="max-w-2xl mx-auto">
      
      <!-- Loader -->
      <div v-if="loading" class="bg-white p-12 rounded-lg shadow text-center">
        <div class="animate-spin inline-block w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full mb-4"></div>
        <p class="text-gray-500">Cargando datos...</p>
      </div>

      <!-- Error -->
      <div v-else-if="errorMessage" class="bg-red-50 p-8 rounded-lg shadow text-center">
        <p class="text-red-600">{{ errorMessage }}</p>
        <button @click="fetchAssignment" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg font-bold">
          Reintentar
        </button>
      </div>

      <!-- Contenido principal -->
      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        
        <!-- Cabecera -->
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 p-6 text-white">
          <div class="flex justify-between items-start">
            <div>
              <h2 class="text-2xl font-bold">
                {{ getTestTypeIcon() }} {{ getTestTypeName() }}
              </h2>
              <p class="text-indigo-200 text-sm mt-1">
                {{ form.test_type === 'OralTest' ? 'FASE 1 - EVALUACIÓN ORAL' : 'FASE 2 - EVALUACIÓN FINAL' }}
              </p>
            </div>
            <div class="flex gap-2">
              <span :class="form.passed ? 'bg-green-500' : 'bg-yellow-500'" 
                    class="px-3 py-1 rounded-full text-xs font-bold">
                {{ form.passed ? '✅ APROBADO' : '📝 PENDIENTE' }}
              </span>
              <span :class="form.active ? 'bg-blue-500' : 'bg-gray-500'" 
                    class="px-3 py-1 rounded-full text-xs font-bold">
                {{ form.active ? '🔓 ACTIVO' : '🔒 INACTIVO' }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-6">
          
          <!-- Información del Estudiante -->
          <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="font-bold text-gray-700 mb-2">📋 Estudiante</h3>
            <p class="font-semibold">{{ form.student?.name }} {{ form.student?.lastname }}</p>
            <p class="text-sm text-gray-600">{{ form.student?.email }}</p>
          </div>

          <!-- ============================================ -->
          <!-- CASO 1: ORAL TEST APROBADO - SIN FASE 2      -->
          <!-- ============================================ -->
          <div v-if="form.test_type === 'OralTest' && form.passed === true && !existingPhase2" 
               class="bg-emerald-50 border-2 border-emerald-200 rounded-lg p-6 text-center mb-6">
            <div class="text-5xl mb-3">🎉</div>
            <h3 class="text-xl font-bold text-emerald-800 mb-2">¡Examen Oral Aprobado!</h3>
            <p class="text-emerald-700 mb-4">
              {{ form.student?.name }} {{ form.student?.lastname }} ha superado la Fase 1 exitosamente.
            </p>
            <div class="bg-white/60 rounded-lg p-4 mb-4">
              <p class="font-bold text-emerald-800">🎯 Próximo paso:</p>
              <p class="text-sm">El sistema creará automáticamente la <strong>Fase 2</strong></p>
              <p class="text-xs text-gray-600">(CompTest o ModularTest - Selección aleatoria)</p>
            </div>
            <button @click="createNextPhase" :disabled="creatingPhase"
                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold transition-all">
              {{ creatingPhase ? '⏳ Creando...' : '🚀 Crear Fase 2' }}
            </button>
          </div>

          <!-- ============================================ -->
          <!-- CASO 2: ORAL TEST APROBADO - CON FASE 2      -->
          <!-- ============================================ -->
          <div v-else-if="form.test_type === 'OralTest' && form.passed === true && existingPhase2" 
               class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center mb-6">
            <div class="text-5xl mb-3">✅</div>
            <h3 class="text-xl font-bold text-blue-800 mb-2">Fase 2 ya creada</h3>
            <p class="text-blue-700 mb-2">
              El estudiante ya tiene un <strong>{{ existingPhase2.test_type }}</strong>.
            </p>
            <p class="text-sm text-blue-600 mb-4">
              {{ existingPhase2.start_at ? `Programado para: ${formatDate(existingPhase2.start_at)}` : 'Aún no programado' }}
            </p>
            <button @click="goToAssignments" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-all">
              Ver listado de asignaciones
            </button>
          </div>

          <!-- ============================================ -->
          <!-- CASO 3: FASE 2 APROBADA - Curso completado   -->
          <!-- ============================================ -->
          <div v-else-if="form.test_type !== 'OralTest' && form.passed === true" 
               class="bg-purple-50 border-2 border-purple-200 rounded-lg p-6 text-center mb-6">
            <div class="text-5xl mb-3">🏆</div>
            <h3 class="text-xl font-bold text-purple-800 mb-2">¡Curso Completado!</h3>
            <p class="text-purple-700 mb-4">El estudiante ha completado todas las fases del curso.</p>
            <div class="bg-white/60 rounded-lg p-3 mb-4 text-sm">
              <p>✅ OralTest aprobado</p>
              <p>✅ {{ form.test_type }} aprobado</p>
            </div>
            <button @click="goToAssignments" 
                    class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold transition-all">
              Ver listado
            </button>
          </div>

          <!-- ============================================ -->
          <!-- CASO 4: EXAMEN NO APROBADO - Formulario       -->
          <!-- ============================================ -->
          <div v-else>
            
            <!-- Fecha actual -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
              <p><strong>📅 Fecha programada actual:</strong> {{ formatDate(form.start_at) }}</p>
              <p class="text-sm mt-1" :class="isDatePassed ? 'text-red-600 font-semibold' : 'text-green-600'">
                {{ isDatePassed ? '⚠️ Esta fecha ya pasó - Debes reprogramar' : '✅ Fecha vigente' }}
              </p>
            </div>

            <!-- Selector de tipo de examen (solo para Fase 2) -->
            <div v-if="form.test_type !== 'OralTest'" class="mb-6">
              <label class="block font-bold text-gray-700 mb-2">🔄 Tipo de Examen</label>
              <div class="grid grid-cols-2 gap-3">
                <button type="button"
                        @click="changeTestType('CompTest')"
                        :class="form.test_type === 'CompTest' ? 'bg-indigo-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                        class="p-3 rounded-lg font-semibold transition-all">
                  💻 CompTest
                </button>
                <button type="button"
                        @click="changeTestType('ModularTest')"
                        :class="form.test_type === 'ModularTest' ? 'bg-indigo-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                        class="p-3 rounded-lg font-semibold transition-all">
                  📦 ModularTest
                </button>
              </div>
              <p class="text-xs text-gray-500 mt-2">
                Puedes cambiar el tipo de examen según sea necesario
              </p>
            </div>

            <!-- Fecha -->
            <div class="mb-6">
              <label class="block font-bold text-gray-700 mb-2">📅 Nueva Fecha</label>
              <input type="date" v-model="selectedDate" :min="minDate" @change="loadAvailableSlots"
                     class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Horarios disponibles -->
            <div v-if="selectedDate" class="mb-6">
              <div v-if="loadingSlots" class="text-center py-8">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full"></div>
                <p class="text-sm text-gray-500 mt-2">Cargando horarios...</p>
              </div>
              
              <div v-else>
                <label class="block font-bold text-gray-700 mb-2">
                  ⏰ Horarios Disponibles
                  <span class="text-sm font-normal text-gray-500">
                    ({{ form.test_type === 'OralTest' ? 'Cada 30 min - 1 cupo' : 'Cada 60 min - 4 cupos' }})
                  </span>
                </label>
                
                <div v-if="availableSlots.length === 0" class="bg-yellow-50 p-4 rounded-lg text-center">
                  <p class="text-yellow-700">No hay horarios disponibles para esta fecha</p>
                  <p class="text-sm text-yellow-600">Prueba con otra fecha</p>
                </div>
                
                <div class="grid grid-cols-4 gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded-lg">
                  <button v-for="slot in availableSlots" :key="slot.time"
                          type="button"
                          @click="selectTimeSlot(slot)"
                          :disabled="!slot.is_available"
                          :class="[
                            'py-2 rounded-lg text-center font-semibold transition-all',
                            selectedTimeSlot === slot.time ? 'bg-indigo-600 text-white' : 
                            slot.is_available ? 'bg-white hover:bg-indigo-100 border border-gray-200 cursor-pointer' : 
                            'bg-gray-200 text-gray-400 cursor-not-allowed'
                          ]">
                    <div>{{ slot.label }}</div>
                    <div class="text-xs">
                      <span v-if="slot.is_available && slot.available !== undefined">
                        🟢 {{ slot.available }}/{{ slot.capacity }}
                      </span>
                      <span v-else-if="!slot.is_available && slot.occupied >= slot.capacity" class="text-red-500">
                        🔴 Lleno
                      </span>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <!-- Estado activo -->
            <div class="mb-6">
              <label class="flex items-center gap-3 cursor-pointer p-4 bg-gray-50 rounded-lg">
                <input type="checkbox" v-model="form.active" class="w-5 h-5">
                <div>
                  <span class="font-bold">🔓 Activo</span>
                  <p class="text-sm text-gray-600">Permite al estudiante acceder al examen</p>
                </div>
              </label>
            </div>

            <!-- Botones de acción -->
            <div class="flex gap-4 mt-8">
              <button @click="updateAssignment" :disabled="processing"
                      class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-bold transition-all disabled:opacity-50">
                {{ processing ? '⏳ Guardando...' : '💾 Guardar Cambios' }}
              </button>
              <button @click="goToAssignments" 
                      class="flex-1 bg-gray-200 hover:bg-gray-300 text-center py-3 rounded-lg font-bold transition-all">
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

// ==================== ESTADO ====================
const loading = ref(true);
const loadingSlots = ref(false);
const processing = ref(false);
const creatingPhase = ref(false);
const errorMessage = ref('');
const existingPhase2 = ref(null);

const selectedDate = ref('');
const selectedTimeSlot = ref('');
const availableSlots = ref([]);

const form = ref({
  id: null,
  student_id: null,
  language_id: 1,
  active: false,
  test_type: '',
  start_at: null,
  student: null,
  passed: false,
  attended: false
});

// ==================== COMPUTED ====================
const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const isDatePassed = computed(() => {
  if (!form.value.start_at) return false;
  return new Date(form.value.start_at) < new Date();
});

const getTestTypeIcon = () => {
  const icons = { OralTest: '🎤', CompTest: '💻', ModularTest: '📦' };
  return icons[form.value.test_type] || '📝';
};

const getTestTypeName = () => {
  const names = { OralTest: 'Examen Oral', CompTest: 'Examen Computarizado', ModularTest: 'Examen Modular' };
  return names[form.value.test_type] || 'Examen';
};

const formatDate = (dateString) => {
  if (!dateString) return 'No programado';
  return new Date(dateString).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// ==================== FUNCIONES ====================

// Redirigir al listado
const goToAssignments = () => {
  window.location.href = '/admin/assignments/index';
};

// Buscar fase 2 existente
const checkExistingPhase2 = async () => {
  try {
    const response = await axios.get('/api/v1/quiz-assignments', {
      params: {
        student_id: form.value.student_id,
        language_id: form.value.language_id
      }
    });
    
    const phase2 = response.data.data.find(a => 
      a.id !== form.value.id && 
      (a.test_type === 'CompTest' || a.test_type === 'ModularTest')
    );
    
    existingPhase2.value = phase2 || null;
  } catch (error) {
    console.error('Error checking phase 2:', error);
  }
};

// Cargar slots disponibles
const loadAvailableSlots = async () => {
  if (!selectedDate.value) return;
  
  loadingSlots.value = true;
  try {
    let testTypeForSlots = form.value.test_type;
    if (form.value.test_type === 'ModularTest') {
      testTypeForSlots = 'CompTest';
    }
    
    const response = await axios.get('/api/v1/admin/get-available-slots', {
      params: {
        date: selectedDate.value,
        test_type: testTypeForSlots,
        language_id: form.value.language_id
      }
    });
    
    if (response.data.status === 'success') {
      availableSlots.value = response.data.slots.map(slot => ({
        ...slot,
        is_available: !slot.blocked && !slot.is_past
      }));
    }
  } catch (error) {
    console.error('Error loading slots:', error);
    availableSlots.value = [];
  } finally {
    loadingSlots.value = false;
  }
};

const selectTimeSlot = (slot) => {
  if (slot.is_available) {
    selectedTimeSlot.value = slot.time;
    form.value.start_at = `${selectedDate.value} ${slot.time}`;
  }
};

// Cambiar tipo de examen (solo para Fase 2)
const changeTestType = async (newType) => {
  if (newType === form.value.test_type) return;
  
  const result = await Swal.fire({
    title: '¿Cambiar tipo de examen?',
    text: `¿Estás seguro de cambiar a ${newType}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, cambiar',
    cancelButtonText: 'Cancelar'
  });
  
  if (result.isConfirmed) {
    form.value.test_type = newType;
    selectedTimeSlot.value = '';
    form.value.start_at = null;
    if (selectedDate.value) {
      await loadAvailableSlots();
    }
    await Swal.fire('Éxito', `Tipo de examen cambiado a ${newType}`, 'success');
  }
};

// ==================== CRUD ====================

// Cargar asignación
const fetchAssignment = async () => {
  loading.value = true;
  errorMessage.value = '';
  
  try {
    const response = await axios.get(`/api/v1/quiz-assignments/${route.params.id}`);
    const data = response.data.data;
    
    form.value = {
      id: data.id,
      student_id: data.student_id,
      language_id: data.language_id || 1,
      active: data.active === 1 || data.active === true,
      test_type: data.test_type,
      start_at: data.start_at,
      student: data.student,
      passed: data.passed === 1 || data.passed === true,
      attended: data.attended === 1 || data.attended === true
    };
    
    if (form.value.start_at) {
      const date = new Date(form.value.start_at);
      selectedDate.value = date.toISOString().split('T')[0];
      selectedTimeSlot.value = date.toTimeString().slice(0, 8);
    }
    
    if (form.value.test_type === 'OralTest' && form.value.passed === true) {
      await checkExistingPhase2();
    }
    
  } catch (error) {
    console.error('Error:', error);
    if (error.response?.status === 401) {
      errorMessage.value = 'No autorizado. Por favor, inicie sesión nuevamente.';
      setTimeout(() => {
        window.location.href = '/login';
      }, 2000);
    } else {
      errorMessage.value = error.response?.data?.message || 'Error al cargar la asignación';
    }
  } finally {
    loading.value = false;
  }
};

// Crear siguiente fase
const createNextPhase = async () => {
  if (existingPhase2.value) {
    await Swal.fire({
      icon: 'info',
      title: 'Fase 2 ya existe',
      text: `Ya existe un ${existingPhase2.value.test_type} para este estudiante.`,
      confirmButtonText: 'Ver listado'
    });
    goToAssignments();
    return;
  }
  
  creatingPhase.value = true;
  try {
    const response = await axios.post(`/api/v1/admin/assignment/${form.value.id}/create-next-phase`);
    
    if (response.data.success) {
      if (response.data.already_exists) {
        await Swal.fire({
          icon: 'info',
          title: 'Fase 2 ya existe',
          text: `Ya existe un ${response.data.data.test_type} para este estudiante.`,
          confirmButtonText: 'Ver listado'
        });
      } else {
        await Swal.fire({
          icon: 'success',
          title: '¡Fase 2 Creada!',
          html: `Se ha creado un <strong>${response.data.random_type}</strong> para <strong>${form.value.student?.name} ${form.value.student?.lastname}</strong>.`,
          confirmButtonText: 'Ver listado'
        });
      }
      goToAssignments();
    }
  } catch (error) {
    console.error('Error:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al crear la siguiente fase'
    });
  } finally {
    creatingPhase.value = false;
  }
};

// Actualizar asignación
const updateAssignment = async () => {
  if (!selectedDate.value || !selectedTimeSlot.value) {
    await Swal.fire('Error', 'Seleccione una fecha y horario', 'error');
    return;
  }
  
  const startDateTime = `${selectedDate.value} ${selectedTimeSlot.value}`;
  const selectedDateTime = new Date(startDateTime);
  
  if (selectedDateTime <= new Date()) {
    await Swal.fire('Error', 'Debe seleccionar una fecha y hora FUTURA', 'error');
    return;
  }
  
  processing.value = true;
  try {
    await axios.put(`/api/v1/quiz-assignments/${form.value.id}`, {
      start_at: startDateTime,
      active: form.value.active ? 1 : 0,
      test_type: form.value.test_type,
      student_id: form.value.student_id,
      language_id: form.value.language_id
    });
    
    await Swal.fire({
      icon: 'success',
      title: '¡Actualizado!',
      text: 'El examen ha sido reprogramado correctamente',
      timer: 1500,
      showConfirmButton: false
    });
    
    goToAssignments();
  } catch (error) {
    console.error('Error:', error);
    const message = error.response?.data?.message || error.response?.data?.error || 'Error al actualizar';
    await Swal.fire('Error', message, 'error');
  } finally {
    processing.value = false;
  }
};

// ==================== LIFECYCLE ====================
onMounted(() => {
  fetchAssignment();
});
</script>