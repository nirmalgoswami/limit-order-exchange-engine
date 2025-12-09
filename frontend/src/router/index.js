import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../composables/useAuth';

import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import DashboardView from '../views/DashboardView.vue';

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guestOnly: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const auth = useAuth();

  if (auth.token.value && !auth.user.value) {
    await auth.fetchUser().catch(() => {});
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated.value) {
    return { name: 'login' };
  }

  if (to.meta.guestOnly && auth.isAuthenticated.value) {
    return { name: 'dashboard' };
  }

  return true;
});

export default router;
