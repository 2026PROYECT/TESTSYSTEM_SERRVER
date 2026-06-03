<template>
    <div class="edit-quiz-container">
        <div class="max-w-2xl mx-auto p-6 bg-white shadow-md rounded-lg mt-10">
            <div class="flex flex-col items-center mb-6">
                <h4 class="text-lg font-bold text-indigo-600">Update Quiz</h4>
                <span class="mt-1 px-3 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase rounded-lg border border-amber-100">
                    Idioma : {{ currentLanguageName }}
                </span>
            </div>

            <div v-if="loading && !quiz.id" class="flex justify-center py-10">
                <div class="animate-spin h-8 w-8 border-4 border-indigo-600 border-t-transparent rounded-full"></div>
                <span class="ml-2 text-gray-500">Cargando datos...</span>
            </div>

            <form v-else @submit.prevent="handleSubmit">
                <div class="mb-4">
                    <label for="quiz-title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input id="quiz-title" v-model="quiz.title" type="text" 
                        class="form-input-custom">
                    <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.title">
                        <p v-for="message in validationErrors.title" :key="message">{{ message }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="quiz-type" class="block text-sm font-medium text-gray-700">Quiz Type</label>
                    <select 
                        id="quiz-type" 
                        v-model="quiz.type" 
                        class="form-input-custom"
                        :class="{'border-red-500': validationErrors?.type}"
                    >
                        <option value="" disabled>Select a type...</option>
                        <option 
                            v-for="quizType in quizTypes" 
                            :key="quizType.value" 
                            :value="quizType.value"
                        >
                            {{ quizType.label }}
                        </option>
                    </select>
                    <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.type">
                        <p v-for="message in validationErrors.type" :key="message">{{ message }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="quiz-subject" class="block text-sm font-medium text-gray-700">Subject Name</label>
                    <input type="text" id="quiz-subject" v-model="quiz.subject_name" 
                        class="form-input-custom">
                    <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.subject_name">
                        <p v-for="message in validationErrors.subject_name" :key="message">{{ message }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="total-mark" class="block text-sm font-medium text-gray-700">Total Mark</label>
                        <input type="number" id="total-mark" v-model="quiz.total_mark" 
                            class="form-input-custom">
                        <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.total_mark">
                            <p v-for="message in validationErrors.total_mark" :key="message">{{ message }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="pass-mark" class="block text-sm font-medium text-gray-700">Pass Mark</label>
                        <input 
                            type="number" 
                            id="pass-mark" 
                            v-model="quiz.pass_mark" 
                            :class="{'border-red-500 ring-1 ring-red-500': Number(quiz.pass_mark) > Number(quiz.total_mark)}"
                            class="form-input-custom"
                        >
                        <p v-if="Number(quiz.pass_mark) > Number(quiz.total_mark)" class="mt-1 text-xs text-red-600 font-bold">
                            ⚠️ Debe ser menor o igual a {{ quiz.total_mark }}
                        </p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Duration (Minutes)</label>
                    <input v-model="quiz.duration_minutes" type="number" class="form-input-custom">
                    <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.duration_minutes">
                        <p v-for="message in validationErrors.duration_minutes" :key="message">{{ message }}</p>
                    </div>
                </div>

                <!-- 🔥 NUEVO: Cantidad de Preguntas -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Número de Preguntas: <span class="font-bold text-indigo-600">{{ quiz.total_questions }}</span>
                    </label>
                    <input 
                        v-model.number="quiz.total_questions" 
                        type="range" 
                        min="5" 
                        max="100" 
                        step="1"
                        class="w-full mt-2"
                    />
                    <div class="flex justify-between text-xs text-gray-400 mt-1 px-1">
    <span>5</span><span>10</span><span>20</span><span>30</span><span>40</span>
    <span>50</span><span>60</span><span>70</span><span>80</span><span>90</span><span>100</span>
</div>
                    <p class="text-xs text-gray-400 mt-1">El examen tomará {{ quiz.total_questions }} preguntas al azar del banco.</p>
                    <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.total_questions">
                        <p v-for="message in validationErrors.total_questions" :key="message">{{ message }}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="quiz-date" class="block text-sm font-medium text-gray-700">Quiz Date</label>
                    <input type="datetime-local" v-model="quizDate" id="quiz-date" 
                        class="form-input-custom">
                    <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.quiz_date">
                        <p v-for="message in validationErrors.quiz_date" :key="message">{{ message }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <router-link :to="{ name: 'quiz.index' }" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Cancel
                    </router-link>
                    <button :disabled="isLoading" 
                        class="inline-flex items-center px-6 py-2 text-sm font-bold text-white uppercase bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <span v-if="isLoading" class="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></span>
                        {{ isLoading ? 'Processing...' : 'Update Quiz' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

// Estados
const quiz = ref({
    id: '',
    title: '',
    subject_name: '',
    total_mark: '',
    pass_mark: '',
    duration_minutes: 60,
    quiz_date: '',
    type: '',
    total_questions: 10, // 👈 NUEVO: Valor por defecto
});

const quizDate = ref('');
const quizTypes = ref([]);
const isLoading = ref(false);
const loading = ref(true);
const validationErrors = ref({});
const languages = ref([]);

// Computed
const currentLanguageName = computed(() => {
    const langId = localStorage.getItem('active_lang_id') || '1';
    const lang = languages.value.find(l => Number(l.id) === Number(langId));
    return lang ? lang.name : 'Inglés';
});

// Método para cargar el quiz
const loadQuiz = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/v1/quizzes/${route.params.id}`);
        const data = response.data.data || response.data;
        quiz.value = {
            id: data.id,
            title: data.title,
            subject_name: data.subject_name,
            total_mark: data.total_mark,
            pass_mark: data.pass_mark,
            duration_minutes: data.duration_minutes,
            quiz_date: data.quiz_date,
            type: data.type || '',
            total_questions: data.total_questions || 10, // 👈 NUEVO
        };
        
        // Formatear fecha para el input datetime-local
        if (quiz.value.quiz_date) {
            const date = new Date(quiz.value.quiz_date);
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            quizDate.value = date.toISOString().slice(0, 16);
        }
    } catch (error) {
        console.error('Error loading quiz:', error);
        Swal.fire('Error', 'No se pudo cargar el quiz', 'error');
        router.push({ name: 'quiz.index' });
    } finally {
        loading.value = false;
    }
};

// Método para actualizar
const handleSubmit = async () => {
    isLoading.value = true;
    validationErrors.value = {};

    // Validación local de notas
    if (Number(quiz.value.pass_mark) > Number(quiz.value.total_mark)) {
        validationErrors.value = {
            pass_mark: ['La nota de aprobación no puede ser mayor al total de puntos.']
        };
        isLoading.value = false;
        return;
    }

    // Validación local del tipo
    if (!quiz.value.type) {
        validationErrors.value = {
            type: ['Por favor, seleccione un tipo de examen válido.']
        };
        isLoading.value = false;
        return;
    }

    // 🔥 Validación de cantidad de preguntas
    if (!quiz.value.total_questions || quiz.value.total_questions < 5 || quiz.value.total_questions > 100) {
        validationErrors.value = {
            total_questions: ['La cantidad de preguntas debe estar entre 5 y 100.']
        };
        isLoading.value = false;
        return;
    }
    
    try {
        const activeLangId = localStorage.getItem('active_lang_id') || '1';

        const payload = {
            id: quiz.value.id,
            language_id: Number(activeLangId),
            title: quiz.value.title,
            subject_name: quiz.value.subject_name,
            total_mark: Number(quiz.value.total_mark),
            pass_mark: Number(quiz.value.pass_mark),
            duration_minutes: Number(quiz.value.duration_minutes),
            quiz_date: quizDate.value,
            type: quiz.value.type,
            total_questions: Number(quiz.value.total_questions), // 👈 NUEVO
        };
        
        await axios.put(`/api/v1/quizzes/${route.params.id}`, payload);
        
        Swal.fire({
            icon: 'success',
            title: '¡Actualizado!',
            text: 'El quiz ha sido actualizado correctamente.',
            timer: 2000,
            showConfirmButton: false
        });
        
        router.push({ name: 'quiz.index' });
    } catch (error) {
        console.error('Error updating quiz:', error);
        
        if (error.response?.status === 422) {
            validationErrors.value = error.response.data.errors;
            Swal.fire('Error de validación', 'Por favor, revisa los campos marcados.', 'error');
        } else {
            Swal.fire('Error', error.response?.data?.message || 'No se pudo actualizar el quiz', 'error');
        }
    } finally {
        isLoading.value = false;
    }
};

// Cargar idiomas y tipos de exámenes al montar
onMounted(async () => {
    // 1. Cargar Idiomas
    try {
        const res = await axios.get('/api/v1/languages');
        languages.value = res.data.data || res.data || [];
    } catch (e) {
        console.error("Error al cargar idiomas:", e);
        languages.value = [];
    }

    // 2. Traer los tipos de exámenes
    try {
        const res = await axios.get('/api/v1/quizzes/types');
        if (res.data && Array.isArray(res.data.data)) {
            quizTypes.value = res.data.data;
        } else if (Array.isArray(res.data)) {
            quizTypes.value = res.data;
        }
    } catch (e) {
        console.error("Error al cargar tipos de examen:", e);
    }
    
    // 3. Cargar la información del Quiz a editar
    await loadQuiz();
});
</script>

<style scoped>
.form-input-custom {
    display: block;
    width: 100%;
    height: 2.5rem;
    padding: 0 0.75rem;
    margin-top: 0.25rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.form-input-custom:focus {
    border-color: #6366f1;
    outline: none;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

input[type="range"] {
    -webkit-appearance: none;
    background: #e5e7eb;
    height: 4px;
    border-radius: 5px;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #4f46e5;
    cursor: pointer;
}
</style>