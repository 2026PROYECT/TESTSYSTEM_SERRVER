<template>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Vista Previa</h2>
        
        <div class="flex flex-col items-center">
            <!-- Input para contenido -->
            <div class="w-full mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Contenido para prueba
                </label>
                <div class="flex gap-2">
                    <input 
                        v-model="localContent" 
                        type="text" 
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg"
                        placeholder="URL o texto"
                    >
                    <button 
                        @click="generatePreview"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
                    >
                        Generar
                    </button>
                </div>
            </div>

            <!-- Vista previa del QR -->
            <div v-if="previewImage" class="mt-4 text-center">
                <div class="border rounded-lg p-4 bg-white inline-block">
                    <img :src="previewImage" alt="Vista previa QR" class="mx-auto" style="width: 200px; height: 200px;">
                </div>
                <p class="mt-2 text-sm text-gray-500 break-all">{{ localContent }}</p>
                <button 
                    @click="downloadPreview"
                    class="mt-3 text-indigo-600 hover:text-indigo-800 text-sm"
                >
                    📥 Descargar QR
                </button>
            </div>

            <div v-else class="text-center text-gray-400 py-8">
                <p>Ingresa un contenido y haz clic en "Generar"</p>
                <p class="text-xs mt-2">Esta vista previa es temporal, no se guarda</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { qrApi } from '@/api/qr';

const localContent = ref('');
const previewImage = ref(null);
let currentBlob = null;

const generatePreview = async () => {
    if (!localContent.value) return;
    
    try {
        const response = await qrApi.generateQR(localContent.value, 300);
        currentBlob = response.data;
        previewImage.value = URL.createObjectURL(response.data);
    } catch (error) {
        console.error('Error:', error);
        alert('Error al generar el QR');
    }
};

const downloadPreview = () => {
    if (currentBlob) {
        const url = URL.createObjectURL(currentBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `qr-${Date.now()}.svg`;
        a.click();
        URL.revokeObjectURL(url);
    }
};
</script>