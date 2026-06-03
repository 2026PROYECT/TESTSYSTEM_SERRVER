<template>
  <div>
    <button
      @click="deleteAssignment"
      class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
    >
      Delete
    </button>

    <!-- Feedback -->
    <p v-if="message" class="mt-2 text-green-600">{{ message }}</p>
    <p v-if="error" class="mt-2 text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

// Props: pass assignmentId from parent (e.g., table row)
const props = defineProps({
  assignmentId: {
    type: Number,
    required: true,
  },
})

const message = ref('')
const error = ref('')

const deleteAssignment = async () => {
  if (!confirm('Are you sure you want to delete this assignment?')) return

  try {
    await axios.delete(`/api/v1/assignments/${props.assignmentId}`)
    message.value = 'Assignment deleted successfully'
    error.value = ''
    // Optionally emit event to parent to refresh list
    // emit('deleted', props.assignmentId)
  } catch (err) {
    error.value = 'Failed to delete assignment'
    message.value = ''
    console.error(err)
  }
}
</script>

