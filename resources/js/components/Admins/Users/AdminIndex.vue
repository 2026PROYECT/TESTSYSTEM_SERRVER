<template>
  <div class="min-h-screen bg-gray-50/50 py-10 px-4">
    <div class="max-w-6xl mx-auto">
      
      <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
          <h1 class="text-3xl font-black text-gray-900 tracking-tight">System Staff</h1>
          <p class="text-gray-500 font-medium">Manage access for administrators and teachers</p>
        </div>
        <router-link 
          :to="{ name: 'admins.create' }" 
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 transition-all active:scale-95 flex items-center gap-2"
        >
          <i class="fas fa-plus text-sm"></i>
          + New Member
        </router-link>
      </div>

      <div class="mb-6 relative">
        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
          <i class="fas fa-search"></i>
        </span>
        <input 
          v-model="searchQuery" 
          @input="debounceSearch"
          type="text" 
          placeholder="Search by name, last name or email..." 
          class="w-full md:w-96 border-none bg-white shadow-sm rounded-2xl pl-12 p-4 focus:ring-2 focus:ring-indigo-400 font-medium transition-all"
        />
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-left table-auto">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100">
              <th class="p-5 text-xs font-black text-gray-400 uppercase tracking-widest">User</th>
              <th class="p-5 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Role</th>
              <th class="p-5 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="admin in admins.data" :key="admin.id" class="hover:bg-blue-50/30 transition-colors group">
              <td class="p-5">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl overflow-hidden shadow-sm border-2 border-white">
                    <img v-if="admin.picture" :src="'/storage/' + admin.picture" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full bg-indigo-600 flex items-center justify-center text-white font-black">
                      {{ admin.name?.charAt(0) }}
                    </div>
                  </div>
                  <div>
                    <p class="font-bold text-gray-900 text-sm">{{ admin.name }} {{ admin.lastname }}</p>
                    <p class="text-[11px] text-gray-500 font-medium">{{ admin.email }}</p>
                  </div>
                </div>
              </td>

              <td class="p-5 text-center">
                <span :class="admin.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                      class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase">
                  {{ admin.role === 'admin' ? 'Administrator' : 'Teacher' }}
                </span>
              </td>

              <td class="p-5">
                <div class="flex justify-end gap-3">
                  <router-link :to="{ name: 'admins.edit', params: { id: admin.id } }" 
                               class="flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition shadow-md shadow-blue-100">
                       <i class="fas fa-edit text-xs"></i>
                       <span class="text-[10px] font-black uppercase">Edit</span>
                  </router-link>
                  
                  <button @click="deleteAdmin(admin.id)" 
                          class="flex items-center gap-2 px-3 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition shadow-md shadow-red-100">
                      <i class="fas fa-trash text-xs"></i>
                      <span class="text-[10px] font-black uppercase">Delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="admins.links?.length > 3" class="p-5 bg-gray-50/50 border-t border-gray-100 flex justify-center gap-2">
        <button v-for="link in admins.links" 
                :key="link.label"
                @click="fetchAdmins(link.url ? link.url.split('page=')[1] : 1)"
                :disabled="!link.url || link.active"
                class="px-4 py-2 rounded-xl text-xs font-black transition-all"
                :class="link.active ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-400 hover:bg-gray-100'"
                v-html="link.label">
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const admins = ref({ data: [] });
const searchQuery = ref('');
let debounceTimer = null;

const fetchAdmins = async (page = 1) => {
    try {
        const response = await axios.get('/api/v1/admins', {
            params: { 
                search: searchQuery.value,
                page: page 
            }
        });
        admins.value = response.data;
    } catch (error) {
        console.error("Error fetching admins:", error);
    }
};

const selectedImage = ref(null);

const openImage = (path) => {
    selectedImage.value = '/storage/' + path;
};

const closeImage = () => {
    selectedImage.value = null;
}; 

const debounceSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchAdmins();
    }, 500);
};

const deleteAdmin = async (id) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "This administrator will lose all access to the system!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/v1/admins/${id}`);
            Swal.fire('Deleted!', 'The administrator has been removed.', 'success');
            fetchAdmins();
        } catch (error) {
            Swal.fire('Error', 'The user could not be deleted.', 'error');
        }
    }
};

onMounted(() => {
    fetchAdmins();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>