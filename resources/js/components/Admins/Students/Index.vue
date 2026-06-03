<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { TailwindPagination } from 'laravel-vue-pagination';
import * as XLSX from 'xlsx';

// --- ESTADOS ---
const students = ref({ data: [] });
const loading = ref(true);
const search = ref('');
const importing = ref(false);
const uploadPercentage = ref(0);
const selectedImage = ref(null); // Única declaración de selectedImage

// --- CARGA DE DATOS ---
const getStudents = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/v1/students?page=${page}&search=${search.value}`);
        students.value = response.data;
    } catch (e) {
        console.error("Error al cargar estudiantes");
    } finally {
        loading.value = false;
    }
};

// --- FUNCIONES DE IMAGEN (Declaradas una sola vez) ---
const openImage = (path) => {
    selectedImage.value = '/storage/' + path;
};

const closeImage = () => {
    selectedImage.value = null;
};

// --- OTRAS ACCIONES ---
const downloadReport = async () => {
    try {
        const response = await axios.get('/api/v1/students/export-pdf', { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'Reporte_Estudiantes.pdf');
        document.body.appendChild(link);
        link.click();
    } catch (e) {
        Swal.fire('Error', 'No se pudo generar el reporte', 'error');
    }
};

const handleImport = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    importing.value = true;

    try {
        await axios.post('/api/v1/students/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (p) => uploadPercentage.value = Math.round((p.loaded * 100) / p.total)
        });

        Swal.fire('Éxito', 'Importación completada con éxito', 'success');
        getStudents();
   } catch (e) {
    console.error("Detalle técnico:", e.response.data); // Esto imprimirá el objeto que viste
    
    let errorMessages = '';
    if (e.response && e.response.status === 422) {
        const errors = e.response.data.errors;
        // Recorremos los 3 errores que detectó Laravel
        errorMessages = Object.values(errors).flat().join('<br> • ');
    }

    Swal.fire({
        icon: 'error',
        title: 'Errores encontrados en el Excel',
        html: `<div style="text-align: left; font-size: 0.9em;"> • ${errorMessages}</div>`,
        confirmButtonText: 'Entendido'
    });
}
};
const downloadTemplate = () => {
    const wb = XLSX.utils.book_new();
    
    // Los encabezados deben ser MINÚSCULAS y sin espacios, igual que el archivo exitoso
    const headers = [[
        'name', 
        'lastname', 
        'surname', 
        'email', 
        'id_number', 
        'saga_code', 
        'career_id', 
        'semester'
    ]];
    
    // Datos de ejemplo basados en tu archivo Codificado(3)
    const exampleData = [
        ['Alejandro', 'Vargas', 'Rojas', 'a.vargas@emi.edu.bo', '10203041', 'S-2601', 1, 1]
    ];
    
    const ws1 = XLSX.utils.aoa_to_sheet([...headers, ...exampleData]);
    XLSX.utils.book_append_sheet(wb, ws1, "Importar_Estudiantes");

    XLSX.writeFile(wb, "EmiSystem_Plantilla_Oficial.xlsx");
};
const confirmDelete = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar estudiante?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar'
    });
    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/v1/students/${id}`);
            getStudents();
            Swal.fire('Eliminado', '', 'success');
        } catch (e) {
            Swal.fire('Error', 'No se pudo eliminar', 'error');
        }
    }
};

onMounted(() => getStudents());
watch(search, () => getStudents());
</script>
<template>
    <div class="index-page-container">
        <div class="min-h-screen bg-gray-50/50 py-10 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Student Directory</h1>
                        <p class="text-gray-500 mt-1 font-medium">Manage student enrollments and career details.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <input type="file" ref="fileInput" class="hidden" @change="handleImport" />
                        
                        <button @click="downloadTemplate"
                            class="flex items-center gap-2 bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl font-bold hover:bg-indigo-100 transition border border-indigo-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Plantilla
                        </button>

                        <button @click="$refs.fileInput.click()" class="bg-emerald-50 text-emerald-600 px-4 py-2 rounded-xl font-bold hover:bg-emerald-100 transition border border-emerald-100">
                            Import Excel
                        </button>

                        <button @click="downloadReport" class="bg-red-50 text-red-600 px-4 py-2 rounded-xl font-bold hover:bg-red-100 transition border border-red-100">
                            Export PDF
                        </button>

                        <router-link :to="{ name: 'students.create' }" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-2xl shadow-lg hover:bg-indigo-700 transition-all text-center">
                            Add Student
                        </router-link>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 mb-6">
                    <input v-model="search" type="text" placeholder="Search by name, email or career..."
                        class="block w-full pl-6 pr-4 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-400 transition" />
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-gray-50/50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Full Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Contact</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">ID Card / Document</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="user in students.data" :key="user.id" class="hover:bg-indigo-50/30 transition">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center space-x-4">
                                            <div class="h-12 w-12 flex-shrink-0 cursor-zoom-in" @click="user.picture ? openImage(user.picture) : null">
                                                <img v-if="user.picture" :src="'/storage/' + user.picture" class="h-12 w-12 rounded-full object-cover border-2 border-white shadow-sm" />
                                                <div v-else class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-indigo-600 font-black text-xs uppercase">{{ user.name[0] }}{{ user.lastname[0] }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-800 uppercase text-sm">
                                                    {{ user.name }} {{ user.lastname }} {{ user.surname || '' }}
                                                </div>
                                                <div class="text-[10px] text-gray-400 font-black uppercase tracking-wider">
                                                    SAGA: {{ user.student?.saga_code || 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-sm text-gray-600">
                                        {{ user.email }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="text-sm font-black text-gray-700 tracking-tighter">
                                                {{ user.student?.id_number || 'S/N' }}
                                            </span>
                                            <div v-if="user.student?.idcard_picture" 
                                                 class="cursor-zoom-in group relative" 
                                                 @click="openImage(user.student.idcard_picture)">
                                                <img :src="'/storage/' + user.student.idcard_picture" 
                                                     class="h-8 w-14 rounded-md object-cover border border-gray-200 shadow-sm group-hover:border-indigo-400 transition-all" />
                                            </div>
                                            <span v-else class="text-[9px] text-gray-300 font-bold uppercase italic">Sin Foto</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-right space-x-2 whitespace-nowrap">
                                        <router-link :to="{ name: 'students.edit', params: { id: user.id } }" 
                                            class="text-indigo-600 font-bold hover:underline">
                                            Edit
                                        </router-link>
                                        <button @click="confirmDelete(user.id)" 
                                            class="text-red-600 font-bold hover:underline">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="students.data.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">
                                        No students found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex justify-center">
                    <TailwindPagination :data="students" @pagination-change-page="getStudents" />
                </div>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="selectedImage" @click="closeImage" 
                 class="fixed inset-0 z-[9999] bg-black/85 backdrop-blur-sm flex items-center justify-center p-6 cursor-zoom-out">
                <img :src="selectedImage" class="max-h-[85vh] max-w-[90vw] rounded-3xl border-4 border-white shadow-2xl animate-zoom">
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.cursor-zoom-in { cursor: zoom-in; }
.cursor-zoom-out { cursor: zoom-out; }
.animate-zoom { animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>