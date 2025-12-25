<template>
  <div class="space-y-6">
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Static Routes Tab -->
    <div v-if="activeTab === 'routes'" class="space-y-6">
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

    <!-- BGP Tab -->
    <div v-if="activeTab === 'bgp'" class="space-y-6">
      <!-- BGP Status -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">BGP Status</h3>
          <div class="flex space-x-2">
            <span :class="bgpStatus.running ? 'text-green-600' : 'text-gray-400'" class="flex items-center">
              <span class="h-2 w-2 rounded-full mr-2" :class="bgpStatus.running ? 'bg-green-500' : 'bg-gray-300'"></span>
              {{ bgpStatus.running ? 'Running' : 'Stopped' }}
            </span>
            <button v-if="!bgpStatus.running" @click="enableBGP" class="btn btn-primary text-sm">Enable</button>
            <button v-else @click="disableBGP" class="btn btn-secondary text-sm">Disable</button>
          </div>
        </div>
        <div v-if="bgpStatus.running" class="grid grid-cols-4 gap-4 text-sm">
          <div><span class="text-gray-500">AS Number:</span> {{ bgpConfig.as_number }}</div>
          <div><span class="text-gray-500">Router ID:</span> {{ bgpConfig.router_id }}</div>
          <div><span class="text-gray-500">Neighbors:</span> {{ bgpStatus.neighbor_count }}</div>
          <div><span class="text-gray-500">Prefixes:</span> {{ bgpStatus.rib_stats?.total_prefixes || 0 }}</div>
        </div>
      </div>

      <!-- BGP Configuration -->
      <div v-if="!bgpStatus.running" class="card">
        <h3 class="text-lg font-medium mb-4">BGP Configuration</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">AS Number</label>
            <input v-model.number="bgpConfig.as_number" type="number" class="input" placeholder="65001" />
          </div>
          <div>
            <label class="label">Router ID</label>
            <input v-model="bgpConfig.router_id" class="input" placeholder="192.168.1.1" />
          </div>
        </div>
        <div class="mt-4">
          <button @click="saveBGPConfig" class="btn btn-primary">Save Configuration</button>
        </div>
      </div>

      <!-- BGP Neighbors -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">BGP Neighbors</h3>
          <button @click="showAddNeighbor = true" class="btn btn-primary">Add Neighbor</button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Neighbor</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Remote AS</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prefixes</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Uptime</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="peer in bgpStatus.neighbors" :key="peer.address">
                <td class="px-4 py-3 font-mono">{{ peer.address }}</td>
                <td class="px-4 py-3">{{ peer.remote_as }}</td>
                <td class="px-4 py-3">
                  <span :class="peer.state === 'Established' ? 'text-green-600' : 'text-yellow-600'">
                    {{ peer.state }}
                  </span>
                </td>
                <td class="px-4 py-3">{{ peer.prefix_received || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ peer.uptime }}</td>
                <td class="px-4 py-3 space-x-2">
                  <button @click="refreshNeighbor(peer.address)" class="text-blue-600 hover:text-blue-800 text-sm">Refresh</button>
                  <button @click="clearNeighbor(peer.address)" class="text-yellow-600 hover:text-yellow-800 text-sm">Clear</button>
                  <button @click="removeNeighbor(peer.address)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                </td>
              </tr>
              <tr v-if="!bgpStatus.neighbors?.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No BGP neighbors configured</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- BGP Networks -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">Announced Networks</h3>
          <button @click="showAddNetwork = true" class="btn btn-primary">Add Network</button>
        </div>
        <div class="flex flex-wrap gap-2">
          <span v-for="net in bgpConfig.networks" :key="net.prefix"
                class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm flex items-center">
            {{ net.prefix }}
            <button @click="removeNetwork(net.prefix)" class="ml-2 text-blue-600 hover:text-blue-800">&times;</button>
          </span>
          <span v-if="!bgpConfig.networks?.length" class="text-gray-500">No networks configured</span>
        </div>
      </div>
    </div>

    <!-- OSPF Tab -->
    <div v-if="activeTab === 'ospf'" class="space-y-6">
      <!-- OSPF Status -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">OSPF Status</h3>
          <div class="flex space-x-2">
            <span :class="ospfStatus.running ? 'text-green-600' : 'text-gray-400'" class="flex items-center">
              <span class="h-2 w-2 rounded-full mr-2" :class="ospfStatus.running ? 'bg-green-500' : 'bg-gray-300'"></span>
              {{ ospfStatus.running ? 'Running' : 'Stopped' }}
            </span>
            <button v-if="!ospfStatus.running" @click="enableOSPF" class="btn btn-primary text-sm">Enable</button>
            <button v-else @click="disableOSPF" class="btn btn-secondary text-sm">Disable</button>
          </div>
        </div>
        <div v-if="ospfStatus.running" class="grid grid-cols-4 gap-4 text-sm">
          <div><span class="text-gray-500">Router ID:</span> {{ ospfConfig.router_id }}</div>
          <div><span class="text-gray-500">Neighbors:</span> {{ ospfStatus.neighbor_count }}</div>
          <div><span class="text-gray-500">Areas:</span> {{ ospfConfig.areas?.length || 0 }}</div>
          <div><span class="text-gray-500">LSAs:</span> {{ (ospfStatus.database?.router_lsa || 0) + (ospfStatus.database?.network_lsa || 0) }}</div>
        </div>
      </div>

      <!-- OSPF Configuration -->
      <div v-if="!ospfStatus.running" class="card">
        <h3 class="text-lg font-medium mb-4">OSPF Configuration</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Router ID</label>
            <input v-model="ospfConfig.router_id" class="input" placeholder="192.168.1.1" />
          </div>
          <div class="flex items-center space-x-4 pt-6">
            <label class="flex items-center">
              <input type="checkbox" v-model="ospfConfig.fib_update" class="mr-2" />
              FIB Update
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="ospfConfig.stub_router" class="mr-2" />
              Stub Router
            </label>
          </div>
        </div>
        <div class="mt-4">
          <button @click="saveOSPFConfig" class="btn btn-primary">Save Configuration</button>
        </div>
      </div>

      <!-- OSPF Neighbors -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">OSPF Neighbors</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Router ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Interface</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">State</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Uptime</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="neighbor in ospfStatus.neighbors" :key="neighbor.router_id">
                <td class="px-4 py-3 font-mono">{{ neighbor.router_id }}</td>
                <td class="px-4 py-3 font-mono">{{ neighbor.address }}</td>
                <td class="px-4 py-3">{{ neighbor.interface }}</td>
                <td class="px-4 py-3">
                  <span :class="neighbor.state === 'Full' || neighbor.state.includes('Full') ? 'text-green-600' : 'text-yellow-600'">
                    {{ neighbor.state }}
                  </span>
                </td>
                <td class="px-4 py-3">{{ neighbor.priority }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ neighbor.uptime }}</td>
              </tr>
              <tr v-if="!ospfStatus.neighbors?.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No OSPF neighbors detected</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- OSPF Areas -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">OSPF Areas</h3>
          <button @click="showAddArea = true" class="btn btn-primary">Add Area</button>
        </div>
        <div class="space-y-4">
          <div v-for="area in ospfConfig.areas" :key="area.id" class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium">Area {{ area.id }}</span>
              <div class="space-x-2">
                <span v-if="area.stub" class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">Stub</span>
                <span v-if="area.nssa" class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">NSSA</span>
                <button @click="removeArea(area.id)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
              </div>
            </div>
            <div class="text-sm text-gray-600">
              Interfaces: {{ area.interfaces?.map(i => i.name).join(', ') || 'None' }}
            </div>
          </div>
          <div v-if="!ospfConfig.areas?.length" class="text-center text-gray-500 py-4">
            No OSPF areas configured
          </div>
        </div>
      </div>
    </div>

    <!-- Add BGP Neighbor Modal -->
    <div v-if="showAddNeighbor" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-medium mb-4">Add BGP Neighbor</h3>
        <div class="space-y-4">
          <div>
            <label class="label">Neighbor Address</label>
            <input v-model="newNeighbor.address" class="input" placeholder="10.0.0.1" />
          </div>
          <div>
            <label class="label">Remote AS</label>
            <input v-model.number="newNeighbor.remote_as" type="number" class="input" placeholder="65002" />
          </div>
          <div>
            <label class="label">Description</label>
            <input v-model="newNeighbor.description" class="input" placeholder="Transit Provider" />
          </div>
          <div>
            <label class="label">Multihop (optional)</label>
            <input v-model.number="newNeighbor.multihop" type="number" class="input" placeholder="0" />
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-2">
          <button @click="showAddNeighbor = false" class="btn btn-secondary">Cancel</button>
          <button @click="addNeighbor" class="btn btn-primary">Add</button>
        </div>
      </div>
    </div>

    <!-- Add BGP Network Modal -->
    <div v-if="showAddNetwork" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-medium mb-4">Add Network to Announce</h3>
        <div class="space-y-4">
          <div>
            <label class="label">Network Prefix</label>
            <input v-model="newNetwork.prefix" class="input" placeholder="192.168.0.0/24" />
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-2">
          <button @click="showAddNetwork = false" class="btn btn-secondary">Cancel</button>
          <button @click="addNetwork" class="btn btn-primary">Add</button>
        </div>
      </div>
    </div>

    <!-- Add OSPF Area Modal -->
    <div v-if="showAddArea" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-medium mb-4">Add OSPF Area</h3>
        <div class="space-y-4">
          <div>
            <label class="label">Area ID</label>
            <input v-model="newArea.id" class="input" placeholder="0.0.0.0" />
          </div>
          <div class="flex items-center space-x-4">
            <label class="flex items-center">
              <input type="checkbox" v-model="newArea.stub" class="mr-2" />
              Stub Area
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="newArea.nssa" class="mr-2" />
              NSSA
            </label>
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-2">
          <button @click="showAddArea = false" class="btn btn-secondary">Cancel</button>
          <button @click="addArea" class="btn btn-primary">Add</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

