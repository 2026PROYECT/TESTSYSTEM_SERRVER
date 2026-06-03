<template>
    <div class="qr-manager p-4 md:p-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                    Gestión de Códigos QR
                </h1>
                <p class="text-gray-600 mt-2">
                    Crea, edita y administra tus códigos QR para enlaces, WhatsApp, Facebook, TikTok y más
                </p>
            </div>

            <!-- Grid responsive -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna izquierda: Formulario -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4">{{ editing ? 'Editar QR' : 'Crear Nuevo QR' }}</h2>
                        
                        <form @submit.prevent="saveQR">
                            <div class="space-y-4">
                                <!-- Título -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Título *
                                    </label>
                                    <input 
                                        v-model="form.title" 
                                        type="text" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ej: Mi WhatsApp"
                                    >
                                </div>

                                <!-- Plantillas rápidas -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        Plantillas rápidas
    </label>
    
    <!-- Redes Sociales -->
    <div class="text-xs text-gray-500 mb-1">Redes Sociales</div>
    <div class="grid grid-cols-4 gap-2 mb-3">
        <button 
            type="button"
            @click="selectTemplate('whatsapp')"
            class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors text-xs font-medium"
        >
            📱 WhatsApp
        </button>
        <button 
            type="button"
            @click="selectTemplate('facebook')"
            class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors text-xs font-medium"
        >
            📘 Facebook
        </button>
        <button 
            type="button"
            @click="selectTemplate('instagram')"
            class="p-2 rounded-lg bg-pink-50 text-pink-600 hover:bg-pink-100 transition-colors text-xs font-medium"
        >
            📷 Instagram
        </button>
        <button 
            type="button"
            @click="selectTemplate('tiktok')"
            class="p-2 rounded-lg bg-black/5 text-black hover:bg-black/10 transition-colors text-xs font-medium"
        >
            🎵 TikTok
        </button>
        <button 
            type="button"
            @click="selectTemplate('twitter')"
            class="p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 transition-colors text-xs font-medium"
        >
            🐦 Twitter/X
        </button>
        <button 
            type="button"
            @click="selectTemplate('youtube')"
            class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors text-xs font-medium"
        >
            ▶️ YouTube
        </button>
        <button 
            type="button"
            @click="selectTemplate('linkedin')"
            class="p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors text-xs font-medium"
        >
            🔗 LinkedIn
        </button>
        <button 
            type="button"
            @click="selectTemplate('telegram')"
            class="p-2 rounded-lg bg-cyan-50 text-cyan-600 hover:bg-cyan-100 transition-colors text-xs font-medium"
        >
            ✈️ Telegram
        </button>
    </div>
    
    <!-- Reuniones / Videoconferencias -->
    <div class="text-xs text-gray-500 mb-1">Reuniones Virtuales</div>
    <div class="grid grid-cols-4 gap-2">
        <button 
            type="button"
            @click="selectTemplate('zoom')"
            class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors text-xs font-medium"
        >
            🎥 Zoom
        </button>
        <button 
            type="button"
            @click="selectTemplate('meet')"
            class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors text-xs font-medium"
        >
            🎬 Google Meet
        </button>
        <button 
            type="button"
            @click="selectTemplate('teams')"
            class="p-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors text-xs font-medium"
        >
            💼 Teams
        </button>
        <button 
            type="button"
            @click="selectTemplate('webex')"
            class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors text-xs font-medium"
        >
            🌐 Webex
        </button>
        <button 
            type="button"
            @click="selectTemplate('jitsi')"
            class="p-2 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-100 transition-colors text-xs font-medium"
        >
            🔓 Jitsi
        </button>
    </div>
