<template>
  <div class="min-h-screen bg-gray-50/50 py-10 px-4">
    <div class="max-w-2xl mx-auto">
      <form @submit.prevent="saveAdmin" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
        <div class="mb-8">
          <h2 class="text-2xl font-black text-gray-900">New Administrator</h2>
          <p class="text-sm text-gray-500 font-medium">Create a user with full system access</p>
        </div>

        <div class="mb-6 flex flex-col items-center">
            <div v-if="photoPreview" class="mb-4 relative group">
                <img :src="photoPreview" class="w-28 h-28 object-cover rounded-full border-4 border-indigo-100 shadow-sm" />
                <button type="button" @click="photoPreview = null; form.picture = null; fileName = ''" 
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div v-else class="w-28 h-28 rounded-full bg-gray-100 flex items-center justify-center border-4 border-gray-200 mb-4">
                <i class="fas fa-user text-3xl text-gray-400"></i>
            </div>
            
            <div class="flex gap-3">
                <button type="button" @click="startCamera" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold flex items-center gap-2 hover:bg-indigo-700 transition">
                    <i class="fas fa-camera"></i> Tomar Foto
                </button>
                <label class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold flex items-center gap-2 cursor-pointer hover:bg-gray-200 transition">
                    <i class="fas fa-upload"></i> Subir Archivo
                    <input type="file" class="hidden" @change="handleFileUpload" accept="image/*" />
                </label>
            </div>
            <p v-if="fileName" class="text-xs text-gray-400 mt-2">{{ fileName }}</p>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase mb-2 ml-1">First Name</label>
              <input v-model="form.name" type="text" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold" required />
            </div>
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase mb-2 ml-1">Last Name</label>
              <input v-model="form.lastname" type="text" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold" required />
            </div>
          </div>
          
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase mb-2 ml-1">Email Address</label>
            <input v-model="form.email" type="email" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold" required />
          </div>
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase mb-2 ml-1">User Type</label>
            <select v-model="form.role" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold appearance-none">
              <option value="admin">Administrator</option>
              <option value="teacher">Teacher</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase mb-2 ml-1">Temporary Password</label>
            <input v-model="form.password" type="password" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold" required />
          </div>
        </div>

        <div v-if="Object.keys(validationErrors).length > 0" class="mt-4 p-4 bg-red-50 rounded-2xl text-red-600 text-sm">
          <ul class="list-disc list-inside font-bold">
            <li v-for="(errors, field) in validationErrors" :key="field">{{ errors[0] }}</li>
          </ul>
        </div>

        <div class="mt-8 flex gap-3">
          <button type="submit" :disabled="processing" class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl font-bold hover:bg-indigo-700 transition disabled:opacity-50 shadow-lg shadow-indigo-100">
            {{ processing ? 'Processing...' : 'Create Account' }}
          </button>
          <router-link :to="{ name: 'admins.index' }" class="px-6 py-4 bg-gray-100 text-gray-500 rounded-2xl font-bold hover:bg-gray-200 transition">
            Cancel
          </router-link>
        </div>
      </form>
    </div>

    <!-- Modal de Cámara -->
    <div v-if="showCamera" class="fixed inset-0 z-[100] bg-black/95 flex flex-col items-center justify-center p-6">
      <div class="relative w-full max-w-md bg-black rounded-3xl overflow-hidden shadow-2xl border border-white/10">
        <video ref="video" autoplay playsinline class="w-full h-auto transform scale-x-[-1]"></video>
        
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
          <div class="w-48 h-48 border-2 border-dashed border-white/50 rounded-full shadow-[0_0_0_1000px_rgba(0,0,0,0.6)]"></div>
        </div>

        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black to-transparent p-6 flex items-center justify-between">
          <button type="button" @click="stopCamera" class="text-white bg-white/10 px-6 py-2 rounded-full font-bold backdrop-blur-md hover:bg-white/20">
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
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const processing = ref(false);
const validationErrors = ref({});
const photoPreview = ref(null);
const fileName = ref('');

// Webcam Logic
const showCamera = ref(false);
const video = ref(null);
const canvas = ref(null);
const stream = ref(null);

const form = reactive({
    name: '',
    lastname: '',
    email: '',
    password: '',
    picture: null,
    role: 'admin'
});

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.picture = file;
        fileName.value = file.name;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const startCamera = async () => {
    showCamera.value = true;
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 720 } } 
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
    
    // Espejo para selfie
    context.translate(canvas.value.width, 0);
    context.scale(-1, 1);
    context.drawImage(video.value, 0, 0);
    
    const dataUrl = canvas.value.toDataURL('image/jpeg', 0.8);
    photoPreview.value = dataUrl;
    
    // Convertir dataURL a File
    fetch(dataUrl)
        .then(res => res.blob())
        .then(blob => {
            const file = new File([blob], "camera_photo.jpg", { type: "image/jpeg" });
            form.picture = file;
            fileName.value = "camera_photo.jpg";
        });
    
    stopCamera();
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
    showCamera.value = false;
};

const saveAdmin = async () => {
    if (form.password.length < 8) {
        validationErrors.value = { password: ['Password must be at least 8 characters long.'] };
        return;
    }
    processing.value = true;
    validationErrors.value = {};

    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('lastname', form.lastname);
    formData.append('email', form.email);
    formData.append('password', form.password);
    formData.append('role', form.role);
    
    if (form.picture) {
        formData.append('picture', form.picture);
    }

    try {
        await axios.post('/api/v1/admins', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        Swal.fire({
            icon: 'success',
            title: 'Administrator Created',
            text: 'The account has been successfully generated.',
            timer: 2000,
            showConfirmButton: false
        });
        
        router.push({ name: 'admins.index' });
    } catch (e) {
        if (e.response?.status === 422) {
            validationErrors.value = e.response.data.errors;
        } else {
            Swal.fire('Error', 'An unexpected error occurred.', 'error');
        }
    } finally {
        processing.value = false;
    }
};
</script>