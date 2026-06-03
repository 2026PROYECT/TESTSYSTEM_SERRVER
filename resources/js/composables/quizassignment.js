import { ref } from 'vue';
import axios from 'axios';

export default function useQuizAssignment() {
    const assignments = ref({ data: [], meta: { current_page: 1 } });
    const errors = ref({});
    const isLoading = ref(false);

    /**
     * Obtener asignaciones con soporte para paginación, búsqueda e IDIOMA
     */
    const getAssignments = async (page = 1, search = '', language_id = '') => {
        isLoading.value = true;
        try {
            const response = await axios.get('/api/v1/quiz-assignments', {
                // Enviamos el language_id para discriminar por departamento
                params: { 
                    page, 
                    search, 
                    language_id 
                }
            });
            assignments.value = response.data;
        } catch (error) {
            console.error("Error fetching assignments:", error);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Guardar nueva autorización con captura de errores de validación
     */
    const storeAssignment = async (data) => {
        errors.value = {};
        isLoading.value = true;
        try {
            await axios.post('/api/v1/quiz-assignments', data);
            return true;
        } catch (e) {
            // Capturamos errores 422 (como choque de horarios o alumno ya certificado)
            if (e.response?.status === 422) {
                errors.value = e.response.data.errors || { message: [e.response.data.message] };
            }
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Actualizar autorización existente con exclusión de colisión propia
     */
    const updateAssignment = async (id, data) => {
        errors.value = {};
        isLoading.value = true;
        try {
            const response = await axios.put(`/api/v1/quiz-assignments/${id}`, data);
            
            // Sincronización optimista del estado local
            if (assignments.value.data) {
                const index = assignments.value.data.findIndex(asig => asig.id === id);
                if (index !== -1) {
                    assignments.value.data[index] = { 
                        ...assignments.value.data[index], 
                        ...response.data.data 
                    };
                }
            }
            return true;
        } catch (e) {
            console.error("Update error:", e);
            if (e.response?.status === 422) {
                // Guardamos el mensaje específico para que el componente lo use en SweetAlert
                errors.value = e.response.data.errors || { message: [e.response.data.message] };
            }
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Eliminar autorización de forma permanente
     */
    const destroyAssignment = async (id) => {
        try {
            await axios.delete(`/api/v1/quiz-assignments/${id}`);
            return true;
        } catch (error) {
            console.error("Error deleting assignment:", error);
            return false;
        }
    };

    return {
        assignments,
        errors,
        isLoading,
        getAssignments,
        storeAssignment,
        updateAssignment,
        destroyAssignment
    };
}