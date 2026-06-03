<template>
  <div class="p-6 bg-white shadow rounded-lg">
    <div class="mt-6 border-t pt-6">
      <h3 class="font-bold mb-2">Paso 2: Subir Nuevo SQL</h3>
      <input 
        type="file" 
        @change="handleFileSelect" 
        accept=".sql"
        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
      />
      
      <button 
        @click="uploadFile" 
        :disabled="!selectedFile || loading"
        class="mt-4 w-full py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:bg-gray-400"
      >
        {{ loading ? 'Subiendo datos...' : 'Cargar SQL en la Base de Datos' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const selectedLanguage = ref('1'); // Por defecto o dinámico
const selectedFile = ref(null);
const loading = ref(false);

const handleFileSelect = (event) => {
  selectedFile.value = event.target.files[0];
};

const uploadFile = async () => {
  if (!selectedFile.value) return;

  loading.value = true;
  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('language_id', selectedLanguage.value);

  try {
    const response = await axios.post('/api/v1/admin/quizzes/upload-sql', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    alert(response.data.message);
  } catch (error) {
    alert("Error al subir el archivo.");
  } finally {
    loading.value = false;
  }
};
</script>