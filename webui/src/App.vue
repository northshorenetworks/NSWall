<template>
  <div class="min-h-screen bg-cyber-black">
    <!-- Background effects -->
    <div class="fixed inset-0 pointer-events-none">
      <!-- Gradient orbs -->
      <div class="absolute top-0 left-1/4 w-96 h-96 bg-neon-cyan/5 rounded-full blur-3xl" />
      <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-neon-purple/5 rounded-full blur-3xl" />
      <!-- Grid pattern -->
      <div class="absolute inset-0 cyber-grid opacity-30" />
    </div>

    <!-- Login page (no sidebar) -->
    <template v-if="route.name === 'Login' || route.name === 'NotFound' || route.name === 'Setup'">
      <router-view />
    </template>

    <!-- Main layout with sidebar -->
    <template v-else>
      <!-- Sidebar -->
      <aside
        class="fixed inset-y-0 left-0 z-40 transition-all duration-300"
        :class="settingsStore.sidebarCollapsed ? 'w-16' : 'w-64'"
      >
        <!-- Sidebar glass background -->
        <div class="absolute inset-0 bg-cyber-darker/90 backdrop-blur-xl border-r border-white/5" />

        <!-- Sidebar content -->
        <div class="relative h-full flex flex-col">
          <!-- Logo -->
          <div class="flex items-center h-16 px-4">
            <div class="relative flex items-center justify-center w-9 h-9">
              <!-- Animated ring -->
              <div class="absolute inset-0 rounded-lg bg-gradient-to-br from-neon-cyan to-neon-purple opacity-20 animate-pulse" />
              <div class="relative flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-neon-cyan/20 to-neon-purple/20 border border-neon-cyan/30">
                <svg class="w-5 h-5 text-neon-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
            </div>
            <template v-if="!settingsStore.sidebarCollapsed">
              <h1 class="ml-3 text-xl font-bold font-display tracking-wider text-gradient">NSWall</h1>
              <span class="ml-2 text-xs text-neon-purple/80 font-mono">2.0</span>
            </template>
          </div>

          <!-- Navigation -->
          <nav class="flex-1 mt-2 px-2 space-y-0.5 overflow-y-auto">
            <router-link
              v-for="item in navigation"
              :key="item.path"
              :to="item.path"
              :title="item.name"
              class="nav-item"
              :class="{ 'justify-center': settingsStore.sidebarCollapsed }"
              active-class="active"
            >
              <component :is="item.icon" class="nav-item-icon" />
              <span v-if="!settingsStore.sidebarCollapsed" class="text-sm">{{ item.name }}</span>
            </router-link>
          </nav>

          <!-- Sidebar footer -->
          <div class="p-3 border-t border-white/5">
            <button
              @click="settingsStore.toggleSidebar()"
              class="w-full flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-neon-cyan hover:bg-white/5 transition-all"
            >
              <svg
                class="w-4 h-4 transition-transform duration-300"
                :class="{ 'rotate-180': settingsStore.sidebarCollapsed }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
              </svg>
            </button>
          </div>
        </div>
      </aside>

      <!-- Main content -->
      <main
        class="relative transition-all duration-300"
        :class="settingsStore.sidebarCollapsed ? 'pl-16' : 'pl-64'"
      >
        <!-- Top bar -->
        <header class="sticky top-0 z-30 h-16 flex items-center justify-between px-6 bg-cyber-darker/80 backdrop-blur-xl border-b border-white/5">
          <!-- Page title -->
          <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-white">{{ currentRoute }}</h2>
            <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 font-mono">
              <span class="px-2 py-0.5 bg-cyber-lighter/50 rounded">{{ hostname }}</span>
            </div>
          </div>

          <!-- Right section -->
          <div class="flex items-center gap-4">
            <!-- System status -->
            <div class="hidden md:flex items-center gap-3 pr-4 border-r border-white/10">
              <StatusIndicator
                :status="systemStatus === 'ok' ? 'online' : 'error'"
                :label="systemStatus === 'ok' ? 'System OK' : 'Error'"
              />
            </div>

            <!-- Notifications -->
            <button class="relative p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span
                v-if="notificationStore.unreadCount > 0"
                class="absolute top-1 right-1 w-2 h-2 bg-neon-red rounded-full animate-pulse"
              />
            </button>

            <!-- User menu -->
            <div class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center gap-3 p-1.5 rounded-lg hover:bg-white/5 transition-colors"
              >
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-neon-cyan/20 to-neon-purple/20 border border-neon-cyan/30 flex items-center justify-center">
                  <span class="text-sm font-semibold text-neon-cyan">
                    {{ authStore.username.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <span class="hidden md:block text-sm text-gray-300">{{ authStore.username }}</span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown -->
              <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
              >
                <div
                  v-if="showUserMenu"
                  class="absolute right-0 mt-2 w-56 py-2 bg-cyber-dark/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl"
                >
                  <div class="px-4 py-2 border-b border-white/5">
                    <p class="text-sm font-medium text-white">{{ authStore.username }}</p>
                    <p class="text-xs text-gray-500">Administrator</p>
                  </div>
                  <router-link
                    to="/settings"
                    @click="showUserMenu = false"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-300 hover:text-white hover:bg-white/5"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                  </router-link>
                  <div class="divider my-2" />
                  <button
                    @click="logout"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-neon-red hover:bg-neon-red/10"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Sign Out
                  </button>
                </div>
              </Transition>
            </div>
          </div>
        </header>

        <!-- Page content -->
        <div class="p-6 min-h-[calc(100vh-4rem)]">
          <router-view v-slot="{ Component }">
            <Transition
              mode="out-in"
              enter-active-class="transition-all duration-200"
              enter-from-class="opacity-0 translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <component :is="Component" />
            </Transition>
          </router-view>
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
import { StatusIndicator } from '@/components/ui'

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
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
])
const ServerIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01' })
])
const RouteIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' })
])
const ShieldIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })
])
const LockIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' })
])
const HAIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' })
])
const DHCPIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z' })
])
const CogIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }),
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })
])
const TerminalIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
])
const UsersIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
])
const DocIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
])
const LogIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' })
])
const ToolIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }),
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })
])
const FleetIcon = () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' })
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
  { name: 'Fleet', path: '/fleet', icon: FleetIcon, admin: true },
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

onMounted(async () => {
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
