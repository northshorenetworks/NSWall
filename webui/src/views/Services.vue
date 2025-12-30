<template>
  <div class="space-y-6">
    <div class="card">
      <h3 class="text-lg font-medium mb-4">System Services</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Enabled</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="svc in services" :key="svc.name">
              <td class="px-4 py-3 font-medium">{{ svc.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ svc.description }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-1 text-xs rounded-full', svc.status === 'running' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800']">
                  {{ svc.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                <input
                  type="checkbox"
                  :checked="svc.enabled"
                  @change="toggleEnabled(svc)"
                  class="h-4 w-4 text-primary-600 rounded"
                />
              </td>
              <td class="px-4 py-3 space-x-2">
                <button
                  v-if="svc.status !== 'running'"
                  @click="controlService(svc.name, 'start')"
                  class="text-green-600 hover:text-green-800 text-sm"
                >
                  Start
                </button>
                <button
                  v-if="svc.status === 'running'"
                  @click="controlService(svc.name, 'stop')"
                  class="text-red-600 hover:text-red-800 text-sm"
                >
                  Stop
                </button>
                <button
                  @click="controlService(svc.name, 'restart')"
                  class="text-blue-600 hover:text-blue-800 text-sm"
                >
                  Restart
                </button>
                <button
                  @click="controlService(svc.name, 'reload')"
                  class="text-gray-600 hover:text-gray-800 text-sm"
                >
                  Reload
                </button>
              </td>
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

const services = ref([])

async function loadServices() {
  try {
    const { data } = await api.get('/api/v1/services')
    if (data.success) {
      services.value = data.data
    }
  } catch (e) {
    console.error('Failed to load services:', e)
  }
}

async function controlService(name, action) {
  try {
    await api.post(`/api/v1/services/${name}`, { action })
    await loadServices()
  } catch (e) {
    console.error(`Failed to ${action} service:`, e)
  }
}

async function toggleEnabled(svc) {
  const action = svc.enabled ? 'disable' : 'enable'
  await controlService(svc.name, action)
}

onMounted(loadServices)
</script>
