<template>
    <div class="main-history-container min-h-screen bg-gray-50/50 p-6 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight">Mis Exámenes Modulares</h1>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Historial de evaluaciones modulares</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div v-if="isLoading" class="p-20 text-center text-gray-400">
                    <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-4"></div>
                    <p class="font-bold text-xs uppercase tracking-widest">Cargando historial...</p>
                </div>

                <div v-else-if="attempts.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Idioma</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Puntaje (%)</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Estado</th>
                                <th class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 bg-white">
                            <tr v-for="attempt in attempts" :key="attempt.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600">
                                    {{ attempt.date }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded-lg">
                                        {{ attempt.language_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap font-mono font-bold text-indigo-600">
                                    {{ attempt.total_percentage }}%
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span :class="attempt.total_percentage >= 60 ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100'" 
                                          class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border">
                                        {{ attempt.total_percentage >= 60 ? 'Aprobado' : 'Reprobado' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <router-link :to="{ name: 'student.modular.results', params: { id: attempt.attempt_id } }" 
                                                 class="bg-indigo-600 text-white px-5 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all">
                                        Ver Detalles
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-20 text-center">
                    <div class="text-6xl mb-4">📦</div>
                    <h3 class="text-xl font-black text-gray-800 tracking-tight">Sin exámenes modulares</h3>
                    <p class="text-gray-400 text-sm font-medium">Aún no has realizado ningún examen modular.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const isLoading = ref(true);
const attempts = ref([]);

const fetchModularHistory = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/v1/student/modular-history');
        attempts.value = response.data.attempts || [];
    } catch (error) {
        console.error("Error cargando historial modular:", error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchModularHistory();
});
</script>