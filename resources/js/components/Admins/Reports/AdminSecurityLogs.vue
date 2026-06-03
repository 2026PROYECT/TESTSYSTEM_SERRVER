<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900">Registros de Seguridad</h1>
            <p class="text-gray-500">Seguimiento de intentos de fraude durante los exámenes</p>
        </div>

        <!-- Panel de selección de examen -->
        <div class="bg-white rounded-2xl shadow-sm border mb-6 overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-black text-gray-800 flex items-center gap-2">
                    <span class="text-indigo-600">🔍</span> Seleccionar Examen
                </h3>
            </div>
            
            <div class="p-4">
                <!-- Búsqueda -->
                <div class="mb-4">
                    <div class="relative">
                        <input 
                            type="text" 
                            v-model="searchTerm" 
                            @input="filterExams"
                            placeholder="Buscar por ID de examen, estudiante o email..."
                            class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                        >
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                    </div>
                </div>

                <!-- Lista de exámenes con violaciones -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-96 overflow-y-auto">
                    <div 
                        v-for="exam in filteredExams" 
                        :key="exam.exam_attempt_id"
                        @click="selectExam(exam.exam_attempt_id)"
                        :class="[
                            selectedExamId === exam.exam_attempt_id 
                                ? 'bg-indigo-50 border-indigo-300 ring-2 ring-indigo-200' 
                                : 'bg-white border-gray-200 hover:border-indigo-200 hover:bg-gray-50',
                            'p-3 rounded-xl border cursor-pointer transition-all duration-200'
                        ]"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-black text-gray-800 text-sm">
                                    Examen #{{ exam.exam_attempt_id }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ exam.student_name || 'Estudiante desconocido' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ exam.email || '' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">
                                    {{ exam.violations_count }} violaciones
                                </span>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ formatDateShort(exam.last_violation) }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span 
                                v-for="(count, type) in exam.violations_by_type" 
                                :key="type"
                                :class="getEventBadgeClass(type)"
                                class="text-[10px] px-2 py-0.5 rounded-full"
                            >
                                {{ getEventLabel(type) }}: {{ count }}
                            </span>
                        </div>
                    </div>
                    
                    <div v-if="filteredExams.length === 0 && !loadingExams" class="col-span-full text-center py-8 text-gray-400">
                        No hay exámenes con violaciones registradas
                    </div>
                    
                    <div v-if="loadingExams" class="col-span-full text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                        <p class="text-gray-400 mt-2">Cargando exámenes...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles del examen seleccionado -->
        <div v-if="selectedExamId && examDetails" class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-black text-gray-800 flex items-center gap-2">
                    <span class="text-indigo-600">📋</span> Detalles del Examen #{{ selectedExamId }}
                </h3>
                <button @click="exportLogs" class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-bold flex items-center gap-1">
                    📄 Exportar PDF
                </button>
            </div>
            
            <!-- Información del examen -->
            <div class="p-4 border-b bg-gray-50/50 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-400">Estudiante</p>
                    <p class="font-bold text-sm">{{ examDetails.student?.name || '-' }}</p>
                    <p class="text-xs text-gray-500">{{ examDetails.student?.email || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Total Violaciones</p>
                    <p class="font-bold text-xl text-red-600">{{ examDetails.stats?.total_violations || 0 }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Examen Invalidado</p>
                    <span :class="examDetails.was_invalidated ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'" class="px-2 py-1 rounded-full text-xs font-bold">
                        {{ examDetails.was_invalidated ? 'Sí' : 'No' }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Última Violación</p>
                    <p class="text-sm">{{ formatDate(examDetails.violations?.[0]?.created_at) }}</p>
                </div>
            </div>

            <!-- Estadísticas por tipo de violación -->
            <div class="p-4 border-b">
                <h4 class="text-sm font-bold mb-3">📊 Estadísticas por tipo de violación</h4>
                <div class="flex flex-wrap gap-3">
                    <div v-for="(count, type) in examDetails.stats?.by_type" :key="type" class="flex items-center gap-2">
                        <span :class="getEventBadgeClass(type)" class="px-3 py-1.5 rounded-full text-xs font-bold">
                            {{ getEventLabel(type) }}: {{ count }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Timeline de violaciones -->
            <div class="p-4">
                <h4 class="text-sm font-bold mb-3">⏱️ Timeline de Violaciones</h4>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div v-for="violation in examDetails.violations" :key="violation.id" class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600">{{ getEventIcon(violation.event_type) }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span :class="getEventBadgeClass(violation.event_type)" class="px-2 py-0.5 rounded-full text-xs font-bold">
                                        {{ getEventLabel(violation.event_type) }}
                                    </span>
                                </div>
                                <span class="text-xs text-gray-400">{{ formatTime(violation.created_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ violation.details || 'Sin detalles adicionales' }}</p>
                            <p class="text-xs text-gray-400 mt-1">IP: {{ violation.ip_address || '-' }} | User Agent: {{ truncateUserAgent(violation.user_agent) }}</p>
                        </div>
                    </div>
                    
                    <div v-if="examDetails.violations?.length === 0" class="text-center py-8 text-gray-400">
                        No hay violaciones registradas para este examen
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mensaje cuando no hay examen seleccionado -->
        <div v-else-if="!loadingExams" class="bg-white rounded-2xl shadow-sm border p-12 text-center">
            <div class="text-6xl mb-4">🔒</div>
            <h3 class="text-xl font-black text-gray-800 mb-2">Selecciona un examen</h3>
            <p class="text-gray-500">Elige un examen de la lista para ver los registros de seguridad</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const selectedExamId = ref(null);
const examDetails = ref(null);
const examsList = ref([]);
const loadingExams = ref(false);
const searchTerm = ref('');

// Computed para filtrar exámenes
const filteredExams = computed(() => {
    if (!searchTerm.value) return examsList.value;
    
    const term = searchTerm.value.toLowerCase();
    return examsList.value.filter(exam => 
        exam.exam_attempt_id.toString().includes(term) ||
        (exam.student_name && exam.student_name.toLowerCase().includes(term)) ||
        (exam.email && exam.email.toLowerCase().includes(term))
    );
});

// Cargar lista de exámenes con violaciones
const loadExamsList = async () => {
    loadingExams.value = true;
    try {
        const response = await axios.get('/api/v1/admin/security/exams-with-violations');
        examsList.value = response.data.exams || [];
    } catch (error) {
        console.error('Error loading exams list:', error);
        Swal.fire('Error', 'No se pudo cargar la lista de exámenes', 'error');
    } finally {
        loadingExams.value = false;
    }
};

// Seleccionar un examen y cargar sus detalles
const selectExam = async (examId) => {
    selectedExamId.value = examId;
    try {
        const response = await axios.get(`/api/v1/admin/security/logs/${examId}`);
        examDetails.value = response.data;
    } catch (error) {
        console.error('Error loading exam details:', error);
        Swal.fire('Error', 'No se pudieron cargar los detalles del examen', 'error');
    }
};

// Exportar logs del examen seleccionado
const exportLogs = async () => {
    if (!selectedExamId.value) return;
    
    try {
        const response = await axios.get(`/api/v1/admin/security/logs/${selectedExamId.value}/export-pdf`, {
            responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `security_logs_exam_${selectedExamId.value}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        Swal.fire('Error', 'No se pudo exportar el reporte', 'error');
    }
};

const filterExams = () => {
    // El filtro se maneja con computed
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-ES');
};

const formatDateShort = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES');
};

const formatTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleTimeString('es-ES');
};

const truncateUserAgent = (ua) => {
    if (!ua) return '-';
    return ua.length > 50 ? ua.substring(0, 50) + '...' : ua;
};

const getEventLabel = (type) => {
    const labels = {
        'tab_switch': 'Cambio pestaña',
        'devtools_opened': 'DevTools',
        'screenshot_attempt': 'Captura pantalla',
        'mouse_leave': 'Salida área',
        'exam_invalidated': 'INVALIDADO',
        'copy_attempt': 'Intento copiar',
        'reload_attempt': 'Recarga página'
    };
    return labels[type] || type;
};

const getEventBadgeClass = (type) => {
    const classes = {
        'tab_switch': 'bg-yellow-100 text-yellow-700',
        'devtools_opened': 'bg-red-100 text-red-700',
        'screenshot_attempt': 'bg-red-100 text-red-700',
        'mouse_leave': 'bg-orange-100 text-orange-700',
        'exam_invalidated': 'bg-black text-white',
        'copy_attempt': 'bg-purple-100 text-purple-700',
        'reload_attempt': 'bg-blue-100 text-blue-700'
    };
    return classes[type] || 'bg-gray-100 text-gray-700';
};

const getEventIcon = (type) => {
    const icons = {
        'tab_switch': '🔄',
        'devtools_opened': '🛠️',
        'screenshot_attempt': '📸',
        'mouse_leave': '🚪',
        'exam_invalidated': '❌',
        'copy_attempt': '📋',
        'reload_attempt': '🔄'
    };
    return icons[type] || '⚠️';
};

onMounted(() => {
    loadExamsList();
});
</script>