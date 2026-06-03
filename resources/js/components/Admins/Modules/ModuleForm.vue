<!-- resources/js/components/Admins/Modules/ModuleForm.vue -->
<template>
    <div class="p-6 max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900">{{ isEditing ? '✏️ Editar Módulo' : '➕ Nuevo Módulo' }}</h1>
            <p class="text-gray-500">Crea o edita un módulo para exámenes modulares</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <form @submit.prevent="saveModule">
                <div class="space-y-4">
                    <!-- Título -->
                    <div>
                        <label class="block text-sm font-bold mb-1">Título *</label>
                        <input v-model="form.title" type="text" class="w-full p-3 rounded-xl border" required>
                    </div>

                    <!-- 🔥 IDIOMA: AUTO-LLENADO con active_lang_id (sin selector) -->
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200">
                        <label class="block text-sm font-bold mb-1">Idioma *</label>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-bold rounded-full">
                                {{ currentLanguageName }}
                            </span>
                            <span class="text-xs text-gray-500">(El idioma se asigna automáticamente según tu selección)</span>
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-bold mb-1">Tipo *</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer transition-all"
                                   :class="form.type === 'reading' ? 'bg-blue-50 border-blue-500' : 'bg-white border-gray-200'">
                                <input type="radio" value="reading" v-model="form.type" class="hidden">
                                <span>📖 Lectura</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer transition-all"
                                   :class="form.type === 'listening' ? 'bg-purple-50 border-purple-500' : 'bg-white border-gray-200'">
                                <input type="radio" value="listening" v-model="form.type" class="hidden">
                                <span>🎧 Listening</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer transition-all"
                                   :class="form.type === 'mixed' ? 'bg-green-50 border-green-500' : 'bg-white border-gray-200'">
                                <input type="radio" value="mixed" v-model="form.type" class="hidden">
                                <span>🎯 Mixto</span>
                            </label>
                        </div>
                    </div>

                    <!-- Nivel -->
                    <div>
                        <label class="block text-sm font-bold mb-1">Nivel *</label>
                        <select v-model="form.level" class="w-full p-3 rounded-xl border" required>
                            <option value="">Seleccione un nivel</option>
                            <option value="A1">A1 - Principiante</option>
                            <option value="A2">A2 - Básico</option>
                            <option value="B1">B1 - Intermedio</option>
                            <option value="B2">B2 - Intermedio Alto</option>
                            <option value="C1">C1 - Avanzado</option>
                            <option value="C2">C2 - Avanzado Superior</option>
                        </select>
                    </div>

                    <!-- Duración -->
                    <div>
                        <label class="block text-sm font-bold mb-1">Duración (segundos)</label>
                        <div class="flex gap-2">
                            <input v-model="form.duration_seconds" type="number" class="flex-1 p-3 rounded-xl border" 
                                   placeholder="Ej: 300">
                            <span class="p-3 text-gray-500">min: {{ Math.floor(form.duration_seconds / 60) || 0 }}</span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div>
                        <label class="block text-sm font-bold mb-1">
                            Contenido del Módulo
                            <span class="text-xs text-gray-500" v-if="form.type === 'listening'">(Transcripción del audio)</span>
                            <span class="text-xs text-gray-500" v-else-if="form.type === 'reading'">(Texto de lectura)</span>
                        </label>
                        <textarea v-model="form.content" rows="8" class="w-full p-3 rounded-xl border font-mono text-sm" 
                                  :placeholder="contentPlaceholder"></textarea>
                    </div>

                    <!-- Audio URL (solo para listening/mixed) -->
                    <div v-if="form.type !== 'reading'">
                        <label class="block text-sm font-bold mb-1">URL del Audio</label>
                        <input v-model="form.audio" type="text" class="w-full p-3 rounded-xl border" 
                               placeholder="Ej: /storage/audio/module1.mp3">
                        <p class="text-xs text-gray-500 mt-1">Ruta relativa o URL completa del archivo de audio</p>
                    </div>

                    <!-- Imagen URL (opcional) -->
                    <div>
                        <label class="block text-sm font-bold mb-1">URL de Imagen (opcional)</label>
                        <input v-model="form.picture" type="text" class="w-full p-3 rounded-xl border" 
                               placeholder="Ej: /storage/images/module1.jpg">
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-3 pt-4">
                        <button type="submit" :disabled="loading" 
                                class="flex-1 bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition">
                            {{ loading ? 'Guardando...' : (isEditing ? 'Actualizar Módulo' : 'Crear Módulo') }}
                        </button>
                        <router-link :to="{ name: 'admin.modules.index' }" 
                                     class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition text-center">
                            Cancelar
                        </router-link>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const languages = ref([]);
