<template>
  <div class="max-w-5xl mx-auto pb-10">
    <header class="mb-8">
      <h1 class="text-3xl font-black text-slate-800">Mi Perfil</h1>
      <p class="text-slate-500">Gestiona tu información personal y seguridad de la cuenta.</p>
    </header>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-8">
        <form @submit.prevent="saveProfile" class="space-y-8">
          <!-- Sección de foto -->
          <div class="flex flex-col items-center gap-6 pb-8 border-b border-slate-100">
            <!-- Preview de foto -->
            <div class="relative group">
              <div v-if="photoPreview" class="relative">
                <img 
                  :src="photoPreview" 
                  class="h-32 w-32 rounded-3xl object-cover border-4 border-white shadow-xl"
                />
                <button 
                  type="button" 
                  @click="removePhoto" 
                  class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition shadow-lg"
                >
                  ✕
                </button>
              </div>
              <div v-else class="h-32 w-32 rounded-3xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center border-4 border-white shadow-xl">
                <span class="text-4xl text-indigo-400">👤</span>
              </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="flex gap-3">
              <button 
                type="button" 
                @click="startCamera" 
                class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-indigo-700 transition shadow-md"
              >
                <span>📸</span> Tomar Foto
              </button>
              
              <label class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-bold flex items-center gap-2 cursor-pointer hover:bg-slate-200 transition shadow-md">
                <span>📁</span> Subir Archivo
                <input type="file" class="hidden" @change="handleFileUpload" accept="image/*" />
              </label>
            </div>
            
            <p v-if="fileName" class="text-xs text-slate-400 mt-1">
              Archivo: {{ fileName }}
            </p>
            <p class="text-xs text-slate-400">
              Formatos: JPG, PNG (Máx. 2MB)
            </p>
            
            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase rounded-full border border-indigo-100">
              Rol: {{ user.role }}
            </span>
          </div>

          <!-- Campos del formulario con name, lastname y surname -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-700 ml-1">
                Nombre <span class="text-red-500">*</span>
              </label>
              <input 
                v-model="form.name" 
                type="text" 
                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all" 
                placeholder="Ej: Juan"
                required
              />
            </div>
            
            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-700 ml-1">
                Apellido Paterno <span class="text-red-500">*</span>
              </label>
              <input 
                v-model="form.lastname" 
                type="text" 
                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all" 
                placeholder="Ej: Pérez"
                required
              />
            </div>
            
            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-700 ml-1">
                Apellido Materno
              </label>
              <input 
                v-model="form.surname" 
                type="text" 
                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all" 
                placeholder="Ej: González"
              />
            </div>
          </div>

          <!-- Email (solo lectura) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2 md:col-span-2">
              <label class="text-sm font-bold text-slate-700 ml-1">
                Email (Identificador)
              </label>
              <input 
                :value="user.email" 
                disabled 
                type="email" 
                class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-slate-400 cursor-not-allowed italic" 
              />
            </div>
          </div>
          
          <!-- Mostrar nombre completo en vivo -->
          <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
            <p class="text-xs text-slate-500 font-medium mb-1">Vista previa del nombre completo:</p>
            <p class="text-lg font-bold text-slate-800">
              {{ fullName }} 
              <span class="text-sm font-normal text-slate-500 ml-2">
                ({{ user.email }})
              </span>
            </p>
          </div>
          
          <div class="flex justify-end">
            <button type="submit" :disabled="loading" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-10 rounded-xl transition-all shadow-lg shadow-indigo-100 active:scale-95 disabled:opacity-50">
              {{ loading ? 'Actualizando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>

        <!-- Sección de seguridad (sin cambios) -->
        <div class="mt-12 pt-10 border-t border-slate-100">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <h3 class="text-xl font-bold text-slate-800">Seguridad</h3>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-xl border border-indigo-100 text-xs font-medium">
              <span class="animate-pulse">📧</span>
              Se enviará aviso a: <strong class="ml-1">{{ user.email }}</strong>
            </div>
          </div>

          <form @submit.prevent="changePassword" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div v-for="field in passwordFields" :key="field.id" class="space-y-2">
                <label class="text-sm font-bold text-slate-700 ml-1">{{ field.label }}</label>
                <div class="relative">
                  <input 
                    :type="showPasswords[field.id] ? 'text' : 'password'" 
                    v-model="passwordForm[field.model]" 
                    class="w-full pl-4 pr-12 py-3 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all"
                  />
                  <button type="button" @click="showPasswords[field.id] = !showPasswords[field.id]" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition-colors">
                    {{ showPasswords[field.id] ? '👁️‍🗨️' : '👁️' }}
                  </button>
                </div>
              </div>
            </div>
            <div class="flex justify-end">
              <button type="submit" :disabled="pwLoading" class="bg-slate-900 hover:bg-black text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg active:scale-95 disabled:opacity-50">
                {{ pwLoading ? 'Cambiando clave...' : 'Actualizar Contraseña' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de Cámara -->
    <div v-if="showCamera" class="fixed inset-0 z-[100] bg-black/95 flex flex-col items-center justify-center p-6">
      <div class="relative w-full max-w-md bg-black rounded-3xl overflow-hidden shadow-2xl border border-white/10">
        <video ref="video" autoplay playsinline class="w-full h-auto transform scale-x-[-1]"></video>
        
        <!-- Guía circular para encuadre -->
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
          <div class="w-48 h-48 border-2 border-dashed border-white/50 rounded-full shadow-[0_0_0_1000px_rgba(0,0,0,0.6)]"></div>
        </div>

        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black to-transparent p-6 flex items-center justify-between">
          <button type="button" @click="stopCamera" class="text-white bg-white/10 px-6 py-2 rounded-full font-bold backdrop-blur-md hover:bg-white/20 transition">
            Cerrar
          </button>
          <button type="button" @click="takePhoto" class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-2xl border-4 border-gray-400 active:scale-90 transition">
            <div class="w-12 h-12 bg-red-600 rounded-full border-2 border-black"></div>
          </button>
          <div class="w-16"></div>
        </div>
      </div>
    </div>
    
    <canvas ref="canvas" class="hidden"></canvas>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// Datos del usuario
const user = ref(JSON.parse(localStorage.getItem('user_data')) || {});
const loading = ref(false);
const pwLoading = ref(false);

// Variables para la foto
const photoPreview = ref(null);
const fileName = ref('');

// Webcam Logic
const showCamera = ref(false);
const video = ref(null);
const canvas = ref(null);
const stream = ref(null);

// Inicializar el formulario con los datos del usuario
const form = reactive({ 
  name: user.value.name || '',
  lastname: user.value.lastname || '',
  surname: user.value.surname || '',
  picture: null
});

// Computed para mostrar nombre completo
const fullName = computed(() => {
  const parts = [form.name, form.lastname, form.surname].filter(part => part && part.trim());
  return parts.length > 0 ? parts.join(' ') : 'Nombre no especificado';
});

const passwordForm = reactive({ 
  current_password: '', 
  password: '', 
  password_confirmation: '' 
});

const showPasswords = reactive({ current: false, new: false, confirm: false });

const passwordFields = [
  { id: 'current', label: 'Contraseña Actual', model: 'current_password' },
  { id: 'new', label: 'Nueva Contraseña', model: 'password' },
  { id: 'confirm', label: 'Confirmar Nueva', model: 'password_confirmation' }
];

// Inicializar preview si ya tiene foto
onMounted(() => {
  if (user.value.picture) {
    photoPreview.value = '/storage/' + user.value.picture;
  }
});

// Manejar subida de archivo
const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.size > 2 * 1024 * 1024) {
    Swal.fire('Error', 'La imagen es muy pesada (máx 2MB)', 'error');
    return;
  }

  form.picture = file;
  fileName.value = file.name;
  photoPreview.value = URL.createObjectURL(file);
  event.target.value = '';
};

// Remover foto
const removePhoto = () => {
  Swal.fire({
    title: '¿Eliminar foto?',
    text: 'La foto actual será eliminada de tu perfil',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      photoPreview.value = null;
      form.picture = null;
      fileName.value = '';
      Swal.fire('Eliminada', 'La foto ha sido eliminada', 'success');
    }
  });
};

// Iniciar cámara
const startCamera = async () => {
  showCamera.value = true;
  try {
    stream.value = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        facingMode: "user",
        width: { ideal: 1280 }, 
        height: { ideal: 720 } 
      } 
    });
    if (video.value) {
      video.value.srcObject = stream.value;
    }
  } catch (err) {
    console.error('Error de cámara:', err);
    Swal.fire('Cámara', 'No se pudo acceder a la cámara. Revisa los permisos.', 'error');
    showCamera.value = false;
  }
};

