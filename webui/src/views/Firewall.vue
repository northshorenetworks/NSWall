<template>
  <div class="space-y-6">
    <!-- PF Status -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">PF Firewall Status</h3>
        <div class="space-x-2">
          <button @click="reloadPf" class="btn btn-secondary">Reload Rules</button>
          <button @click="togglePf" :class="['btn', pfInfo.enabled ? 'btn-danger' : 'btn-primary']">
            {{ pfInfo.enabled ? 'Disable' : 'Enable' }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-4 gap-4">
        <div>
          <span class="text-sm text-gray-500">Status</span>
          <p class="text-lg font-medium">{{ pfInfo.enabled ? 'Enabled' : 'Disabled' }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">States</span>
          <p class="text-lg font-medium">{{ pfInfo.state_count }} / {{ pfInfo.state_limit }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Source Nodes</span>
          <p class="text-lg font-medium">{{ pfInfo.src_nodes }}</p>
        </div>
        <div>
          <span class="text-sm text-gray-500">Debug</span>
          <p class="text-lg font-medium">{{ pfInfo.debug }}</p>
        </div>
      </div>
    </div>

    <!-- Rules -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">Firewall Rules</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dir</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Interface</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proto</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Packets</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="rule in rules" :key="rule.number">
              <td class="px-4 py-3 text-sm">{{ rule.number }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-1 text-xs rounded-full', rule.action === 'pass' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                  {{ rule.action }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm">{{ rule.direction }}</td>
              <td class="px-4 py-3 text-sm">{{ rule.interface || '*' }}</td>
              <td class="px-4 py-3 text-sm">{{ rule.protocol || '*' }}</td>
              <td class="px-4 py-3 text-sm">{{ rule.source || 'any' }}</td>
              <td class="px-4 py-3 text-sm">{{ rule.destination || 'any' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ rule.packets }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tables -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">PF Tables</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="table in tables" :key="table.name" class="border rounded-lg p-4">
          <div class="flex justify-between items-center mb-2">
            <h4 class="font-medium">{{ table.name }}</h4>
            <span class="text-sm text-gray-500">{{ table.count }} entries</span>
          </div>
          <div class="flex space-x-2">
            <button @click="viewTable(table.name)" class="btn btn-secondary text-sm">View</button>
            <button @click="flushTable(table.name)" class="btn btn-danger text-sm">Flush</button>
          </div>
        </div>
      </div>
    </div>

    <!-- States -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">Active States</h3>
        <button @click="killAllStates" class="btn btn-danger">Kill All</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Protocol</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">State</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(state, idx) in states.slice(0, 50)" :key="idx">
              <td class="px-4 py-3 text-sm">{{ state.protocol }}</td>
              <td class="px-4 py-3 text-sm font-mono">{{ state.source }}</td>
              <td class="px-4 py-3 text-sm font-mono">{{ state.destination }}</td>
              <td class="px-4 py-3 text-sm">{{ state.state }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ state.age }}</td>
            </tr>
          </tbody>
        </table>
        <p v-if="states.length > 50" class="mt-2 text-sm text-gray-500">
          Showing 50 of {{ states.length }} states
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const pfInfo = ref({})
const rules = ref([])
const states = ref([])
const tables = ref([])

async function loadData() {
  try {
    const [infoRes, rulesRes, statesRes, tablesRes] = await Promise.all([
      api.get('/api/v1/pf'),
      api.get('/api/v1/pf/rules'),
      api.get('/api/v1/pf/states'),
      api.get('/api/v1/pf/tables')
    ])

    if (infoRes.data.success) pfInfo.value = infoRes.data.data
    if (rulesRes.data.success) rules.value = rulesRes.data.data
    if (statesRes.data.success) states.value = statesRes.data.data
    if (tablesRes.data.success) tables.value = tablesRes.data.data
  } catch (e) {
    console.error('Failed to load firewall data:', e)
  }
}

async function togglePf() {
  try {
    const endpoint = pfInfo.value.enabled ? '/api/v1/pf/disable' : '/api/v1/pf/enable'
    await api.post(endpoint)
    await loadData()
  } catch (e) {
    console.error('Failed to toggle PF:', e)
  }
}

async function reloadPf() {
  try {
    await api.post('/api/v1/pf/reload')
    await loadData()
  } catch (e) {
    console.error('Failed to reload PF:', e)
  }
}

async function flushTable(name) {
  if (!confirm(`Flush all entries from table ${name}?`)) return
  try {
    await api.post(`/api/v1/pf/tables/${name}/flush`)
    await loadData()
  } catch (e) {
    console.error('Failed to flush table:', e)
  }
}

async function killAllStates() {
  if (!confirm('Kill all PF states?')) return
  try {
    await api.delete('/api/v1/pf/states')
    await loadData()
  } catch (e) {
    console.error('Failed to kill states:', e)
  }
}

function viewTable(name) {
  // TODO: Open modal with table contents
  console.log('View table:', name)
}

onMounted(loadData)
</script>
