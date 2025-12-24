<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-gray-900">
      <div class="flex items-center h-16 px-6 bg-gray-800">
        <h1 class="text-xl font-bold text-white">NSWall</h1>
        <span class="ml-2 text-xs text-gray-400">v2.0</span>
      </div>

      <nav class="mt-6">
        <router-link
          v-for="item in navigation"
          :key="item.path"
          :to="item.path"
          class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white"
          active-class="bg-gray-800 text-white border-l-4 border-primary-500"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          {{ item.name }}
        </router-link>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="pl-64">
      <!-- Header -->
      <header class="h-16 bg-white shadow flex items-center justify-between px-6">
        <h2 class="text-lg font-semibold text-gray-800">{{ currentRoute }}</h2>
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-500">{{ hostname }}</span>
          <div :class="['w-3 h-3 rounded-full', systemStatus === 'ok' ? 'bg-green-500' : 'bg-red-500']"></div>
        </div>
      </header>

      <!-- Page content -->
      <div class="p-6">
        <router-view />
      </div>
    </header>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from './api'

const route = useRoute()
const currentRoute = computed(() => route.name)

const hostname = ref('loading...')
const systemStatus = ref('ok')

const navigation = [
  { name: 'Dashboard', path: '/', icon: 'HomeIcon' },
  { name: 'Interfaces', path: '/interfaces', icon: 'ServerIcon' },
  { name: 'Routing', path: '/routing', icon: 'ArrowsRightLeftIcon' },
  { name: 'Firewall', path: '/firewall', icon: 'ShieldCheckIcon' },
  { name: 'VPN', path: '/vpn', icon: 'LockClosedIcon' },
  { name: 'Services', path: '/services', icon: 'CogIcon' },
  { name: 'Configuration', path: '/config', icon: 'DocumentTextIcon' },
  { name: 'Logs', path: '/logs', icon: 'ClipboardDocumentListIcon' },
]

onMounted(async () => {
  try {
    const { data } = await api.get('/api/v1/system')
    if (data.success) {
      hostname.value = data.data.hostname
    }
  } catch (e) {
    systemStatus.value = 'error'
  }
})
</script>
