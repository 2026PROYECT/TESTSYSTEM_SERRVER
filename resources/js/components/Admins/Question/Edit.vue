<template>
    <div class="min-h-screen bg-gray-50/50 py-10 px-4">
        <div v-if="questionData.id" class="max-w-4xl mx-auto">
            
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Update Question</h1>
                    <span class="mt-2 inline-block px-3 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase rounded-lg border border-amber-100">
                        {{ currentLanguageName }}
                    </span>
                </div>
                <button @click.prevent="goBack" class="flex items-center text-sm font-medium text-indigo-600 bg-indigo-50 px-4 py-2 rounded-2xl hover:bg-indigo-100 transition">
                    ← Back
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 ml-1">Select Quiz</label>
                        <select v-model="questionData.quiz_id" class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-400 transition">
                            <option value="">Choose a Quiz</option>
                            <option v-for="quiz in quizzes.data" :key="quiz.id" :value="quiz.id">{{ quiz.title }}</option>
                        </select>
                        <p v-if="validationErrors.quiz_id" class="text-red-500 text-xs mt-2 ml-1">{{ validationErrors.quiz_id[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Area</label>
                        <select v-model="questionData.area" class="w-full bg-gray-50 border-none rounded-2xl px-4 py-3 focus:ring-2 focus:ring-indigo-400 transition-all">
                            <option value="" disabled>-- Choose Area --</option>
                            <option value="L">Left (L)</option>
                            <option value="R">Right (R)</option>
                        </select>
                        <p v-if="validationErrors.area" class="text-red-500 text-xs mt-1 ml-1">{{ validationErrors.area[0] }}</p>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-6">
                    <div class="flex items-center mb-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1">Question Variations</label>
                    </div>

                    <div class="relative">
                        <span class="absolute -left-4 top-4 flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-xs font-bold shadow-lg shadow-indigo-100">1</span>
                        <textarea v-model="questionData.question1" rows="2" placeholder="Primary Question Text (Required)" 
                            class="w-full bg-gray-50 border-none rounded-2xl px-8 py-4 focus:ring-2 focus:ring-indigo-400 transition"></textarea>
                        <p v-if="validationErrors.question1" class="text-red-500 text-[10px] mt-1 ml-4">{{ validationErrors.question1[0] }}</p>
                    </div>

                    <div class="relative">
                        <span class="absolute -left-4 top-4 flex items-center justify-center w-8 h-8 bg-gray-200 text-gray-500 rounded-full text-xs font-bold">2</span>
                        <textarea v-model="questionData.question2" rows="2" placeholder="Second Variation (Optional)" 
                            class="w-full bg-gray-50 border-none rounded-2xl px-8 py-4 focus:ring-2 focus:ring-indigo-400 transition"></textarea>
                    </div>

                    <div class="relative">
                        <span class="absolute -left-4 top-4 flex items-center justify-center w-8 h-8 bg-gray-200 text-gray-500 rounded-full text-xs font-bold">3</span>
                        <textarea v-model="questionData.question3" rows="2" placeholder="Third Variation (Optional)" 
                            class="w-full bg-gray-50 border-none rounded-2xl px-8 py-4 focus:ring-2 focus:ring-indigo-400 transition"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Question Image</label>
                        
                        <div v-if="questionData.picture && typeof questionData.picture === 'string' && !picturePreview" class="mb-4">
                            <img :src="'/storage/' + questionData.picture" class="h-32 w-full object-cover rounded-2xl border border-gray-100">
                            <p class="text-[10px] text-gray-400 mt-2 text-center uppercase tracking-tighter">Current Image</p>
                        </div>
                        
                        <div v-if="picturePreview" class="mb-4">
                            <img :src="picturePreview" class="h-32 w-full object-cover rounded-2xl border border-gray-100">
                            <p class="text-[10px] text-green-600 mt-2 text-center uppercase tracking-tighter">New Image Selected</p>
                        </div>

                        <input type="file" accept="image/*" @change="handlePicture" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>

                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Audio Clip</label>
                        
                        <div v-if="questionData.sound && typeof questionData.sound === 'string' && !soundPreview" class="mb-4">
                            <audio controls :src="'/storage/' + questionData.sound" class="w-full h-10"></audio>
                            <p class="text-[10px] text-gray-400 mt-2 text-center uppercase tracking-tighter">Current Audio</p>
                        </div>
                        
                        <div v-if="soundPreview" class="mb-4">
                            <audio controls :src="soundPreview" class="w-full h-10"></audio>
                            <p class="text-[10px] text-green-600 mt-2 text-center uppercase tracking-tighter">New Audio Selected</p>
                        </div>

                        <input type="file" accept="audio/*" @change="handleSound" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-6 ml-1">Answer Configuration</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <div v-for="opt in ['a', 'b', 'c', 'd']" :key="opt">
                            <input v-model="questionData['option_' + opt]" type="text" :placeholder="'Option ' + opt.toUpperCase()" class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-400 transition">
                            <p v-if="validationErrors['option_' + opt]" class="text-red-500 text-xs mt-1">{{ validationErrors['option_' + opt][0] }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3 ml-1">Correct Letter</label>
                        <select v-model="questionData.right_answer" class="w-full bg-indigo-50 border-none rounded-2xl px-5 py-4 focus:ring-2 focus:ring-indigo-400 font-bold text-indigo-700">
                            <option :value="1">A</option>
                            <option :value="2">B</option>
                            <option :value="3">C</option>
                            <option :value="4">D</option>
                        </select>
                        <p v-if="validationErrors.right_answer" class="text-red-500 text-xs mt-1">{{ validationErrors.right_answer[0] }}</p>
                    </div>
                </div>

                <button :disabled="isLoading" class="group relative w-full flex justify-center py-5 px-4 border border-transparent text-lg font-bold rounded-3xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-xl shadow-indigo-200 disabled:opacity-50">
                    <span v-if="isLoading">Updating Question...</span>
                    <span v-else>Apply Changes</span>
                </button>
            </form>
        </div>

        <div v-else class="flex flex-col justify-center items-center h-96">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
            <p class="text-gray-400 font-medium">Fetching question data...</p>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import useQuiz from '@/composables/quiz';
import useQuestion from '@/composables/question';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

const { quizzes, getQuizzes } = useQuiz();
const { updateQuestion, isLoading, validationErrors } = useQuestion();

// Datos locales del formulario
const questionData = ref({
    id: '',
    quiz_id: '',
    area: '',
    question1: '',
    question2: '',
    question3: '',
    picture: null,
    sound: null,
    option_a: '',
    option_b: '',
    option_c: '',
    option_d: '',
    right_answer: ''
});

const picturePreview = ref(null);
const soundPreview = ref(null);
const languages = ref([]);

const currentLanguageName = computed(() => {
    const langId = localStorage.getItem('active_lang_id') || '1';
    const lang = languages.value.find(l => Number(l.id) === Number(langId));
    return lang ? lang.name : 'Inglés';
});

// Cargar datos de la pregunta
const loadQuestion = async () => {
    try {
        const response = await axios.get(`/api/v1/questions/${route.params.id}`);
        const data = response.data.data || response.data;
        
        questionData.value = {
            id: data.id,
            quiz_id: data.quiz_id,
            area: data.area,
            question1: data.question1 || '',
            question2: data.question2 || '',
            question3: data.question3 || '',
            picture: data.picture || null,
            sound: data.sound || null,
            option_a: data.option_a || '',
            option_b: data.option_b || '',
            option_c: data.option_c || '',
            option_d: data.option_d || '',
            right_answer: data.right_answer ? Number(data.right_answer) : ''
        };
    } catch (error) {
        console.error('Error loading question:', error);
        Swal.fire('Error', 'No se pudo cargar la pregunta', 'error');
        router.push({ name: 'question.index' });
    }
};

const handlePicture = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    questionData.value.picture = file;
    picturePreview.value = URL.createObjectURL(file);
};

const handleSound = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) { 
        Swal.fire('Error', 'El archivo es demasiado grande. Máximo 10MB.', 'error');
        e.target.value = '';
        return;
    }
    
    questionData.value.sound = file;
    soundPreview.value = URL.createObjectURL(file);
};

const handleSubmit = async () => {
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('quiz_id', questionData.value.quiz_id);
        formData.append('area', questionData.value.area);
        formData.append('question1', questionData.value.question1 || '');
        formData.append('question2', questionData.value.question2 || '');
        formData.append('question3', questionData.value.question3 || '');
        formData.append('option_a', questionData.value.option_a);
        formData.append('option_b', questionData.value.option_b);
        formData.append('option_c', questionData.value.option_c);
        formData.append('option_d', questionData.value.option_d);
        formData.append('right_answer', questionData.value.right_answer);

        if (questionData.value.picture instanceof File) {
            formData.append('picture', questionData.value.picture);
        }
        if (questionData.value.sound instanceof File) {
            formData.append('sound', questionData.value.sound);
        }

        await updateQuestion(route.params.id, formData);
        
        Swal.fire('Éxito', 'Pregunta actualizada correctamente', 'success');
        router.push({ name: 'question.index' });
    } catch (error) {
        console.error("Error al actualizar:", error);
        if (!validationErrors.value || Object.keys(validationErrors.value).length === 0) {
            Swal.fire('Error', error.response?.data?.message || 'Error al actualizar la pregunta', 'error');
        }
    }
};

const goBack = () => {
    router.push({ name: 'question.index' });
};

onMounted(async () => {
    await getQuizzes();
    await loadQuestion();
    
    try {
        const res = await axios.get('/api/v1/languages');
        languages.value = res.data.data || res.data;
    } catch (e) { 
        console.error(e); 
    }
});
</script>