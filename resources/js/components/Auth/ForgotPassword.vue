<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-6">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8 border border-slate-100">
      <header class="text-center mb-8">
        <h1 class="text-2xl font-black text-slate-800">¿Olvidaste tu clave?</h1>
        <p class="text-slate-500 mt-2">Ingresa tu correo y te enviaremos un enlace para recuperarla.</p>
      </header>

      <form @submit.prevent="sendResetLink" class="space-y-6">
        <div class="space-y-2">
          <label class="text-sm font-bold text-slate-700 ml-1">Correo Electrónico</label>
          <input 
            v-model="email" 
            type="email" 
            required
            placeholder="ejemplo@correo.com"
            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all"
          />
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-indigo-100 disabled:opacity-50"
        >
          {{ loading ? 'Enviando...' : 'Enviar Enlace' }}
        </button>
      </form>

      <div class="mt-8 text-center">
        <router-link to="/login" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">
          ← Volver al inicio de sesión
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const email = ref('');
const loading = ref(false);

const sendResetLink = async () => {
  loading.value = true;
  try {
    await axios.post('/api/v1/password/email', { email: email.value });
    Swal.fire({
      title: '¡Correo Enviado!',
      text: 'Revisa tu bandeja de entrada (y la carpeta de spam).',
      icon: 'success',
      confirmButtonColor: '#4f46e5'
    });
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'No se pudo procesar la solicitud.', 'error');
  } finally {
    loading.value = false;
  }
};
</script>