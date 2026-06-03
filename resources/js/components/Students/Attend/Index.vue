<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8 border border-gray-100 text-center">
            <div class="mb-8">
                <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-black text-gray-900 leading-tight">Exam Portal</h1>
                <p class="text-sm text-gray-500 font-medium uppercase tracking-widest mt-1">{{ user.subject_name }}</p>
            </div>

            <div v-if="loading" class="py-10">
                <div class="animate-spin h-8 w-8 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto"></div>
            </div>

            <div v-else>
                <div v-if="isAuthorized">
                    <div class="bg-green-50 text-green-700 p-4 rounded-2xl mb-8 text-sm font-bold border border-green-100">
                        ✓ You are authorized to begin.
                    </div>
                    <button 
                        @click="startRandomExam"
                        :disabled="starting"
                        class="w-full py-5 bg-indigo-600 text-white rounded-2xl font-black text-lg shadow-lg hover:bg-indigo-700 transition-all active:scale-95 disabled:opacity-50"
                    >
                        {{ starting ? 'Generating Quiz...' : 'Start Random Quiz' }}
                    </button>
                </div>

                <div v-else class="space-y-4">
                    <div class="bg-gray-50 p-6 rounded-3xl border border-dashed border-gray-200">
                        <p class="text-sm text-gray-400 font-bold">Session Locked</p>
                        <p class="text-xs text-gray-400 mt-2 leading-relaxed">Please wait for your instructor to activate your session in the Assignments panel.</p>
                    </div>
                    <button @click="checkStatus" class="text-indigo-600 text-[10px] font-black uppercase tracking-widest hover:underline">
                        Refresh Status
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();

// 1. FIX: Use 'user_data' to match your auth.js
const storedData = localStorage.getItem('user_data');
const user = ref(storedData ? JSON.parse(storedData) : {});

const loading = ref(true);
const starting = ref(false);
const isAuthorized = ref(false);

const checkStatus = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/student/check-status');
        isAuthorized.value = res.data.active;
        
        // Update local user object if backend sent a subject
        if (res.data.subject) {
            user.value.subject_name = res.data.subject;
        }
    } catch (e) {
        // 2. DEBUG: Log the full response to see the Laravel error
        console.error("Status check failed:", e.response?.data || e.message);
        
        if (e.response?.status === 500) {
            Swal.fire('Server Error', 'The backend crashed. Check Laravel logs.', 'error');
        }
    } finally {
        loading.value = false;
    }
};

const startRandomExam = async () => {
    starting.value = true;
    try {
        const res = await axios.post('/api/student/generate-quiz');
        router.push({ name: 'attenquiz.show', params: { id: res.data.quiz_id } });
    } catch (e) {
        Swal.fire('Locked', e.response?.data?.message || 'Contact your Admin.', 'warning');
    } finally {
        starting.value = false;
    }
};

onMounted(checkStatus);
</script>