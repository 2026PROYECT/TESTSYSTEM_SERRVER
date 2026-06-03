<template>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900">Gestión de Backups</h1>
            <p class="text-gray-500">Administra las copias de seguridad de la base de datos</p>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Total Backups</p>
                <p class="text-2xl font-black">{{ stats.total_backups || 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Espacio Total</p>
                <p class="text-2xl font-black">{{ stats.total_size || 0 }} MB</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Último Backup</p>
                <p class="text-sm font-bold">{{ stats.last_backup || 'Ninguno' }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border">
                <p class="text-xs text-gray-400">Acción Rápida</p>
                <div class="flex gap-2">
                    <button @click="createBackup(true)" :disabled="loading" class="flex-1 bg-indigo-600 text-white px-3 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700">
                        <span v-if="loading">⏳...</span>
                        <span v-else>📦 ZIP</span>
                    </button>
                    <button @click="createBackup(false)" :disabled="loading" class="flex-1 bg-gray-600 text-white px-3 py-2 rounded-xl text-sm font-bold hover:bg-gray-700">
                        <span v-if="loading">⏳...</span>
                        <span v-else>📄 SQL</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Opciones adicionales -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border mb-6">
            <div class="flex flex-wrap gap-6 items-center">
                <label class="flex items-center gap-2">
                    <input type="checkbox" v-model="showCompressed" class="rounded">
                    <span class="text-sm">Mostrar solo backups comprimidos</span>
                </label>
                <div class="text-sm text-gray-500">
                    💡 Los backups comprimidos (ZIP) ocupan menos espacio
                </div>
            </div>
        </div>

        <!-- Tabla de backups -->
        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-black text-gray-800">📁 Backups Disponibles</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left p-4 text-xs font-black text-gray-400">#</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Nombre</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Fecha</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Tamaño</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Tipo</th>
                            <th class="text-left p-4 text-xs font-black text-gray-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(backup, index) in filteredBackups" :key="backup.name" class="border-b hover:bg-gray-50">
                            <td class="p-4 text-sm">{{ index + 1 }}</td>
                            <td class="p-4 text-sm font-mono text-xs">{{ backup.name }}</td>
                            <td class="p-4 text-sm">{{ backup.date }}</td>
                            <td class="p-4 text-sm">{{ backup.size }} MB</td>
                            <td class="p-4">
                                <span :class="backup.type === 'zip' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'" class="px-2 py-1 rounded-full text-xs font-bold">
                                    {{ backup.type === 'zip' ? '📦 ZIP' : '📄 SQL' }}
                                </span>
                             </td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <!-- ✅ Correcto: Pasa el objeto completo -->
<button @click="downloadBackup(backup)" class="text-indigo-600 hover:text-indigo-800" title="Descargar">
    📥
</button>
                                    <button @click="restoreBackup(backup.name)" class="text-yellow-600 hover:text-yellow-800" title="Restaurar">
                                        🔄
                                    </button>
                                    <button @click="deleteBackup(backup.name)" class="text-red-600 hover:text-red-800" title="Eliminar">
                                        🗑️
                                    </button>
                                </div>
                             </td>
                        </tr>
                        <tr v-if="filteredBackups.length === 0">
                            <td colspan="6" class="p-8 text-center text-gray-400">No hay backups disponibles</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Modal de confirmación para restaurar -->
        <div v-if="showRestoreModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6">
                <h3 class="text-xl font-black mb-4">⚠️ Restaurar Backup</h3>
                <p class="text-gray-600 mb-4">
                    ¿Estás seguro de que deseas restaurar el backup <strong>{{ restoreFilename }}</strong>?
                </p>
                <p class="text-red-600 text-sm mb-6">⚠️ Esta acción sobrescribirá la base de datos actual.</p>
                <div class="flex gap-3 justify-end">
                    <button @click="showRestoreModal = false" class="px-4 py-2 border rounded-xl">Cancelar</button>
                    <button @click="confirmRestore" class="px-4 py-2 bg-red-600 text-white rounded-xl">Confirmar Restauración</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const backups = ref([]);
const stats = ref({});
const loading = ref(false);
const showCompressed = ref(false);
const showRestoreModal = ref(false);
const restoreFilename = ref('');

const filteredBackups = computed(() => {
    if (!showCompressed.value) return backups.value;
    return backups.value.filter(b => b.type === 'zip');
});

// Método para cargar backups
const loadBackups = async () => {
    try {
        const response = await axios.get('/api/v1/backup-hashes');
        backups.value = response.data;
        
        const totalSize = backups.value.reduce((sum, b) => sum + (b.size || 0), 0);
        stats.value = {
            total_backups: backups.value.length,
            total_size: totalSize.toFixed(2),
            last_backup: backups.value[0]?.date || null,
            last_backup_name: backups.value[0]?.name || null,
        };
    } catch (error) {
        console.error('Error loading backups:', error);
        Swal.fire('Error', 'No se pudieron cargar los backups', 'error');
    }
};

// ✅ Método correcto
const downloadBackup = (backup) => {
    if (!backup.hash) {
        Swal.fire('Error', 'No se puede descargar este backup', 'error');
        return;
    }
    const url = `/api/v1/secure/download/${backup.hash}`;
    window.open(url, '_blank');
    Swal.fire('Descargando', 'El backup comenzará a descargarse', 'info');
};

// ✅ Método para crear backup
const createBackup = async (compress) => {
    loading.value = true;
    try {
        const response = await axios.post('/api/v1/admin/backups', { compress: compress });
        if (response.data.success) {
            Swal.fire('Éxito', `Backup ${compress ? 'comprimido (ZIP)' : 'SQL'} creado correctamente`, 'success');
            loadBackups();
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo crear el backup', 'error');
    } finally {
        loading.value = false;
    }
};

// ✅ Método para restaurar
const restoreBackup = (backup) => {
    restoreFilename.value = backup.name;
    showRestoreModal.value = true;
};

const confirmRestore = async () => {
    showRestoreModal.value = false;
    loading.value = true;
    try {
        const response = await axios.post('/api/v1/admin/backups/restore', { filename: restoreFilename.value });
        if (response.data.success) {
            Swal.fire('Éxito', 'Backup restaurado correctamente', 'success');
            loadBackups();
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo restaurar el backup', 'error');
    } finally {
        loading.value = false;
    }
};

// ✅ Método para eliminar
const deleteBackup = async (backup) => {
    const result = await Swal.fire({
        title: '¿Eliminar backup?',
        text: `¿Estás seguro de que deseas eliminar ${backup.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/v1/admin/backups/${backup.name}`);
            Swal.fire('Eliminado', 'Backup eliminado correctamente', 'success');
            loadBackups();
        } catch (error) {
            Swal.fire('Error', 'No se pudo eliminar el backup', 'error');
        }
    }
};

onMounted(() => {
    loadBackups();
});
</script>