</div>

                                <!-- Contenido / URL -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Contenido / URL *
                                    </label>
                                    <textarea 
                                        v-model="form.content" 
                                        rows="3" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                                        placeholder="https://... o texto que quieras codificar"
                                    ></textarea>
                                </div>

                                <!-- Descripción -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Descripción (opcional)
                                    </label>
                                    <textarea 
                                        v-model="form.description" 
                                        rows="2" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Descripción interna del QR"
                                    ></textarea>
                                </div>

                                <!-- Tamaño -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Tamaño: {{ form.size }}px
                                    </label>
                                    <input 
                                        v-model.number="form.size" 
                                        type="range" 
                                        min="100" 
                                        max="600" 
                                        step="10"
                                        class="w-full"
                                    >
                                </div>

                                <!-- Selector de Formato -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Formato de descarga
                                    </label>
                                    <div class="flex gap-4 mt-1">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                v-model="downloadFormat" 
                                                value="svg"
                                                class="w-4 h-4 text-indigo-600"
                                            >
                                            <span class="text-sm font-medium text-gray-700">🎨 SVG (Vectorial)</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                v-model="downloadFormat" 
                                                value="png"
                                                class="w-4 h-4 text-indigo-600"
                                            >
                                            <span class="text-sm font-medium text-gray-700">📷 PNG (Imagen)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Vista previa -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Vista previa
                                    </label>
                                    <div class="flex gap-2">
                                        <input 
                                            v-model="previewContent" 
                                            type="text" 
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg"
                                            placeholder="URL o texto para probar"
                                        >
                                        <button 
                                            type="button"
                                            @click="generatePreview"
                                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
                                        >
                                            Generar
                                        </button>
                                    </div>
                                </div>

                                <div v-if="previewImage" class="text-center">
                                    <div class="border rounded-lg p-4 bg-white inline-block">
                                        <img :src="previewImage" alt="Vista previa QR" class="mx-auto" style="width: 150px; height: 150px;">
                                    </div>
                                    <button 
                                        type="button"
                                        @click="downloadPreview"
                                        class="mt-2 text-indigo-600 hover:text-indigo-800 text-sm"
                                    >
                                        📥 Descargar {{ downloadFormat.toUpperCase() }}
                                    </button>
                                </div>

                                <div class="flex gap-3 pt-4">
                                    <button 
                                        type="submit" 
                                        :disabled="saving"
                                        class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {{ saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear QR') }}
                                    </button>
                                    <button 
                                        v-if="editing" 
                                        type="button" 
                                        @click="cancelEdit"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Columna derecha: Lista de QRs -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h2 class="text-xl font-bold">Códigos QR Existentes</h2>
                                    <p class="text-sm text-gray-500">Total: {{ qrs.length }}</p>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-gray-500">Descargar como:</span>
                                    <button 
                                        @click="downloadFormat = 'svg'"
                                        :class="downloadFormat === 'svg' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1 rounded-lg text-xs font-medium transition-colors"
                                    >
                                        SVG
                                    </button>
                                    <button 
                                        @click="downloadFormat = 'png'"
                                        :class="downloadFormat === 'png' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1 rounded-lg text-xs font-medium transition-colors"
                                    >
                                        PNG
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="loading" class="p-8 text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            <p class="mt-2 text-gray-500">Cargando...</p>
                        </div>

                        <div v-else-if="qrs.length === 0" class="p-8 text-center text-gray-500">
                            No hay códigos QR creados aún.
                        </div>

                        <div v-else class="divide-y divide-gray-200">
                            <div v-for="qr in qrs" :key="qr.id" class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ getSocialIcon(qr.content) }}</span>
                                            <h3 class="font-semibold text-gray-800">{{ qr.title }}</h3>
                                        </div>
                                        <p class="text-sm text-gray-500 truncate">{{ qr.content }}</p>
                                        <p v-if="qr.description" class="text-xs text-gray-400 mt-1">{{ qr.description }}</p>
                                        <div class="flex gap-3 mt-2 text-xs text-gray-400">
                                            <span>📊 Escaneos: {{ qr.scans || 0 }}</span>
                                            <span>📏 Tamaño: {{ qr.size || 300 }}px</span>
                                            <span>📅 {{ formatDate(qr.created_at) }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2 ml-4">
                                        <button 
                                            @click="viewQR(qr)"
                                            class="text-indigo-600 hover:text-indigo-800 p-1"
                                            title="Ver QR"
                                        >
                                            👁️
                                        </button>
                                        <button 
                                            @click="downloadSingleQR(qr)"
                                            class="text-green-600 hover:text-green-800 p-1"
                                            title="Descargar {{ downloadFormat.toUpperCase() }}"
                                        >
                                            📥
                                        </button>
                                        <button 
                                            @click="editQR(qr)"
                                            class="text-indigo-600 hover:text-indigo-800 p-1"
                                            title="Editar"
                                        >
                                            ✏️
                                        </button>
                                        <button 
                                            @click="deleteQR(qr.id)"
                                            class="text-red-600 hover:text-red-800 p-1"
                                            title="Eliminar"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de vista previa -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ getSocialIcon(selectedQr?.content) }}</span>
                        <h3 class="text-lg font-bold text-gray-800">{{ selectedQr?.title }}</h3>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>
                <div class="text-center py-4">
                    <div class="bg-gray-100 inline-block p-4 rounded-lg mb-4">
                        <img :src="modalQrImage" alt="QR" class="w-48 h-48 mx-auto" v-if="modalQrImage" />
                        <div v-else class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                            <span class="text-gray-400">Cargando...</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 break-all">Contenido: {{ selectedQr?.content }}</p>
                    <p class="text-xs text-gray-400 mt-2">Escaneos: {{ selectedQr?.scans || 0 }}</p>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="copyToClipboard" class="flex-1 bg-gray-600 text-white py-2 rounded-lg hover:bg-gray-700">
                        📋 Copiar enlace
                    </button>
                    <button @click="downloadCurrentQR" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
                        Descargar {{ downloadFormat.toUpperCase() }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// ==================== PLANTILLAS DE REDES SOCIALES ====================

// ==================== PLANTILLAS DE REDES SOCIALES Y REUNIONES ====================
const templates = {
    // Redes Sociales
    whatsapp: {
        title: 'Mi WhatsApp',
        content: 'https://wa.me/591XXXXXXXXX',
        placeholder: 'Ej: https://wa.me/59171234567',
        icon: '📱',
        color: 'green'
    },
    facebook: {
        title: 'Mi Facebook',
        content: 'https://facebook.com/tu_perfil',
        placeholder: 'https://facebook.com/tu_usuario',
        icon: '📘',
        color: 'blue'
    },
    instagram: {
        title: 'Mi Instagram',
        content: 'https://instagram.com/tu_usuario',
        placeholder: 'https://instagram.com/tu_usuario',
        icon: '📷',
        color: 'pink'
    },
    tiktok: {
        title: 'Mi TikTok',
        content: 'https://tiktok.com/@tu_usuario',
        placeholder: 'https://tiktok.com/@tu_usuario',
        icon: '🎵',
        color: 'black'
    },
    twitter: {
        title: 'Mi Twitter/X',
        content: 'https://twitter.com/tu_usuario',
        placeholder: 'https://twitter.com/tu_usuario',
        icon: '🐦',
        color: 'sky'
    },
    youtube: {
        title: 'Mi YouTube',
        content: 'https://youtube.com/@tu_canal',
        placeholder: 'https://youtube.com/@tu_canal',
        icon: '▶️',
        color: 'red'
    },
    linkedin: {
        title: 'Mi LinkedIn',
        content: 'https://linkedin.com/in/tu_perfil',
        placeholder: 'https://linkedin.com/in/tu_usuario',
        icon: '🔗',
        color: 'blue'
    },
    telegram: {
        title: 'Mi Telegram',
        content: 'https://t.me/tu_usuario',
        placeholder: 'https://t.me/tu_usuario',
        icon: '✈️',
        color: 'cyan'
    },
    
    // ========== REUNIONES / VIDEOCONFERENCIAS ==========
    zoom: {
        title: 'Reunión Zoom',
        content: 'https://zoom.us/j/123456789',
        placeholder: 'https://zoom.us/j/123456789',
        icon: '🎥',
        color: 'blue',
        help: 'Copia el enlace de tu reunión Zoom'
    },
    meet: {
        title: 'Google Meet',
        content: 'https://meet.google.com/xxx-xxxx-xxx',
        placeholder: 'https://meet.google.com/abc-defg-hij',
        icon: '🎬',
        color: 'green',
        help: 'Crea una reunión en Google Meet y copia el enlace'
    },
    teams: {
        title: 'Microsoft Teams',
        content: 'https://teams.microsoft.com/l/meetup-join/...',
        placeholder: 'https://teams.microsoft.com/l/meetup-join/...',
        icon: '💼',
        color: 'purple',
        help: 'Copia el enlace de tu reunión de Teams'
    },
    webex: {
        title: 'Cisco Webex',
        content: 'https://miempresa.webex.com/meet/...',
        placeholder: 'https://tuempresa.webex.com/meet/tu_nombre',
        icon: '🌐',
        color: 'indigo',
        help: 'Copia el enlace de tu reunión Webex'
    },
    jitsi: {
        title: 'Jitsi Meet',
        content: 'https://meet.jit.si/tu-sala',
        placeholder: 'https://meet.jit.si/nombre-de-tu-sala',
        icon: '🔓',
        color: 'teal',
        help: 'Puedes crear una sala en meet.jit.si con cualquier nombre'
    }
};
// ==================== ESTADO ====================
const loading = ref(false);
const saving = ref(false);
const editing = ref(false);
const qrs = ref([]);
const selectedQr = ref(null);
const previewImage = ref(null);
const previewContent = ref('');
const showModal = ref(false);
const modalQrImage = ref('');
const downloadFormat = ref('svg');
let currentPreviewBlob = null;
let currentDownloadBlob = null;

// Formulario
const form = reactive({
    title: '',
    content: '',
    description: '',
    size: 300
});

// ==================== FUNCIONES ====================

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('es-ES');
};

