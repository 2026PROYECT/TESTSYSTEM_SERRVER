<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Usuarios Pendientes de Aprobación</h2>
            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                {{ pendingUsers.length }} Solicitudes
            </span>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-[10px] leading-normal font-black">
                        <th class="py-3 px-4 text-left">Datos Personales</th>
                        <th class="py-3 px-4 text-left">Identidad (CI/SAGA)</th>
                        <th class="py-3 px-4 text-left">Carrera</th>
                        <th class="py-3 px-4 text-center">Documentación</th>
                        <th class="py-3 px-4 text-center">Fecha</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <tr v-if="isLoading">
                        <td colspan="6" class="py-10 text-center">Cargando aspirantes...</td>
                    </tr>
                    <tr v-else-if="pendingUsers.length === 0">
                        <td colspan="6" class="py-10 text-center text-gray-500">No hay solicitudes pendientes.</td>
                    </tr>
                    <tr v-for="user in pendingUsers" :key="user.id" class="border-b border-gray-200 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-left">
                            <div class="font-bold text-gray-800">{{ user.name }} {{ user.lastname }} {{ user.surname }}</div>
                            <div class="text-[11px] text-gray-400">{{ user.email }}</div>
                        </td>

                        <td class="py-3 px-4 text-left text-xs">
                            <div><span class="font-bold">CI:</span> {{ user.id_number }}</div>
                            <div><span class="font-bold text-indigo-600">SAGA:</span> {{ user.saga_code }}</div>
                        </td>

                        <td class="py-3 px-4 text-left text-xs italic">
                            {{ user.career ? user.career.name : 'No asignada' }}
                        </td>

                        <td class="py-3 px-4 text-center text-xl">
                            <div class="flex justify-center space-x-3">
                                <a :href="`/storage/${user.ci_front}`" target="_blank" title="Ver CI Frontal" class="hover:scale-110 transition cursor-pointer">🪪</a>
                                <a :href="`/storage/${user.ci_back}`" target="_blank" title="Ver CI Reverso" class="hover:scale-110 transition cursor-pointer">🆔</a>
                                <a :href="`/storage/${user.user_photo}`" target="_blank" title="Ver Selfie" class="hover:scale-110 transition cursor-pointer">🤳</a>
                            </div>
                        </td>

                        <td class="py-3 px-4 text-center text-[11px]">
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </td>

                        <td class="py-3 px-4 text-center">
                            <div class="flex item-center justify-center space-x-2">
                                <button @click="approveUser(user.id)" 
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold px-3 py-1.5 rounded-lg text-[10px] uppercase shadow-sm transition">
                                    Aprobar
                                </button>
                                <button @click="rejectUser(user.id)" 
                                        class="bg-red-500 hover:bg-red-600 text-white font-bold px-3 py-1.5 rounded-lg text-[10px] uppercase shadow-sm transition">
                                    Rechazar
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
import { onMounted, watch } from 'vue'
import usePendingUsers from '@/composables/pendingUsers'

const { pendingUsers, isLoading, getPendingUsers, approveUser, rejectUser } = usePendingUsers()

// 🔥 Para depurar
watch(pendingUsers, (newVal) => {
    console.log('pendingUsers actualizado:', newVal)
}, { deep: true })

onMounted(() => {
    getPendingUsers()
})
</script>