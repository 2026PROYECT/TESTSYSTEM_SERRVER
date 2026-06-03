<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-6">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8 border border-slate-100">
      <header class="text-center mb-8">
        <h1 class="text-2xl font-black text-slate-800">Nueva Contraseña</h1>
        <p class="text-slate-500 mt-2">Crea una clave segura para volver a entrar.</p>
      </header>

      <form @submit.prevent="handleReset" class="space-y-6">
        <div class="space-y-2">
          <label class="text-sm font-bold text-slate-700 ml-1">Nueva Contraseña</label>
          <input v-model="form.password" type="password" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all" />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-bold text-slate-700 ml-1">Confirmar Contraseña</label>
          <input v-model="form.password_confirmation" type="password" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all" />
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full bg-slate-900 hover:bg-black text-white font-bold py-3 rounded-xl transition-all shadow-lg disabled:opacity-50"
        >
          {{ loading ? 'Actualizando...' : 'Restablecer Contraseña' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const loading = ref(false);

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: ''
});

// Capturamos los datos de la URL al cargar la página
onMounted(() => {
  form.token = route.query.token;
  form.email = route.query.email;

  if (!form.token || !form.email) {
    Swal.fire('Error', 'El enlace es inválido o ha expirado.', 'error');
    router.push('/login');
  }
});

const handleReset = async () => {
  loading.value = true;
  try {
    await axios.post('/api/v1/password/reset', form);
    await Swal.fire('¡Éxito!', 'Tu contraseña ha sido cambiada. Ya puedes iniciar sesión.', 'success');
    router.push('/login');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Ocurrió un error.', 'error');
  } finally {
    loading.value = false;
  }
};
</script>