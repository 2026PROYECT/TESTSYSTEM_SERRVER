<template>
    <div class="p-6 max-w-7xl mx-auto min-h-screen bg-gray-50/50">
        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase italic">Control de Laboratorio</h1>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-widest">Habilitación de equipos por turnos</p>
            </div>
            <button @click="fetchData" 
                    class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 hover:bg-indigo-50 transition-all">
                <span :class="{ 'animate-spin inline-block': loading }">
                    <i class="fas fa-sync-alt text-indigo-600"></i>
                </span>
            </button>
        </header>

        <!-- Filtros -->
        <div class="mb-6 flex items-center justify-between gap-4 bg-white p-4 rounded-3xl border border-gray-100 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-indigo-500 uppercase ml-1 mb-1 tracking-widest">Fecha de Examen</label>
                    <input type="date" v-model="filters.date" @change="fetchData" 
                           class="border border-gray-200 bg-gray-50 rounded-xl font-bold text-slate-700 text-sm px-3 py-2">
                </div>
                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-indigo-500 uppercase ml-1 mb-1 tracking-widest">Buscar</label>
                    <input type="text" v-model="filters.search" placeholder="Nombre o ID..." 
                           class="border border-gray-200 bg-gray-50 rounded-xl font-bold text-slate-700 text-sm px-3 py-2 w-64">
                </div>
            </div>
            <div :class="isToday ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'" 
                 class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2">
                <i :class="isToday ? 'fas fa-bolt' : 'fas fa-history'" class="text-lg"></i>
                <span>{{ isToday ? 'En Vivo: Habilitación Activa' : 'Historial: Solo Lectura' }}</span>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase w-32">Hora</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Aspirante</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-center w-28">Nivel</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-center w-24">Estado</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-center w-28">Acción</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-center w-28">Sanción</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-center w-28">Admin</th>
                        </tr>
                    </thead>
                    <tbody v-if="!loading && filteredStudents.length > 0">
                        <tr v-for="student in filteredStudents" :key="student.assignment_id" 
                            class="border-b border-gray-50 hover:bg-slate-50/50 transition-colors">
                            
                            <!-- Hora con botón editar -->
<td class="px-6 py-4">
    <div class="flex items-center gap-2">
        <span class="font-mono text-xs font-black">{{ formatTime(student.start_at) }}</span>
        <!-- FORZADO: Siempre mostrar el botón para prueba -->
        <button 
            @click="openEditModal(student)" 
            class="text-blue-500 hover:text-blue-700 transition-all px-2 py-1 rounded bg-blue-50"
            title="Editar fecha/hora">
            ✏️ EDITAR
        </button>
    </div>
