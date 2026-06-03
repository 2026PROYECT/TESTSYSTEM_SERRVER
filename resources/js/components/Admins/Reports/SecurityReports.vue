<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900">Reporte de Violaciones de Seguridad</h1>
            <p class="text-gray-500">Seguimiento de intentos de fraude durante los exámenes</p>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Total Violaciones</p>
                <p class="text-2xl font-black">{{ summary.total || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Hoy</p>
                <p class="text-2xl font-black text-orange-600">{{ summary.today || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Esta Semana</p>
                <p class="text-2xl font-black">{{ summary.this_week || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Este Mes</p>
                <p class="text-2xl font-black">{{ summary.this_month || 0 }}</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs font-bold text-gray-400">Estudiante</label>
                    <input type="text" v-model="filters.student" @change="loadReports" placeholder="ID o nombre..." class="w-full px-3 py-2 rounded-xl border">
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-400">Tipo de Evento</label>
                    <select v-model="filters.event_type" @change="loadReports" class="w-full px-3 py-2 rounded-xl border">
                        <option value="">Todos</option>
                        <option value="tab_switch">Cambio de pestaña</option>
                        <option value="devtools_opened">DevTools</option>
                        <option value="screenshot_attempt">Captura de pantalla</option>
                        <option value="mouse_leave">Salida del área</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-400">&nbsp;</label>
                    <button @click="exportReport" class="w-full bg-red-600 text-white py-2 rounded-xl font-bold">📄 Exportar Reporte PDF</button>
                </div>
            </div>
        </div>

        <!-- Top estudiantes con violaciones -->
        <div class="bg-white rounded-2xl shadow-sm border mb-6">
            <div class="p-4 border-b">
                <h3 class="font-black">📊 Estudiantes con más violaciones</h3>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <div v-for="student in topStudents" :key="student.user_id" class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                        <span>{{ student.user_name }}</span>
                        <span class="font-bold text-red-600">{{ student.total }} violaciones</span>
                    </div>
                    <p v-if="topStudents.length === 0" class="text-gray-400 text-center py-4">No hay datos</p>
                </div>
            </div>
        </div>

        <!-- Tabla de violaciones -->
        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Fecha</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Estudiante</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Examen ID</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Evento</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Detalles</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in violations" :key="log.id" class="border-b hover:bg-gray-50">
                            <td class="p-4 text-sm">{{ formatDate(log.created_at) }}</td>
                            <td class="p-4 text-sm">{{ log.user?.name || 'Desconocido' }}</td>
                            <td class="p-4 text-sm font-mono">{{ log.exam_attempt_id || '-' }}</td>
                            <td class="p-4">
                                <span :class="getEventClass(log.event_type)" class="px-2 py-1 rounded-full text-xs font-bold">
                                    {{ getEventLabel(log.event_type) }}
                                </span>
                            </td>
                            <td class="p-4 text-sm">{{ log.details || '-' }}</td>
                            <td class="p-4 text-sm font-mono">{{ log.ip_address || '-' }}</td>
                        </tr>
                        <tr v-if="violations.length === 0">
                            <td colspan="6" class="p-8 text-center text-gray-400">No hay violaciones registradas</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const violations = ref([]);
const summary = ref({});
const topStudents = ref([]);
const filters = ref({
    student: '',
    event_type: ''
});

const loadReports = async () => {
    try {
        const response = await axios.get('/api/v1/admin/security/reports', { params: filters.value });
        violations.value = response.data.logs?.data || [];
        summary.value = response.data.stats || {};
        topStudents.value = response.data.stats?.by_student || [];
    } catch (error) {
        console.error('Error loading reports:', error);
    }
};

const exportReport = async () => {
    try {
        const response = await axios.get('/api/v1/admin/security/reports/export-pdf', { 
            params: filters.value,
            responseType: 'blob' 
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'reporte_seguridad.pdf');
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        Swal.fire('Error', 'No se pudo exportar el reporte', 'error');
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('es-ES');
};

const getEventLabel = (type) => {
    const labels = {
        'tab_switch': 'Cambio pestaña',
        'devtools_opened': 'DevTools',
        'screenshot_attempt': 'Captura',
        'mouse_leave': 'Salida área',
        'exam_invalidated': 'INVALIDADO'
    };
    return labels[type] || type;
};

const getEventClass = (type) => {
    const classes = {
        'tab_switch': 'bg-yellow-100 text-yellow-700',
        'devtools_opened': 'bg-red-100 text-red-700',
        'screenshot_attempt': 'bg-red-100 text-red-700',
        'mouse_leave': 'bg-orange-100 text-orange-700',
        'exam_invalidated': 'bg-black text-white'
    };
    return classes[type] || 'bg-gray-100 text-gray-700';
};

onMounted(() => {
    loadReports();
});
</script>