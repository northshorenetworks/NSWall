<template>
  <div class="space-y-6">
    <!-- Config Actions -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">Configuration Management</h3>
        <div class="space-x-2">
          <button @click="saveConfig" class="btn btn-primary">Save Config</button>
          <button @click="createBackup" class="btn btn-secondary">Create Backup</button>
        </div>
      </div>

      <div v-if="configDiff" class="mb-4">
        <div :class="['p-3 rounded', configDiff.same ? 'bg-green-50' : 'bg-yellow-50']">
          <p :class="configDiff.same ? 'text-green-800' : 'text-yellow-800'">
            {{ configDiff.same ? 'Running config matches startup config' : 'Running config differs from startup config' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Running Config -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium">Running Configuration</h3>
        <button @click="refreshConfig" class="btn btn-secondary text-sm">Refresh</button>
      </div>
      <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm font-mono">{{ runningConfig }}</pre>
    </div>

    <!-- Backups -->
    <div class="card">
      <h3 class="text-lg font-medium mb-4">Configuration Backups</h3>
      <div v-if="backups.length === 0" class="text-gray-500 text-center py-4">
        No backups available
      </div>
      <div v-else class="space-y-2">
        <div v-for="backup in backups" :key="backup.name" class="flex items-center justify-between p-3 bg-gray-50 rounded">
          <div>
            <p class="font-medium">{{ backup.name }}</p>
            <p class="text-sm text-gray-500">{{ formatDate(backup.modified) }} · {{ formatBytes(backup.size) }}</p>
          </div>
          <div class="space-x-2">
            <button @click="restoreBackup(backup.name)" class="text-blue-600 hover:text-blue-800 text-sm">Restore</button>
            <button @click="deleteBackup(backup.name)" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const runningConfig = ref('')
const configDiff = ref(null)
const backups = ref([])

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB']
  let i = 0
  while (bytes >= 1024 && i < units.length - 1) {
    bytes /= 1024
    i++
  }
  return `${bytes.toFixed(1)} ${units[i]}`
}

function formatDate(date) {
  return new Date(date).toLocaleString()
}

async function loadData() {
  try {
    const [configRes, diffRes, backupsRes] = await Promise.all([
      api.get('/api/v1/config/running'),
      api.get('/api/v1/config/diff'),
      api.get('/api/v1/config/backups')
    ])
    if (configRes.data.success) runningConfig.value = configRes.data.data?.raw || ''
    if (diffRes.data.success) configDiff.value = diffRes.data.data
    if (backupsRes.data.success) backups.value = backupsRes.data.data || []
  } catch (e) {
    console.error('Failed to load config:', e)
  }
}

async function refreshConfig() {
  await loadData()
}

async function saveConfig() {
  try {
    await api.post('/api/v1/config/save')
    await loadData()
    alert('Configuration saved!')
  } catch (e) {
    console.error('Failed to save config:', e)
    alert('Failed to save configuration')
  }
}

async function createBackup() {
  const name = prompt('Backup name (optional):')
  try {
    await api.post('/api/v1/config/backups', { name })
    await loadData()
  } catch (e) {
    console.error('Failed to create backup:', e)
  }
}

async function restoreBackup(name) {
  if (!confirm(`Restore backup "${name}"? This will overwrite current configuration.`)) return
  try {
    await api.post(`/api/v1/config/backups/${name}/restore`)
    await loadData()
    alert('Backup restored!')
  } catch (e) {
    console.error('Failed to restore backup:', e)
  }
}

async function deleteBackup(name) {
  if (!confirm(`Delete backup "${name}"?`)) return
  try {
    await api.delete(`/api/v1/config/backups/${name}`)
    await loadData()
  } catch (e) {
    console.error('Failed to delete backup:', e)
  }
}

onMounted(loadData)
</script>