// Tomar foto
const takePhoto = () => {
  if (!video.value || !canvas.value) return;
  
  const context = canvas.value.getContext('2d');
  canvas.value.width = video.value.videoWidth;
  canvas.value.height = video.value.videoHeight;
  
  // Espejo para selfie
  context.save();
  context.translate(canvas.value.width, 0);
  context.scale(-1, 1);
  context.drawImage(video.value, 0, 0);
  context.restore();
  
  const dataUrl = canvas.value.toDataURL('image/jpeg', 0.8);
  photoPreview.value = dataUrl;
  
  // Convertir dataURL a File
  fetch(dataUrl)
    .then(res => res.blob())
    .then(blob => {
      const file = new File([blob], "selfie.jpg", { type: "image/jpeg" });
      form.picture = file;
      fileName.value = "selfie.jpg";
    });
  
  stopCamera();
  
  Swal.fire({
    title: '¡Foto tomada!',
    text: 'La foto se ha capturado correctamente',
    icon: 'success',
    timer: 1500,
    showConfirmButton: false
  });
};

// Detener cámara
const stopCamera = () => {
  if (stream.value) {
    stream.value.getTracks().forEach(track => track.stop());
    stream.value = null;
  }
  showCamera.value = false;
};

// Guardar perfil
const saveProfile = async () => {
  // Validaciones
  if (!form.name || !form.name.trim()) {
    Swal.fire('Error', 'El nombre es obligatorio', 'error');
    return;
  }
  
  if (!form.lastname || !form.lastname.trim()) {
    Swal.fire('Error', 'El apellido paterno es obligatorio', 'error');
    return;
  }

  loading.value = true;
  try {
    const fd = new FormData();
    fd.append('name', form.name.trim());
    fd.append('lastname', form.lastname.trim());
    fd.append('surname', form.surname?.trim() || '');
    
    if (form.picture && form.picture instanceof File) {
      fd.append('picture', form.picture);
    }

    const { data } = await axios.post('/api/v1/user/update-profile', fd);

    // Actualizar localStorage
    localStorage.setItem('user_data', JSON.stringify(data.user));
    user.value = data.user;
    
    // Actualizar el formulario con los datos guardados
    form.name = data.user.name;
    form.lastname = data.user.lastname;
    form.surname = data.user.surname || '';
    
    // Si la foto se actualizó correctamente, actualizar preview
    if (data.user.picture) {
      photoPreview.value = '/storage/' + data.user.picture;
      form.picture = null; // Limpiar archivo temporal
    }

    await Swal.fire({
      title: '¡Éxito!',
      text: 'Perfil actualizado correctamente.',
      icon: 'success',
      confirmButtonColor: '#4f46e5',
      timer: 2000,
      showConfirmButton: false
    });

    // Recargar para reflejar cambios
    setTimeout(() => {
      window.location.href = '/profile';
    }, 500);

  } catch (error) {
    console.error('Error:', error);
    const message = error.response?.data?.message || error.response?.data?.errors || 'No se pudo actualizar el perfil.';
    Swal.fire('Error', message, 'error');
  } finally {
    loading.value = false;
  }
};

