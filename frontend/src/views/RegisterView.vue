<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-950 text-slate-100 px-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/70 p-8 shadow-xl shadow-slate-950/40">
      <h1 class="text-xl font-semibold mb-6 text-center">Create an account</h1>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block text-xs font-medium text-slate-400 mb-1">Name</label>
          <input
            v-model="name"
            type="text"
            required
            autocomplete="name"
            class="block w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Your name"
          />
        </div>

        <div class="mb-4">
          <label class="block text-xs font-medium text-slate-400 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="block w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="you@example.com"
          />
        </div>

        <div class="mb-4">
          <label class="block text-xs font-medium text-slate-400 mb-1">Password</label>
          <input
            v-model="password"
            type="password"
            required
            autocomplete="new-password"
            class="block w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Minimum 8 characters"
          />
        </div>

        <div class="mb-4">
          <label class="block text-xs font-medium text-slate-400 mb-1">Confirm Password</label>
          <input
            v-model="passwordConfirmation"
            type="password"
            required
            autocomplete="new-password"
            class="block w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Repeat password"
          />
        </div>

        <p v-if="error" class="mb-3 text-xs text-rose-400">
          {{ error }}
        </p>

        <button
          type="submit"
          class="mt-1 inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 hover:bg-blue-500 disabled:opacity-60"
          :disabled="submitting"
        >
          <span v-if="!submitting">Create account</span>
          <span v-else>Creating…</span>
        </button>
      </form>

      <p class="mt-4 text-xs text-slate-400 text-center">
        Already have an account?
        <RouterLink to="/login" class="text-blue-400 hover:text-blue-300">
          Sign in
        </RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api,{ webapi } from '../lib/http'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const submitting = ref(false)
const error = ref('')

const submit = async () => {
  error.value = ''
  submitting.value = true

  try {
    await webapi.get('/sanctum/csrf-cookie')

    await api.post('/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    router.push({ name: 'dashboard' })
  } catch (e) {
    console.error(e)
    error.value =
      e?.response?.data?.message || 'Registration failed. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>
