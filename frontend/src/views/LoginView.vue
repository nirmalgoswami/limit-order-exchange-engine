<!-- frontend/src/views/LoginView.vue -->
<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-900">
    <div class="w-full max-w-md bg-slate-800/80 p-8 rounded-2xl shadow-xl border border-slate-700">
      <h1 class="text-2xl font-semibold text-white mb-6 text-center">
        Login
      </h1>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm text-slate-300 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 focus:outline-none focus:ring focus:ring-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Password</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 focus:outline-none focus:ring focus:ring-indigo-500"
          />
        </div>

        <p v-if="error" class="text-sm text-red-400">
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white font-medium transition"
        >
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>

      <p class="mt-4 text-center text-slate-400 text-sm">
        Don’t have an account?
        <router-link to="/register" class="text-indigo-400 hover:text-indigo-300">
          Register
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api, { setAuthToken } from '../lib/http';

const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  try {
    // ✅ Token-based Sanctum auth (no csrf-cookie needed)
    const { data } = await api.post('/login', {
      email: email.value,
      password: password.value,
    });

    // Expecting: { token: '...', user: { ... } }
    if (!data.token) {
      throw new Error('No token returned from API');
    }

    localStorage.setItem('auth_token', data.token);
    setAuthToken(data.token);

    // Redirect to originally requested route if exists
    //const redirect = route.query.redirect || '/dashboard';
    //router.push(redirect);

    //Force page reload to ensure session
    window.location.href = '/dashboard';

  } catch (e) {
    console.error(e);
    error.value =
      e.response?.data?.message || 'Login failed. Please check your credentials.';
  } finally {
    loading.value = false;
  }
};
</script>
