// resources/js/composables/useInactivity.js
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

export function useInactivity(timeoutMinutes = 30) {
    const router = useRouter();
    let inactivityTimer = null;
    let warningTimer = null;
    const isWarningShowing = ref(false);
    
    const timeoutMs = timeoutMinutes * 60 * 1000; // Convertir a milisegundos
    const warningMs = 60 * 1000; // 1 minuto antes de cerrar
    
    // Función para cerrar sesión
    const logoutUser = async () => {
        clearTimers();
        
        try {
            // Llamar al endpoint de logout
            await axios.post('/api/v1/logout');
            
            // Limpiar localStorage
            localStorage.removeItem('user_data');
            localStorage.removeItem('loggedIn');
            localStorage.removeItem('token');
            
            // Redirigir al login
            router.push('/login');
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
            router.push('/login');
        }
    };
    
    // Mostrar advertencia antes de cerrar sesión
    const showInactivityWarning = () => {
        if (isWarningShowing.value) return;
        isWarningShowing.value = true;
        
        Swal.fire({
            title: '⚠️ Sesión por expirar',
            text: `Has estado inactivo por ${timeoutMinutes} minutos. ¿Deseas continuar?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cerrar sesión',
            timer: 30000, // 30 segundos para responder
            timerProgressBar: true,
            allowOutsideClick: false,
            didOpen: () => {
                // Si el usuario interactúa con la alerta, resetear timers
                document.addEventListener('click', resetTimers, { once: true });
            }
        }).then((result) => {
            isWarningShowing.value = false;
            
            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                // Usuario quiere continuar
                resetInactivityTimer();
                startInactivityTimer();
            } else {
                // Usuario cierra sesión
                logoutUser();
            }
        });
    };
    
    // Reiniciar el timer de inactividad
    const resetInactivityTimer = () => {
        clearTimeout(inactivityTimer);
        clearTimeout(warningTimer);
    };
    
    const startInactivityTimer = () => {
        // Timer para mostrar advertencia
        warningTimer = setTimeout(() => {
            showInactivityWarning();
        }, timeoutMs - warningMs);
        
        // Timer para cerrar sesión
        inactivityTimer = setTimeout(() => {
            logoutUser();
        }, timeoutMs);
    };
    
    const resetTimers = () => {
        if (isWarningShowing.value) return;
        resetInactivityTimer();
        startInactivityTimer();
    };
    
    const clearTimers = () => {
        clearTimeout(inactivityTimer);
        clearTimeout(warningTimer);
        inactivityTimer = null;
        warningTimer = null;
    };
    
    // Configurar eventos que detectan actividad
    const setupActivityListeners = () => {
        const events = [
            'mousemove', 'mousedown', 'click',
            'keydown', 'keypress', 'scroll',
            'touchstart', 'touchmove', 'wheel'
        ];
        
        events.forEach(event => {
            window.addEventListener(event, resetTimers);
        });
    };
    
    const removeActivityListeners = () => {
        const events = [
            'mousemove', 'mousedown', 'click',
            'keydown', 'keypress', 'scroll',
            'touchstart', 'touchmove', 'wheel'
        ];
        
        events.forEach(event => {
            window.removeEventListener(event, resetTimers);
        });
    };
    
    // Iniciar el sistema
    const init = () => {
        if (!localStorage.getItem('loggedIn')) return;
        
        setupActivityListeners();
        startInactivityTimer();
        
        // Limpiar al cerrar la página
        window.addEventListener('beforeunload', () => {
            clearTimers();
        });
    };
    
    const stop = () => {
        removeActivityListeners();
        clearTimers();
    };
    
    onMounted(() => {
        init();
    });
    
    onUnmounted(() => {
        stop();
    });
    
    return {
        init,
        stop,
        resetTimers,
        isWarningShowing
    };
}