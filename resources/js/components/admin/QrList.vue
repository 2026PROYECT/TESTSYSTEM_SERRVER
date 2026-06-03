<template>
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold">Códigos QR Existentes</h2>
            <p class="text-sm text-gray-500">Total: {{ qrs.length }}</p>
        </div>

        <div v-if="loading" class="p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-gray-500">Cargando...</p>
        </div>

        <div v-else-if="qrs.length === 0" class="p-8 text-center text-gray-500">
            No hay códigos QR creados aún.
        </div>

        <div v-else class="divide-y divide-gray-200">
            <div v-for="qr in qrs" :key="qr.id" class="p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-800">{{ qr.title }}</h3>
                        <p class="text-sm text-gray-500 truncate">{{ qr.content }}</p>
                        <p v-if="qr.description" class="text-xs text-gray-400 mt-1">{{ qr.description }}</p>
                        <div class="flex gap-3 mt-2 text-xs text-gray-400">
                            <span>📊 Escaneos: {{ qr.scans || 0 }}</span>
                            <span>📏 Tamaño: {{ qr.size || 300 }}px</span>
                            <span>📅 {{ formatDate(qr.created_at) }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 ml-4">
                        <button 
                            @click="$emit('edit', qr)"
                            class="text-indigo-600 hover:text-indigo-800 p-1"
                            title="Editar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button 
                            @click="$emit('delete', qr.id)"
                            class="text-red-600 hover:text-red-800 p-1"
                            title="Eliminar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    qrs: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
});

defineEmits(['edit', 'delete']);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('es-ES');
};
</script>