// Tab navigation
const tabs = [
  { id: 'routes', name: 'Static Routes' },
  { id: 'bgp', name: 'BGP' },
  { id: 'ospf', name: 'OSPF' }
]
const activeTab = ref('routes')

// Static routes state
const routes = ref([])
const arp = ref([])
const showAddRoute = ref(false)
const newRoute = ref({ destination: '', gateway: '' })

// BGP state
const bgpStatus = ref({ running: false, neighbors: [], rib_stats: {} })
const bgpConfig = ref({ as_number: 0, router_id: '', networks: [], neighbors: [] })
const showAddNeighbor = ref(false)
const showAddNetwork = ref(false)
const newNeighbor = ref({ address: '', remote_as: 0, description: '', multihop: 0 })
const newNetwork = ref({ prefix: '' })

// OSPF state
const ospfStatus = ref({ running: false, neighbors: [], database: {} })
const ospfConfig = ref({ router_id: '', fib_update: true, stub_router: false, areas: [] })
const showAddArea = ref(false)
const newArea = ref({ id: '', stub: false, nssa: false })

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

async function loadBGP() {
  try {
    const [statusRes, configRes] = await Promise.all([
      api.get('/api/v1/bgp/status'),
      api.get('/api/v1/bgp/config')
    ])
    if (statusRes.data.success) bgpStatus.value = statusRes.data.data
    if (configRes.data.success) bgpConfig.value = configRes.data.data
  } catch (e) {
    console.error('Failed to load BGP data:', e)
  }
}

