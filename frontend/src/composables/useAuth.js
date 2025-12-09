import { reactive, computed } from 'vue';
import api, { setAuthToken } from '../lib/http';

const state = reactive({
  user: null,
  token: localStorage.getItem('auth_token') || null,
  loading: false,
  error: null,
});

if (state.token) {
  setAuthToken(state.token);
}

async function login(credentials) {
  state.loading = true;
  state.error = null;
  try {
    const { data } = await api.post('/login', credentials);
    state.token = data.token;
    state.user = data.user;
    localStorage.setItem('auth_token', data.token);
    setAuthToken(data.token);
  } catch (error) {
    state.error =
      error.response?.data?.message ||
      error.response?.data?.errors?.email?.[0] ||
      'Login failed';
    throw error;
  } finally {
    state.loading = false;
  }
}

async function register(payload) {
  state.loading = true;
  state.error = null;
  try {
    const { data } = await api.post('/register', payload);
    state.token = data.token;
    state.user = data.user;
    localStorage.setItem('auth_token', data.token);
    setAuthToken(data.token);
  } catch (error) {
    state.error =
      error.response?.data?.message ||
      Object.values(error.response?.data?.errors || {})[0]?.[0] ||
      'Registration failed';
    throw error;
  } finally {
    state.loading = false;
  }
}

async function fetchUser() {
  if (!state.token) return;
  try {
    const { data } = await api.get('/me');
    state.user = data;
  } catch {
    // token invalid
    await logout();
  }
}

async function logout() {
  try {
    await api.post('/logout');
  } catch {
    // ignore errors, just clear client state
  }
  state.user = null;
  state.token = null;
  localStorage.removeItem('auth_token');
  setAuthToken(null);
}

export function useAuth() {
  return {
    user: computed(() => state.user),
    token: computed(() => state.token),
    loading: computed(() => state.loading),
    error: computed(() => state.error),
    isAuthenticated: computed(() => !!state.token),
    login,
    register,
    logout,
    fetchUser,
  };
}
