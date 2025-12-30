<template>
  <div class="space-y-6">
    <!-- WireGuard -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">WireGuard Interfaces</h3>
      <div v-if="wireguard.length === 0" class="text-gray-500 text-center py-8">
        No WireGuard interfaces configured
      </div>
      <div v-else class="space-y-4">
        <div v-for="wg in wireguard" :key="wg.name" class="border rounded-lg p-4">
          <div class="flex justify-between items-center mb-4">
            <div>
              <h4 class="font-medium">{{ wg.name }}</h4>
              <p class="text-sm text-gray-500">Port: {{ wg.listen_port || 'Not set' }}</p>
            </div>
            <span class="text-sm font-mono text-gray-500">{{ wg.public_key?.slice(0, 20) }}...</span>
          </div>

          <h5 class="text-sm font-medium text-gray-700 mb-2">Peers ({{ wg.peers?.length || 0 }})</h5>
          <div class="space-y-2">
            <div v-for="peer in wg.peers" :key="peer.public_key" class="bg-gray-50 rounded p-3">
              <div class="flex justify-between items-start">
                <div>
                  <p class="text-sm font-mono">{{ peer.public_key?.slice(0, 32) }}...</p>
                  <p class="text-xs text-gray-500">{{ peer.endpoint || 'No endpoint' }}</p>
                </div>
                <div class="text-right text-xs text-gray-500">
                  <p>RX: {{ formatBytes(peer.transfer_rx) }}</p>
                  <p>TX: {{ formatBytes(peer.transfer_tx) }}</p>
                </div>
              </div>
              <div class="mt-2 flex flex-wrap gap-1">
                <span v-for="ip in peer.allowed_ips" :key="ip" class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded">
                  {{ ip }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IKE/IPsec -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">IKE Security Associations</h3>
      <div v-if="ikeSAs.length === 0" class="text-gray-500 text-center py-8">
        No IKE SAs established
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Local</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Remote</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="sa in ikeSAs" :key="sa.id">
              <td class="px-4 py-3 font-medium">{{ sa.name }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-1 text-xs rounded-full', sa.status === 'ESTABLISHED' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800']">
                  {{ sa.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm font-mono">{{ sa.local_addr }}</td>
              <td class="px-4 py-3 text-sm font-mono">{{ sa.remote_addr }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- IPsec SAs -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">IPsec Security Associations</h3>
      <div v-if="ipsecSAs.length === 0" class="text-gray-500 text-center py-8">
        No IPsec SAs established
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SPI</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocol</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Transform</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="sa in ipsecSAs" :key="sa.spi">
              <td class="px-4 py-3 font-mono text-sm">{{ sa.spi }}</td>
              <td class="px-4 py-3">{{ sa.protocol }}</td>
              <td class="px-4 py-3 font-mono text-sm">{{ sa.source }}</td>
              <td class="px-4 py-3 font-mono text-sm">{{ sa.destination }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ sa.transform }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const wireguard = ref([])
const ikeSAs = ref([])
const ipsecSAs = ref([])

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let i = 0
  while (bytes >= 1024 && i < units.length - 1) {
    bytes /= 1024
    i++
  }
  return `${bytes.toFixed(1)} ${units[i]}`
}

onMounted(async () => {
  try {
    const [wgRes, ikeRes, ipsecRes] = await Promise.all([
      api.get('/api/v1/vpn/wireguard'),
      api.get('/api/v1/vpn/ike/sas'),
      api.get('/api/v1/vpn/ipsec/sas')
    ])
    if (wgRes.data.success) wireguard.value = wgRes.data.data || []
    if (ikeRes.data.success) ikeSAs.value = ikeRes.data.data || []
    if (ipsecRes.data.success) ipsecSAs.value = ipsecRes.data.data || []
  } catch (e) {
    console.error('Failed to load VPN data:', e)
  }
})
</script>
