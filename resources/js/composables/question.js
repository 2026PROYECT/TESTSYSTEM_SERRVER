import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export default function useQuestion() {
    const questions = ref({ data: [] });
    const isLoading = ref(false);
    const validationErrors = ref({});

    const getQuestions = async (page = 1, quizId = null) => {
        isLoading.value = true;
        try {
            let url = `/api/v1/questions?page=${page}`;
            if (quizId) {
                url += `&quiz_id=${quizId}`;
            }
            const response = await axios.get(url);
            questions.value = response.data.data || response.data;
        } catch (error) {
            console.error('Error fetching questions:', error);
        } finally {
            isLoading.value = false;
        }
    };

    const storeQuestion = async (questionData) => {
        isLoading.value = true;
        validationErrors.value = {};
        
        try {
            const formData = new FormData();
            
            // Agregar todos los campos
            Object.keys(questionData).forEach(key => {
                if (questionData[key] !== null && questionData[key] !== undefined && questionData[key] !== '') {
                    if (key === 'picture' || key === 'sound') {
                        if (questionData[key] instanceof File) {
                            formData.append(key, questionData[key]);
                        }
                    } else {
                        formData.append(key, questionData[key]);
                    }
                }
            });
            
            const response = await axios.post('/api/v1/questions', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            
            Swal.fire('Éxito', 'Pregunta creada correctamente', 'success');
            return response.data;
        } catch (error) {
            if (error.response?.status === 422) {
                validationErrors.value = error.response.data.errors;
                Swal.fire('Error de validación', 'Por favor revisa los campos', 'error');
            } else {
                const msg = error.response?.data?.message || 'Error al guardar la pregunta';
                Swal.fire('Error', msg, 'error');
            }
            throw error;
        } finally {
            isLoading.value = false;
        }
    };

    const updateQuestion = async (id, formData) => {
    isLoading.value = true;
    validationErrors.value = {};
    
    try {
        const response = await axios.post(`/api/v1/questions/${id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        Swal.fire('Éxito', 'Pregunta actualizada correctamente', 'success');
        return response.data;
    } catch (error) {
        if (error.response?.status === 422) {
            validationErrors.value = error.response.data.errors;
            Swal.fire('Error de validación', 'Por favor revisa los campos', 'error');
        } else {
            const msg = error.response?.data?.message || 'Error al actualizar la pregunta';
            Swal.fire('Error', msg, 'error');
        }
        throw error;
    } finally {
        isLoading.value = false;
    }
};
    const deleteQuestion = async (id) => {
        const result = await Swal.fire({
            title: '¿Eliminar pregunta?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        });
        
        if (result.isConfirmed) {
            isLoading.value = true;
            try {
                await axios.delete(`/api/v1/questions/${id}`);
                Swal.fire('Eliminada', 'Pregunta eliminada correctamente', 'success');
                return true;
            } catch (error) {
                Swal.fire('Error', 'Error al eliminar la pregunta', 'error');
                return false;
            } finally {
                isLoading.value = false;
            }
        }
        return false;
    };

    return {
        questions,
        isLoading,
        validationErrors,
        getQuestions,
        storeQuestion,
        updateQuestion,
        deleteQuestion
    };
}