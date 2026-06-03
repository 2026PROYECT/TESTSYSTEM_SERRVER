import axios from "axios";
window.axios = axios;

axios.defaults.withCredentials = true; 
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- INTERCEPTOR DE SOLICITUD PARA EL IDIOMA ---
window.axios.interceptors.request.use(
    (config) => {
        // Obtenemos el ID del idioma del localStorage
        const langId = localStorage.getItem('active_lang_id');
        
        if (langId) {
            // Lo añadimos como un Header personalizado
            config.headers['X-Language-Id'] = langId;
        }
        
        // 🔥 NUEVO: Log de peticiones en desarrollo
        if (import.meta.env.DEV) {
            console.group('📤 PETICIÓN API');
            console.log('URL:', config.url);
            console.log('Método:', config.method?.toUpperCase());
            console.log('Datos:', config.data);
            console.log('Headers:', config.headers);
            console.groupEnd();
        }
        
        return config;
    },
    (error) => {
        console.error('❌ Error en interceptor de petición:', error);
        return Promise.reject(error);
    }
);

// --- INTERCEPTOR DE RESPUESTA MEJORADO ---
window.axios.interceptors.response.use(
    // Respuesta exitosa
    (response) => {
        // 🔥 NUEVO: Log de respuestas exitosas en desarrollo
        if (import.meta.env.DEV) {
            console.group('✅ RESPUESTA API');
            console.log('URL:', response.config.url);
            console.log('Status:', response.status);
            console.log('Data:', response.data);
            console.groupEnd();
        }
        return response;
    },
    
    // Manejo de errores
    (error) => {
        // 🔥 NUEVO: Manejo específico para ERROR 400
        if (error.response?.status === 400) {
            console.group('🚨 ERROR 400 - BAD REQUEST');
            console.error('URL:', error.config?.url);
            console.error('Método:', error.config?.method?.toUpperCase());
            console.error('Datos enviados:', error.config?.data);
            console.error('Respuesta del servidor:', error.response.data);
            console.groupEnd();
            
            // Extraer mensaje de error (compatible con diferentes formatos)
            let errorMessage = 'Error en la petición';
            
            if (error.response.data) {
                if (typeof error.response.data === 'string') {
                    errorMessage = error.response.data;
                } else if (error.response.data.message) {
                    errorMessage = error.response.data.message;
                } else if (error.response.data.error) {
                    errorMessage = error.response.data.error;
                } else if (error.response.data.errors) {
                    // Errores de validación de Laravel
                    const validationErrors = Object.values(error.response.data.errors).flat();
                    errorMessage = validationErrors.join(', ');
                } else {
                    errorMessage = JSON.stringify(error.response.data);
                }
            }
            
            // Mostrar alerta visible SOLO en desarrollo
            if (import.meta.env.DEV) {
                // Si tienes SweetAlert2 disponible
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: `Error ${error.response.status}`,
                        text: errorMessage,
                        confirmButtonColor: '#4f46e5'
                    });
                } else {
                    // Fallback con alert nativo
                    alert(`Error 400:\n${errorMessage}`);
                }
            }
        }
        
        // 🔥 NUEVO: Manejo de ERROR 422 (Validación de Laravel)
        if (error.response?.status === 422) {
            console.group('⚠️ ERROR 422 - VALIDACIÓN');
            console.error('URL:', error.config?.url);
            console.error('Datos enviados:', error.config?.data);
            console.error('Errores de validación:', error.response.data.errors);
            console.groupEnd();
            
            if (import.meta.env.DEV && typeof Swal !== 'undefined') {
                const errors = error.response.data.errors;
                let errorText = '';
                for (const field in errors) {
                    errorText += `${field}: ${errors[field].join(', ')}\n`;
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Error de validación',
                    text: errorText,
                    confirmButtonColor: '#4f46e5'
                });
            }
        }
        
        // Manejo de ERROR 401 (No autenticado) - TU CÓDIGO EXISTENTE MEJORADO
        if (error.response?.status === 401 || error.response?.status === 419) {
            console.warn('⚠️ Sesión expirada o no autenticado');
            
            const loggedIn = localStorage.getItem("loggedIn");
            if (loggedIn && JSON.parse(loggedIn)) {
                localStorage.setItem("loggedIn", false);
                
                if (import.meta.env.DEV) {
                    console.log('🔄 Redirigiendo a login...');
                }
                
                location.assign("/login");
            }
        }
        
        // 🔥 NUEVO: Manejo de ERROR 403 (Sin permisos)
        if (error.response?.status === 403) {
            console.error('⛔ Error 403 - Sin permisos para esta acción');
            
            if (import.meta.env.DEV && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Sin permisos',
                    text: error.response.data?.message || 'No tienes permisos para realizar esta acción',
                    confirmButtonColor: '#4f46e5'
                });
            }
        }
        
        // 🔥 NUEVO: Manejo de ERROR 500 (Error del servidor)
        if (error.response?.status === 500) {
            console.error('💥 Error 500 - Error interno del servidor');
            console.error('Detalles:', error.response.data);
            
            if (import.meta.env.DEV && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: 'Ocurrió un error interno. Revisa los logs de Laravel.',
                    confirmButtonColor: '#4f46e5'
                });
            }
        }
        
        // 🔥 NUEVO: Manejo de errores sin conexión
        if (error.code === 'ERR_NETWORK') {
            console.error('🌐 Error de red - No hay conexión con el servidor');
            
            if (import.meta.env.DEV && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor. Verifica que XAMPP esté corriendo.',
                    confirmButtonColor: '#4f46e5'
                });
            }
        }
        
        return Promise.reject(error);
    }
);