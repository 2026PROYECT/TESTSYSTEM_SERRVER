<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800">👤 Usuarios Bloqueados</h1>
      <p class="text-gray-500 mt-1">Gestión de usuarios bloqueados por violaciones de seguridad en exámenes modulares</p>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
        <p class="text-gray-500 text-sm">Total Bloqueados</p>
        <p class="text-2xl font-bold text-red-600">{{ stats.total_blocked || 0 }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-yellow-500">
        <p class="text-gray-500 text-sm">Esta semana</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.this_week || 0 }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500">
        <p class="text-gray-500 text-sm">Este mes</p>
        <p class="text-2xl font-bold text-indigo-600">{{ stats.this_month || 0 }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
        <p class="text-gray-500 text-sm">Nuevos Exámenes</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.new_assignments || 0 }}</p>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
      <div class="flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-2">
          <button 
            @click="filterType = 'all'" 
            :class="filterType === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition">
            Todos
          </button>
          <button 
            @click="filterType = 'modular'" 
            :class="filterType === 'modular' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition">
            📦 Exámenes Modulares
          </button>
          <button 
            @click="filterType = 'penalty'" 
            :class="filterType === 'penalty' ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition">
            ⏰ En Penalización
          </button>
        </div>
        
        <div class="relative">
          <input 
            v-model="searchTerm" 
            type="text" 
            placeholder="Buscar por nombre o email..." 
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 w-64"
          />
          <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
        </div>
        
        <button @click="refreshData" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
          🔄 Refrescar
        </button>
      </div>
    </div>

    <!-- Tabla de usuarios bloqueados -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Bloqueo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penalización</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Razón</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50 transition">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                      <span class="text-red-600 font-bold">{{ user.name.charAt(0) }}</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(user.blocked_at) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="user.penalty_until && isPenaltyActive(user.penalty_until)" 
                      class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">
                  ⏰ {{ getPenaltyDaysLeft(user.penalty_until) }} días
                </span>
                <span v-else class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                  ✓ Penalización expirada
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate">{{ user.blocked_reason || 'Violaciones de seguridad' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex gap-2">
                  <button 
                    @click="viewUserDetails(user.id)" 
                    class="text-indigo-600 hover:text-indigo-900 transition" 
                    title="Ver detalles">
                    👁️
                  </button>
                  <button 
                    @click="downloadReport(user.id)" 
                    class="text-blue-600 hover:text-blue-900 transition" 
                    title="Descargar reporte PDF">
                    📄
                  </button>
                  <button 
                    v-if="!isPenaltyActive(user.penalty_until)"
                    @click="createNewAssignment(user.id)" 
                    class="text-green-600 hover:text-green-900 transition" 
                    title="Crear nueva asignación">
                    📝
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                No hay usuarios bloqueados
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de detalles del usuario -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
      <div class="bg-white rounded-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="p-6 border-b sticky top-0 bg-white">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Detalles del Usuario</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
        </div>
        <div class="p-6" v-if="selectedUser">
          <!-- Información personal -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Información Personal</h3>
            <div class="bg-gray-50 rounded-lg p-4">
              <p><span class="font-medium">Nombre:</span> {{ selectedUser.user?.name }}</p>
              <p><span class="font-medium">Email:</span> {{ selectedUser.user?.email }}</p>
              <p><span class="font-medium">Bloqueado el:</span> {{ formatDate(selectedUser.user?.blocked_at) }}</p>
              <p><span class="font-medium">Penalización hasta:</span> {{ formatDate(selectedUser.user?.penalty_until) }}</p>
              <p><span class="font-medium">Razón:</span> {{ selectedUser.user?.blocked_reason || 'Violaciones de seguridad' }}</p>
            </div>
          </div>

          <!-- Exámenes invalidados -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Exámenes Invalidados</h3>
            <div class="space-y-2">
              <div v-for="exam in selectedUser.invalidated_exams" :key="exam.id" class="bg-red-50 rounded-lg p-3">
                <p><span class="font-medium">ID Examen:</span> {{ exam.id }}</p>
                <p><span class="font-medium">Fecha:</span> {{ formatDate(exam.started_at) }}</p>
                <p><span class="font-medium">Violaciones:</span> {{ exam.violations_count }}</p>
                <p v-if="exam.penalty_until" class="text-orange-600 text-sm">
                  ⏰ Penalización hasta: {{ formatDate(exam.penalty_until) }}
                </p>
                <button @click="downloadExamReport(exam.id)" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                  📄 Descargar reporte de este examen
                </button>
              </div>
              <div v-if="!selectedUser.invalidated_exams || selectedUser.invalidated_exams.length === 0" class="text-gray-500 text-sm">
                No hay exámenes invalidados
              </div>
            </div>
          </div>

          <!-- Historial de violaciones -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Historial de Violaciones</h3>
            <div class="space-y-2 max-h-64 overflow-y-auto">
              <div v-for="violation in selectedUser.violations" :key="violation.id" class="border-l-4 border-red-400 bg-gray-50 rounded-lg p-3">
                <p class="font-medium text-red-600">{{ violation.event_type }}</p>
                <p class="text-sm text-gray-600">{{ violation.details }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ formatDate(violation.created_at) }}</p>
              </div>
              <div v-if="!selectedUser.violations || selectedUser.violations.length === 0" class="text-gray-500 text-sm">
                No hay registros de violaciones
              </div>
            </div>
          </div>
        </div>
        <div class="p-6 border-t bg-gray-50 flex justify-end gap-3">
          <button @click="closeModal" class="px-4 py-2 bg-gray-200 rounded-lg">Cerrar</button>
          <button 
            @click="downloadReport(selectedUser?.user?.id)" 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            📄 Descargar Reporte Completo
          </button>
          <button 
            v-if="selectedUser && !isPenaltyActive(selectedUser.user?.penalty_until)"
            @click="createNewAssignment(selectedUser.user?.id)" 
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            📝 Crear Nueva Asignación
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();