// Seleccionar plantilla
const selectTemplate = (type) => {
    const template = templates[type];
    if (template) {
        form.title = template.title;
        form.content = template.content;
        // Mostrar instrucción al usuario
        Swal.fire({
            title: `Plantilla de ${type.charAt(0).toUpperCase() + type.slice(1)}`,
            text: `Reemplaza "tu_usuario" con tu nombre de usuario real.\n\nEjemplo: ${template.placeholder}`,
            icon: 'info',
            confirmButtonText: 'Entendido'
        });
    }
};

// Obtener ícono según la URL
const getSocialIcon = (url) => {
    if (!url) return '🔗';
    if (url.includes('wa.me') || url.includes('whatsapp')) return '📱';
    if (url.includes('facebook.com') || url.includes('fb.com')) return '📘';
    if (url.includes('instagram.com')) return '📷';
    if (url.includes('tiktok.com')) return '🎵';
    if (url.includes('twitter.com') || url.includes('x.com')) return '🐦';
    if (url.includes('youtube.com') || url.includes('youtu.be')) return '▶️';
    if (url.includes('linkedin.com')) return '🔗';
    if (url.includes('t.me') || url.includes('telegram')) return '✈️';
    return '🔗';
};

// Copiar al portapapeles
const copyToClipboard = () => {
    if (selectedQr.value?.content) {
        navigator.clipboard.writeText(selectedQr.value.content);
        Swal.fire('Copiado', 'Enlace copiado al portapapeles', 'success');
    }
};

