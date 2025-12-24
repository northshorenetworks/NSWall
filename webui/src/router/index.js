import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue')
  },
  {
    path: '/interfaces',
    name: 'Interfaces',
    component: () => import('../views/Interfaces.vue')
  },
  {
    path: '/routing',
    name: 'Routing',
    component: () => import('../views/Routing.vue')
  },
  {
    path: '/firewall',
    name: 'Firewall',
    component: () => import('../views/Firewall.vue')
  },
  {
    path: '/vpn',
    name: 'VPN',
    component: () => import('../views/VPN.vue')
  },
  {
    path: '/services',
    name: 'Services',
    component: () => import('../views/Services.vue')
  },
  {
    path: '/config',
    name: 'Configuration',
    component: () => import('../views/Configuration.vue')
  },
  {
    path: '/logs',
    name: 'Logs',
    component: () => import('../views/Logs.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
