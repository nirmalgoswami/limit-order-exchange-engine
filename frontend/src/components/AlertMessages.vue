<script setup>
defineProps({
  error: String,
  success: String,
});

defineEmits(['clear-error', 'clear-success']);
</script>

<template>
  <div>
    <div 
      v-if="error" 
      class="text-sm rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-red-200"
    >
      {{ error }}
    </div>
    <div 
      v-if="success" 
      class="text-sm rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-emerald-200"
    >
      {{ success }}
    </div>
  </div>
</template>