<template>
    <div class="container mx-auto">
        <div class="flex flex-col gap-6 p-6 text-sm">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-3">
                    <h4 class="text-xl font-bold text-gray-800">Módulos del Sistema</h4>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase rounded-full border border-indigo-200">
                        {{ currentLanguageName }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Selector de Idioma -->
                    <select 
                        v-model="selectedLanguageId"
                        @change="changeLanguage"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option v-for="lang in languages" :key="lang.id" :value="lang.id">
                            {{ lang.name }}
                        </option>
                    </select>

                    <!-- Botón Vaciar Base de Datos -->
                    <button
                        v-if="user?.isAdmin"
                        @click="showCleanupModal = true"
                        class="inline-flex items-center px-4 py-2 font-semibold text-white rounded-lg bg-red-600 hover:bg-red-700 transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Vaciar BD
                    </button>

                    <!-- Botón Mantenimiento SQL -->
                    <button
                        v-if="user?.isAdmin"
                        @click="showMaintenance = true"
                        class="inline-flex items-center px-4 py-2 font-semibold text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Mantenimiento SQL
                    </button>

                    <router-link
                        v-if="user?.isAdmin"
                        :to="{ name: 'admin.modules.create' }"
                        class="inline-flex items-center px-4 py-2 font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Crear Módulo
                    </router-link>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tipo</label>
                        <select v-model="filters.type" @change="applyFilters" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Todos</option>
                            <option value="reading">Lectura</option>
                            <option value="listening">Audio</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nivel</label>
                        <select v-model="filters.level" @change="applyFilters" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Todos</option>
                            <option value="A1">Nivel A1</option>
                            <option value="A2">Nivel A2</option>
                            <option value="B1">Nivel B1</option>
                            <option value="B2">Nivel B2</option>
                            
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button @click="resetFilters" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Módulos -->
            <div class="p-6 bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Preguntas</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="module in modules.data" :key="module.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ module.id }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ module.title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span :class="getTypeBadgeClass(module.type)" class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize">
                                        {{ formatTypeName(module.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">
                                        Nivel {{ module.level }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDuration(module.duration_seconds) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <router-link 
                                        :to="{ name: 'admin.modules.questions', params: { id: module.id } }"
                                        class="inline-flex items-center px-2 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold hover:bg-indigo-100 transition-colors"
                                    >
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ module.questions_count || 0 }}
                                    </router-link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <router-link 
                                            :to="{ name: 'admin.modules.questions', params: { id: module.id } }"
                                            class="text-green-600 hover:text-green-900 inline-flex items-center px-2 py-1 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
                                            title="Gestionar preguntas"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Preguntas
                                        </router-link>
                                        
                                        <router-link 
                                            :to="{ name: 'admin.modules.edit', params: { id: module.id } }"
                                            class="text-indigo-600 hover:text-indigo-900 inline-flex items-center"
                                            title="Editar módulo"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </router-link>
                                        
                                        <button 
                                            @click="deleteModule(module.id)" 
                                            v-if="user?.isAdmin" 
                                            class="text-red-600 hover:text-red-900 inline-flex items-center"
                                            title="Eliminar módulo"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!modules.data || modules.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No hay módulos registrados
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    <TailwindPagination :data="modules" @pagination-change-page="handlePageChange" />
                </div>
            </div>
        </div>

        <!-- Modal Mantenimiento SQL -->
        <div v-if="showMaintenance" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeMaintenance">
            <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Mantenimiento de Datos</h3>
                    <button @click="closeMaintenance" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- Sección 1: Importar Módulos -->
                    <div class="border rounded-lg p-4 bg-blue-50">
                        <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                            <span class="text-lg">📦</span> 1. Importar Módulos (SQL)
                        </h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Archivo SQL de módulos
                            </label>
                            <input 
                                type="file" 
                                accept=".sql" 
                                @change="onModulesSQLChange"
                                ref="modulesFileInput"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                            />
                            <button 
                                @click="uploadModulesSQL"
                                :disabled="processingModules || !modulesSQLFile"
                                class="mt-3 w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50"
                            >
                                <span v-if="processingModules" class="inline-flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                </span>
                                <span v-else>📤 Importar Módulos</span>
                            </button>
                            <p class="text-xs text-gray-500 mt-2">
                                ℹ️ El archivo debe contener INSERT INTO modules
                            </p>
                        </div>
                    </div>

                    <!-- Sección 2: Importar Preguntas -->
                    <div class="border rounded-lg p-4 bg-purple-50">
                        <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                            <span class="text-lg">❓</span> 2. Importar Preguntas (SQL)
                        </h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Archivo SQL de preguntas
                            </label>
                            <input 
                                type="file" 
                                accept=".sql" 
                                @change="onQuestionsSQLChange"
                                ref="questionsFileInput"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700"
                            />
                            <button 
                                @click="uploadQuestionsSQL"
                                :disabled="processingQuestions || !questionsSQLFile"
                                class="mt-3 w-full bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition disabled:opacity-50"
                            >
                                <span v-if="processingQuestions" class="inline-flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                </span>
                                <span v-else>📤 Importar Preguntas</span>
                            </button>
                            <p class="text-xs text-gray-500 mt-2">
                                ℹ️ Los módulos deben existir antes de importar preguntas
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t">
                    <button 
                        @click="closeMaintenance"
                        class="w-full text-gray-500 hover:text-gray-700 font-medium py-2"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Vaciar Base de Datos (TRUNCATE) -->
        <div v-if="showCleanupModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeCleanupModal">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-red-600 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        ⚠️ Vaciar Base de Datos
                    </h3>
                    <button @click="closeCleanupModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800 font-semibold mb-2">⚠️ ADVERTENCIA</p>
                        <p class="text-red-700 text-sm">Esta acción ELIMINARÁ PERMANENTEMENTE todo el contenido de:</p>
                        <ul class="list-disc list-inside text-sm text-red-700 mt-2 space-y-1">
                            <li>Todos los módulos</li>
                            <li>Todas las preguntas de los módulos</li>
                        </ul>
                        <p class="text-red-800 font-bold text-sm mt-3">
                            ✅ Las tablas conservarán su estructura pero quedarán VACÍAS.
                        </p>
                        <p class="text-red-800 font-bold text-sm mt-2">
                            ⚠️ Esta operación NO se puede deshacer.
                        </p>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-yellow-800 mb-2">
                            Escribe <span class="font-bold">CONFIRMAR</span> para continuar:
                        </label>
                        <input 
                            v-model="cleanupConfirmationText"
                            type="text"
                            placeholder="Escribe CONFIRMAR aquí"
                            class="w-full px-3 py-2 border border-yellow-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                            @keyup.enter="executeCleanup"
                        />
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button 
                            @click="closeCleanupModal"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="executeCleanup"
                            :disabled="cleanupConfirmationText !== 'CONFIRMAR' || cleaning"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="cleaning" class="inline-flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Vaciar...
                            </span>
                            <span v-else>🗑️ Sí, vaciar todo</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from 'axios';
import { TailwindPagination } from "laravel-vue-pagination";
import useModule from "@/composables/module";
import useAuth from "@/composables/auth";

const { modules, getModules, deleteModule } = useModule();
const { user, getUser } = useAuth();

// Estados
const showMaintenance = ref(false);
const showCleanupModal = ref(false);
const cleanupConfirmationText = ref('');
const cleaning = ref(false);
const modulesSQLFile = ref(null);
const questionsSQLFile = ref(null);
const processingModules = ref(false);
const processingQuestions = ref(false);
const languages = ref([]);
const selectedLanguageId = ref(localStorage.getItem('active_lang_id') || '1');
const modulesFileInput = ref(null);
const questionsFileInput = ref(null);

// Filtros
const filters = ref({
    type: '',
    level: '',
    sort_by: 'id',
    sort_order: 'asc'
});

// Computed
const activeLangId = computed(() => selectedLanguageId.value);
const currentLanguageName = computed(() => {
    if (!languages.value.length) return 'Cargando...';
    const lang = languages.value.find(l => Number(l.id) === Number(activeLangId.value));
    return lang ? lang.name : 'Idioma Seleccionado';
});

// Métodos de filtros
const changeLanguage = () => {
    localStorage.setItem('active_lang_id', selectedLanguageId.value);
    applyFilters();
};

const applyFilters = async () => {
    await getModules(1, {
        language_id: activeLangId.value,
        type: filters.value.type,
        level: filters.value.level,
        sort_by: filters.value.sort_by,
        sort_order: filters.value.sort_order
    });
};

const resetFilters = () => {
    filters.value = {
        type: '',
        level: '',
        sort_by: 'id',
        sort_order: 'asc'
    };
    applyFilters();
};

// Vaciar Base de Datos (TRUNCATE)
const executeCleanup = async () => {
    if (cleanupConfirmationText.value !== 'CONFIRMAR') {
        alert('Debes escribir CONFIRMAR para continuar');
        return;
    }
    
    cleaning.value = true;
    
    try {
        const response = await axios.post('/api/v1/admin/maintenance/truncate-modules-questions', {
            confirm: 'YES'
        });
        
        if (response.data.success) {
            alert(`✅ ${response.data.message}\n\nLas tablas están vacías pero conservan su estructura.`);
            closeCleanupModal();
            await applyFilters(); // Recargar la lista (vacía)
        } else {
            alert(`❌ Error: ${response.data.message}`);
        }
    } catch (error) {
        console.error('Error:', error);
        const errorMsg = error.response?.data?.message || error.message;
        alert(`❌ Error al vaciar las tablas:\n${errorMsg}`);
    } finally {
        cleaning.value = false;
    }
};

const closeCleanupModal = () => {
    showCleanupModal.value = false;
    cleanupConfirmationText.value = '';
};

// Importar módulos
const uploadModulesSQL = async () => {
    if (!modulesSQLFile.value) {
        alert('❌ Por favor, seleccione un archivo .sql de módulos');
        return;
    }
    
    processingModules.value = true;
    const formData = new FormData();
    formData.append('file', modulesSQLFile.value);
    formData.append('language_id', activeLangId.value);
    formData.append('type', 'modules');

    try {
        const response = await axios.post('/api/v1/admin/maintenance/upload-sql', formData, {
            headers: { 
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json'
            }
        });
        
        if (response.data.success) {
            alert(`✅ ${response.data.message}\n\n📊 Importados: ${response.data.imported_count || 0}`);
            modulesSQLFile.value = null;
            
            if (modulesFileInput.value) {
                modulesFileInput.value.value = '';
            }
            
            await applyFilters();
            
            setTimeout(() => {
                if (confirm('✅ Módulos importados correctamente.\n\n¿Desea importar ahora las preguntas para estos módulos?')) {
                    const questionsSection = document.querySelector('.border-purple-50');
                    if (questionsSection) {
                        questionsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }, 500);
        } else {
            alert(`❌ Error: ${response.data.message}`);
        }
    } catch (e) {
        console.error('Error:', e);
        const errorMsg = e.response?.data?.message || e.message;
        alert(`❌ Error al importar módulos:\n${errorMsg}`);
    } finally {
        processingModules.value = false;
    }
};

// Importar preguntas
const uploadQuestionsSQL = async () => {
    if (!questionsSQLFile.value) {
        alert('❌ Por favor, seleccione un archivo .sql de preguntas');
        return;
    }
    
    processingQuestions.value = true;
    const formData = new FormData();
    formData.append('file', questionsSQLFile.value);
    formData.append('language_id', activeLangId.value);
    formData.append('type', 'questions');

    try {
        const response = await axios.post('/api/v1/admin/maintenance/upload-sql', formData, {
            headers: { 
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json'
            }
        });
        
        if (response.data.success) {
            let message = `✅ ${response.data.message}`;
            if (response.data.missing_modules && response.data.missing_modules.length > 0) {
                message += `\n\n⚠️ Módulos faltantes: ${response.data.missing_modules.join(', ')}`;
                message += `\n💡 Debe importar esos módulos primero.`;
            }
            alert(message);
            
            questionsSQLFile.value = null;
            
            if (questionsFileInput.value) {
                questionsFileInput.value.value = '';
            }
            
            await applyFilters();
        } else {
            alert(`❌ Error: ${response.data.message}`);
        }
    } catch (e) {
        console.error('Error:', e);
        const errorMsg = e.response?.data?.message || e.message;
        alert(`❌ Error al importar preguntas:\n${errorMsg}`);
    } finally {
        processingQuestions.value = false;
    }
};

// Manejadores de archivos
const onModulesSQLChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (!file.name.endsWith('.sql')) {
            alert('⚠️ El archivo debe tener extensión .sql');
            event.target.value = '';
            modulesSQLFile.value = null;
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            alert('⚠️ El archivo es demasiado grande (máximo 10MB)');
            event.target.value = '';
            modulesSQLFile.value = null;
            return;
        }
        
        modulesSQLFile.value = file;
    }
};

const onQuestionsSQLChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (!file.name.endsWith('.sql')) {
            alert('⚠️ El archivo debe tener extensión .sql');
            event.target.value = '';
            questionsSQLFile.value = null;
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            alert('⚠️ El archivo es demasiado grande (máximo 10MB)');
            event.target.value = '';
            questionsSQLFile.value = null;
            return;
        }
        
        questionsSQLFile.value = file;
    }
};

