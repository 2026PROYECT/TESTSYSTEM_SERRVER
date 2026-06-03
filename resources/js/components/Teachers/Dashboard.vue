<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useRouter } from 'vue-router';

const router = useRouter();

// --- ESTADOS ---
const loading = ref(true);
const exams = ref([]);
const stats = ref({
    pending_exams: 0,
    completed_today: 0,
    total_students: 0
});

const currentTime = ref(new Date());
let timer = null;

// --- LÓGICA DE FILTRADO Y CARGA ---
const getDashboardData = async () => {
    loading.value = true;
    try {
        const activeLangId = Number(localStorage.getItem('active_lang_id') || 1);

        const response = await axios.get('/api/v1/teacher/dashboard', {
            headers: { 'X-Language-Id': activeLangId }
        });

        if (response.data) {
            // 1. MAPEADO DE ESTADÍSTICAS (Sin sobreescribir después)
            if (response.data.stats) {
                stats.value = {
                    pending_exams: response.data.stats.find(s => s.label.includes('Pendientes'))?.value || 0,
                    completed_today: response.data.stats.find(s => s.label.includes('Completados'))?.value || 0,
                    total_students: response.data.stats.find(s => s.label.includes('Total'))?.value || 0
                };
            }

            // 2. FILTRADO DE EXÁMENES
            if (response.data.exams) {
                exams.value = response.data.exams.filter(e => {
                    const isLangMatch = Number(e.language_id) === activeLangId;
                    // IMPORTANTE: Solo mostramos los que siguen activos (active === 1)
                    // Esto garantiza que al marcar inasistencia (active=0), el alumno DESAPAREZCA.
                    const isActive = Number(e.active) === 1; 

                    return isLangMatch && isActive;
                });

                if (exams.value.length === 0 && response.data.exams.length > 0) {
                    console.warn("Filtro: No hay exámenes activos para el idioma ID:", activeLangId);
                }
            }
        }
    } catch (error) {
        console.error("Error al obtener datos:", error);
    } finally {
        loading.value = false;
    }
};
// --- LÓGICA DE TIEMPO (Cambiado a start_at) ---
const getExamStatus = (scheduledTime) => {
    if (!scheduledTime) return 'restringido';
    
    const now = currentTime.value;
    const examDate = new Date(scheduledTime.replace(' ', 'T'));
    const diffInMinutes = (examDate - now) / (1000 * 60);

    if (diffInMinutes > 15) return 'restringido'; 
    if (Math.abs(diffInMinutes) <= 15) return 'evaluar'; 
    return 'ausente'; 
};

// --- ACCIONES ---
const openEvaluation = (assignmentId) => {
    router.push({ name: 'teacher.evaluate', params: { id: assignmentId } });
};

