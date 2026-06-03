<template>
  <div class="max-w-4xl mx-auto bg-white shadow rounded p-6">
    <!-- Test Header -->
    <div class="mb-6 border-b pb-4">
      <h2 class="text-2xl font-bold text-gray-800">{{ test?.name }}</h2>
      <p class="text-gray-600">{{ test?.description }}</p>
    </div>

    <!-- Sections & Questions -->
    <div v-if="sections.length">
      <div v-for="section in sections" :key="section.id" class="mb-8">
        <h3 class="text-lg font-semibold text-indigo-700 mb-4">{{ section.title }}</h3>
        <ul class="space-y-6">
          <li v-for="question in section.questions" :key="question.id" class="border p-4 rounded">
            <p class="font-medium text-gray-900 mb-2">{{ question.prompt }}</p>
            <textarea
              v-model="answers[question.id]"
              class="w-full border rounded p-2 text-sm focus:ring focus:ring-indigo-300"
              placeholder="Type your answer here..."
            ></textarea>
            <div class="mt-2 flex justify-end">
              <button
                @click="submitResponse(question.id)"
                class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition"
              >
                Save Response
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else class="text-center text-gray-500">
      Loading questions...
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Props or route param for assignmentId
const assignmentId = 1 // Replace with dynamic value (prop or route param)

const test = ref(null)
const sections = ref([])
const answers = ref({})

onMounted(async () => {
  // Fetch assignment questions
  const { data } = await axios.get(`/api/v1/assignments/${assignmentId}/questions`)
  sections.value = data
})

async function submitResponse(questionId) {
  await axios.post('/api/v1/responses', {
    assignment_id: assignmentId,
    question_id: questionId,
    response_text: answers.value[questionId] || ''
  })
  alert('Response saved!')
}
</script>

