<template>
    <div class="container mx-auto p-6">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <router-link 
                    :to="{ name: 'admin.modules.index' }" 
                    class="text-indigo-600 hover:text-indigo-800 mb-2 inline-flex items-center"
                >
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver a Módulos
                </router-link>
                <h1 class="text-2xl font-bold text-gray-800">Preguntas del Módulo</h1>
                <p class="text-gray-600 mt-1">{{ module?.title }}</p>
            </div>
            <button 
                @click="showQuestionModal = true"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nueva Pregunta
            </button>
        </div>

        <!-- Tabla de Preguntas -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opciones</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Respuesta Correcta</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Puntos</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Orden</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(question, index) in questions" :key="question.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                            <div class="truncate">{{ question.question_text }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="space-y-1">
                                <div v-if="question.option_a" class="text-xs"><span class="font-bold">A:</span> {{ truncateText(question.option_a, 30) }}</div>
                                <div v-if="question.option_b" class="text-xs"><span class="font-bold">B:</span> {{ truncateText(question.option_b, 30) }}</div>
                                <div v-if="question.option_c" class="text-xs"><span class="font-bold">C:</span> {{ truncateText(question.option_c, 30) }}</div>
                                <div v-if="question.option_d" class="text-xs"><span class="font-bold">D:</span> {{ truncateText(question.option_d, 30) }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Opción {{ getLetterFromNumber(question.right_answer) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">{{ question.points }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <div class="flex items-center justify-center space-x-2">
                                <button 
                                    @click="moveQuestion(question.id, 'up')"
                                    :disabled="index === 0"
                                    class="text-gray-400 hover:text-indigo-600 disabled:opacity-50"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                                <span>{{ question.order_position || index + 1 }}</span>
                                <button 
                                    @click="moveQuestion(question.id, 'down')"
                                    :disabled="index === questions.length - 1"
                                    class="text-gray-400 hover:text-indigo-600 disabled:opacity-50"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <button 
                                @click="editQuestion(question)"
                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                            >
                                Editar
                            </button>
                            <button 
                                @click="deleteQuestion(question.id)"
                                class="text-red-600 hover:text-red-900"
                            >
                                Eliminar
                            </button>
                        </td>
                    </tr>
                    <tr v-if="questions.length === 0">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No hay preguntas para este módulo
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal para Crear/Editar Pregunta -->
        <div v-if="showQuestionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">{{ editingQuestion ? 'Editar Pregunta' : 'Nueva Pregunta' }}</h3>
                    
                    <form @submit.prevent="saveQuestion">
                        <!-- Pregunta -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Texto de la Pregunta *</label>
                            <textarea 
                                v-model="questionForm.question_text"
                                rows="3"
                                class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            ></textarea>
                        </div>

                        <!-- Opciones -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Opción A *</label>
                                <input 
                                    type="text" 
                                    v-model="questionForm.option_a"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Opción B *</label>
                                <input 
                                    type="text" 
                                    v-model="questionForm.option_b"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Opción C</label>
                                <input 
                                    type="text" 
                                    v-model="questionForm.option_c"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Opción D</label>
                                <input 
                                    type="text" 
                                    v-model="questionForm.option_d"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                        </div>

                        <!-- Respuesta Correcta y Puntos -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Respuesta Correcta *</label>
                                <select 
                                    v-model="questionForm.right_answer"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                >
                                    <option :value="1">Opción A</option>
                                    <option :value="2">Opción B</option>
                                    <option :value="3">Opción C</option>
                                    <option :value="4">Opción D</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Puntos *</label>
                                <input 
                                    type="number" 
                                    v-model.number="questionForm.points"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                    min="1"
                                    max="100"
                                >
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <button 
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button 
                                type="submit"
                                :disabled="saving"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ saving ? 'Guardando...' : 'Guardar Pregunta' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const moduleId = route.params.id;

// Estados
const module = ref(null);
const questions = ref([]);
const showQuestionModal = ref(false);
const editingQuestion = ref(null);
const saving = ref(false);

// Formulario
const questionForm = ref({
    question_text: '',
    option_a: '',
    option_b: '',
    option_c: '',
    option_d: '',
    right_answer: 1,
    points: 10
});

// Métodos
const loadModule = async () => {
    try {
        const response = await axios.get(`/api/v1/admin/modules/${moduleId}`);
        module.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error cargando módulo:', error);
        alert('Error al cargar el módulo');
    }
};

const loadQuestions = async () => {
    try {
        const response = await axios.get(`/api/v1/admin/modules/${moduleId}/questions`);
        questions.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error cargando preguntas:', error);
        questions.value = [];
    }
};

const saveQuestion = async () => {
    saving.value = true;
    try {
        if (editingQuestion.value) {
            // Actualizar pregunta existente
            await axios.put(`/api/v1/admin/modules/${moduleId}/questions/${editingQuestion.value.id}`, questionForm.value);
            alert('Pregunta actualizada correctamente');
        } else {
            // Crear nueva pregunta
            await axios.post(`/api/v1/admin/modules/${moduleId}/questions`, questionForm.value);
            alert('Pregunta creada correctamente');
        }
        
        closeModal();
        await loadQuestions();
    } catch (error) {
        console.error('Error guardando pregunta:', error);
        alert(error.response?.data?.message || 'Error al guardar la pregunta');
    } finally {
        saving.value = false;
    }
};

const editQuestion = (question) => {
    editingQuestion.value = question;
    questionForm.value = {
        question_text: question.question_text,
        option_a: question.option_a,
        option_b: question.option_b,
        option_c: question.option_c,
        option_d: question.option_d,
        right_answer: question.right_answer,
        points: question.points
    };
    showQuestionModal.value = true;
};

const deleteQuestion = async (questionId) => {
    if (!confirm('¿Estás seguro de eliminar esta pregunta?')) return;
    
    try {
        await axios.delete(`/api/v1/admin/modules/${moduleId}/questions/${questionId}`);
        alert('Pregunta eliminada correctamente');
        await loadQuestions();
    } catch (error) {
        console.error('Error eliminando pregunta:', error);
        alert('Error al eliminar la pregunta');
    }
};

const moveQuestion = async (questionId, direction) => {
    try {
        await axios.post(`/api/v1/admin/modules/${moduleId}/questions/${questionId}/move`, { direction });
        await loadQuestions();
    } catch (error) {
        console.error('Error moviendo pregunta:', error);
        alert('Error al mover la pregunta');
    }
};

const getLetterFromNumber = (num) => {
    const letters = { 1: 'A', 2: 'B', 3: 'C', 4: 'D' };
    return letters[num] || '?';
};

const truncateText = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

const closeModal = () => {
    showQuestionModal.value = false;
    editingQuestion.value = null;
    questionForm.value = {
        question_text: '',
        option_a: '',
        option_b: '',
        option_c: '',
        option_d: '',
        right_answer: 1,
        points: 10
    };
};

onMounted(() => {
    loadModule();
    loadQuestions();
});
</script>