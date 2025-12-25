<template>
  <div class="min-h-screen" :class="settingsStore.darkMode ? 'dark bg-gray-900' : 'bg-gray-100'">
    <!-- Login page (no sidebar) -->
    <template v-if="route.name === 'Login' || route.name === 'NotFound'">
      <router-view />
    </template>

    <!-- Main layout with sidebar -->
    <template v-else>
      <!-- Sidebar -->
      <aside
        class="fixed inset-y-0 left-0 bg-gray-900 dark:bg-gray-950 transition-all duration-300 z-40"
        :class="settingsStore.sidebarCollapsed ? 'w-16' : 'w-64'"
      >
        <div class="flex items-center h-16 px-4 bg-gray-800 dark:bg-gray-900">
          <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <template v-if="!settingsStore.sidebarCollapsed">
            <h1 class="ml-3 text-xl font-bold text-white">NSWall</h1>
            <span class="ml-2 text-xs text-gray-400">v2.0</span>
          </template>
        </div>

        <nav class="mt-4 px-2 space-y-1">
          <router-link
            v-for="item in navigation"
            :key="item.path"
            :to="item.path"
            :title="item.name"
            class="flex items-center px-3 py-2.5 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            :class="{ 'justify-center': settingsStore.sidebarCollapsed }"
            active-class="bg-blue-600 text-white hover:bg-blue-700"
          >
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
            <span v-if="!settingsStore.sidebarCollapsed" class="ml-3">{{ item.name }}</span>
          </router-link>
        </nav>

        <!-- Sidebar toggle -->
        <button
          @click="settingsStore.toggleSidebar()"
          class="absolute bottom-4 right-0 translate-x-1/2 p-1.5 bg-gray-800 rounded-full text-gray-400 hover:text-white"
        >
          <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': settingsStore.sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
      </aside>

      <!-- Main content -->
      <main
        class="transition-all duration-300"
        :class="settingsStore.sidebarCollapsed ? 'pl-16' : 'pl-64'"
      >
        <!-- Header -->
        <header class="h-16 bg-white dark:bg-gray-800 shadow flex items-center justify-between px-6 sticky top-0 z-30">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-white">{{ currentRoute }}</h2>
          <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span v-if="notificationStore.unreadCount > 0"
                class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- Dark mode toggle -->
            <button @click="settingsStore.toggleDarkMode()"
              class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <svg v-if="settingsStore.darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>

            <!-- System status -->
            <div class="flex items-center space-x-2">
              <span class="text-sm text-gray-500 dark:text-gray-400">{{ hostname }}</span>
              <div :class="['w-2.5 h-2.5 rounded-full', systemStatus === 'ok' ? 'bg-green-500' : 'bg-red-500']"></div>
            </div>

            <!-- User menu -->
            <div class="relative">
              <button @click="showUserMenu = !showUserMenu"
                class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium">
                  {{ authStore.username.charAt(0).toUpperCase() }}
                </div>
                <span class="text-sm hidden md:block">{{ authStore.username }}</span>
              </button>

              <!-- Dropdown -->
              <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 z-50">
                <router-link to="/settings" @click="showUserMenu = false"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                  Settings
                </router-link>
                <button @click="logout"
                  class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                  Sign Out
                </button>
              </div>
            </div>
          </div>
        </header>

        <!-- Page content -->
        <div class="p-6 bg-gray-100 dark:bg-gray-900 min-h-[calc(100vh-4rem)]">
          <router-view />
        </div>
      </main>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from './api'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { useWebSocketStore } from '@/stores/websocket'

const route = useRoute()
const router = useRouter()
const settingsStore = useSettingsStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const wsStore = useWebSocketStore()

const currentRoute = computed(() => route.name)
const showUserMenu = ref(false)

const hostname = ref('NSWall')
const systemStatus = ref('ok')

// Icon components (inline SVG as functional components)
const HomeIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
])
const ServerIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01' })
])
const RouteIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' })
])
const ShieldIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })
])
const LockIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' })
])
const HAIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' })
])
const DHCPIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z' })
])
const CogIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }),
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })
])
const TerminalIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
])
const UsersIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
])
const DocIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
])
const LogIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' })
])
const ToolIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' })
])

const navigation = [
  { name: 'Dashboard', path: '/', icon: HomeIcon },
  { name: 'Interfaces', path: '/interfaces', icon: ServerIcon },
  { name: 'Routing', path: '/routing', icon: RouteIcon },
  { name: 'Firewall', path: '/firewall', icon: ShieldIcon },
  { name: 'VPN', path: '/vpn', icon: LockIcon },
  { name: 'High Availability', path: '/ha', icon: HAIcon },
  { name: 'DHCP & DNS', path: '/dhcp', icon: DHCPIcon },
  { name: 'Services', path: '/services', icon: CogIcon },
  { name: 'Terminal', path: '/terminal', icon: TerminalIcon },
  { name: 'Users', path: '/users', icon: UsersIcon, admin: true },
  { name: 'Configuration', path: '/config', icon: DocIcon },
  { name: 'Logs', path: '/logs', icon: LogIcon },
  { name: 'Diagnostics', path: '/diagnostics', icon: ToolIcon },
]

function logout() {
  showUserMenu.value = false
  authStore.logout()
  router.push('/login')
}

// Close user menu when clicking outside
function handleClickOutside(event) {
  if (showUserMenu.value && !event.target.closest('.relative')) {
    showUserMenu.value = false
  }
}

// Apply dark mode on mount
onMounted(async () => {
  settingsStore.applyTheme()
  document.addEventListener('click', handleClickOutside)

  // Connect WebSocket if authenticated
  if (authStore.isAuthenticated) {
    wsStore.connect()
  }

  // Fetch system info
  try {
    const { data } = await api.get('/system/info')
    if (data.success) {
      hostname.value = data.data.hostname
    }
  } catch (e) {
    systemStatus.value = 'error'
  }
})

// Watch for auth changes
watch(() => authStore.isAuthenticated, (isAuth) => {
  if (isAuth) {
    wsStore.connect()
  } else {
    wsStore.disconnect()
  }
})
</script>

<style>
/* Global button styles */
.btn-primary {
  @apply px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>
