<template>
    <div class="container mx-auto p-4 max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Mi Historial de Exámenes</h1>

        <div v-if="loading" class="text-center py-10">Cargando...</div>

        <div v-else-if="history.length === 0" class="bg-blue-50 p-6 rounded-lg text-center">
            <p class="text-blue-700">Aún no has realizado ningún examen.</p>
        </div>

        <div v-else class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <th class="px-5 py-3 border-b text-left">Examen</th>
                        <th class="px-5 py-3 border-b text-left">Fecha</th>
                        <th class="px-5 py-3 border-b text-center">Puntaje</th>
                        <th class="px-5 py-3 border-b text-center">Estado</th>
                        <th class="px-5 py-3 border-b text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="attempt in history" :key="attempt.id" class="hover:bg-gray-50">
                        <td class="px-5 py-4 border-b text-sm font-bold">{{ attempt.quiz_name }}</td>
                        <td class="px-5 py-4 border-b text-sm">{{ formatDate(attempt.completed_at) }}</td>
                        
                        <td class="px-5 py-4 border-b text-center">
                            <span class="text-lg font-bold" :class="attempt.score >= 60 ? 'text-green-600' : 'text-red-600'">
                                {{ attempt.score }} / 100
                            </span>
                        </td>

                        <td class="px-5 py-4 border-b text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-bold uppercase" 
                                  :class="attempt.score >= 60 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                {{ attempt.score >= 60 ? 'Aprobado' : 'Reprobado' }}
                            </span>
                        </td>

                        <td class="px-5 py-4 border-b text-center">
                           <router-link :to="{ name: 'student.results.comp', params: { id: attempt.id } }" 
             class="text-blue-600 hover:text-blue-900 font-semibold">
    Ver Detalle
</router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const history = ref([]);
const loading = ref(true);

const loadHistory = async () => {
    try {
        const response = await axios.get('/api/v1/student/results-history');
        history.value = response.data.data;
    } catch (error) {
        console.error("Error al cargar el historial", error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('es-ES', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
};

onMounted(loadHistory);
</script>