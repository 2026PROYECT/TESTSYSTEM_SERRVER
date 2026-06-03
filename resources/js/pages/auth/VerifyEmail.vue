<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50">
    <div class="max-w-md w-full bg-white p-8 rounded-3xl shadow-xl text-center">
      <div v-if="verifying" class="space-y-4">
        <div class="animate-spin h-12 w-12 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto"></div>
        <h2 class="text-xl font-bold text-slate-800">Procesando verificación...</h2>
        <p class="text-slate-500 text-sm">Estamos validando tu cuenta con los servidores de la EMI.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const verifying = ref(true)

onMounted(async () => {
    try {
        // Llamamos a la API de Laravel de forma "oculta" para el usuario
        const response = await axios.get(`/api/v1/verify-email/${route.params.token}`)
        
        await Swal.fire({
            title: '¡Cuenta Verificada!',
            text: 'Tu correo ha sido validado con éxito. Ahora la administración revisará tu perfil.',
            icon: 'success',
            confirmButtonText: 'Ir al Login',
            confirmButtonColor: '#4f46e5'
        })
        
        router.push('/login')
    } catch (e) {
        Swal.fire({
            title: 'Error de Verificación',
            text: e.response?.data?.message || 'El enlace es inválido o ha expirado.',
            icon: 'error'
        }).then(() => router.push('/login'))
    } finally {
        verifying.value = false
    }
})
</script>