<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900">Auditoría de Actividad</h1>
            <p class="text-gray-500">Registro de todas las acciones realizadas en el sistema</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-xs font-bold text-gray-400">Fecha Inicio</label>
                    <input type="date" v-model="filters.start_date" @change="loadLogs" class="w-full px-3 py-2 rounded-xl border">
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-400">Fecha Fin</label>
                    <input type="date" v-model="filters.end_date" @change="loadLogs" class="w-full px-3 py-2 rounded-xl border">
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-400">Acción</label>
                    <input type="text" v-model="filters.action" @change="loadLogs" placeholder="Buscar acción..." class="w-full px-3 py-2 rounded-xl border">
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-400">Severidad</label>
                    <select v-model="filters.severity" @change="loadLogs" class="w-full px-3 py-2 rounded-xl border">
                        <option value="">Todas</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Danger</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button @click="exportPdf" class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-bold">📄 Exportar PDF</button>
                <button @click="exportCsv" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-bold">📊 Exportar CSV</button>
                <button @click="loadLogs" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold">🔍 Buscar</button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Total Eventos</p>
                <p class="text-2xl font-black">{{ stats.total || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Hoy</p>
                <p class="text-2xl font-black text-indigo-600">{{ stats.today || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Esta Semana</p>
                <p class="text-2xl font-black">{{ stats.this_week || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Este Mes</p>
                <p class="text-2xl font-black">{{ stats.this_month || 0 }}</p>
            </div>
        </div>

        <!-- Tabla de logs -->
        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Fecha</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Usuario</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Acción</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Entidad</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Severidad</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id" class="border-b hover:bg-gray-50">
                            <td class="p-4 text-sm">{{ formatDate(log.created_at) }}</td>
                            <td class="p-4 text-sm">{{ log.user?.name || 'Sistema' }}</td>
                            <td class="p-4 text-sm font-mono text-xs">{{ log.action }}</td>
                            <td class="p-4 text-sm">{{ log.entity_type || '-' }}</td>
                            <td class="p-4">
                                <span :class="getSeverityClass(log.severity)" class="px-2 py-1 rounded-full text-xs font-bold">
                                    {{ log.severity }}
                                </span>
                            </td>
                            <td class="p-4 text-sm font-mono text-xs">{{ log.ip_address || '-' }}</td>
                        </tr>
                        <tr v-if="logs.data?.length === 0">
                            <td colspan="6" class="p-8 text-center text-gray-400">No hay registros de auditoría</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <div class="p-4 border-t flex justify-between items-center">
                <p class="text-sm text-gray-500">Mostrando {{ logs.from || 0 }} a {{ logs.to || 0 }} de {{ logs.total || 0 }} registros</p>
                <div class="flex gap-2">
                    <button @click="prevPage" :disabled="!logs.prev_page_url" class="px-3 py-1 rounded-lg border disabled:opacity-50">Anterior</button>
                    <button @click="nextPage" :disabled="!logs.next_page_url" class="px-3 py-1 rounded-lg border disabled:opacity-50">Siguiente</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const logs = ref({ data: [], total: 0 });
const stats = ref({});
const filters = ref({
    start_date: '',
    end_date: '',
    action: '',
    severity: ''
});

const loadLogs = async () => {
    try {
        const response = await axios.get('/api/v1/admin/audit/logs', { params: filters.value });
        logs.value = response.data.logs;
        stats.value = response.data.stats;
    } catch (error) {
        console.error('Error loading logs:', error);
        Swal.fire('Error', 'No se pudieron cargar los logs', 'error');
    }
};

const exportPdf = async () => {
    try {
        // Usar params en lugar de construirlo manualmente
        const params = new URLSearchParams();
        if (filters.value.start_date) params.append('start_date', filters.value.start_date);
        if (filters.value.end_date) params.append('end_date', filters.value.end_date);
        if (filters.value.action) params.append('action', filters.value.action);
        if (filters.value.severity) params.append('severity', filters.value.severity);
        
        const url = `/api/v1/admin/audit/logs/export-pdf${params.toString() ? '?' + params.toString() : ''}`;
        
        console.log('URL:', url);
        
        const response = await axios.get(url, { 
            responseType: 'blob',
            params: {
                start_date: filters.value.start_date,
                end_date: filters.value.end_date,
                action: filters.value.action,
                severity: filters.value.severity
            }
        });
        
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `auditoria_${new Date().toISOString().slice(0, 19)}.pdf`;
        link.click();
        URL.revokeObjectURL(link.href);
        
    } catch (error) {
        console.error('Error exportando PDF:', error);
        Swal.fire('Error', 'No se pudo exportar el PDF', 'error');
    }
};

const exportCsv = async () => {
    try {
        const response = await axios.get('/api/v1/admin/audit/logs/export-csv', { 
            params: filters.value,
            responseType: 'blob' 
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'auditoria.csv');
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        Swal.fire('Error', 'No se pudo exportar el CSV', 'error');
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('es-ES');
};

const getSeverityClass = (severity) => {
    const classes = {
        info: 'bg-blue-100 text-blue-700',
        warning: 'bg-yellow-100 text-yellow-700',
        danger: 'bg-red-100 text-red-700',
        critical: 'bg-purple-100 text-purple-700'
    };
    return classes[severity] || 'bg-gray-100 text-gray-700';
};

const prevPage = () => {
    if (logs.value.prev_page_url) {
        loadLogsPage(logs.value.prev_page_url);
    }
};

const nextPage = () => {
    if (logs.value.next_page_url) {
        loadLogsPage(logs.value.next_page_url);
    }
};

const loadLogsPage = async (url) => {
    try {
        const response = await axios.get(url);
        logs.value = response.data.logs;
        stats.value = response.data.stats;
    } catch (error) {
        console.error('Error loading page:', error);
    }
};

onMounted(() => {
    loadLogs();
});
</script>