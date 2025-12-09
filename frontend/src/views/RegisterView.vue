<!-- frontend/src/views/RegisterView.vue -->
<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-900">
    <div class="w-full max-w-md bg-slate-800/80 p-8 rounded-2xl shadow-xl border border-slate-700">
      <h1 class="text-2xl font-semibold text-white mb-6 text-center">
        Register
      </h1>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block text-sm text-slate-300 mb-1">Name</label>
          <input
            v-model="name"
            type="text"
            required
            class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-100 focus:outline-none focus:ring focus:ring-indigo-500"
          />
        </div>

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

        <div>
          <label class="block text-sm text-slate-300 mb-1">Confirm Password</label>
          <input
            v-model="passwordConfirmation"
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
          {{ loading ? 'Creating account...' : 'Register' }}
        </button>
      </form>

      <p class="mt-4 text-center text-slate-400 text-sm">
        Already have an account?
        <router-link to="/login" class="text-indigo-400 hover:text-indigo-300">
          Login
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api, { setAuthToken } from '../lib/http';

const router = useRouter();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref('');

const handleRegister = async () => {
  loading.value = true;
  error.value = '';

  try {
    const { data } = await api.post('/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    // If your API also returns token on register, you can auto-login:
    if (data.token) {
      localStorage.setItem('auth_token', data.token);
      setAuthToken(data.token);
      router.push('/dashboard');
    } else {
      // Otherwise, send user to login page
      router.push('/login');
    }
  } catch (e) {
    console.error(e);
    error.value =
      e.response?.data?.message || 'Registration failed. Please check the form.';
  } finally {
    loading.value = false;
  }
};
</script>
