<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-200">
      
     <div class="flex justify-between items-center mb-8">
  <div>
    <div class="flex items-center gap-3">
      <h1 class="text-2xl font-bold text-gray-800">Gestión de Preguntas Orales</h1>
      <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase rounded-full border border-indigo-200">
        {{ currentLanguageName }}
      </span>
    </div>
    <p class="text-sm text-gray-500">Administra el banco de preguntas para los exámenes de entrevista.</p>
  </div>
  <button @click="openModal()" class="bg-indigo-600 text-white ...">
    <span class="text-xl">+</span> Nueva Pregunta
  </button>
</div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="relative">
          <input 
            v-model="filters.search" 
            type="text" 
            placeholder="Buscar pregunta..." 
            class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none pl-10"
          />
          <span class="absolute left-3 top-3 text-gray-400">🔍</span>
        </div>
        
        <select v-model="filters.level" class="border border-gray-300 p-2.5 rounded-lg text-gray-700 outline-none focus:ring-2 focus:ring-indigo-500">
          <option value="all">Todos los niveles</option>
          <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
        </select>

        <select v-model="filters.active" class="border border-gray-300 p-2.5 rounded-lg text-gray-700 outline-none focus:ring-2 focus:ring-indigo-500">
          <option value="all">Todos los estados</option>
          <option value="1">Activas</option>
          <option value="0">Inactivas</option>
        </select>

        <div class="flex items-center gap-2 border border-gray-300 p-2.5 rounded-lg bg-gray-50">
          <label class="text-xs text-gray-500 uppercase font-bold px-1">Ver:</label>
          <select v-model="filters.per_page" class="w-full text-sm bg-transparent outline-none font-semibold text-gray-700">
            <option :value="5">5 registros</option>
            <option :value="15">15 registros</option>
            <option :value="30">30 registros</option>
            <option :value="50">50 registros</option>
          </select>
        </div>
      </div>

      <div class="overflow-hidden border border-gray-200 rounded-xl">
        <table class="w-full text-left border-collapse bg-white">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Pregunta</th>
              <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nivel</th>
              <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Categoría</th>
              <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
              <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="q in questions" :key="q.id" class="hover:bg-indigo-50/30 transition-colors">
              <td class="p-4 text-gray-700 font-medium">{{ q.question_text }}</td>
              <td class="p-4">
                <span :class="levelBadge(q.level)" class="px-2.5 py-1 rounded-lg text-xs font-black uppercase shadow-sm">
                  {{ q.level }}
                </span>
              </td>
              <td class="p-4 text-gray-500 text-sm italic">{{ q.category || 'Sin categoría' }}</td>
              <td class="p-4">
                <span :class="q.active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded-md text-[10px] font-bold uppercase">
                  {{ q.active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex justify-end gap-2">
                  <button @click="openModal(q)" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" title="Editar">
                    📝
                  </button>
                  <button @click="deleteQuestion(q.id)" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Eliminar">
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="questions.length === 0">
              <td colspan="5" class="p-8 text-center text-gray-400 italic">No se encontraron preguntas con estos filtros.</td>
            </tr>
          </tbody>
        </table>

        <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
          <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-600">
                Mostrando <span class="font-bold text-gray-900">{{ pagination.from || 0 }}</span> a 
                <span class="font-bold text-gray-900">{{ pagination.to || 0 }}</span> de 
                <span class="font-bold text-gray-900">{{ pagination.total || 0 }}</span> resultados
              </p>
            </div>
            
            <nav class="isolate inline-flex -space-x-px rounded-lg shadow-sm bg-white" aria-label="Pagination">
              <button @click="fetchQuestions(pagination.current_page - 1)" 
                      :disabled="pagination.current_page === 1"
                      class="relative inline-flex items-center rounded-l-lg px-3 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-30">
                &laquo;
              </button>

              <template v-for="page in pagination.last_page" :key="page">
                <button 
                  v-if="Math.abs(page - pagination.current_page) < 3 || page === 1 || page === pagination.last_page"
                  @click="fetchQuestions(page)"
                  :class="[
                    page === pagination.current_page 
                    ? 'z-10 bg-indigo-600 text-white ring-indigo-600' 
                    : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
                    'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20'
                  ]"
                >
                  {{ page }}
                </button>
                <span v-else-if="Math.abs(page - pagination.current_page) === 3" 
                      class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-400 ring-1 ring-inset ring-gray-300">
                  ...
                </span>
              </template>

              <button @click="fetchQuestions(pagination.current_page + 1)" 
                      :disabled="pagination.current_page === pagination.last_page"
                      class="relative inline-flex items-center rounded-r-lg px-3 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-30">
                &raquo;
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-8 shadow-2xl border border-gray-100">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
          <span>{{ form.id ? '✏️' : '✨' }}</span>
          {{ form.id ? 'Editar' : 'Nueva' }} Pregunta Oral
        </h2>
        
        <div class="space-y-5">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Texto de la Pregunta</label>
            <textarea v-model="form.question_text" 
                      placeholder="Escribe la pregunta aquí..."
                      class="w-full border border-gray-300 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" 
                      rows="3"></textarea>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nivel</label>
              <select v-model="form.level" class="w-full border border-gray-300 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-gray-700">
                <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Categoría</label>
              <input v-model="form.category" type="text" placeholder="Ej. General"
                     class="w-full border border-gray-300 p-3 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" />
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
            <input v-model="form.active" type="checkbox" id="active" class="w-5 h-5 rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
            <label for="active" class="text-sm font-bold text-gray-700 cursor-pointer">Pregunta Activa</label>
          </div>
        </div>

        <div class="flex justify-end mt-8 gap-3">
          <button @click="showModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
            Cancelar
          </button>
          <button @click="saveQuestion" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-md active:scale-95">
            {{ form.id ? 'Guardar Cambios' : 'Crear Pregunta' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'; // Añadido computed
import axios from 'axios';

// URL de la API
const API_URL = '/api/v1/oral-questions';

// Estado
const questions = ref([]);
const pagination = ref({});
const levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
const showModal = ref(false);

const languages = ref([]); // Estado para almacenar idiomas

// Computamos el nombre del idioma basado en el ID activo
const currentLanguageName = computed(() => {
  const langId = localStorage.getItem('active_lang_id') || '1';
  if (languages.value.length === 0) return 'Cargando...';
  const lang = languages.value.find(l => Number(l.id) === Number(langId));
  return lang ? lang.name : 'Inglés';
});

// Modificamos los filtros para incluir el language_id
const filters = reactive({
  search: '',
  level: 'all',
  active: 'all',
  per_page: 15,
  language_id: localStorage.getItem('active_lang_id') || '1' // <--- NUEVO
});

const form = reactive({
  id: null,
  question_text: '',
  level: 'A1',
  category: 'General',
  active: true
});

// Métodos
const fetchQuestions = async (page = 1) => {
  try {
    // Actualizamos el language_id por si cambió en otra pestaña
    filters.language_id = localStorage.getItem('active_lang_id') || '1';
    
    const params = { ...filters, page };
    const response = await axios.get(API_URL, { params });
    questions.value = response.data.data;
    pagination.value = response.data;
  } catch (error) {
    console.error("Error al cargar datos:", error);
  }
};

// Modificamos saveQuestion para incluir el language_id al crear
const saveQuestion = async () => {
  try {
    if (!form.question_text) return alert("El texto de la pregunta es obligatorio");
    
    // Aseguramos que la pregunta se guarde en el idioma activo
    const payload = { 
      ...form, 
      language_id: localStorage.getItem('active_lang_id') || '1' 
    };

    if (form.id) {
      await axios.put(`${API_URL}/${form.id}`, payload);
    } else {
      await axios.post(API_URL, payload);
    }
    showModal.value = false;
    fetchQuestions(pagination.value.current_page || 1);
  } catch (error) {
    // ... error handling
  }
};
const deleteQuestion = async (id) => {
  if (confirm("¿Seguro que deseas eliminar esta pregunta de forma permanente?")) {
    try {
      await axios.delete(`${API_URL}/${id}`);
      fetchQuestions(pagination.value.current_page || 1);
    } catch (error) {
      alert("No se pudo eliminar la pregunta");
    }
  }
};

const openModal = (data = null) => {
  if (data) {
    Object.assign(form, {
      id: data.id,
      question_text: data.question_text,
      level: data.level,
      category: data.category,
      active: !!data.active
    });
  } else {
    Object.assign(form, { id: null, question_text: '', level: 'A1', category: 'General', active: true });
  }
  showModal.value = true;
};

const levelBadge = (lvl) => {
  const colors = {
    'A1': 'bg-emerald-100 text-emerald-700', 'A2': 'bg-teal-100 text-teal-700',
    'B1': 'bg-amber-100 text-amber-700', 'B2': 'bg-orange-100 text-orange-700',
    'C1': 'bg-rose-100 text-rose-700', 'C2': 'bg-purple-100 text-purple-700'
  };
  return colors[lvl] || 'bg-gray-100 text-gray-600';
};

// Observar filtros (con debounce manual simple al escribir)
let searchTimeout;
watch(() => [filters.search, filters.level, filters.active, filters.per_page], () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchQuestions(1);
  }, 300);
});

onMounted(async () => {
  // Cargamos los idiomas al montar el componente
  try {
    const res = await axios.get('/api/v1/languages');
    languages.value = res.data.data || res.data;
  } catch (e) { console.error(e); }
  
  fetchQuestions();
});
</script>

<style scoped>
/* Transición suave para el modal */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>