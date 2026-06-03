<template>
  <div class="max-w-md mx-auto bg-white shadow rounded p-6">
    <h3 class="text-lg font-semibold text-indigo-700 mb-4">Create Assignment</h3>

    <form @submit.prevent="createAssignment">
      <!-- Student Dropdown -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
        <select v-model="form.user_id" class="w-full border rounded px-3 py-2" required>
          <option disabled value="">Select a student</option>
          <option v-for="student in students" :key="student.id" :value="student.id">
            {{ student.name }}
          </option>
        </select>
      </div>

      <!-- Test Dropdown -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Test</label>
        <select v-model="form.test_id" class="w-full border rounded px-3 py-2" required>
          <option disabled value="">Select a test</option>
          <option v-for="test in tests" :key="test.id" :value="test.id">
            {{ test.name }}
          </option>
        </select>
      </div>

      <!-- Status -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select v-model="form.status" class="w-full border rounded px-3 py-2">
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
        </select>
      </div>

      <!-- Submit -->
      <button
        type="submit"
        class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
      >
        Save Assignment
      </button>
    </form>

    <!-- Feedback -->
    <p v-if="message" class="mt-4 text-green-600">{{ message }}</p>
    <p v-if="error" class="mt-4 text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const form = ref({
  user_id: '',
  test_id: '',
  status: 'pending',
})

const students = ref([])
const tests = ref([])
const message = ref('')
const error = ref('')

// Fetch students and tests on mount
onMounted(async () => {
  try {
    const [usersRes, testsRes] = await Promise.all([
      axios.get('/api/v1/users'),
      axios.get('/api/v1/tests'),
    ])
    students.value = usersRes.data
    tests.value = testsRes.data
  } catch (err) {
    console.error('Failed to fetch dropdown data:', err)
  }
})

const createAssignment = async () => {
  try {
    const { data } = await axios.post('/api/v1/assignments', form.value)
    message.value = `Assignment created for ${data.user.name} - ${data.test.name}`
    error.value = ''
    form.value = { user_id: '', test_id: '', status: 'pending' }
  } catch (err) {
    error.value = 'Failed to create assignment'
    message.value = ''
    console.error(err)
  }
}
</script>