const markAsAbsent = async (exam) => {
    const { isConfirmed } = await Swal.fire({
        title: '¿Registrar Inasistencia?',
        text: `Se marcará a ${exam.name} con puntaje 0.00.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5', // Color índigo de tu tema
        confirmButtonText: 'Sí, marcar ausente',
    });

    if (isConfirmed) {
        try {
            // Usamos el assignment_id que ya tienes en el objeto exam
            await axios.post(`/api/v1/teacher/mark-absent/${exam.assignment_id}`, {
                student_id: exam.student_id,
                final_level: '0',
                final_score: 0,
                detailed_scores: { "status": "no se presento" } // Enviamos un objeto para evitar errores de JSON
            });

            await Swal.fire({ 
                icon: 'success', 
                title: 'Alumno retirado', 
                timer: 1500, 
                showConfirmButton: false 
            });

            // Esto es VITAL: vuelve a pedir los datos al servidor
            await getDashboardData(); 
            
        } catch (error) {
            console.error("Error al marcar inasistencia:", error);
            Swal.fire('Error', 'No se pudo procesar el retiro.', 'error');
        }
    }
};
// --- MANEJO DE EVENTOS ---
onMounted(() => {
    getDashboardData();
    
    // 1. Escuchar si Authenticated.vue cambia el idioma
    window.addEventListener('lang-changed', getDashboardData);
    
    // 2. Reloj
    timer = setInterval(() => { currentTime.value = new Date(); }, 1000);
});

onUnmounted(() => { 
    if (timer) clearInterval(timer); 
    // Limpiar el escuchador para evitar fugas de memoria
    window.removeEventListener('lang-changed', getDashboardData);
});

const showImage = (url, name) => { /* ... tu lógica de SweetAlert para imagen ... */ };
</script>

<template>
    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-indigo-600 p-6 rounded-[2rem] text-white shadow-xl flex justify-between items-center">
                <div>
                    <p class="text-[10px] font-black uppercase opacity-70 tracking-tighter">Exámenes Orales</p>
                    <h3 class="text-3xl font-black">{{ stats.pending_exams }}</h3>
                </div>
                <div class="text-4xl opacity-30">🎤</div>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex justify-between items-center">
                <div>
                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">Completados Hoy</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ stats.completed_today }}</h3>
                </div>
                <div class="text-4xl">✅</div>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex justify-between items-center">
                <div>
                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">Estudiantes Únicos</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ stats.total_students }}</h3>
                </div>
                <div class="text-4xl">👥</div>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                <div>
                    <h2 class="text-xl font-black text-gray-900">Alumnos en Espera</h2>
                    <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mt-1">
                        Reloj del Sistema: {{ currentTime.toLocaleTimeString() }}
                    </p>
                </div>
                <button @click="getDashboardData" class="text-gray-400 hover:text-indigo-600 transition-colors">
                    <i class="fas fa-sync-alt" :class="{'animate-spin': loading}"></i>
                </button>
            </div>

            <div v-if="loading" class="p-20 text-center text-gray-400 font-bold uppercase text-xs tracking-widest">
                Sincronizando asignaciones...
            </div>

            <div v-else-if="exams.length > 0" class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <th class="px-8 py-4">Estudiante</th>
                            <th class="px-8 py-4 text-center">Horario Programado</th>
                            <th class="px-8 py-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="exam in exams" :key="exam.assignment_id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div @click="showImage(exam.picture ? '/storage/' + exam.picture : '/images/default-avatar.png', `${exam.name} ${exam.lastname}`)"
                                        class="w-12 h-12 rounded-2xl bg-gray-100 overflow-hidden border-2 border-white shadow-sm cursor-zoom-in hover:ring-4 hover:ring-indigo-100 transition-all">
                                        <img :src="exam.picture ? '/storage/' + exam.picture : '/images/default-avatar.png'" class="w-full h-full object-cover">
                                    </div>
                                    <p class="font-black text-gray-900 leading-none">{{ exam.name }} {{ exam.lastname }}</p>
                                </div>
                            </td>
                            
                            <td class="px-8 py-5 text-center">
    <div class="inline-flex flex-col items-center">
        <span class="text-[11px] font-bold text-gray-500 flex items-center gap-1 mb-1">
            <i class="far fa-calendar-alt text-[10px]"></i>
            {{ new Date(exam.start_at.replace(' ', 'T')).toLocaleDateString() }}
        </span>
        <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-[10px] font-black border border-indigo-100 shadow-sm">
            {{ new Date(exam.start_at.replace(' ', 'T')).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
        </span>
    </div>
</td>

                            <td class="px-8 py-5 text-right">
                                <button v-if="getExamStatus(exam.start_at) === 'evaluar'"
                                    @click="openEvaluation(exam.assignment_id)" 
                                    class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg shadow-gray-200">
                                    Evaluar Alumno
                                </button>

                                <button v-else-if="getExamStatus(exam.start_at) === 'ausente'"
                                    @click="markAsAbsent(exam)"
                                    class="bg-red-50 text-red-600 border border-red-100 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all">
                                    Marcar Inasistencia (0)
                                </button>

                                <div v-else class="flex flex-col items-end">
                                    <span class="text-[9px] font-black text-gray-400 uppercase bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                        Acceso Restringido
                                    </span>
                                    <span class="text-[8px] text-gray-400 mt-1 italic tracking-tighter">Habilitado ±15 min del horario</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="p-20 text-center">
                <p class="text-gray-400 font-bold italic text-sm">No hay exámenes programados para este momento.</p>
            </div>
        </div>
    </div>
</template>