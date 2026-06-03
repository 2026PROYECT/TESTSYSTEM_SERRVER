<template>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">{{ editing ? 'Editar QR' : 'Crear Nuevo QR' }}</h2>
        
        <form @submit.prevent="handleSubmit">
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
                        placeholder="Ej: Enlace a WhatsApp"
                    >
                </div>

                <!-- Contenido -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contenido / URL *
                    </label>
                    <textarea 
                        v-model="form.content" 
                        rows="3" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
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

                <!-- Botones -->
                <div class="flex gap-3 pt-4">
                    <button 
                        type="submit" 
                        :disabled="loading"
                        class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ loading ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear QR') }}
                    </button>
                    <button 
                        v-if="editing" 
                        type="button" 
                        @click="$emit('cancel')"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    editing: {
        type: Boolean,
        default: false
    },
    qrData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['save', 'cancel']);

const loading = ref(false);
const form = ref({
    title: '',
    content: '',
    description: '',
    size: 300
});

// Cargar datos cuando se edita
watch(() => props.qrData, (newData) => {
    if (newData) {
        form.value = { ...newData };
    } else if (!props.editing) {
        resetForm();
    }
}, { immediate: true });

const resetForm = () => {
    form.value = {
        title: '',
        content: '',
        description: '',
        size: 300
    };
};

const handleSubmit = async () => {
    loading.value = true;
    try {
        await emit('save', form.value);
        if (!props.editing) {
            resetForm();
        }
    } finally {
        loading.value = false;
    }
};
</script>