const isEditing = ref(false);

// 🔥 MISMA LÓGICA que en el index: obtener idioma del localStorage
const activeLangId = computed(() => {
    return localStorage.getItem('active_lang_id') || '1';
});

const currentLanguageName = ref('Cargando...');

const contentPlaceholder = computed(() => {
    if (form.value.type === 'reading') {
        return "Escribe el texto de lectura aquí...\n\nLorem ipsum dolor sit amet...";
    } else if (form.value.type === 'listening') {
        return "Escribe la transcripción del audio aquí...\n\nSpeaker: Hello everyone...";
    }
    return "Escribe el contenido del módulo aquí...";
});

const form = ref({
    title: '',
    language_id: null,  // Se llenará automáticamente
    type: 'reading',
    level: '',
    content: '',
    duration_seconds: 300,
    audio: null,
    picture: null
});

// Cargar idiomas solo para mostrar el nombre
const loadLanguages = async () => {
    try {
        const response = await axios.get('/api/v1/languages');
        languages.value = response.data.data || response.data;
        
        // Actualizar el nombre del idioma actual
        const currentLang = languages.value.find(l => l.id == activeLangId.value);
        currentLanguageName.value = currentLang ? currentLang.name : 'Idioma Seleccionado';
        
        // 🔥 AUTO-ASIGNAR el language_id
        form.value.language_id = parseInt(activeLangId.value);
        
    } catch (error) {
        console.error('Error loading languages:', error);
        currentLanguageName.value = 'Idioma no disponible';
    }
};

const loadModule = async (id) => {
    try {
        const response = await axios.get(`/api/v1/admin/modules/${id}`);
        const module = response.data.data || response.data;
        
        form.value = {
            title: module.title,
            language_id: module.language_id,
            type: module.type,
            level: module.level || '',
            content: module.content || '',
            duration_seconds: module.duration_seconds || 300,
            audio: module.audio || null,
            picture: module.picture || null
        };
        
        // Verificar que el módulo pertenezca al idioma actual
        if (form.value.language_id != activeLangId.value && !isEditing.value) {
            Swal.fire({
                title: 'Idioma diferente',
                text: `Este módulo pertenece a otro idioma. Se mostrará pero no podrás editarlo.`,
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
        }
        
    } catch (error) {
        Swal.fire('Error', 'No se pudo cargar el módulo', 'error');
        router.push({ name: 'admin.modules.index' });
    }
};

const saveModule = async () => {
    // 🔥 Validaciones
    if (!form.value.title) {
        Swal.fire('Error', 'El título es requerido', 'error');
        return;
    }
    
    if (!form.value.level) {
        Swal.fire('Error', 'El nivel es requerido', 'error');
        return;
    }
    
    if (!form.value.language_id) {
        Swal.fire('Error', 'El idioma es requerido', 'error');
        return;
    }
    
    loading.value = true;
    
    try {
        // 🔥 Asegurar que language_id sea el correcto
        form.value.language_id = parseInt(activeLangId.value);
        
        if (isEditing.value) {
            await axios.put(`/api/v1/admin/modules/${route.params.id}`, form.value);
            Swal.fire('Actualizado', 'Módulo actualizado correctamente', 'success');
        } else {
            await axios.post('/api/v1/admin/modules', form.value);
            Swal.fire('Creado', 'Módulo creado correctamente', 'success');
        }
        router.push({ name: 'admin.modules.index' });
        
    } catch (error) {
        console.error('Error:', error);
        
        if (error.response?.status === 422) {
            // Errores de validación
            const errors = error.response.data.errors;
            const firstError = Object.values(errors)[0]?.[0] || 'Error de validación';
            Swal.fire('Error', firstError, 'error');
        } else {
            Swal.fire('Error', error.response?.data?.message || 'Error al guardar el módulo', 'error');
        }
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadLanguages();
    
    if (route.params.id) {
        isEditing.value = true;
        await loadModule(route.params.id);
    }
});
</script>