</td>
                            
                            <!-- Aspirante -->
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-800">{{ student.name }}</p>
                                <div class="flex items-center gap-1 mt-1 flex-wrap">
                                    <span class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-500 uppercase">
                                        {{ student.test_type }}
                                    </span>
                                    <span v-if="student.active === 1 && student.attended === 0" 
                                          class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-green-50 text-green-500">
                                        Pendiente
                                    </span>
                                    <span v-if="student.is_sancionado" 
                                          class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-red-50 text-red-500 flex items-center gap-1">
                                        <i class="fas fa-ban text-[8px]"></i>
                                        Sancionado hasta {{ formatDate(student.penalty_until) }}
                                    </span>
                                    <span v-if="student.attended === 2 && !student.is_sancionado" 
                                          class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-orange-50 text-orange-500">
                                        Inasistencia
                                    </span>
                                </div>
                            </td>
                            
                            <!-- Nivel -->
                            <td class="px-6 py-4 text-center">
                                <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                    {{ student.level }}
                                </span>
                                <p class="text-[9px] text-gray-400 font-bold italic mt-1">{{ student.oral_score }}% Oral</p>
                            </td>
                            
                            <!-- Estado Habilitado/En Espera -->
                            <td class="px-6 py-4 text-center">
                                <span :class="student.is_unlocked ? 'text-emerald-500 bg-emerald-50' : 'text-amber-500 bg-amber-50'" 
                                      class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                    {{ student.is_unlocked ? '✅ Habilitado' : '⏳ En Espera' }}
                                </span>
                            </td>
                            
                            <!-- Acción: Habilitar/Bloquear -->
                            <td class="px-6 py-4 text-center">
                                <button 
                                    v-if="student.active === 1 && student.attended === 0 && !student.is_sancionado"
                                    @click="toggleUnlock(student)" 
                                    :disabled="!isToday"
                                    :class="[
                                        !isToday ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 
                                        (student.is_unlocked ? 'bg-rose-500 hover:bg-rose-600' : 'bg-indigo-600 hover:bg-indigo-700')
                                    ]" 
                                    class="px-4 py-2 rounded-xl text-white text-[11px] font-black uppercase shadow-sm transition-all w-full">
                                    {{ student.is_unlocked ? '🔒 Bloquear' : '🔓 Habilitar' }}
                                </button>
                                <span v-else class="text-gray-300 text-[10px]">---</span>
                            </td>
                            
                            <!-- Sanción -->
                            <td class="px-6 py-4 text-center">
                                <button 
                                    v-if="student.active === 1 && student.attended === 0 && !student.is_sancionado"
                                    @click="confirmSanction(student)" 
                                    :disabled="!isToday"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-[11px] font-black uppercase shadow-sm transition-all flex items-center gap-2 mx-auto">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Sancionar</span>
                                </button>
                                <span v-else-if="student.attended === 2 || student.is_sancionado" 
                                      class="text-gray-400 text-[10px]">Sancionado</span>
                                <span v-else class="text-gray-300 text-[10px]">---</span>
                            </td>
                            
                            <!-- Admin: Levantar/Reactivar -->
                            <td class="px-6 py-4 text-center">
                                <button 
                                    v-if="student.is_sancionado"
                                    @click="removePenalty(student)"
                                    :disabled="!isToday"
                                    class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-xl text-[10px] font-black uppercase shadow-sm transition-all flex items-center gap-1 mx-auto">
                                    <i class="fas fa-gavel"></i>
                                    <span>Levantar</span>
                                </button>
                                <button 
                                    v-else-if="student.attended === 2 && !student.is_sancionado"
                                    @click="reactivateAssignment(student)"
                                    :disabled="!isToday"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-xl text-[10px] font-black uppercase shadow-sm transition-all flex items-center gap-1 mx-auto">
                                    <i class="fas fa-undo-alt"></i>
                                    <span>Reactivar</span>
                                </button>
                                <span v-else class="text-gray-300 text-[10px]">---</span>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else-if="!loading && filteredStudents.length === 0">
                        <tr>
                            <td colspan="7" class="py-20 text-center text-gray-400 font-bold uppercase text-xs">
                                <i class="fas fa-calendar-alt text-3xl mb-3 block"></i>
                                No hay programaciones para esta fecha
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-if="loading">
                        <tr>
                            <td colspan="7" class="py-20 text-center">
                                <i class="fas fa-spinner fa-spin text-indigo-600 text-2xl"></i>
                                <p class="text-gray-400 mt-2">Cargando...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL EDITAR FECHA/HORA -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeEditModal">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-slate-800">Editar Asignación</h3>
                    <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold mb-1 text-slate-600">Estudiante</label>
                        <input type="text" :value="editData.student?.name" disabled 
                               class="w-full px-3 py-2 bg-gray-100 rounded-lg text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold mb-1 text-slate-600">Fecha</label>
                        <input type="date" v-model="editData.date" @change="loadSchedules" 
                               :min="minDate"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold mb-1 text-slate-600">Horario</label>
                        <div class="grid grid-cols-4 gap-2 max-h-60 overflow-y-auto p-2 border rounded-lg">
                            <button 
                                v-for="slot in schedules" 
                                :key="slot.start"
                                type="button"
                                @click="selectSlot(slot)"
                                :disabled="!slot.is_available"
                                :class="[
                                    'p-2 rounded-lg text-center transition-all',
                                    editData.selectedSlot === slot.start 
                                        ? 'bg-indigo-600 text-white' 
                                        : (slot.is_available 
                                            ? 'bg-gray-100 hover:bg-indigo-100 text-gray-700 border' 
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed opacity-50')
                                ]">
                                <div class="text-sm font-bold">{{ slot.label }}</div>
                                <div class="text-[9px] mt-1">
                                    <span v-if="slot.is_past" class="text-gray-400">Pasado</span>
                                    <span v-else-if="!slot.is_available" class="text-red-500">Ocupado</span>
                                    <span v-else class="text-green-500">Libre</span>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold mb-1 text-slate-600">Tipo de Examen</label>
                        <select v-model="editData.test_type" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="OralTest">📝 OralTest</option>
                            <option value="CompTest">💻 CompTest</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="closeEditModal" class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</button>
                    <button @click="saveChanges" 
                            :disabled="!canSaveChanges"
                            :class="[
                                'px-4 py-2 rounded-lg',
                                canSaveChanges ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                            ]">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// ==================== ESTADO ====================
const students = ref([]);
const loading = ref(false);
const filters = ref({
    date: new Date().toLocaleDateString('en-CA'),
    search: ''
});

// Modal
const showEditModal = ref(false);
const editData = ref({
    student: null,
    date: '',
    selectedSlot: '',
    test_type: ''
});
const schedules = ref([]);

// ==================== COMPUTED ====================
const isToday = computed(() => filters.value.date === new Date().toLocaleDateString('en-CA'));
const minDate = computed(() => new Date().toISOString().split('T')[0]);

