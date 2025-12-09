<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <h1 class="text-lg font-semibold">Limit Order Exchange</h1>

        <div class="flex items-center gap-3">
          <span v-if="auth.isAuthenticated" class="text-sm text-slate-300">
            {{ auth.user?.name }}
          </span>

          <button
            v-if="auth.isAuthenticated"
            @click="handleLogout"
            class="rounded-md bg-slate-800 px-3 py-1.5 text-sm font-medium text-slate-100 hover:bg-slate-700"
          >
            Logout
          </button>

          <router-link
            v-else
            to="/login"
            class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500"
          >
            Login
          </router-link>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuth } from './composables/useAuth';

const auth = useAuth();
const router = useRouter();

const handleLogout = async () => {
  await auth.logout();
  router.push({ name: 'login' });
};
</script>
