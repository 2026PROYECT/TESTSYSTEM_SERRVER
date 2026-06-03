<template>
  <div v-if="active" class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/60 backdrop-blur-sm transition-opacity">
    <div class="flex flex-col items-center">
      <div class="h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
      <p class="mt-4 text-sm font-bold text-indigo-600 uppercase tracking-widest animate-pulse">
        Loading...
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  active: Boolean
});
</script>