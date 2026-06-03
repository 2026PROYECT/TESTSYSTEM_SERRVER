// resources/js/composables/module.js
import { ref } from 'vue';
import axios from 'axios';

export default function useModule() {
    const modules = ref({
        data: [],
        current_page: 1,
        last_page: 1,
        total: 0
    });
    const isLoading = ref(false);
    const error = ref(null);

    /**
     * Obtener módulos con filtros y paginación
     * @param {number} page - Número de página
     * @param {Object} filters - Filtros opcionales
     * @param {string} filters.language_id - ID del idioma
     * @param {string} filters.type - Tipo de módulo (reading, listening, mixed)
     * @param {string} filters.level - Nivel (A1, A2, B1, B2, C1)
     * @param {string} filters.sort_by - Campo para ordenar (id, title, type, level, created_at)
     * @param {string} filters.sort_order - Orden (asc, desc)
     */
    const getModules = async (page = 1, filters = {}) => {
        isLoading.value = true;
        error.value = null;
        
        try {
            // Obtener idioma activo del localStorage
            const activeLangId = localStorage.getItem('active_lang_id') || '1';
            
            // Construir parámetros
            const params = {
                page: page,
                per_page: 15,
                language_id: filters.language_id || activeLangId,
                sort_by: filters.sort_by || 'id',
                sort_order: filters.sort_order || 'asc'
            };
            
            // Agregar filtros opcionales
            if (filters.type && filters.type !== '') {
                params.type = filters.type;
            }
            if (filters.level && filters.level !== '') {
                params.level = filters.level;
            }
            
            const response = await axios.get('/api/v1/admin/modules', { params });
            modules.value = response.data;
            return modules.value;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al cargar módulos';
            console.error('Error en getModules:', error.value);
            throw error.value;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Eliminar un módulo
     * @param {number} id - ID del módulo a eliminar
     */
    const deleteModule = async (id) => {
        if (!confirm('⚠️ ¿Eliminar este módulo?\n\nSe perderán TODAS las preguntas asociadas.\n\nEsta acción no se puede deshacer.')) {
            return false;
        }
        
        isLoading.value = true;
        
        try {
            await axios.delete(`/api/v1/admin/modules/${id}`);
            // Recargar la página actual después de eliminar
            await getModules(modules.value.current_page);
            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al eliminar';
            console.error('Error en deleteModule:', error.value);
            alert(`❌ ${error.value}`);
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Recargar módulos con los filtros actuales
     */
    const refreshModules = async () => {
        return await getModules(modules.value.current_page);
    };

    return {
        modules,
        isLoading,
        error,
        getModules,
        deleteModule,
        refreshModules
    };
}