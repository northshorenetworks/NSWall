<template>
  <div class="space-y-6">
    <!-- Routes -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">Routing Table</h3>
        <button @click="showAddRoute = true" class="btn btn-primary">Add Route</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Gateway</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Flags</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Interface</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="route in routes" :key="route.destination">
              <td class="px-4 py-3 font-mono">{{ route.destination }}</td>
              <td class="px-4 py-3 font-mono">{{ route.gateway || '-' }}</td>
              <td class="px-4 py-3 text-sm">{{ route.flags }}</td>
              <td class="px-4 py-3">{{ route.interface }}</td>
              <td class="px-4 py-3">
                <button @click="deleteRoute(route.destination)" class="text-red-600 hover:text-red-800 text-sm">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ARP Table -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">ARP Table</h3>
        <button @click="flushArp" class="btn btn-secondary">Flush ARP</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">MAC Address</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Interface</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Flags</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="entry in arp" :key="entry.ip_address">
              <td class="px-4 py-3 font-mono">{{ entry.ip_address }}</td>
              <td class="px-4 py-3 font-mono">{{ entry.mac_address }}</td>
              <td class="px-4 py-3">{{ entry.interface }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ entry.flags }}</td>
              <td class="px-4 py-3">
                <button @click="deleteArp(entry.ip_address)" class="text-red-600 hover:text-red-800 text-sm">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Route Modal -->
    <div v-if="showAddRoute" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-medium mb-4">Add Route</h3>
        <div class="space-y-4">
          <div>
            <label class="label">Destination</label>
            <input v-model="newRoute.destination" class="input" placeholder="10.0.0.0/8" />
          </div>
          <div>
            <label class="label">Gateway</label>
            <input v-model="newRoute.gateway" class="input" placeholder="192.168.1.1" />
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-2">
          <button @click="showAddRoute = false" class="btn btn-secondary">Cancel</button>
          <button @click="addRoute" class="btn btn-primary">Add</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const routes = ref([])
const arp = ref([])
const showAddRoute = ref(false)
const newRoute = ref({ destination: '', gateway: '' })

async function loadData() {
  try {
    const [routesRes, arpRes] = await Promise.all([
      api.get('/api/v1/routes'),
      api.get('/api/v1/arp')
    ])
    if (routesRes.data.success) routes.value = routesRes.data.data
    if (arpRes.data.success) arp.value = arpRes.data.data
  } catch (e) {
    console.error('Failed to load routing data:', e)
  }
}

async function addRoute() {
  try {
    await api.post('/api/v1/routes', newRoute.value)
    showAddRoute.value = false
    newRoute.value = { destination: '', gateway: '' }
    await loadData()
  } catch (e) {
    console.error('Failed to add route:', e)
  }
}

async function deleteRoute(dest) {
  if (!confirm(`Delete route to ${dest}?`)) return
  try {
    await api.delete(`/api/v1/routes/${encodeURIComponent(dest)}`)
    await loadData()
  } catch (e) {
    console.error('Failed to delete route:', e)
  }
}

async function deleteArp(ip) {
  try {
    await api.delete(`/api/v1/arp/${ip}`)
    await loadData()
  } catch (e) {
    console.error('Failed to delete ARP entry:', e)
  }
}

async function flushArp() {
  if (!confirm('Flush entire ARP table?')) return
  try {
    await api.post('/api/v1/arp/flush')
    await loadData()
  } catch (e) {
    console.error('Failed to flush ARP:', e)
  }
}

onMounted(loadData)
</script>