// Cambiar contraseña
const changePassword = async () => {
  if (passwordForm.password !== passwordForm.password_confirmation) {
    return Swal.fire('Error', 'Las nuevas contraseñas no coinciden.', 'error');
  }
  
  if (passwordForm.password.length < 6) {
    return Swal.fire('Error', 'La contraseña debe tener al menos 6 caracteres.', 'error');
  }
  
  pwLoading.value = true;
  try {
    await axios.post('/api/v1/user/update-password', passwordForm);
    
    await Swal.fire({
      title: '¡Éxito!',
      text: 'Contraseña actualizada. Se ha enviado un correo de seguridad a ' + user.value.email,
      icon: 'success',
      confirmButtonColor: '#4f46e5'
    });

    // Limpiar campos
    Object.keys(passwordForm).forEach(key => passwordForm[key] = '');
    
  } catch (e) {
    const errorMsg = e.response?.data?.message || e.response?.data?.error || 'Error al cambiar contraseña.';
    Swal.fire('Error', errorMsg, 'error');
  } finally {
    pwLoading.value = false;
  }
};
</script>

<style scoped>
/* Animaciones adicionales */
button, label {
  transition: all 0.2s ease;
}

/* Mejora para el modal de cámara */
.fixed {
  backdrop-filter: blur(8px);
}

/* Animación suave para el preview */
img {
  transition: all 0.3s ease;
}
</style>