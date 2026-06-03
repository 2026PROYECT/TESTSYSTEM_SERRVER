<template>
  <div class="min-h-screen bg-gray-50/50 py-10 px-4">
    <div class="max-w-4xl mx-auto">
      <form @submit.prevent="saveStudent" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
        <div class="mb-8">
          <h2 class="text-2xl font-black text-gray-900">Inscribir Nuevo Estudiante</h2>
          <p class="text-sm text-gray-500 font-medium">Gestión de identidad y perfil académico</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
          
          <div class="space-y-6">
            <h3 class="text-xs font-black text-indigo-500 uppercase tracking-widest ml-1">Documentación e Identidad</h3>
            
            <div class="flex flex-col items-center p-4 bg-gray-50 rounded-3xl border border-gray-100">
              <label class="text-[10px] font-black text-gray-400 uppercase mb-3">Foto de Perfil *</label>
              <div class="flex gap-4 items-center">
                <button type="button" @click="startCamera('picture')" class="w-16 h-16 bg-white text-indigo-600 rounded-2xl flex flex-col items-center justify-center shadow-sm border border-indigo-50 hover:bg-indigo-50 transition">
                  <i class="fas fa-camera text-lg"></i>
                  <span class="text-[8px] font-bold mt-1">Webcam</span>
                </button>
                
                <div class="relative">
                  <img v-if="photoPreview" :src="photoPreview" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg" />
                  <div v-else class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 border-4 border-white">
                    <i class="fas fa-user text-2xl"></i>
                  </div>
                </div>

                <label class="w-16 h-16 bg-white text-gray-500 rounded-2xl flex flex-col items-center justify-center shadow-sm border border-dashed border-gray-300 cursor-pointer hover:bg-gray-50 transition">
                  <i class="fas fa-upload text-lg"></i>
                  <span class="text-[8px] font-bold mt-1">Archivo</span>
                  <input type="file" class="hidden" @change="handleFileUpload($event, 'picture')" accept="image/*" />
                </label>
              </div>
            </div>

            <div class="flex flex-col items-center p-4 bg-gray-50 rounded-3xl border border-gray-100">
              <label class="text-[10px] font-black text-gray-400 uppercase mb-3">Captura de Carnet (CI) *</label>
              <div class="w-full space-y-3">
                <div class="w-full h-40 bg-white rounded-2xl border border-gray-200 overflow-hidden relative">
                   <img v-if="idCardPreview" :src="idCardPreview" class="w-full h-full object-cover" />
                   <div v-else class="flex flex-col items-center justify-center h-full text-gray-300">
                      <i class="fas fa-id-card text-4xl mb-2"></i>
                      <span class="text-[10px] font-bold uppercase">Esperando captura</span>
                   </div>
                </div>
                <div class="flex gap-2">
                  <button type="button" @click="startCamera('idcard_picture')" class="flex-1 bg-indigo-600 text-white py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 hover:bg-indigo-700 transition shadow-md shadow-indigo-100">
                    <i class="fas fa-camera"></i> Webcam
                  </button>
                  <label class="flex-1 bg-white border border-gray-200 text-gray-600 py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 cursor-pointer hover:bg-gray-50 transition">
                    <i class="fas fa-file-image"></i> Archivo
                    <input type="file" class="hidden" @change="handleFileUpload($event, 'idcard_picture')" accept="image/*" />
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <h3 class="text-xs font-black text-indigo-500 uppercase tracking-widest ml-1">Información Académica</h3>
            
            <div class="grid grid-cols-2 gap-4">
              <input v-model="form.name" type="text" placeholder="Nombres *" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700 placeholder:text-gray-400" required />
              <input v-model="form.lastname" type="text" placeholder="Ap. Paterno *" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700 placeholder:text-gray-400" required />
            </div>
            <input v-model="form.surname" type="text" placeholder="Ap. Materno" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700 placeholder:text-gray-400" />
            <input v-model="form.email" type="email" placeholder="Correo Institucional *" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700 placeholder:text-gray-400" required />
            
            <div class="pt-2">
              <label class="text-[10px] font-black text-gray-400 uppercase ml-2">Carrera / Ingeniería *</label>
              <select v-model="form.career_id" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700" required>
                <option value="" disabled>Seleccionar Carrera</option>
                <option v-for="career in careers" :key="career.id" :value="career.id">
                  {{ career.name }}
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-[10px] font-black text-gray-400 uppercase ml-2">Semestre</label>
                <select v-model="form.semester" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700">
                  <option v-for="n in 10" :key="n" :value="n">{{ n }}° Semestre</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-gray-400 uppercase ml-2">Código SAGA *</label>
                <input v-model="form.saga_code" type="text" placeholder="Ej. S1234-5" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700 placeholder:text-gray-400" required />
              </div>
            </div>

            <input v-model="form.id_number" type="text" placeholder="N° de Carnet (CI) *" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700 placeholder:text-gray-400" required />
            <input v-model="form.password" type="password" placeholder="Contraseña Inicial *" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 transition font-medium text-gray-700 placeholder:text-gray-400" required />
          </div>
        </div>

        <!-- Modal de Cámara -->
        <div v-if="showCamera" class="fixed inset-0 z-[100] bg-black/95 flex flex-col items-center justify-center p-6">
          <div class="relative w-full max-w-2xl bg-black rounded-3xl overflow-hidden shadow-2xl border border-white/10">
            <video ref="video" autoplay playsinline class="w-full h-auto" :class="activeCameraType === 'picture' ? 'transform scale-x-[-1]' : ''"></video>
            
            <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
              <div v-if="activeCameraType === 'picture'" class="w-64 h-64 border-2 border-dashed border-white/50 rounded-full shadow-[0_0_0_1000px_rgba(0,0,0,0.6)]"></div>
              <div v-else class="w-[80%] h-[50%] border-2 border-dashed border-white/70 rounded-xl shadow-[0_0_0_1000px_rgba(0,0,0,0.6)]"></div>
            </div>

            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black to-transparent p-8 flex items-center justify-between">
              <button type="button" @click="stopCamera" class="text-white bg-white/10 px-6 py-2 rounded-full font-bold backdrop-blur-md hover:bg-white/20">Cerrar</button>
              <button type="button" @click="takePhoto" class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-2xl border-4 border-gray-400 active:scale-90 transition">
                <div class="w-16 h-16 bg-red-600 rounded-full border-2 border-black"></div>
              </button>
              <div class="w-20"></div> 
            </div>
          </div>
        </div>
        
        <canvas ref="canvas" class="hidden"></canvas>

        <div v-if="Object.keys(validationErrors).length > 0" class="mt-8 p-4 bg-red-50 rounded-2xl border border-red-100">
           <ul class="text-red-600 text-xs font-bold list-disc list-inside">
              <li v-for="(errors, field) in validationErrors" :key="field">{{ errors[0] }}</li>
           </ul>
        </div>

        <div class="mt-10 flex gap-4">
          <button type="submit" :disabled="processing" class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl font-black hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition disabled:opacity-50">
            {{ processing ? 'PROCESANDO REGISTRO...' : 'FINALIZAR INSCRIPCIÓN' }}
          </button>
          <router-link :to="{ name: 'students.index' }" class="px-8 py-4 bg-gray-100 text-gray-500 rounded-2xl font-bold hover:bg-gray-200 transition">Cancelar</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const careers = ref([]);