async function loadOSPF() {
  try {
    const [statusRes, configRes] = await Promise.all([
      api.get('/api/v1/ospf/status'),
      api.get('/api/v1/ospf/config')
    ])
    if (statusRes.data.success) ospfStatus.value = statusRes.data.data
    if (configRes.data.success) ospfConfig.value = configRes.data.data
  } catch (e) {
    console.error('Failed to load OSPF data:', e)
  }
}

// Static route functions
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

// BGP functions
async function enableBGP() {
  try {
    await api.post('/api/v1/bgp/enable')
    await loadBGP()
  } catch (e) {
    console.error('Failed to enable BGP:', e)
  }
}

async function disableBGP() {
  if (!confirm('Disable BGP? This will tear down all sessions.')) return
  try {
    await api.post('/api/v1/bgp/disable')
    await loadBGP()
  } catch (e) {
    console.error('Failed to disable BGP:', e)
  }
}

async function saveBGPConfig() {
  try {
    await api.put('/api/v1/bgp/config', bgpConfig.value)
    await loadBGP()
  } catch (e) {
    console.error('Failed to save BGP config:', e)
  }
}

async function addNeighbor() {
  try {
    await api.post('/api/v1/bgp/neighbors', newNeighbor.value)
    showAddNeighbor.value = false
    newNeighbor.value = { address: '', remote_as: 0, description: '', multihop: 0 }
    await loadBGP()
  } catch (e) {
    console.error('Failed to add neighbor:', e)
  }
}

