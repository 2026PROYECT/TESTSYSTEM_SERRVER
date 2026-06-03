// resources/js/composables/quiz.js
import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export default function useQuiz() {
    const quizzes = ref({ data: [] });
    const quiz = ref({});
    const isLoading = ref(false);
    const validationErrors = ref({});

    // 🔥 MODIFICADO: Acepta página y tipo como parámetros
    const getQuizzes = async (page = 1, type = '') => {
        isLoading.value = true;
        try {
            let url = `/api/v1/quizzes?page=${page}`;
            if (type) {
                url += `&type=${type}`;
            }
            const response = await axios.get(url);
            quizzes.value = response.data.data || response.data;
        } catch (error) {
            console.error('Error fetching quizzes:', error);
        } finally {
            isLoading.value = false;
        }
    };

    const getQuiz = async (id) => {
        isLoading.value = true;
        try {
            const response = await axios.get(`/api/v1/quizzes/${id}`);
            quiz.value = response.data.data || response.data;
            return quiz.value;
        } catch (error) {
            console.error('Error fetching quiz:', error);
            throw error;
        } finally {
            isLoading.value = false;
        }
    };

    const storeQuiz = async (data) => {
        isLoading.value = true;
        validationErrors.value = {};
        try {
            await axios.post('/api/v1/quizzes', data);
            await Swal.fire({
                icon: 'success',
                title: '¡Creado!',
                text: 'Quiz creado correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
            return true;
        } catch (error) {
            if (error.response?.status === 422) {
                validationErrors.value = error.response.data.errors;
            } else {
                Swal.fire('Error', error.response?.data?.message || 'Error al crear el quiz', 'error');
            }
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const updateQuiz = async (data) => {
        isLoading.value = true;
        validationErrors.value = {};
        try {
            await axios.put(`/api/v1/quizzes/${data.id}`, data);
            await Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'Quiz actualizado correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
            return true;
        } catch (error) {
            if (error.response?.status === 422) {
                validationErrors.value = error.response.data.errors;
            } else {
                Swal.fire('Error', error.response?.data?.message || 'Error al actualizar el quiz', 'error');
            }
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteQuiz = async (id) => {
        const result = await Swal.fire({
            title: '¿Eliminar examen?',
            html: `
                <div class="text-left">
                    <p class="font-bold text-red-600 mb-2">⚠️ Esta acción eliminará:</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li>El examen completo</li>
                        <li>Todas sus preguntas</li>
                        <li>Los intentos de los estudiantes</li>
                    </ul>
                    <p class="text-red-500 mt-3 text-xs">Esta acción no se puede deshacer.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar todo',
            cancelButtonText: 'Cancelar'
        });
        
        if (result.isConfirmed) {
            isLoading.value = true;
            try {
                await axios.delete(`/api/v1/quizzes/${id}`);
                await Swal.fire('Eliminado', 'El quiz ha sido eliminado correctamente', 'success');
                // Recargar manteniendo la página actual
                await getQuizzes();
            } catch (error) {
                console.error('Error deleting quiz:', error);
                Swal.fire('Error', error.response?.data?.message || 'Error al eliminar el quiz', 'error');
            } finally {
                isLoading.value = false;
            }
        }
    };

    return {
        quizzes,
        quiz,
        isLoading,
        validationErrors,
        getQuizzes,
        getQuiz,
        storeQuiz,
        updateQuiz,
        deleteQuiz
    };
}