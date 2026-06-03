<template>
    <div class="container mx-auto">
        <div class="flex flex-col gap-6 p-6 text-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-3">
                    <h4 class="text-xl font-bold text-gray-800">Quiz List</h4>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase rounded-full border border-indigo-200">
                        {{ currentLanguageName }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 shadow-sm">
                        <label for="filter-type" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo:</label>
                        <select 
                            id="filter-type" 
                            v-model="selectedType" 
                            @change="handleFilterChange"
                            class="bg-transparent border-none text-sm font-medium text-gray-700 p-0 pr-6 focus:ring-0 cursor-pointer"
                        >
                            <option value="">Todos los tipos</option>
                            <option 
                                v-for="quizType in quizTypes" 
                                :key="quizType.value" 
                                :value="quizType.value"
                            >
                                {{ quizType.label }}
                            </option>
                        </select>
                    </div>

                    <button
                        v-if="user?.isAdmin"
                        @click="showMaintenance = true"
                        class="inline-flex items-center px-4 py-2 font-semibold text-white rounded-lg bg-red-600 hover:bg-red-700 transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Mantenimiento SQL
                    </button>

                    <router-link
                        v-if="user?.isAdmin"
                        :to="{ name: 'quiz.create' }"
                        class="inline-flex items-center px-4 py-2 font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Quiz
                    </router-link>
                </div>
            </div>

            <div class="p-6 bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Mark</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass Mark</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Questions for the test</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="quiz in quizzes.data" :key="quiz.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ quiz.id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ quiz.title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span :class="getTypeBadgeClass(quiz.type)" class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize">
                                        {{ formatTypeName(quiz.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ quiz.subject_name || 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ quiz.total_mark }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ quiz.pass_mark }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ quiz.duration_minutes }} min</td>
                                <!-- 🔥 NUEVA COLUMNA: Total Questions -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold">
                                        {{ quiz.total_questions || 10 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <router-link 
                                        :to="{ name: 'quiz.edit', params: { id: quiz.id } }"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3 inline-flex items-center"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Editar
                                    </router-link>
                                    
                                    <button 
                                        @click="deleteQuiz(quiz.id)" 
                                        v-if="user?.isAdmin" 
                                        class="text-red-600 hover:text-red-900 inline-flex items-center"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <TailwindPagination :data="quizzes" @pagination-change-page="handlePageChange" />
                </div>
            </div>
        </div>

        <!-- Modal Mantenimiento SQL -->
        <div v-if="showMaintenance" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Mantenimiento de Base de Datos</h3>
                
                <div class="space-y-4">
                    <button 
                        @click="truncateLanguage"
                        :disabled="processing"
                        class="w-full bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition disabled:opacity-50"
                    >
                        🗑️ Limpiar todo ({{ currentLanguageName }})
                    </button>
                    
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            📤 Importar SQL Backup
                        </label>
                        <input 
                            type="file" 
                            accept=".sql" 
                            @change="onFileChange"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <button 
                            @click="uploadSql"
                            :disabled="processing || !selectedFile"
                            class="mt-3 w-full bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition disabled:opacity-50"
                        >
                            {{ processing ? 'Procesando...' : 'Subir y Restaurar' }}
                        </button>
                    </div>
                </div>
                
                <button 
                    @click="showMaintenance = false"
                    class="mt-4 w-full text-gray-500 hover:text-gray-700 font-medium py-2"
                >
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axios from 'axios';
import { TailwindPagination } from "laravel-vue-pagination";
import useQuiz from "@/composables/quiz"; 
import useAuth from "@/composables/auth";

// --- COMPOSABLES ---
const { quizzes, getQuizzes, deleteQuiz, isLoading } = useQuiz();
const { user, getUser } = useAuth();

// --- ESTADOS ---
const showMaintenance = ref(false);
const selectedFile = ref(null);
const processing = ref(false);
const languages = ref([]);
const quizTypes = ref([]);
const selectedType = ref('');

// --- COMPUTED PROPERTIES ---
const activeLangId = computed(() => {
    return localStorage.getItem('active_lang_id') || '1';
});

const currentLanguageName = computed(() => {
    if (!languages.value || languages.value.length === 0) {
        return 'Cargando...';
    }
    const lang = languages.value.find(l => Number(l.id) === Number(activeLangId.value));
    return lang ? lang.name : 'Idioma Seleccionado';
});

// --- MÉTODOS ---
const onFileChange = (e) => {
    selectedFile.value = e.target.files[0];
};

const handleFilterChange = async () => {
    await getQuizzes(1, selectedType.value);
};

const handlePageChange = async (page) => {
    await getQuizzes(page, selectedType.value);
};

const formatTypeName = (typeValue) => {
    if (!typeValue) return 'N/A';
    const match = quizTypes.value.find(t => t.value === typeValue);
    return match ? match.label : typeValue;
};

const getTypeBadgeClass = (typeValue) => {
    switch (typeValue) {
        case 'simulation':
            return 'bg-amber-50 text-amber-700 border border-amber-200';
        case 'final_exam':
            return 'bg-red-50 text-red-700 border border-red-200';
        case 'practice':
        default:
            return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    }
};

const truncateLanguage = async () => {
    const confirmMsg = `¿ESTÁS SEGURO? Se borrarán TODOS los quizzes, preguntas e intentos del idioma: ${currentLanguageName.value}.\n\nEsta acción no se puede deshacer.`;
    
    if (!confirm(confirmMsg)) return;
    
    processing.value = true;
    try {
        await axios.post('/api/v1/admin/maintenance/truncate', { 
            language_id: activeLangId.value 
        });
        alert('Tablas vaciadas correctamente para ' + currentLanguageName.value);
        await getQuizzes(1, selectedType.value);
    } catch (e) {
        console.error(e);
        alert('Error al limpiar las tablas. Verifique los permisos del administrador.');
    } finally {
        processing.value = false;
    }
};

const uploadSql = async () => {
    if (!selectedFile.value) {
        alert('Por favor, seleccione un archivo .sql primero.');
        return;
    }
    
    processing.value = true;
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('language_id', activeLangId.value);

    try {
        const response = await axios.post('/api/v1/admin/maintenance/upload-sql', formData, {
            headers: { 
                'Content-Type': 'multipart/form-data' 
            }
        });
        
        alert('¡Importación exitosa! Los datos se han cargado correctamente.');
        showMaintenance.value = false;
        selectedFile.value = null;
        await getQuizzes(1, selectedType.value);
    } catch (e) {
        console.error(e);
        const errorMsg = e.response?.data?.message || 'Error desconocido al importar.';
        alert('Error al importar el SQL: ' + errorMsg);
    } finally {
        processing.value = false;
    }
};

// Watch para cambios en el idioma
watch(activeLangId, async () => {
    await getQuizzes(1, selectedType.value);
});

// --- CICLO DE VIDA ---
onMounted(async () => {
    await getUser();
    
    try {
        const res = await axios.get('/api/v1/languages');
        languages.value = res.data.data || res.data || [];
    } catch (e) {
        console.error("Error cargando idiomas", e);
        languages.value = [];
    }

    try {
        const res = await axios.get('/api/v1/quizzes/types');
        quizTypes.value = res.data.data || res.data || [];
    } catch (e) {
        console.error("Error cargando tipos de exámenes", e);
        quizTypes.value = [];
    }
    
    await getQuizzes(1, selectedType.value);
});
</script>