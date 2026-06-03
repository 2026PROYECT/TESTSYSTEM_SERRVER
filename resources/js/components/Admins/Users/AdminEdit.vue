<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const processing = ref(false);
const loading = ref(true);
const validationErrors = ref({});
const photoPreview = ref(null);
const currentPicture = ref(null);

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
    role: '', 
    picture: null
});

const fetchAdminData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/v1/admins/${route.params.id}`);
        const admin = response.data;
        
        form.name = admin.name;
        form.lastname = admin.lastname;
        form.email = admin.email;
        form.role = admin.role; 
        currentPicture.value = admin.picture;
    } catch (error) {
        console.error("Error fetching data:", error);
        Swal.fire('Error', 'User not found or server not responding.', 'error');
    } finally {
        loading.value = false;
    }
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.picture = file;
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
        });
    
    stopCamera();
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
    showCamera.value = false;
};

const updateAdmin = async () => {
    processing.value = true;
    validationErrors.value = {};

    const data = new FormData();
    data.append('_method', 'PUT');
    
    data.append('name', form.name);
    data.append('lastname', form.lastname);
    data.append('email', form.email);
    data.append('role', form.role); 
    
    if (form.password) {
        data.append('password', form.password);
    }
    
    if (form.picture) {
        data.append('picture', form.picture);
    }

    try {
        await axios.post(`/api/v1/admins/${route.params.id}`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        Swal.fire({
            title: 'Updated!',
            text: 'The profile has been successfully modified.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        
        router.push({ name: 'admins.index' });
    } catch (e) {
        if (e.response && e.response.status === 422) {
            validationErrors.value = e.response.data.errors;
        } else {
            Swal.fire('Error', 'The user could not be updated.', 'error');
        }
    } finally {
        processing.value = false;
    }
};

onMounted(fetchAdminData);
</script>

<template>
  <div class="min-h-screen bg-gray-50/50 py-10 px-4">
    <div class="max-w-2xl mx-auto">
      
      <div v-if="loading" class="text-center py-20">
        <div class="animate-spin inline-block w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full mb-4"></div>
        <p class="text-gray-400 font-black uppercase tracking-widest text-xs">Loading Staff Data...</p>
      </div>

      <form v-else @submit.prevent="updateAdmin" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
        <div class="mb-8 flex justify-between items-center">
          <div>
            <h2 class="text-2xl font-black text-gray-900 leading-tight">Edit Member</h2>
            <p class="text-sm text-gray-500 font-medium">Updating access information</p>
          </div>
          <span :class="form.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'" 
                class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest ring-1 ring-inset ring-black/5">
            {{ form.role === 'admin' ? 'Administrator' : 'Teacher' }}
          </span>
        </div>
        
        <div class="space-y-5">
          <div class="flex flex-col items-center p-6 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
              <div class="mb-4 relative group">
                <img v-if="photoPreview || currentPicture" 
                     :src="photoPreview || (currentPicture ? '/storage/' + currentPicture : null)"
                     class="w-28 h-28 rounded-3xl object-cover border-4 border-white shadow-lg transition-transform group-hover:scale-105" />
                <div v-else class="w-28 h-28 rounded-3xl bg-indigo-600 flex items-center justify-center text-white font-black text-3xl shadow-lg">
                    {{ form.name?.charAt(0) }}
                </div>
                <button v-if="photoPreview || currentPicture" type="button" @click="photoPreview = null; form.picture = null; currentPicture = null" 
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition">
                    <i class="fas fa-times"></i>
                </button>
              </div>
              
              <div class="flex gap-3">
                <button type="button" @click="startCamera" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-indigo-700 transition">
                    <i class="fas fa-camera"></i> Tomar Foto
                </button>
                <label class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 cursor-pointer hover:bg-gray-300 transition">
                    <i class="fas fa-upload"></i> Subir Archivo
                    <input type="file" class="hidden" @change="handleFileUpload" accept="image/*" />
                </label>
              </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">First Name</label>
              <input v-model="form.name" type="text" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold text-gray-700" required />
            </div>
            <div>
              <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Last Name</label>
              <input v-model="form.lastname" type="text" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold text-gray-700" required />
            </div>
          </div>
          
          <div>
            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Email Address</label>
            <input v-model="form.email" type="email" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold text-gray-700" required />
          </div>

          <div>
            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Assign Role</label>
            <select v-model="form.role" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold text-gray-700 appearance-none" required>
                <option value="admin">Administrator</option>
                <option value="teacher">Teacher</option>
            </select>
          </div>
          
          <div>
            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">New Password (Optional)</label>
            <input v-model="form.password" type="password" placeholder="Leave blank to keep current" class="w-full border-none bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold text-gray-700" />
          </div>
        </div>

        <div v-if="Object.keys(validationErrors).length > 0" class="mt-6 p-4 bg-red-50 rounded-2xl border border-red-100 text-red-600 text-xs">
          <ul class="space-y-1 font-bold uppercase tracking-tight">
            <li v-for="(errors, field) in validationErrors" :key="field">
              <i class="fas fa-exclamation-circle mr-1"></i> {{ errors[0] }}
            </li>
          </ul>
        </div>

        <div class="mt-8 flex flex-col md:flex-row gap-3">
          <button type="submit" :disabled="processing" class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 shadow-lg shadow-indigo-100">
            {{ processing ? 'Saving Changes...' : 'Update Profile' }}
          </button>
          <router-link :to="{ name: 'admins.index' }" class="px-8 py-4 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-gray-200 transition text-center">
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