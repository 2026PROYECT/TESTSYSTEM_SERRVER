<template>
    <div class="relative">
        <button @click="toggleDropdown" class="relative p-2 rounded-full hover:bg-gray-100 transition-colors">
            <span class="text-xl">🔔</span>
            <span v-if="unreadCount > 0" 
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-5 h-5 px-1 flex items-center justify-center animate-pulse">
                {{ unreadCount }}
            </span>
        </button>
        
        <div v-if="showDropdown" class="absolute right-0 mt-2 w-96 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
            <!-- Cabecera -->
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-black text-gray-800">Notificaciones</h3>
                <div class="flex gap-2">
                    <button @click="markAllAsRead" v-if="unreadCount > 0" 
                            class="text-xs text-indigo-600 hover:text-indigo-800">
                        Leer todas
                    </button>
                    <button @click="deleteAllRead" v-if="hasReadNotifications" 
                            class="text-xs text-red-500 hover:text-red-700">
                        Limpiar leídas
                    </button>
                    <button @click="closeDropdown" class="text-xs text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>
            </div>
            
            <!-- Lista de notificaciones -->
            <div class="max-h-96 overflow-y-auto">
                <div v-if="notifications.length === 0" class="p-8 text-center text-gray-400">
                    <span class="text-4xl block mb-2">📭</span>
                    No hay notificaciones
                    <button @click="closeDropdown" class="block mx-auto mt-3 text-xs text-indigo-600">
                        Cerrar
                    </button>
                </div>
                
                <div v-for="notification in notifications" :key="notification.id" 
                     class="p-4 hover:bg-gray-50 transition border-b last:border-0 group"
                     :class="{ 'bg-gray-50': notification.read_at }">
                    <div class="flex gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                             :class="getIconBgClass(notification)">
                            <span class="text-lg">{{ getIcon(notification) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">{{ getTitle(notification) }}</p>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ getMessage(notification) }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ formatDate(notification.created_at) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <div v-if="!notification.read_at" class="w-2 h-2 rounded-full bg-red-500"></div>
                            <button @click.stop="deleteNotification(notification.id)" 
                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition text-xs">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pie -->
            <div class="p-2 border-t bg-gray-50 text-center">
                <button @click="loadNotifications" class="text-xs text-gray-400 hover:text-gray-600">
                    🔄 Actualizar
                </button>
                <button v-if="notifications.length > 0" @click="closeDropdown" class="ml-3 text-xs text-gray-400 hover:text-gray-600">
                    ✕ Cerrar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    userRole: {
        type: String,
        required: true
    }
});

const showDropdown = ref(false);
const notifications = ref([]);
let pollInterval = null;

const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.read_at).length;
});

const hasReadNotifications = computed(() => {
    return notifications.value.some(n => n.read_at);
});

const loadNotifications = async () => {
    try {
        const response = await axios.get(`/api/v1/${props.userRole}/notifications`);
        notifications.value = response.data;
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
};

const markAsRead = async (id) => {
    try {
        await axios.post(`/api/v1/${props.userRole}/notifications/${id}/read`);
        await loadNotifications();
    } catch (error) {
        console.error('Error marking as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post(`/api/v1/${props.userRole}/notifications/read-all`);
        await loadNotifications();
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
};

const deleteNotification = async (id) => {
    try {
        await axios.delete(`/api/v1/${props.userRole}/notifications/${id}`);
        await loadNotifications();
        // No cerrar automáticamente, solo recargar
    } catch (error) {
        console.error('Error deleting notification:', error);
    }
};

const deleteAllRead = async () => {
    try {
        await axios.delete(`/api/v1/${props.userRole}/notifications/read`);
        await loadNotifications();
        // Si después de eliminar no quedan notificaciones, cerrar dropdown
        if (notifications.value.length === 0) {
            closeDropdown();
        }
    } catch (error) {
        console.error('Error deleting read notifications:', error);
    }
};

const closeDropdown = () => {
    showDropdown.value = false;
};

const formatDate = (date) => {
    const now = new Date();
    const diff = Math.floor((now - new Date(date)) / 1000 / 60);
    
    if (diff < 1) return 'Ahora';
    if (diff < 60) return `Hace ${diff} minutos`;
    if (diff < 1440) return `Hace ${Math.floor(diff / 60)} horas`;
    return new Date(date).toLocaleDateString('es-ES');
};

const getIcon = (notification) => {
    const type = notification.data?.type || notification.type;
    const icons = {
        missed_exam: '⚠️',
        exam_reminder: '⏰',
        exam_result: '🎉',
        exam_unlocked: '🔓',
        new_evaluation: '📝',
        exam_completed: '✅',
        student_absent: '❌',
        registration: '👤',
        backup: '💾',
        security: '🚨'
    };
    return icons[type] || '📋';
};

const getIconBgClass = (notification) => {
    const type = notification.data?.type || notification.type;
    const classes = {
        missed_exam: 'bg-red-100',
        exam_reminder: 'bg-yellow-100',
        exam_result: 'bg-green-100',
        exam_unlocked: 'bg-blue-100',
        new_evaluation: 'bg-purple-100',
        exam_completed: 'bg-green-100',
        student_absent: 'bg-red-100',
        registration: 'bg-blue-100',
        backup: 'bg-gray-100',
        security: 'bg-red-100'
    };
    return classes[type] || 'bg-gray-100';
};

const getTitle = (notification) => {
    return notification.data?.title || 'Notificación';
};

const getMessage = (notification) => {
    return notification.data?.message || 'Tienes una nueva notificación';
};

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value) {
        loadNotifications();
    }
};

onMounted(() => {
    loadNotifications();
    pollInterval = setInterval(() => {
        if (!showDropdown.value) {
            loadNotifications();
        }
    }, 30000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2; /* versión con prefijo */
    line-clamp: 2;         /* versión estándar */
    overflow: hidden;
}
</style>