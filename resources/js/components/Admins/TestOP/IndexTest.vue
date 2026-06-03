<template>
  <div class="max-w-5xl mx-auto bg-white shadow rounded p-6">
    <!-- Header -->
    <div class="mb-6 border-b pb-4">
      <h2 class="text-2xl font-bold text-gray-800">TestOP Dashboard</h2>
      <p class="text-gray-600">Manage assignments and tests</p>
    </div>

    <!-- Assignments Table -->
    <h3 class="text-lg font-semibold text-indigo-700 mb-4">Assignments</h3>
    <table class="min-w-full border rounded">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Student</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Test</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Status</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="assignment in assignments" :key="assignment.id" class="border-t">
          <td class="px-4 py-2">{{ assignment.user?.name || 'Unknown' }}</td>
          <td class="px-4 py-2">{{ assignment.test?.name || 'Untitled Test' }}</td>
          <td class="px-4 py-2 capitalize">{{ assignment.status || 'N/A' }}</td>
          <td class="px-4 py-2 flex gap-2">
            <!-- Edit Button -->
            <button
              @click="openEditModal(assignment.id)"
              class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700"
            >
              Edit
            </button>
            <!-- Delete Button -->
            <button
              @click="deleteAssignment(assignment.id)"
              class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
            >
              Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Error Feedback -->
    <p v-if="error" class="mt-4 text-red-600">{{ error }}</p>

    <!-- Edit Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
    >
      <div class="bg-white rounded shadow-lg p-6 w-96">
        <h3 class="text-lg font-semibold mb-4">Edit Assignment</h3>
        <EditAssignment
          :assignmentId="selectedAssignmentId"
          @updated="handleUpdated"
        />
        <button
          @click="closeEditModal"
          class="mt-4 bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import EditAssignment from './EditAssignment.vue'

const assignments = ref([])
const error = ref('')
const showEditModal = ref(false)
const selectedAssignmentId = ref(null)

// Fetch assignments
const fetchAssignments = async () => {
  try {
    const { data } = await axios.get('/api/v1/assignments')
    assignments.value = data
    error.value = ''
  } catch (err) {
    console.error('Failed to fetch assignments:', err)
    error.value = 'Failed to load assignments'
  }
}

// Delete assignment
const deleteAssignment = async (id) => {
  if (!confirm('Are you sure you want to delete this assignment?')) return
  try {
    await axios.delete(`/api/v1/assignments/${id}`)
    await fetchAssignments()
  } catch (err) {
    console.error('Failed to delete assignment:', err)
    error.value = 'Failed to delete assignment'
  }
}

// Modal controls
const openEditModal = (id) => {
  selectedAssignmentId.value = id
  showEditModal.value = true
}
const closeEditModal = () => {
  showEditModal.value = false
  selectedAssignmentId.value = null
}
const handleUpdated = () => {
  fetchAssignments()
  closeEditModal()
}

onMounted(fetchAssignments)
</script>
