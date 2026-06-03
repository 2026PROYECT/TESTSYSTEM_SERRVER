<!-- resources/js/components/Admins/Assignments/SpecialSlotManager.vue -->
<template>
  <div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h3 class="text-lg font-bold text-gray-800">📅 Horarios Especiales</h3>
        <p class="text-sm text-gray-500">Excepciones, feriados o modificaciones puntuales</p>
      </div>
      <button @click="openAddModal" 
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700">
        + Agregar Horario Especial
      </button>
    </div>

    <!-- Lista de Horarios Especiales -->
    <div v-if="specialSlots.length === 0" class="text-center py-8 text-gray-400">
      No hay horarios especiales configurados
    </div>

    <div v-else class="space-y-3 max-h-96 overflow-y-auto">
      <div v-for="slot in specialSlots" :key="slot.id" 
        :class="slot.is_blocked ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'"
        class="border rounded-lg p-4 flex justify-between items-center">
        
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <span :class="slot.is_blocked ? 'bg-red-500' : 'bg-amber-500'" 
              class="w-2 h-2 rounded-full"></span>
            <span class="text-xs font-bold uppercase tracking-wider">
              {{ slot.test_type === 'OralTest' ? 'Oral Test' : 'Comp Test' }}
            </span>
            <span class="text-xs text-gray-500">{{ formatDate(slot.date) }}</span>
          </div>
          
          <div class="flex items-center gap-4 text-sm">
            <span class="font-mono">{{ slot.start_time.substring(0, 5) }}</span>
            <span>→</span>
            <span class="font-mono">{{ slot.end_time.substring(0, 5) }}</span>
            
            <span v-if="!slot.is_blocked" class="ml-4 text-indigo-600">
              Capacidad: {{ slot.capacity }}
            </span>
            
            <span v-if="slot.is_blocked" class="ml-4 text-red-600 font-bold">
              BLOQUEADO
            </span>
          </div>
          
          <p v-if="slot.description" class="text-xs text-gray-500 mt-1">
            {{ slot.description }}
          </p>
        </div>
        
        <button @click="deleteSpecialSlot(slot.id)" 
          class="text-red-500 hover:text-red-700 px-3 py-1">
          🗑️ Eliminar
        </button>
      </div>
    </div>

    <!-- Modal para agregar/editar -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ editingSlot ? 'Editar' : 'Nuevo' }} Horario Especial</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-bold mb-1">Tipo de Examen</label>
            <select v-model="form.test_type" class="w-full border rounded-lg p-2">
              <option value="OralTest">Oral Test</option>
              <option value="CompTest">Comp Test</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-bold mb-1">Fecha</label>
            <input type="date" v-model="form.date" :min="minDate" class="w-full border rounded-lg p-2">
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-bold mb-1">Hora Inicio</label>
              <input type="time" v-model="form.start_time" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block text-sm font-bold mb-1">Hora Fin</label>
              <input type="time" v-model="form.end_time" class="w-full border rounded-lg p-2">
            </div>
          </div>
          
          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.is_blocked" class="w-4 h-4">
              <span class="text-sm font-bold">Bloquear este horario</span>
            </label>
          </div>
          
          <div v-if="!form.is_blocked">
            <label class="block text-sm font-bold mb-1">Capacidad</label>
            <input type="number" v-model="form.capacity" min="1" class="w-full border rounded-lg p-2">
          </div>
          
          <div>
            <label class="block text-sm font-bold mb-1">Descripción (opcional)</label>
            <input type="text" v-model="form.description" placeholder="Ej: Feriado, Recuperación, etc." 
              class="w-full border rounded-lg p-2">
          </div>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button @click="closeModal" class="px-4 py-2 border rounded-lg">Cancelar</button>
          <button @click="saveSpecialSlot" :disabled="saving" 
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold disabled:opacity-50">
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const specialSlots = ref([]);
const showModal = ref(false);
const editingSlot = ref(null);
const saving = ref(false);

const minDate = ref(new Date().toISOString().split('T')[0]);

const form = ref({
  test_type: 'OralTest',
  date: new Date().toISOString().split('T')[0],
  start_time: '08:00',
  end_time: '12:00',
  capacity: 4,
  is_blocked: false,
  description: ''
});

const loadSpecialSlots = async () => {
  try {
    const response = await axios.get('/api/v1/admin/special-slots', {
      params: {
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date(new Date().setMonth(new Date().getMonth() + 3)).toISOString().split('T')[0]
      }
    });
    specialSlots.value = response.data.special_slots || [];
  } catch (error) {
    console.error('Error loading special slots:', error);
  }
};

const openAddModal = () => {
  editingSlot.value = null;
  form.value = {
    test_type: 'OralTest',
    date: new Date().toISOString().split('T')[0],
    start_time: '08:00',
    end_time: '12:00',
    capacity: 4,
    is_blocked: false,
    description: ''
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingSlot.value = null;
};

const saveSpecialSlot = async () => {
  saving.value = true;
  try {
    // Validaciones previas
    if (!form.value.date) {
      throw new Error('La fecha es requerida');
    }
    if (!form.value.start_time) {
      throw new Error('La hora de inicio es requerida');
    }
    if (!form.value.end_time) {
      throw new Error('La hora de fin es requerida');
    }
    
    const payload = {
      test_type: form.value.test_type,
      configs: [],  // Importante: enviar array vacío, no objeto
      breaks: {},   // Importante: enviar objeto vacío
      special_slots: [{
        date: form.value.date,
        start_time: form.value.start_time,
        end_time: form.value.end_time,
        capacity: form.value.is_blocked ? null : parseInt(form.value.capacity || 4),
        is_blocked: form.value.is_blocked || false,
        description: form.value.description || null
      }]
    };
    
    console.log('Enviando payload:', JSON.stringify(payload, null, 2));
    
    const response = await axios.post('/api/v1/admin/time-slots-config', payload);
    
    console.log('Respuesta:', response.data);
    
    Swal.fire('Éxito', 'Horario especial guardado correctamente', 'success');
    closeModal();
    await loadSpecialSlots();
    
  } catch (error) {
    console.error('Error completo:', error);
    
    // Mostrar detalles del error
    if (error.response) {
      console.error('Status:', error.response.status);
      console.error('Data:', error.response.data);
      Swal.fire('Error', error.response.data?.message || 'Error del servidor', 'error');
    } else if (error.request) {
      console.error('No response:', error.request);
      Swal.fire('Error', 'No se recibió respuesta del servidor', 'error');
    } else {
      console.error('Error message:', error.message);
      Swal.fire('Error', error.message, 'error');
    }
  } finally {
    saving.value = false;
  }
};

const deleteSpecialSlot = async (id) => {
  const result = await Swal.fire({
    title: '¿Eliminar?',
    text: '¿Estás seguro de eliminar este horario especial?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar'
  });
  
  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/v1/admin/special-slots/${id}`);
      Swal.fire('Eliminado', 'Horario especial eliminado', 'success');
      loadSpecialSlots();
    } catch (error) {
      Swal.fire('Error', 'No se pudo eliminar', 'error');
    }
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES');
};

onMounted(() => {
  loadSpecialSlots();
});
</script>