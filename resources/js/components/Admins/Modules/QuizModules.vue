<!-- resources/js/components/Admins/Modules/QuizModules.vue -->
<template>
    <div class="p-6 max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900">📦 Módulos del Examen</h1>
            <p class="text-gray-500">{{ quiz?.title }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Módulos disponibles -->
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-black">📚 Módulos Disponibles</h3>
                </div>
                <div class="divide-y max-h-96 overflow-y-auto">
                    <div v-for="module in availableModules" :key="module.id" class="p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold">{{ module.title }}</p>
                                <p class="text-xs text-gray-500">{{ getTypeLabel(module.type) }}</p>
                            </div>
                            <button @click="addModule(module.id)" 
                                    class="bg-indigo-600 text-white px-3 py-1 rounded-lg text-xs font-bold">
                                + Agregar
                            </button>
                        </div>
                    </div>
                    <div v-if="availableModules.length === 0" class="p-8 text-center text-gray-400">
                        No hay módulos disponibles
                    </div>
                </div>
            </div>

            <!-- Módulos asignados -->
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-black">✅ Módulos Asignados</h3>
                </div>
                <div class="divide-y max-h-96 overflow-y-auto">
                    <div v-for="(module, index) in assignedModules" :key="module.id" class="p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold">{{ module.title }}</p>
                                <p class="text-xs text-gray-500">{{ getTypeLabel(module.type) }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button @click="moveUp(index)" v-if="index > 0" class="text-gray-400 hover:text-gray-600">↑</button>
                                <button @click="moveDown(index)" v-if="index < assignedModules.length - 1" class="text-gray-400 hover:text-gray-600">↓</button>
                                <button @click="removeModule(module.id)" class="text-red-500 hover:text-red-700">🗑️</button>
                            </div>
                        </div>
                    </div>
                    <div v-if="assignedModules.length === 0" class="p-8 text-center text-gray-400">
                        No hay módulos asignados
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <router-link :to="{ name: 'quiz.edit', params: { id: quizId } }" 
                         class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700">
                Guardar Cambios
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const quizId = route.params.id;
const quiz = ref(null);
const availableModules = ref([]);
const assignedModules = ref([]);

const getTypeLabel = (type) => {
    const labels = { 'reading': '📖 Lectura', 'listening': '🎧 Listening', 'mixed': '🎯 Mixto' };
    return labels[type] || type;
};

const loadQuiz = async () => {
    try {
        const response = await axios.get(`/api/v1/admin/quizzes/${quizId}`);
        quiz.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error loading quiz:', error);
    }
};

const loadModules = async () => {
    try {
        const response = await axios.get('/api/v1/admin/modules');
        const allModules = response.data.data || response.data;
        
        // Obtener módulos ya asignados
        const modulesResponse = await axios.get(`/api/v1/admin/quizzes/${quizId}/modules`);
        const assigned = modulesResponse.data.data || modulesResponse.data;
        
        assignedModules.value = assigned;
        availableModules.value = allModules.filter(m => !assigned.some(a => a.id === m.id));
    } catch (error) {
        console.error('Error loading modules:', error);
    }
};

const addModule = async (moduleId) => {
    try {
        await axios.post(`/api/v1/admin/quizzes/${quizId}/modules/${moduleId}`);
        await loadModules();
        Swal.fire('Éxito', 'Módulo agregado al examen', 'success');
    } catch (error) {
        Swal.fire('Error', 'No se pudo agregar el módulo', 'error');
    }
};

const removeModule = async (moduleId) => {
    try {
        await axios.delete(`/api/v1/admin/quizzes/${quizId}/modules/${moduleId}`);
        await loadModules();
        Swal.fire('Éxito', 'Módulo eliminado del examen', 'success');
    } catch (error) {
        Swal.fire('Error', 'No se pudo eliminar el módulo', 'error');
    }
};

const moveUp = (index) => {
    if (index > 0) {
        const temp = assignedModules.value[index];
        assignedModules.value[index] = assignedModules.value[index - 1];
        assignedModules.value[index - 1] = temp;
        updateOrder();
    }
};

const moveDown = (index) => {
    if (index < assignedModules.value.length - 1) {
        const temp = assignedModules.value[index];
        assignedModules.value[index] = assignedModules.value[index + 1];
        assignedModules.value[index + 1] = temp;
        updateOrder();
    }
};

const updateOrder = async () => {
    const order = assignedModules.value.map((m, idx) => ({ id: m.id, order: idx + 1 }));
    await axios.put(`/api/v1/admin/quizzes/${quizId}/modules/order`, { order });
};

onMounted(() => {
    loadQuiz();
    loadModules();
});
</script>