<template>
  <div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="card">
        <h3 class="text-sm font-medium text-gray-500">Hostname</h3>
        <p class="mt-2 text-2xl font-semibold">{{ system.hostname }}</p>
      </div>
      <div class="card">
        <h3 class="text-sm font-medium text-gray-500">Uptime</h3>
        <p class="mt-2 text-2xl font-semibold">{{ system.uptime_human }}</p>
      </div>
      <div class="card">
        <h3 class="text-sm font-medium text-gray-500">Load Average</h3>
        <p class="mt-2 text-2xl font-semibold">{{ loadAvg }}</p>
      </div>
      <div class="card">
        <h3 class="text-sm font-medium text-gray-500">PF States</h3>
        <p class="mt-2 text-2xl font-semibold">{{ pfStates }}</p>
      </div>
    </div>

    <!-- Interface Status -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">Network Interfaces</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">RX/TX</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="iface in interfaces" :key="iface.name">
              <td class="px-4 py-3 font-medium">{{ iface.name }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-1 text-xs rounded-full', iface.status === 'up' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800']">
                  {{ iface.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ iface.ipv4_addresses?.[0]?.cidr || '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ formatBytes(iface.statistics?.rx_bytes) }} / {{ formatBytes(iface.statistics?.tx_bytes) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Services Status -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">Services</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div v-for="svc in services" :key="svc.name" class="flex items-center space-x-2">
          <div :class="['w-2 h-2 rounded-full', svc.status === 'running' ? 'bg-green-500' : 'bg-gray-300']"></div>
          <span class="text-sm">{{ svc.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'

const system = ref({})
const interfaces = ref([])
const services = ref([])
const pfInfo = ref({})

const loadAvg = computed(() => {
  if (!system.value.load_average) return '-'
  return system.value.load_average.map(l => l.toFixed(2)).join(' ')
})

const pfStates = computed(() => {
  return pfInfo.value.state_count || 0
})

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0
  while (bytes >= 1024 && i < units.length - 1) {
    bytes /= 1024
    i++
  }
  return `${bytes.toFixed(1)} ${units[i]}`
}

onMounted(async () => {
  try {
    const [sysRes, ifaceRes, svcRes, pfRes] = await Promise.all([
      api.get('/api/v1/system'),
      api.get('/api/v1/interfaces'),
      api.get('/api/v1/services'),
      api.get('/api/v1/pf')
    ])

    if (sysRes.data.success) system.value = sysRes.data.data
    if (ifaceRes.data.success) interfaces.value = ifaceRes.data.data
    if (svcRes.data.success) services.value = svcRes.data.data
    if (pfRes.data.success) pfInfo.value = pfRes.data.data
  } catch (e) {
    console.error('Failed to load dashboard data:', e)
  }
})
</script>
