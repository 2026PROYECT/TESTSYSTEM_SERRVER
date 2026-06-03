<template>
  <div class="max-w-md mx-auto bg-white shadow rounded p-6">
    <h3 class="text-lg font-semibold text-indigo-700 mb-4">Edit Assignment</h3>

    <form @submit.prevent="updateAssignment">
      <!-- Student Dropdown -->
<div class="mb-4">
  <label
    for="student-select"
    class="block text-sm font-medium text-gray-700 mb-1"
  >
    Student
  </label>
<select
  id="student-select"
  name="student_id"
  v-model="form.user_id"
  class="w-full border rounded px-3 py-2"
  required
>
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
          <option
            v-for="test in tests"
            :key="test.id"
            :value="test.id"
            :class="{ 'bg-indigo-100 text-indigo-700 font-semibold': form.test_id === test.id }"
          >
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
        Update Assignment
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

// Props: pass assignmentId from parent (e.g., table row)
const props = defineProps({
  assignmentId: {
    type: Number,
    required: true,
  },
})

// Emit event back to parent
const emit = defineEmits(['updated'])

const form = ref({
  user_id: '',
  test_id: '',
  status: 'pending',
})

const students = ref([])
const tests = ref([])
const message = ref('')
const error = ref('')

onMounted(async () => {
  try {
    const [studentsRes, testsRes, assignmentRes] = await Promise.all([
  axios.get('/api/v1/students'), // ✅ only students
  axios.get('/api/v1/tests'),
  axios.get(`/api/v1/assignments/${props.assignmentId}`),
])

students.value = studentsRes.data
tests.value = testsRes.data

form.value.user_id = assignmentRes.data.user?.id || ''
form.value.test_id = assignmentRes.data.test?.id || ''
form.value.status = assignmentRes.data.status || 'pending'


  } catch (err) {
    console.error('Failed to fetch assignment data:', err)
  }
})

const updateAssignment = async () => {
  try {
    const { data } = await axios.put(`/api/v1/assignments/${props.assignmentId}`, form.value)
    message.value = `Assignment updated: ${data.user.name} - ${data.test.name} (${data.status})`
    error.value = ''
    emit('updated', props.assignmentId) // ✅ notify parent to refresh
  } catch (err) {
    error.value = 'Failed to update assignment'
    message.value = ''
    console.error(err)
  }
}
</script>

