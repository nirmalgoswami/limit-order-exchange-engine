<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <!-- Global fade transition -->
    <transition
      name="fade"
      mode="out-in"
      appear
    >
      <RouterView />
    </transition>
  </div>
</template>

<script setup>
// No logic needed here for now.
// Global layout + router view only.
</script>

<style scoped>
/* Smooth page transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.28s ease, transform 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
.fade-enter-to,
.fade-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
