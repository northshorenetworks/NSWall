<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">DHCP & DNS</h1>
      <button @click="refresh" class="btn-secondary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- DHCP Leases Tab -->
    <div v-if="activeTab === 'leases'" class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Active Leases</h2>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ leases.length }} total</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">MAC Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hostname</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Lease Start</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Lease End</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="lease in leases" :key="lease.ip_address" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-sm font-mono text-gray-900 dark:text-white">{{ lease.ip_address }}</td>
              <td class="px-6 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">{{ lease.mac_address }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ lease.hostname || '-' }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(lease.lease_start) }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(lease.lease_end) }}</td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="isLeaseActive(lease) ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'">
                  {{ isLeaseActive(lease) ? 'Active' : 'Expired' }}
                </span>
              </td>
            </tr>
            <tr v-if="leases.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                No active DHCP leases
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Static Mappings Tab -->
    <div v-if="activeTab === 'static'" class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Static Mappings</h2>
        <button @click="showAddStatic = true" class="btn-primary text-sm">Add Mapping</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">MAC Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hostname</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="mapping in staticMappings" :key="mapping.mac" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">{{ mapping.mac }}</td>
              <td class="px-6 py-4 text-sm font-mono text-gray-900 dark:text-white">{{ mapping.ip }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ mapping.hostname || '-' }}</td>
              <td class="px-6 py-4 text-sm">
                <button @click="deleteStaticMapping(mapping)" class="text-red-600 hover:text-red-800">Delete</button>
              </td>
            </tr>
            <tr v-if="staticMappings.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                No static mappings configured
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DNS Tab -->
    <div v-if="activeTab === 'dns'" class="space-y-6">
      <!-- DNS Resolvers -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">DNS Resolvers</h2>
        </div>
        <div class="p-6 space-y-4">
          <div v-for="(resolver, index) in dnsResolvers" :key="index" class="flex items-center space-x-4">
            <span class="text-sm text-gray-500 dark:text-gray-400 w-24">Resolver {{ index + 1 }}</span>
            <code class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded text-sm text-gray-900 dark:text-white">{{ resolver }}</code>
          </div>
          <div v-if="dnsResolvers.length === 0" class="text-gray-500 dark:text-gray-400">
            No DNS resolvers configured
          </div>
        </div>
      </div>

      <!-- DNS Cache -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">DNS Cache</h2>
          <button @click="flushDNSCache" class="btn-secondary text-sm">Flush Cache</button>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Entries</dt>
              <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ dnsStats.cache_entries || 0 }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Hits</dt>
              <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ dnsStats.cache_hits || 0 }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Misses</dt>
              <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ dnsStats.cache_misses || 0 }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useToast } from 'vue-toastification'

const toast = useToast()
const activeTab = ref('leases')
const tabs = [
  { id: 'leases', name: 'DHCP Leases' },
  { id: 'static', name: 'Static Mappings' },
  { id: 'dns', name: 'DNS' }
]

const leases = ref([])
const staticMappings = ref([])
const dnsResolvers = ref([])
const dnsStats = ref({})
const showAddStatic = ref(false)

async function fetchData() {
  try {
    const [leasesRes, dnsRes] = await Promise.all([
      api.get('/dhcp/leases'),
      api.get('/dns/resolvers')
    ])

    if (leasesRes.data.success) {
      leases.value = leasesRes.data.data || []
    }

    if (dnsRes.data.success) {
      dnsResolvers.value = dnsRes.data.data?.resolvers || []
      dnsStats.value = dnsRes.data.data?.stats || {}
    }
  } catch (err) {
    console.error('Failed to fetch DHCP/DNS data:', err)
  }
}

async function refresh() {
  await fetchData()
  toast.success('Data refreshed')
}

async function flushDNSCache() {
  try {
    await api.post('/dns/flush')
    toast.success('DNS cache flushed')
    await fetchData()
  } catch (err) {
    toast.error('Failed to flush DNS cache')
  }
}

async function deleteStaticMapping(mapping) {
  if (!confirm(`Delete static mapping for ${mapping.mac}?`)) return

  try {
    await api.delete(`/dhcp/static/${mapping.mac}`)
    toast.success('Static mapping deleted')
    await fetchData()
  } catch (err) {
    toast.error('Failed to delete static mapping')
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString()
}

function isLeaseActive(lease) {
  if (!lease.lease_end) return true
  return new Date(lease.lease_end) > new Date()
}

onMounted(() => {
  fetchData()
})
</script>
