<template>
  <div class="space-y-6">
    <div class="card">
      <h3 class="text-lg font-medium mb-4">Network Interfaces</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">MAC Address</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IPv4</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">MTU</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Speed</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="iface in interfaces" :key="iface.name" @click="selectInterface(iface)" class="cursor-pointer hover:bg-gray-50">
              <td class="px-4 py-3 font-medium">{{ iface.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ iface.description || '-' }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-1 text-xs rounded-full', iface.status === 'up' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800']">
                  {{ iface.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm font-mono">{{ iface.mac_address || '-' }}</td>
              <td class="px-4 py-3 text-sm">
                <span v-for="(ip, idx) in iface.ipv4_addresses" :key="idx" class="block">
                  {{ ip.cidr }}
                </span>
                <span v-if="!iface.ipv4_addresses?.length">-</span>
              </td>
              <td class="px-4 py-3 text-sm">{{ iface.mtu }}</td>
              <td class="px-4 py-3 text-sm">{{ iface.speed || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Interface Details -->
    <div v-if="selected" class="card">
      <h3 class="text-lg font-medium mb-4">{{ selected.name }} Statistics</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <span class="text-sm text-gray-500">RX Bytes</span>
          <p class="text-lg font-medium">{{ formatBytes(selected.statistics?.rx_bytes) }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">TX Bytes</span>
          <p class="text-lg font-medium">{{ formatBytes(selected.statistics?.tx_bytes) }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">RX Packets</span>
          <p class="text-lg font-medium">{{ selected.statistics?.rx_packets?.toLocaleString() }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">TX Packets</span>
          <p class="text-lg font-medium">{{ selected.statistics?.tx_packets?.toLocaleString() }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">RX Errors</span>
          <p class="text-lg font-medium">{{ selected.statistics?.rx_errors }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">TX Errors</span>
          <p class="text-lg font-medium">{{ selected.statistics?.tx_errors }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Collisions</span>
          <p class="text-lg font-medium">{{ selected.statistics?.collisions }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Flags</span>
          <p class="text-sm">{{ selected.flags?.join(', ') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const interfaces = ref([])
const selected = ref(null)

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

async function selectInterface(iface) {
  try {
    const { data } = await api.get(`/api/v1/interfaces/${iface.name}`)
    if (data.success) {
      selected.value = data.data
    }
  } catch (e) {
    console.error('Failed to load interface:', e)
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/api/v1/interfaces')
    if (data.success) {
      interfaces.value = data.data
    }
  } catch (e) {
    console.error('Failed to load interfaces:', e)
  }
})
</script>
