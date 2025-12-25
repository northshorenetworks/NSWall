<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">High Availability</h1>
      <button @click="refresh" class="btn-secondary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <!-- HA Status Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full" :class="carpStatus.role === 'MASTER' ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900'">
            <svg class="w-6 h-6" :class="carpStatus.role === 'MASTER' ? 'text-green-600' : 'text-yellow-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CARP Role</p>
            <p class="text-2xl font-semibold" :class="carpStatus.role === 'MASTER' ? 'text-green-600' : 'text-yellow-600'">
              {{ carpStatus.role || 'Unknown' }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full" :class="pfsyncStatus.enabled ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-100 dark:bg-gray-700'">
            <svg class="w-6 h-6" :class="pfsyncStatus.enabled ? 'text-green-600' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">pfsync</p>
            <p class="text-2xl font-semibold" :class="pfsyncStatus.enabled ? 'text-green-600' : 'text-gray-600 dark:text-gray-400'">
              {{ pfsyncStatus.enabled ? 'Active' : 'Inactive' }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">States Synced</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ pfsyncStatus.states || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- CARP Interfaces -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">CARP Interfaces</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Interface</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Virtual IP</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">VHID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">State</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Advbase</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Advskew</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="carp in carpInterfaces" :key="carp.interface" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ carp.interface }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ carp.virtual_ip }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ carp.vhid }}</td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="carp.state === 'MASTER' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'">
                  {{ carp.state }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ carp.advbase }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ carp.advskew }}</td>
            </tr>
            <tr v-if="carpInterfaces.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                No CARP interfaces configured
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- pfsync Status -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">pfsync Status</h2>
      </div>
      <div class="p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sync Interface</dt>
            <dd class="text-lg text-gray-900 dark:text-white">{{ pfsyncStatus.interface || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sync Peer</dt>
            <dd class="text-lg text-gray-900 dark:text-white">{{ pfsyncStatus.peer || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Packets In</dt>
            <dd class="text-lg text-gray-900 dark:text-white">{{ pfsyncStatus.packets_in || 0 }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Packets Out</dt>
            <dd class="text-lg text-gray-900 dark:text-white">{{ pfsyncStatus.packets_out || 0 }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Failover Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Failover Actions</h2>
      <div class="flex space-x-4">
        <button @click="triggerFailover" class="btn-primary" :disabled="loading">
          Force Failover
        </button>
        <button @click="preempt" class="btn-secondary" :disabled="loading">
          Preempt (Become Master)
        </button>
      </div>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Warning: These actions may cause temporary service interruption.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/api'
import { useToast } from 'vue-toastification'

const toast = useToast()
const loading = ref(false)
const carpStatus = ref({ role: 'Unknown' })
const carpInterfaces = ref([])
const pfsyncStatus = ref({ enabled: false })
let refreshInterval = null

async function fetchData() {
  try {
    const [carpRes, pfsyncRes] = await Promise.all([
      api.get('/ha/carp'),
      api.get('/ha/pfsync')
    ])

    if (carpRes.data.success) {
      carpInterfaces.value = carpRes.data.data.interfaces || []
      // Determine overall role
      const masterCount = carpInterfaces.value.filter(c => c.state === 'MASTER').length
      const backupCount = carpInterfaces.value.filter(c => c.state === 'BACKUP').length
      if (masterCount > backupCount) {
        carpStatus.value.role = 'MASTER'
      } else if (backupCount > 0) {
        carpStatus.value.role = 'BACKUP'
      } else {
        carpStatus.value.role = 'Unknown'
      }
    }

    if (pfsyncRes.data.success) {
      pfsyncStatus.value = pfsyncRes.data.data
    }
  } catch (err) {
    console.error('Failed to fetch HA status:', err)
  }
}

async function refresh() {
  loading.value = true
  await fetchData()
  loading.value = false
}

async function triggerFailover() {
  if (!confirm('Are you sure you want to trigger a failover?')) return

  loading.value = true
  try {
    await api.post('/ha/failover')
    toast.success('Failover triggered successfully')
    await fetchData()
  } catch (err) {
    toast.error('Failed to trigger failover')
  } finally {
    loading.value = false
  }
}

async function preempt() {
  if (!confirm('Are you sure you want this node to become MASTER?')) return

  loading.value = true
  try {
    await api.post('/ha/preempt')
    toast.success('Preempt command sent')
    await fetchData()
  } catch (err) {
    toast.error('Failed to preempt')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
  refreshInterval = setInterval(fetchData, 5000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})
</script>