const filteredStudents = computed(() => {
    if (!filters.value.search) return students.value;
    const searchLower = filters.value.search.toLowerCase();
    return students.value.filter(s => 
        s.name.toLowerCase().includes(searchLower) || 
        s.student_id.toString().includes(searchLower)
    );
});

const canSaveChanges = computed(() => {
    return editData.value.date && editData.value.selectedSlot && editData.value.test_type;
});

// ==================== FUNCIONES ====================
const canEdit = (student) => {
    return isToday.value && 
           student.active === 1 && 
           student.attended === 0 && 
           !student.is_sancionado;
};

const formatTime = (datetime) => {
    if (!datetime) return '--:--';
    return new Date(datetime).toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit', 
        hour12: false 
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES');
};

// ==================== API ====================
const fetchData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/pending-lab-access', {
            params: { date: filters.value.date }
        });
        
        // Procesar datos para agregar is_sancionado
        students.value = response.data.map(s => ({
            ...s,
            is_sancionado: s.penalty_until && new Date(s.penalty_until) > new Date()
        }));
        
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'No se pudo cargar la lista', 'error');
    } finally {
        loading.value = false;
    }
};

const loadSchedules = async () => {
    if (!editData.value.date) return;
    
    try {
        const response = await axios.get('/api/v1/admin/available-schedules', {
            params: { date: editData.value.date }
        });
        schedules.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

const toggleUnlock = async (student) => {
    const newState = !student.is_unlocked;
    
    try {
        const response = await axios.post(`/api/v1/admin/unlock-exam-by-id/${student.assignment_id}`, {
            is_unlocked: newState
        });
        
        if (response.data.status === 'success') {
            student.is_unlocked = newState ? 1 : 0;
            Swal.fire({
                title: 'Éxito',
                text: `Equipo ${newState ? 'habilitado' : 'bloqueado'}`,
                icon: 'success',
                toast: true,
                timer: 2000
            });
        }
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Error al actualizar', 'error');
    }
};

const confirmSanction = (student) => {
    Swal.fire({
        title: '¿Marcar Inasistencia?',
        text: `${student.name} será sancionado por 14 días`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, sancionar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.post(`/api/v1/admin/mark-as-missed/${student.student_id}`);
                await fetchData();
                Swal.fire('Sancionado', 'Sanción aplicada por 14 días', 'success');
            } catch (error) {
                Swal.fire('Error', 'No se pudo aplicar la sanción', 'error');
            }
        }
    });
};

const removePenalty = async (student) => {
    Swal.fire({
        title: '¿Levantar sanción?',
        text: `Se eliminará la sanción de ${student.name}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, levantar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.post(`/api/v1/admin/remove-penalty/${student.student_id}`);
                await fetchData();
                Swal.fire('Sanción eliminada', 'El estudiante ya puede rendir', 'success');
            } catch (error) {
                Swal.fire('Error', 'No se pudo eliminar la sanción', 'error');
            }
        }
    });
};

const reactivateAssignment = async (student) => {
    Swal.fire({
        title: '¿Reactivar asignación?',
        text: `${student.name} podrá rendir el examen nuevamente`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, reactivar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.post(`/api/v1/admin/clear-missed/${student.student_id}`);
                await fetchData();
                Swal.fire('Reactivado', 'Asignación reactivada correctamente', 'success');
            } catch (error) {
                Swal.fire('Error', 'No se pudo reactivar', 'error');
            }
        }
    });
};

// ==================== MODAL ====================
const openEditModal = (student) => {
    const startDate = new Date(student.start_at);
    const formattedDate = startDate.toISOString().split('T')[0];
    const currentTime = startDate.toTimeString().split(' ')[0].slice(0, 5);
    
    editData.value = {
        student: student,
        date: formattedDate,
        selectedSlot: `${currentTime}:00`,
        test_type: student.test_type
    };
    
    showEditModal.value = true;
    loadSchedules();
};

const closeEditModal = () => {
    showEditModal.value = false;
    editData.value = { student: null, date: '', selectedSlot: '', test_type: '' };
    schedules.value = [];
};

const selectSlot = (slot) => {
    if (slot.is_available) {
        editData.value.selectedSlot = slot.start;
    }
};

const saveChanges = async () => {
    if (!canSaveChanges.value) return;
    
    const start_at = `${editData.value.date}T${editData.value.selectedSlot}`;
    
    try {
        const response = await axios.put(`/api/v1/admin/assignment/${editData.value.student.assignment_id}/datetime`, {
            start_at: start_at,
            test_type: editData.value.test_type
        });
        
        if (response.data.status === 'success') {
            await fetchData();
            Swal.fire('Éxito', 'Asignación actualizada', 'success');
            closeEditModal();
        }
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'No se pudo actualizar', 'error');
    }
};

// ==================== LIFECYCLE ====================
onMounted(() => {
    fetchData();
});
</script>