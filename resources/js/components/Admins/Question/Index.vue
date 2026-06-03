<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from 'axios';
import { TailwindPagination } from "laravel-vue-pagination";
import useQuestion from "@/composables/question";
import useQuiz from "@/composables/quiz";

const { questions, getQuestions, deleteQuestion } = useQuestion();
const { quizzes, getQuizzes } = useQuiz();

// 🔥 NUEVO: Estado para la lista completa de quizzes (sin paginación)
const allQuizzes = ref([]);

const selectedQuiz = ref(""); 
const search = ref("");

const refresh = (page = 1) => {
    getQuestions(page, selectedQuiz.value, search.value);
};

watch([selectedQuiz, search], () => {
    if (selectedQuiz.value) refresh(1);
});

const languages = ref([]);

const currentLanguageName = computed(() => {
    const langId = localStorage.getItem('active_lang_id') || '1';
    if (languages.value.length === 0) return 'Cargando...';
    const lang = languages.value.find(l => Number(l.id) === Number(langId));
    return lang ? lang.name : 'Inglés';
});

// 🔥 NUEVO: Función para cargar TODOS los quizzes sin paginación
const loadAllQuizzes = async () => {
    try {
        const response = await axios.get('/api/v1/quizzes/all');
        allQuizzes.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error cargando todos los quizzes:', error);
    }
};

onMounted(async () => {
    await getQuizzes(); // Para la paginación (si la usas en otro lado)
    await loadAllQuizzes(); // 🔥 Para el select
    try {
        const res = await axios.get('/api/v1/languages');
        languages.value = res.data.data || res.data;
    } catch (e) { console.error(e); }
});
</script>

<template>
  <div class="min-h-screen bg-gray-50/50 p-6">
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 mb-4">
          <h2 class="text-lg font-bold text-gray-800">Cuestionarios</h2>
          <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase rounded-full border border-indigo-200">
            {{ currentLanguageName }}
          </span>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
          
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Filter by Quiz</label>
            <select v-model="selectedQuiz" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-indigo-400 transition-all">
              <option value="">-- Select a Quiz --</option>
              <!-- 🔥 Usar allQuizzes en lugar de quizzes.data -->
              <option v-for="quiz in allQuizzes" :key="quiz.id" :value="quiz.id">
                {{ quiz.title }} ({{ quiz.questions_count }} questions)
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Search Keyword</label>
            <input v-model="search" type="text" placeholder="Search in this quiz..." class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-indigo-400 transition-all" />
          </div>
        </div>
        
        <router-link :to="{ name: 'question.create' }" class="inline-flex items-center px-6 py-3 bg-amber-400 hover:bg-amber-500 text-white font-bold rounded-2xl transition-all shadow-lg shadow-amber-100">
          + New Question
        </router-link>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        
        <div v-if="!selectedQuiz" class="p-20 text-center">
            <div class="h-20 w-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Ready to manage questions?</h3>
            <p class="text-gray-500 max-w-sm mx-auto">Please select a quiz from the dropdown above to view and manage its question bank.</p>
        </div>

        <div v-else-if="questions?.data?.length > 0">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase">Question</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase">Media</th>
                  <th class="px-6 py-4 text-center text-xs font-bold text-gray-400 uppercase">Details</th>
                  <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="question in questions.data" :key="question.id" class="hover:bg-gray-50/50 transition-colors">
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">{{ question.question1 }}</div>
                    <div class="text-[10px] mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-gray-400 uppercase">
                        <span :class="{'text-emerald-600 font-bold': question.right_answer === 'A'}">A: {{ question.option_a }}</span>
                        <span :class="{'text-emerald-600 font-bold': question.right_answer === 'B'}">B: {{ question.option_b }}</span>
                        <span :class="{'text-emerald-600 font-bold': question.right_answer === 'C'}">C: {{ question.option_c }}</span>
                        <span :class="{'text-emerald-600 font-bold': question.right_answer === 'D'}">D: {{ question.option_d }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <img v-if="question.picture" :src="'/storage/' + question.picture" class="h-10 w-10 object-cover rounded-lg border shadow-sm">
                      <audio v-if="question.sound" controls class="h-8 w-32">
                        <source :src="'/storage/' + question.sound" type="audio/mpeg">
                      </audio>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span class="px-2 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black rounded-md mr-2">{{ question.area }}</span>
                    <span class="inline-flex items-center justify-center h-6 w-6 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full">
                      {{ question.right_answer }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right text-sm font-medium">
                    <div class="flex justify-end gap-3">
                      <router-link :to="{ name: 'question.edit', params: { id: question.id } }" class="text-indigo-600 hover:text-indigo-900 font-bold">Edit</router-link>
                      <button @click="deleteQuestion(question.id)" class="text-red-600 hover:text-red-900 font-bold">Delete</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="p-6 bg-gray-50/30 border-t border-gray-100">
            <TailwindPagination :data="questions" @pagination-change-page="refresh" :limit="1" />
          </div>
        </div>

        <div v-else class="p-20 text-center">
            <div class="text-4xl mb-4">🏜️</div>
            <h3 class="text-lg font-bold text-gray-800">No questions found</h3>
            <p class="text-gray-400 mb-6">Either this quiz is empty or your search term didn't match anything.</p>
            <router-link :to="{ name: 'question.create' }" class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold">
                + Add Question
            </router-link>
        </div>

      </div>
    </div>
  </div>
</template>