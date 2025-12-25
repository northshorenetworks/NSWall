import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/interfaces',
    name: 'Interfaces',
    component: () => import('../views/Interfaces.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/routing',
    name: 'Routing',
    component: () => import('../views/Routing.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/firewall',
    name: 'Firewall',
    component: () => import('../views/Firewall.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/vpn',
    name: 'VPN',
    component: () => import('../views/VPN.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/ha',
    name: 'High Availability',
    component: () => import('../views/HA.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dhcp',
    name: 'DHCP & DNS',
    component: () => import('../views/DHCP.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/services',
    name: 'Services',
    component: () => import('../views/Services.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/terminal',
    name: 'Terminal',
    component: () => import('../views/Terminal.vue'),
    meta: { requiresAuth: true, requiresOperator: true }
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../views/Users.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/config',
    name: 'Configuration',
    component: () => import('../views/Configuration.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/logs',
    name: 'Logs',
    component: () => import('../views/Logs.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/diagnostics',
    name: 'Diagnostics',
    component: () => import('../views/Diagnostics.vue'),
    meta: { requiresAuth: true, requiresOperator: true }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('../views/Settings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Check auth status on first load
  if (!authStore.isAuthenticated && authStore.token) {
    await authStore.checkAuth()
  }

  // Handle routes requiring authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } })
  }

  // Handle guest-only routes (like login)
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'Dashboard' })
  }

  // Handle admin-only routes
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next({ name: 'Dashboard' })
  }

  // Handle operator-only routes
  if (to.meta.requiresOperator && !authStore.isOperator) {
    return next({ name: 'Dashboard' })
  }

  next()
})

export default router