async function removeNeighbor(address) {
  if (!confirm(`Remove BGP neighbor ${address}?`)) return
  try {
    await api.delete(`/api/v1/bgp/neighbors/${address}`)
    await loadBGP()
  } catch (e) {
    console.error('Failed to remove neighbor:', e)
  }
}

async function refreshNeighbor(address) {
  try {
    await api.post(`/api/v1/bgp/neighbors/${address}/refresh`)
    await loadBGP()
  } catch (e) {
    console.error('Failed to refresh neighbor:', e)
  }
}

async function clearNeighbor(address) {
  if (!confirm(`Clear and reset session with ${address}?`)) return
  try {
    await api.post(`/api/v1/bgp/neighbors/${address}/clear`)
    await loadBGP()
  } catch (e) {
    console.error('Failed to clear neighbor:', e)
  }
}

async function addNetwork() {
  try {
    await api.post('/api/v1/bgp/networks', newNetwork.value)
    showAddNetwork.value = false
    newNetwork.value = { prefix: '' }
    await loadBGP()
  } catch (e) {
    console.error('Failed to add network:', e)
  }
}

async function removeNetwork(prefix) {
  try {
    await api.delete(`/api/v1/bgp/networks/${encodeURIComponent(prefix)}`)
    await loadBGP()
  } catch (e) {
    console.error('Failed to remove network:', e)
  }
}

// OSPF functions
async function enableOSPF() {
  try {
    await api.post('/api/v1/ospf/enable')
    await loadOSPF()
  } catch (e) {
    console.error('Failed to enable OSPF:', e)
  }
}

async function disableOSPF() {
  if (!confirm('Disable OSPF?')) return
  try {
    await api.post('/api/v1/ospf/disable')
    await loadOSPF()
  } catch (e) {
    console.error('Failed to disable OSPF:', e)
  }
}

async function saveOSPFConfig() {
  try {
    await api.put('/api/v1/ospf/config', ospfConfig.value)
    await loadOSPF()
  } catch (e) {
    console.error('Failed to save OSPF config:', e)
  }
}

async function addArea() {
  try {
    await api.post('/api/v1/ospf/areas', newArea.value)
    showAddArea.value = false
    newArea.value = { id: '', stub: false, nssa: false }
    await loadOSPF()
  } catch (e) {
    console.error('Failed to add area:', e)
  }
}

async function removeArea(areaId) {
  if (!confirm(`Remove OSPF area ${areaId}?`)) return
  try {
    await api.delete(`/api/v1/ospf/areas/${areaId}`)
    await loadOSPF()
  } catch (e) {
    console.error('Failed to remove area:', e)
  }
}

onMounted(() => {
  loadData()
  loadBGP()
  loadOSPF()
})
</script>