const processing = ref(false);
const validationErrors = ref({});

const form = ref({
    name: '',
    lastname: '',
    surname: '',
    email: '',
    password: '',
    career_id: '',
    semester: 1,
    saga_code: '',
    id_number: '',
    picture: null,
    idcard_picture: null,
    role: 'student'
});

const photoPreview = ref(null);
const idCardPreview = ref(null);

// Webcam Logic
const showCamera = ref(false);
const activeCameraType = ref(null);
const video = ref(null);
const canvas = ref(null);
const stream = ref(null);

// CARGA DE CARRERAS AL INICIAR
onMounted(async () => {
    try {
        const res = await axios.get('/api/v1/careers');
        careers.value = res.data.data || res.data;
    } catch (e) { 
        console.error("Error cargando carreras desde la API"); 
    }
});

const handleFileUpload = (e, type) => {
    const file = e.target.files[0];
    if (!file) return;
    form.value[type] = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        if (type === 'picture') photoPreview.value = e.target.result;
        else idCardPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const startCamera = async (type) => {
    activeCameraType.value = type;
    showCamera.value = true;
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: type === 'picture' ? "user" : "environment", 
                width: { ideal: 1280 }, height: { ideal: 720 } 
            } 
        });
        video.value.srcObject = stream.value;
    } catch (err) {
        Swal.fire('Cámara', 'No se pudo acceder a la cámara. Revisa los permisos.', 'error');
        showCamera.value = false;
    }
};

const takePhoto = () => {
    const context = canvas.value.getContext('2d');
    canvas.value.width = video.value.videoWidth;
    canvas.value.height = video.value.videoHeight;
    
    if (activeCameraType.value === 'picture') {
        context.translate(canvas.value.width, 0);
        context.scale(-1, 1);
    }
    
    context.drawImage(video.value, 0, 0);
    const dataUrl = canvas.value.toDataURL('image/jpeg', 0.8);

    if (activeCameraType.value === 'picture') photoPreview.value = dataUrl;
    else idCardPreview.value = dataUrl;
    
    canvas.value.toBlob((blob) => {
        form.value[activeCameraType.value] = new File([blob], `${activeCameraType.value}.jpg`, { type: "image/jpeg" });
    }, 'image/jpeg', 0.8);

    stopCamera();
};

const stopCamera = () => {
    if (stream.value) stream.value.getTracks().forEach(t => t.stop());
    showCamera.value = false;
};

const saveStudent = async () => {
    processing.value = true;
    validationErrors.value = {};
    
    const fd = new FormData();
    Object.keys(form.value).forEach(k => {
        if (form.value[k] !== null) fd.append(k, form.value[k]);
    });

    try {
        await axios.post('/api/v1/students', fd);
        Swal.fire({
            title: '¡Inscrito!',
            text: 'Estudiante registrado correctamente.',
            icon: 'success',
            confirmButtonColor: '#4f46e5'
        }).then(() => router.push({ name: 'students.index' }));
    } catch (e) {
        if (e.response?.status === 422) validationErrors.value = e.response.data.errors;
        else Swal.fire('Error', 'No se pudo guardar el registro', 'error');
    } finally { 
        processing.value = false; 
    }
};
</script>

<!-- Elimina este style o usa clases normales -->
<style scoped>
/* El error venía de aquí, eliminamos el @apply */
</style>