// Convertir SVG a PNG
const convertSvgToPng = (svgBlob, size, callback) => {
    const url = URL.createObjectURL(svgBlob);
    const img = new Image();
    
    img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, size, size);
        ctx.drawImage(img, 0, 0, size, size);
        
        canvas.toBlob((pngBlob) => {
            URL.revokeObjectURL(url);
            callback(pngBlob);
        }, 'image/png');
    };
    
    img.onerror = () => {
        URL.revokeObjectURL(url);
        callback(null);
    };
    
    img.src = url;
};

// Generar QR
const generateQR = async (content, size) => {
    const formData = new FormData();
    formData.append('content', content);
    formData.append('size', size);
    
    const response = await axios.post('/api/v1/admin/qr/generate', formData, {
        responseType: 'blob',
        headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    return response;
};

// Cargar lista de QRs
const loadQRs = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/qrs');
        
        if (response.data && response.data.data) {
            if (response.data.data.data) {
                qrs.value = response.data.data.data;
            } else {
                qrs.value = response.data.data;
            }
        } else if (Array.isArray(response.data)) {
            qrs.value = response.data;
        } else {
            qrs.value = [];
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudieron cargar los QRs', 'error');
    } finally {
        loading.value = false;
    }
};

// Vista previa
const generatePreview = async () => {
    if (!previewContent.value) {
        Swal.fire('Advertencia', 'Ingresa un contenido para generar el QR', 'warning');
        return;
    }
    
    try {
        const response = await generateQR(previewContent.value, 300);
        
        if (downloadFormat.value === 'png') {
            convertSvgToPng(response.data, 300, (pngBlob) => {
                if (pngBlob) {
                    currentPreviewBlob = pngBlob;
                    previewImage.value = URL.createObjectURL(pngBlob);
                }
            });
        } else {
            currentPreviewBlob = response.data;
            previewImage.value = URL.createObjectURL(response.data);
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudo generar el QR', 'error');
    }
};

const downloadPreview = () => {
    if (currentPreviewBlob) {
        const url = URL.createObjectURL(currentPreviewBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `qr-${Date.now()}.${downloadFormat.value}`;
        a.click();
        URL.revokeObjectURL(url);
    }
};

// Guardar QR
const saveQR = async () => {
    saving.value = true;
    try {
        const data = {
            title: form.title,
            content: form.content,
            description: form.description,
            size: form.size
        };
        
        if (editing.value && selectedQr.value) {
            await axios.put(`/api/v1/admin/qrs/${selectedQr.value.id}`, data);
            Swal.fire('Actualizado', 'QR actualizado correctamente', 'success');
        } else {
            await axios.post('/api/v1/admin/qrs', data);
            Swal.fire('Creado', 'QR creado correctamente', 'success');
        }
        
        resetForm();
        await loadQRs();
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', error.response?.data?.message || 'Error al guardar', 'error');
    } finally {
        saving.value = false;
    }
};

const editQR = (qr) => {
    editing.value = true;
    selectedQr.value = qr;
    form.title = qr.title;
    form.content = qr.content;
    form.description = qr.description || '';
    form.size = qr.size || 300;
};

const cancelEdit = () => {
    resetForm();
};

const resetForm = () => {
    editing.value = false;
    selectedQr.value = null;
    form.title = '';
    form.content = '';
    form.description = '';
    form.size = 300;
    previewImage.value = null;
    previewContent.value = '';
};

// Ver QR en modal
const viewQR = async (qr) => {
    selectedQr.value = qr;
    showModal.value = true;
    
    try {
        const response = await generateQR(qr.content, 300);
        
        if (downloadFormat.value === 'png') {
            convertSvgToPng(response.data, 300, (pngBlob) => {
                if (pngBlob) {
                    modalQrImage.value = URL.createObjectURL(pngBlob);
                    currentDownloadBlob = pngBlob;
                }
            });
        } else {
            modalQrImage.value = URL.createObjectURL(response.data);
            currentDownloadBlob = response.data;
        }
    } catch (error) {
        console.error('Error:', error);
    }
};

// Descargar QR
const downloadSingleQR = async (qr) => {
    try {
        const response = await generateQR(qr.content, 500);
        
        if (downloadFormat.value === 'png') {
            convertSvgToPng(response.data, 500, (pngBlob) => {
                if (pngBlob) {
                    const url = URL.createObjectURL(pngBlob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `qr-${qr.title.replace(/\s/g, '-')}.png`;
                    a.click();
                    URL.revokeObjectURL(url);
                    Swal.fire('Éxito', 'QR descargado correctamente', 'success');
                }
            });
        } else {
            const url = URL.createObjectURL(response.data);
            const a = document.createElement('a');
            a.href = url;
            a.download = `qr-${qr.title.replace(/\s/g, '-')}.svg`;
            a.click();
            URL.revokeObjectURL(url);
            Swal.fire('Éxito', 'QR descargado correctamente', 'success');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudo descargar el QR', 'error');
    }
};

const downloadCurrentQR = () => {
    if (currentDownloadBlob) {
        const url = URL.createObjectURL(currentDownloadBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `qr-${selectedQr.value?.title?.replace(/\s/g, '-') || 'qr'}.${downloadFormat.value}`;
        a.click();
        URL.revokeObjectURL(url);
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedQr.value = null;
    if (modalQrImage.value) {
        URL.revokeObjectURL(modalQrImage.value);
        modalQrImage.value = '';
    }
    currentDownloadBlob = null;
};

// Eliminar QR
const deleteQR = async (id) => {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: 'Este código QR será eliminado permanentemente',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/v1/admin/qrs/${id}`);
            Swal.fire('Eliminado', 'QR eliminado correctamente', 'success');
            await loadQRs();
        } catch (error) {
            Swal.fire('Error', 'No se pudo eliminar el QR', 'error');
        }
    }
};

// ==================== LIFECYCLE ====================
onMounted(() => {
    loadQRs();
});
</script>