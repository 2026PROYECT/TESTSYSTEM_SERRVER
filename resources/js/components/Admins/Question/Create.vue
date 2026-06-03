<template>
  <div class="min-h-screen bg-gray-50/50 py-10 px-4">
    <div class="max-w-3xl mx-auto">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Add New Question</h1>
          <p class="text-gray-500 text-sm">Fill in the details to expand your quiz database.</p>
        </div>
        <div class="h-12 w-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Select Quiz</label>
            <select v-model="form.quiz_id" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3">
              <option disabled value="">-- Choose Quiz --</option>
              <option v-for="quiz in quizzes.data" :key="quiz.id" :value="quiz.id">{{ quiz.title }}</option>
            </select>
            <p v-if="errors.quiz_id" class="mt-1 text-xs text-red-500">{{ errors.quiz_id[0] }}</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Area</label>
            <select v-model="form.area" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3">
              <option disabled value="">-- Choose Area --</option>
              <option value="L">Left (L)</option>
              <option value="R">Right (R)</option>
            </select>
            <p v-if="errors.area" class="mt-1 text-xs text-red-500">{{ errors.area[0] }}</p>
          </div>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest">Question Content</label>
          <div v-for="i in [1, 2, 3]" :key="i">
            <textarea 
              v-model="form['question' + i]" 
              :placeholder="'Question Variation ' + i"
              class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3"
              rows="2"
            ></textarea>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Visual Aid</label>
            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-4 text-center relative">
              <input type="file" accept="image/*" @change="handlePicture" class="absolute inset-0 opacity-0 cursor-pointer" />
              <img v-if="picturePreview" :src="picturePreview" class="max-h-32 mx-auto rounded-lg" />
              <div v-else class="text-gray-400">📸 Click to upload image</div>
            </div>
          </div>

          <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Audio Aid</label>
            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-4 text-center relative">
              <input type="file" accept="audio/*" @change="handleSound" class="absolute inset-0 opacity-0 cursor-pointer" />
              <audio v-if="soundPreview" :src="soundPreview" controls class="w-full"></audio>
              <div v-else class="text-gray-400">🎵 Click to upload sound</div>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Options</label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="opt in ['a', 'b', 'c', 'd']" :key="opt">
              <div class="flex items-center bg-gray-50 rounded-2xl px-4 py-2">
                <span class="text-indigo-500 font-bold uppercase mr-3">{{ opt }}</span>
                <input v-model="form['option_' + opt]" type="text" :placeholder="'Option ' + opt.toUpperCase()" class="bg-transparent border-none w-full" />
              </div>
              <p v-if="errors['option_' + opt]" class="text-xs text-red-500 mt-1">{{ errors['option_' + opt][0] }}</p>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t">
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Correct Answer</label>
            <div class="flex gap-4">
              <button 
                v-for="ans in [{ label: 'A', value: 1 }, { label: 'B', value: 2 }, { label: 'C', value: 3 }, { label: 'D', value: 4 }]" 
                :key="ans.value"
                type="button"
                @click="form.right_answer = ans.value" 
                :class="form.right_answer === ans.value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500'"
                class="flex-1 py-3 rounded-2xl font-bold"
              >
                {{ ans.label }}
              </button>
            </div>
            <p v-if="errors.right_answer" class="mt-2 text-xs text-red-500 text-center">{{ errors.right_answer[0] }}</p>
          </div>
        </div>

        <button type="submit" :disabled="loading" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl disabled:opacity-50">
          {{ loading ? 'Saving...' : 'Save Question' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import useQuiz from '@/composables/quiz';
import useQuestion from '@/composables/question';

const { quizzes, getQuizzes } = useQuiz();
const { storeQuestion, validationErrors, isLoading } = useQuestion();

const loading = ref(false);
const picturePreview = ref(null);
const soundPreview = ref(null);

const form = reactive({
  quiz_id: '',
  question1: '',
  question2: '',
  question3: '',
  picture: null,
  sound: null,
  option_a: '',
  option_b: '',
  option_c: '',
  option_d: '',
  right_answer: '',
  area: ''
});

const errors = ref({});

const handlePicture = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.picture = file;
    picturePreview.value = URL.createObjectURL(file);
  }
};

const handleSound = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.sound = file;
    soundPreview.value = URL.createObjectURL(file);
  }
};

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};
  
  try {
    await storeQuestion(form);
    // Resetear formulario después de guardar
    form.quiz_id = '';
    form.question1 = '';
    form.question2 = '';
    form.question3 = '';
    form.option_a = '';
    form.option_b = '';
    form.option_c = '';
    form.option_d = '';
    form.right_answer = '';
    form.area = '';
    form.picture = null;
    form.sound = null;
    picturePreview.value = null;
    soundPreview.value = null;
    
    // Limpiar inputs file
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => input.value = '');
    
  } catch (error) {
    if (validationErrors.value) {
      errors.value = validationErrors.value;
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getQuizzes();
});
</script>