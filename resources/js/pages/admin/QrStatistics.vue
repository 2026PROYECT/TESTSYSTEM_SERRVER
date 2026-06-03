<template>
    <div class="p-4 md:p-6">
        <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
            <!-- Header -->
            <div class="border-b border-gray-200 pb-4 mb-6">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-2xl md:text-3xl">📊</span>
                    Estadísticas de Escaneos QR
                </h1>
                <p class="text-gray-500 text-sm mt-1">Monitoreo y análisis de códigos QR generados</p>
            </div>

            <!-- Tarjetas de estadísticas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-indigo-100 text-sm">Total QRs</p>
                            <p class="text-3xl font-bold">{{ totalQrs }}</p>
                        </div>
                        <div class="text-3xl opacity-80">📱</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm">Total Escaneos</p>
                            <p class="text-3xl font-bold">{{ totalScans }}</p>
                        </div>
                        <div class="text-3xl opacity-80">👆</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Promedio por QR</p>
                            <p class="text-3xl font-bold">{{ avgScans }}</p>
                        </div>
                        <div class="text-3xl opacity-80">📈</div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">QR más escaneado</p>
                            <p class="text-xl font-bold truncate max-w-[150px]">{{ topQr?.title || '---' }}</p>
                        </div>
                        <div class="text-3xl opacity-80">🏆</div>
                    </div>
                </div>
            </div>

            <!-- Tabla de QRs -->
            <div class="mt-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Lista de Códigos QR</h2>
                    <div class="relative">
                        <input 
                            v-model="searchTerm"
                            type="text"
                            placeholder="Buscar QR..."
                            class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
                    </div>
                </div>

                <div v-if="loading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-500">Cargando datos...</p>
                </div>

                <div v-else-if="filteredQrs.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
                    <span class="text-4xl">📭</span>
                    <p class="text-gray-500 mt-2">No hay códigos QR registrados</p>
                    <router-link 
                        :to="{ name: 'admin.qr-manager' }"
                        class="inline-block mt-3 text-indigo-600 hover:text-indigo-800"
                    >
                        + Crear mi primer QR
                    </router-link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contenido</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Escaneos</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="qr in filteredQrs" :key="qr.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ qr.title }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                                    {{ qr.content }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                          :class="(qr.scans || 0) > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                                        {{ qr.scans || 0 }} escaneos
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ formatDate(qr.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <div class="flex justify-center gap-2">
                                        <button 
                                            @click="viewQR(qr)"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            title="Ver detalles"
                                        >
                                            👁️
                                        </button>
                                        <button 
                                            @click="downloadQR(qr)"
                                            class="text-green-600 hover:text-green-800"
                                            title="Descargar QR"
                                        >
                                            📥
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de detalles -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">{{ selectedQr?.title }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>
                <div class="text-center py-4">
                    <div class="bg-gray-100 inline-block p-4 rounded-lg mb-4">
                        <img :src="qrImageUrl" alt="QR" class="w-48 h-48 mx-auto" v-if="qrImageUrl" />
                        <div v-else class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                            <span class="text-gray-400">Cargando...</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 break-all">Contenido: {{ selectedQr?.content }}</p>
                    <p class="text-xs text-gray-400 mt-2">Escaneos: {{ selectedQr?.scans || 0 }}</p>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="downloadQR(selectedQr)" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
                        Descargar QR
                    </button>
                    <button @click="closeModal" class="flex-1 border border-gray-300 py-2 rounded-lg hover:bg-gray-50">
                        Cerrar
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

const loading = ref(false);
const qrs = ref([]);
const searchTerm = ref('');
const showModal = ref(false);
const selectedQr = ref(null);
const qrImageUrl = ref('');

// Computed
const totalQrs = computed(() => qrs.value.length);
const totalScans = computed(() => qrs.value.reduce((sum, qr) => sum + (qr.scans || 0), 0));
const avgScans = computed(() => totalQrs.value > 0 ? Math.round(totalScans.value / totalQrs.value) : 0);
const topQr = computed(() => {
    if (qrs.value.length === 0) return null;
    return [...qrs.value].sort((a, b) => (b.scans || 0) - (a.scans || 0))[0];
});
const filteredQrs = computed(() => {
    if (!searchTerm.value) return qrs.value;
    const term = searchTerm.value.toLowerCase();
    return qrs.value.filter(qr => 
        qr.title.toLowerCase().includes(term) || 
        qr.content.toLowerCase().includes(term)
    );
});

// Métodos
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const loadData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/qrs');
        qrs.value = response.data.data?.data || response.data.data || [];
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudieron cargar las estadísticas', 'error');
    } finally {
        loading.value = false;
    }
};

const viewQR = async (qr) => {
    selectedQr.value = qr;
    showModal.value = true;
    
    try {
        const response = await axios.post('/api/v1/admin/qr/generate', {
            content: qr.content,
            size: 300
        }, {
            responseType: 'blob'
        });
        qrImageUrl.value = URL.createObjectURL(response.data);
    } catch (error) {
        console.error('Error:', error);
    }
};

const downloadQR = async (qr) => {
    try {
        const response = await axios.post('/api/v1/admin/qr/generate', {
            content: qr.content,
            size: 500
        }, {
            responseType: 'blob'
        });
        
        const url = URL.createObjectURL(response.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = `qr-${qr.title.replace(/\s/g, '-')}.svg`;
        a.click();
        URL.revokeObjectURL(url);
        
        Swal.fire('Éxito', 'QR descargado correctamente', 'success');
    } catch (error) {
        Swal.fire('Error', 'No se pudo descargar el QR', 'error');
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedQr.value = null;
    if (qrImageUrl.value) {
        URL.revokeObjectURL(qrImageUrl.value);
        qrImageUrl.value = '';
    }
};

onMounted(() => {
    loadData();
});
</script>

<style scoped>
/* Transiciones y estilos adicionales */
tr {
    transition: background-color 0.2s;
}
</style>