// Formateadores
const formatDuration = (seconds) => {
    if (!seconds) return 'N/A';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (hours > 0) return `${hours}h ${minutes}m`;
    if (minutes > 0) return `${minutes} min`;
    return `${seconds} seg`;
};

const formatTypeName = (typeValue) => {
    const types = { 
        'reading': 'Lectura', 
        'listening': 'Audición', 
        'mixed': 'Mixto',
        'theory': 'Teoría', 
        'practice': 'Práctica', 
        'exam': 'Examen' 
    };
    return types[typeValue] || typeValue;
};

const getTypeBadgeClass = (typeValue) => {
    const classes = {
        'reading': 'bg-blue-50 text-blue-700 border border-blue-200',
        'listening': 'bg-green-50 text-green-700 border border-green-200',
        'mixed': 'bg-purple-50 text-purple-700 border border-purple-200',
        'theory': 'bg-blue-50 text-blue-700 border border-blue-200',
        'practice': 'bg-green-50 text-green-700 border border-green-200',
        'exam': 'bg-red-50 text-red-700 border border-red-200'
    };
    return classes[typeValue] || 'bg-gray-50 text-gray-700 border border-gray-200';
};

const handlePageChange = async (page) => {
    await getModules(page, {
        language_id: activeLangId.value,
        type: filters.value.type,
        level: filters.value.level,
        sort_by: filters.value.sort_by,
        sort_order: filters.value.sort_order
    });
};

const closeMaintenance = () => {
    showMaintenance.value = false;
    modulesSQLFile.value = null;
    questionsSQLFile.value = null;
};

// Watch para cambios de idioma
watch(activeLangId, async () => {
    await applyFilters();
});

// Mounted
onMounted(async () => {
    await getUser();
    
    try {
        const res = await axios.get('/api/v1/languages');
        languages.value = res.data.data || res.data || [];
        console.log('Idiomas cargados:', languages.value);
    } catch (e) {
        console.error("Error cargando idiomas", e);
        languages.value = [];
    }
    
    await applyFilters();
});
</script>