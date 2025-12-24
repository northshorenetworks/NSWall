<template>
  <div class="space-y-6">
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">System Logs</h3>
        <div class="flex items-center space-x-4">
          <select v-model="facility" @change="loadLogs" class="input w-40">
            <option value="">messages</option>
            <option value="authlog">authlog</option>
            <option value="daemon">daemon</option>
            <option value="pflog">pflog</option>
          </select>
          <select v-model="lines" @change="loadLogs" class="input w-24">
            <option :value="50">50</option>
            <option :value="100">100</option>
            <option :value="200">200</option>
            <option :value="500">500</option>
          </select>
          <button @click="loadLogs" class="btn btn-secondary">Refresh</button>
        </div>
      </div>

      <div class="bg-gray-900 text-gray-100 rounded-lg p-4 font-mono text-sm overflow-x-auto max-h-[600px] overflow-y-auto">
        <div v-for="(log, idx) in logs" :key="idx" class="py-0.5">
          <span class="text-gray-500">{{ formatTime(log.timestamp) }}</span>
          <span class="text-blue-400 ml-2">{{ log.program }}</span>
          <span class="ml-2">{{ log.message }}</span>
        </div>
        <div v-if="logs.length === 0" class="text-gray-500 text-center py-8">
          No log entries
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const logs = ref([])
const facility = ref('')
const lines = ref(100)

function formatTime(timestamp) {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  return date.toLocaleTimeString()
}

async function loadLogs() {
  try {
    const params = new URLSearchParams()
    if (facility.value) params.set('facility', facility.value)
    params.set('lines', lines.value.toString())

    const { data } = await api.get(`/api/v1/system/logs?${params}`)
    if (data.success) {
      logs.value = data.data || []
    }
  } catch (e) {
    console.error('Failed to load logs:', e)
  }
}

onMounted(loadLogs)
</script>