// Estado
const blockedUsers = ref([]);
const stats = ref({});
const searchTerm = ref('');
const filterType = ref('all');
const loading = ref(false);
const showDetailModal = ref(false);
const selectedUser = ref(null);

// Usuarios filtrados
const filteredUsers = computed(() => {
  let users = blockedUsers.value;
  
  if (filterType.value === 'modular') {
    users = users.filter(u => u.attempt_id);
  }
  
  if (filterType.value === 'penalty') {
    users = users.filter(u => u.penalty_until && isPenaltyActive(u.penalty_until));
  }
  
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase();
    users = users.filter(u => 
      (u.name && u.name.toLowerCase().includes(term)) || 
      (u.email && u.email.toLowerCase().includes(term))
    );
  }
  
  return users;
});

// Verificar si la penalización sigue activa
const isPenaltyActive = (penaltyUntil) => {
  if (!penaltyUntil) return false;
  return new Date(penaltyUntil) > new Date();
};

// Obtener días restantes de penalización
const getPenaltyDaysLeft = (penaltyUntil) => {
  if (!penaltyUntil) return 0;
  const now = new Date();
  const penalty = new Date(penaltyUntil);
  const diffTime = penalty - now;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays > 0 ? diffDays : 0;
};

// Formatear fecha
const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Cargar datos
const loadBlockedUsers = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/blocked-users');
    if (response.data.success) {
      blockedUsers.value = response.data.users || [];
      stats.value = response.data.stats || {};
    }
  } catch (error) {
    console.error('Error cargando usuarios bloqueados:', error);
    Swal.fire('Error', 'No se pudieron cargar los usuarios bloqueados', 'error');
  } finally {
    loading.value = false;
  }
};

const refreshData = () => {
  loadBlockedUsers();
};

// Ver detalles del usuario
const viewUserDetails = async (userId) => {
  try {
    const response = await axios.get(`/api/v1/admin/blocked-users/${userId}`);
    if (response.data.success) {
      selectedUser.value = response.data;
      showDetailModal.value = true;
    }
  } catch (error) {
    console.error('Error cargando detalles:', error);
    Swal.fire('Error', 'No se pudieron cargar los detalles del usuario', 'error');
  }
};

// Descargar reporte completo del usuario
const downloadReport = async (userId) => {
  if (!userId) return;
  
  try {
    Swal.fire({
      title: 'Generando reporte',
      text: 'Por favor espere...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    const response = await axios.get(`/api/v1/admin/blocked-users/report/${userId}`, {
      responseType: 'blob'
    });
    
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte_seguridad_usuario_${userId}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    Swal.fire('Éxito', 'Reporte descargado correctamente', 'success');
  } catch (error) {
    console.error('Error descargando reporte:', error);
    Swal.fire('Error', 'No se pudo generar el reporte', 'error');
  }
};

// Descargar reporte de un examen específico
const downloadExamReport = async (attemptId) => {
  try {
    Swal.fire({
      title: 'Generando reporte',
      text: 'Por favor espere...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    const response = await axios.get(`/api/v1/admin/blocked-users/exam-report/${attemptId}`, {
      responseType: 'blob'
    });
    
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte_examen_${attemptId}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    Swal.fire('Éxito', 'Reporte descargado correctamente', 'success');
  } catch (error) {
    console.error('Error descargando reporte:', error);
    Swal.fire('Error', 'No se pudo generar el reporte', 'error');
  }
};

// Crear nueva asignación
const createNewAssignment = (userId) => {
  router.push({ 
    name: 'assignments.create', 
    query: { 
      student_id: userId,
      from_blocked: 'true'
    } 
  });
};

// Cerrar modal
const closeModal = () => {
  showDetailModal.value = false;
  selectedUser.value = null;
};

onMounted(() => {
  loadBlockedUsers();
});
</script>