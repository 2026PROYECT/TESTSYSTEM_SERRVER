<template>
    <div class="max-w-2xl mx-auto p-6 bg-white shadow-md rounded-lg mt-10">
        <div class="flex flex-col items-center mb-6">
            <h4 class="text-lg font-bold text-indigo-600">Add New Quiz</h4>
            <span class="mt-2 px-4 py-1 bg-blue-50 text-blue-600 text-[9px] font-black uppercase tracking-widest rounded-full border border-blue-100">
                Idioma : {{ currentLanguageName }}
            </span>
        </div>
        
        <form @submit.prevent="handleSubmit">
            <!-- Título -->
            <div class="mb-4">
                <label for="quiz-title" class="block text-sm font-medium text-gray-700">Title</label>
                <input id="quiz-title" v-model="quiz.title" type="text" class="form-input-custom">
                <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.title">
                    <p v-for="message in validationErrors.title" :key="message">{{ message }}</p>
                </div>
            </div>

            <!-- Tipo de Examen -->
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

            <!-- Asignatura -->
            <div class="mb-4">
                <label for="quiz-subject" class="block text-sm font-medium text-gray-700">Subject Name</label>
                <input type="text" id="quiz-subject" v-model="quiz.subject_name" class="form-input-custom" />
                <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.subject_name">
                    <p v-for="message in validationErrors.subject_name" :key="message">{{ message }}</p>
                </div>
            </div>

            <!-- Puntaje Total y Nota Mínima -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="total-mark" class="block text-sm font-medium text-gray-700">Total Mark</label>
                    <input type="number" id="total-mark" v-model="quiz.total_mark" class="form-input-custom" />
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
                    />
                    <p v-if="Number(quiz.pass_mark) > Number(quiz.total_mark)" class="mt-1 text-xs text-red-600 font-bold">
                        ⚠️ Debe ser menor o igual a {{ quiz.total_mark }}
                    </p>
                </div>
            </div>

            <!-- Duración (minutos) -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Duration (Minutes)</label>
                <input 
                    v-model="quiz.duration_minutes" 
                    type="number" 
                    class="form-input-custom"
                    :class="{'border-red-500': validationErrors?.duration_minutes}"
                    placeholder="e.g. 60"
                >
                <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.duration_minutes">
                    <p v-for="message in validationErrors.duration_minutes" :key="message">{{ message }}</p>
                </div>
            </div>

            <!-- Cantidad de Preguntas -->
<div class="mt-4">
    <label class="block text-sm font-medium text-gray-700">
        Número de Preguntas: <span class="font-bold text-indigo-600">{{ quiz.total_questions }}</span>
    </label>
    <input 
        v-model.number="quiz.total_questions" 
        type="range" 
        min="10" 
        max="100" 
        step="1"
        class="w-full mt-2"
    />
    <p class="text-xs text-gray-400 mt-2">El examen tomará {{ quiz.total_questions }} preguntas al azar del banco (mínimo 5, máximo 100).</p>
    <div class="mt-1 text-xs text-red-600" v-if="validationErrors?.total_questions">
        <p v-for="message in validationErrors.total_questions" :key="message">{{ message }}</p>
    </div>
</div>
            <!-- Fecha del Examen -->
            <div class="mb-6">
                <label for="quiz-date" class="block text-sm font-medium text-gray-700">Quiz Date</label>
                <input 
                    type="datetime-local" 
                    v-model="quiz.quiz_date" 
                    id="quiz-date" 
                    class="form-input-custom"
                    step="60"
                >
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3">
                <router-link 
                    :to="{ name: 'quiz.index' }" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                >
                    Cancel
                </router-link>
                
                <button :disabled="isLoading" class="inline-flex items-center px-6 py-2 text-sm font-bold text-white uppercase bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                    <span v-if="isLoading" class="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></span>
                    {{ isLoading ? 'Processing...' : 'Save Quiz' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import axios from 'axios';
import useQuiz from '@/composables/quiz';

const { storeQuiz, validationErrors, isLoading } = useQuiz();

// Formulario
const quiz = reactive({
    title: '',
    subject_name: '',
    total_mark: '',
    pass_mark: '',
    duration_minutes: 60,
    quiz_date: '',
    type: '',
    total_questions: 10, // 👈 Valor por defecto
});

const quizTypes = ref([]); 
const languages = ref([]);

// Enviar formulario
const handleSubmit = async () => {
    validationErrors.value = {};

    // Validación: Nota de aprobación
    if (Number(quiz.pass_mark) > Number(quiz.total_mark)) {
        validationErrors.value = {
            pass_mark: ['La nota de aprobación no puede ser mayor al total de puntos.']
        };
        return;
    }

    // Validación: Tipo de examen
    if (!quiz.type) {
        validationErrors.value = {
            type: ['Por favor, seleccione un tipo de examen válido.']
        };
        return;
    }

    // 🔥 Validación: Cantidad de preguntas
    if (!quiz.total_questions || quiz.total_questions < 5 || quiz.total_questions > 100) {
        validationErrors.value = {
            total_questions: ['La cantidad de preguntas debe estar entre 5 y 100.']
        };
        return;
    }

    const activeLangId = localStorage.getItem('active_lang_id') || '1';

    const payload = { ...quiz };
    payload.language_id = Number(activeLangId);
    payload.total_mark = Number(payload.total_mark);
    payload.pass_mark = Number(payload.pass_mark);
    payload.duration_minutes = Number(payload.duration_minutes);
    payload.total_questions = Number(payload.total_questions); // 👈 Enviar al backend

    await storeQuiz(payload);
};

// Idioma actual
const currentLanguageName = computed(() => {
    const langId = localStorage.getItem('active_lang_id') || '1';
    const lang = languages.value.find(l => Number(l.id) === Number(langId));
    return lang ? lang.name : 'Inglés';
});

// Cargar datos al montar
onMounted(async () => {
    isLoading.value = false;
    validationErrors.value = {};

    // Fecha por defecto
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    quiz.quiz_date = now.toISOString().slice(0, 16);

    // Cargar idiomas
    try {
        const res = await axios.get('/api/v1/languages');
        languages.value = res.data.data || res.data;
    } catch (e) { 
        console.error("Error al cargar idiomas:", e); 
    }

    // Cargar tipos de examen
    try {
        const res = await axios.get('/api/v1/quizzes/types');
        if (res.data && Array.isArray(res.data.data)) {
            quizTypes.value = res.data.data;
        } else if (Array.isArray(res.data)) {
            quizTypes.value = res.data;
        } else {
            console.warn("La estructura de respuesta no es un array conocido:", res.data);
        }
    } catch (e) {
        console.error("Error al cargar tipos de examen:", e);
    }
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
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
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