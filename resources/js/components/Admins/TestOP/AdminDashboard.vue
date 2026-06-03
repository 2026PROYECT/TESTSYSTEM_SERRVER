<template>
  <div class="max-w-6xl mx-auto bg-white shadow rounded p-6">
    <!-- Header -->
    <div class="mb-6 border-b pb-4">
      <h2 class="text-2xl font-bold text-gray-800">TestOP Admin Dashboard</h2>
      <p class="text-gray-600">Manage assignments and tests</p>
    </div>

    <!-- Create Assignment -->
    <CreateAssignment @created="refreshAssignments" />

    <!-- Assignments Table -->
    <h3 class="text-lg font-semibold text-indigo-700 mt-8 mb-4">Assignments</h3>
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
            <!-- Edit -->
            <EditAssignment
              :assignmentId="assignment.id"
              @updated="refreshAssignments"
            />
            <!-- Delete -->
            <DeleteAssignment
              :assignmentId="assignment.id"
              @deleted="refreshAssignments"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import CreateAssignment from './CreateAssignment.vue'
import EditAssignment from './EditAssignment.vue'
import DeleteAssignment from './DeleteAssignment.vue'

const assignments = ref([])

const refreshAssignments = async () => {
  try {
    const { data } = await axios.get('/api/v1/assignments')
    assignments.value = data
  } catch (error) {
    console.error('Failed to fetch assignments:', error)
  }
}

onMounted(refreshAssignments)
</script>
