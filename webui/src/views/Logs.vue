<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Logs</h1>
      <div class="flex items-center space-x-2">
        <!-- Auto-refresh toggle -->
        <label class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
          <input type="checkbox" v-model="autoRefresh" class="rounded" />
          <span>Auto-refresh</span>
        </label>
        <button @click="exportLogs" class="btn-secondary text-sm">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export
        </button>
      </div>
    </div>

    <!-- Log Type Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="flex space-x-8">
        <button
          v-for="tab in logTabs"
          :key="tab.id"
          @click="activeTab = tab.id; loadLogs()"
          :class="[
            'py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap flex items-center',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
          ]"
        >
          <component :is="tab.icon" class="w-4 h-4 mr-2" />
          {{ tab.name }}
          <span v-if="tab.count" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700">
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Search -->
        <div class="lg:col-span-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search logs..."
              @input="debouncedSearch"
              class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
            />
            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Log Level -->
        <div v-if="activeTab !== 'firewall'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level</label>
          <select v-model="levelFilter" @change="loadLogs"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="">All Levels</option>
            <option value="debug">Debug</option>
            <option value="info">Info</option>
            <option value="warn">Warning</option>
            <option value="error">Error</option>
            <option value="fatal">Fatal</option>
          </select>
        </div>

        <!-- Facility (System logs) -->
        <div v-if="activeTab === 'system'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Facility</label>
          <select v-model="facility" @change="loadLogs"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="">All</option>
            <option value="messages">messages</option>
            <option value="authlog">authlog</option>
            <option value="daemon">daemon</option>
            <option value="maillog">maillog</option>
            <option value="secure">secure</option>
          </select>
        </div>

        <!-- Event Type (Audit logs) -->
        <div v-if="activeTab === 'audit'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Type</label>
          <select v-model="eventType" @change="loadLogs"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="">All Events</option>
            <optgroup label="Authentication">
              <option value="LOGIN">Login</option>
              <option value="LOGIN_FAILED">Login Failed</option>
              <option value="LOGOUT">Logout</option>
            </optgroup>
            <optgroup label="Configuration">
              <option value="CONFIG_CHANGED">Config Changed</option>
              <option value="CONFIG_BACKUP">Config Backup</option>
              <option value="CONFIG_RESTORE">Config Restore</option>
            </optgroup>
            <optgroup label="Firewall">
              <option value="PF_RULE_ADDED">PF Rule Added</option>
              <option value="PF_RULE_REMOVED">PF Rule Removed</option>
              <option value="PF_TABLE_UPDATE">PF Table Update</option>
            </optgroup>
            <optgroup label="Services">
              <option value="SERVICE_START">Service Start</option>
              <option value="SERVICE_STOP">Service Stop</option>
              <option value="SERVICE_RESTART">Service Restart</option>
            </optgroup>
          </select>
        </div>

        <!-- Time Range -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Range</label>
          <select v-model="timeRange" @change="loadLogs"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="15m">Last 15 minutes</option>
            <option value="1h">Last hour</option>
            <option value="6h">Last 6 hours</option>
            <option value="24h">Last 24 hours</option>
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="custom">Custom range</option>
          </select>
        </div>

        <!-- Lines -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Limit</label>
          <select v-model="lines" @change="loadLogs"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option :value="50">50 lines</option>
            <option :value="100">100 lines</option>
            <option :value="250">250 lines</option>
            <option :value="500">500 lines</option>
            <option :value="1000">1000 lines</option>
          </select>
        </div>
      </div>

      <!-- Custom Date Range -->
      <div v-if="timeRange === 'custom'" class="mt-4 flex items-center space-x-4">
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">From</label>
          <input type="datetime-local" v-model="customFrom"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">To</label>
          <input type="datetime-local" v-model="customTo"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <button @click="loadLogs" class="btn-primary mt-6">Apply</button>
      </div>
    </div>

    <!-- Log Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" v-if="stats">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Entries</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Errors</p>
        <p class="text-2xl font-semibold text-red-600">{{ stats.errors }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Warnings</p>
        <p class="text-2xl font-semibold text-yellow-600">{{ stats.warnings }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Rate</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.rate }}/min</p>
      </div>
    </div>

    <!-- Log Viewer -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <span class="text-sm text-gray-500 dark:text-gray-400">
          Showing {{ filteredLogs.length }} of {{ logs.length }} entries
        </span>
        <button @click="loadLogs" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
          <svg class="w-4 h-4 mr-1" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
      </div>

      <!-- System/Access Logs -->
      <div v-if="activeTab === 'system' || activeTab === 'access'"
        class="bg-gray-900 text-gray-100 font-mono text-sm overflow-x-auto max-h-[600px] overflow-y-auto">
        <table class="min-w-full">
          <tbody>
            <tr v-for="(log, idx) in filteredLogs" :key="idx"
              class="hover:bg-gray-800 border-b border-gray-800">
              <td class="px-4 py-2 text-gray-500 whitespace-nowrap w-44">{{ formatTime(log.timestamp) }}</td>
              <td class="px-2 py-2 whitespace-nowrap w-20">
                <span :class="getLevelClass(log.level || log.severity)">
                  {{ (log.level || log.severity || 'info').toUpperCase() }}
                </span>
              </td>
              <td class="px-2 py-2 text-blue-400 whitespace-nowrap w-32">{{ log.program || log.component || '-' }}</td>
              <td class="px-2 py-2 text-gray-300">
                <span v-html="highlightSearch(log.message)"></span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="filteredLogs.length === 0" class="text-gray-500 text-center py-12">
          No log entries found
        </div>
      </div>

      <!-- Audit Logs -->
      <div v-if="activeTab === 'audit'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Time</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Event</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">User</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Result</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Message</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Details</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(log, idx) in filteredLogs" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                {{ formatTime(log.timestamp) }}
              </td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-medium rounded" :class="getEventTypeClass(log.event_type)">
                  {{ log.event_type }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ log.username || '-' }}</td>
              <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ log.client_ip || '-' }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="log.result === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                  {{ log.result }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white max-w-md truncate">
                {{ log.message }}
              </td>
              <td class="px-4 py-3">
                <button v-if="log.details" @click="showDetails(log)"
                  class="text-blue-600 hover:text-blue-800 text-sm">View</button>
              </td>
            </tr>
            <tr v-if="filteredLogs.length === 0">
              <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                No audit events found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Firewall Logs -->
      <div v-if="activeTab === 'firewall'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Time</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Action</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Interface</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Protocol</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Source</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Destination</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rule</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(log, idx) in filteredLogs" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                {{ formatTime(log.timestamp) }}
              </td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="log.action === 'pass' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                  {{ log.action }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ log.interface }}</td>
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ log.protocol }}</td>
              <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">
                {{ log.src_ip }}:{{ log.src_port }}
              </td>
              <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">
                {{ log.dst_ip }}:{{ log.dst_port }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ log.rule || '-' }}</td>
            </tr>
            <tr v-if="filteredLogs.length === 0">
              <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                No firewall logs found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- API Logs -->
      <div v-if="activeTab === 'api'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Time</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Method</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Path</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Duration</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Client IP</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">User</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(log, idx) in filteredLogs" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                {{ formatTime(log.timestamp) }}
              </td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-medium rounded" :class="getMethodClass(log.method)">
                  {{ log.method }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ log.path }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(log.status_code)">
                  {{ log.status_code }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ log.duration_ms?.toFixed(2) }}ms</td>
              <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ log.client_ip }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ log.user_id || '-' }}</td>
            </tr>
            <tr v-if="filteredLogs.length === 0">
              <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                No API logs found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedLog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Log Details</h3>
          <button @click="selectedLog = null" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[60vh]">
          <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg text-sm overflow-x-auto">{{ JSON.stringify(selectedLog, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue'
import api from '@/api'
import { useToast } from 'vue-toastification'

const toast = useToast()

// Icons
const SystemIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
])
const AuditIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })
])
const FirewallIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z' })
])
const APIIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
])

const logTabs = [
  { id: 'system', name: 'System', icon: SystemIcon },
  { id: 'audit', name: 'Audit', icon: AuditIcon },
  { id: 'firewall', name: 'Firewall', icon: FirewallIcon },
  { id: 'api', name: 'API Access', icon: APIIcon }
]

const activeTab = ref('system')
const logs = ref([])
const loading = ref(false)
const autoRefresh = ref(false)
const selectedLog = ref(null)

// Filters
const searchQuery = ref('')
const levelFilter = ref('')
const facility = ref('')
const eventType = ref('')
const timeRange = ref('1h')
const customFrom = ref('')
const customTo = ref('')
const lines = ref(100)

// Stats
const stats = ref(null)

let refreshInterval = null
let searchTimeout = null

const filteredLogs = computed(() => {
  let result = logs.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(log => {
      const message = (log.message || '').toLowerCase()
      const program = (log.program || log.component || '').toLowerCase()
      const user = (log.username || log.user_id || '').toLowerCase()
      return message.includes(query) || program.includes(query) || user.includes(query)
    })
  }

  return result
})

function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    // Local filtering is instant, but could trigger API search for large logs
  }, 300)
}

async function loadLogs() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.set('lines', lines.value.toString())

    if (timeRange.value !== 'custom') {
      params.set('range', timeRange.value)
    } else if (customFrom.value && customTo.value) {
      params.set('from', new Date(customFrom.value).toISOString())
      params.set('to', new Date(customTo.value).toISOString())
    }

    let endpoint = '/system/logs'

    switch (activeTab.value) {
      case 'system':
        if (facility.value) params.set('facility', facility.value)
        if (levelFilter.value) params.set('level', levelFilter.value)
        break
      case 'audit':
        endpoint = '/audit/events'
        if (eventType.value) params.set('type', eventType.value)
        break
      case 'firewall':
        endpoint = '/firewall/logs'
        break
      case 'api':
        endpoint = '/logs/api'
        if (levelFilter.value) params.set('level', levelFilter.value)
        break
    }

    const { data } = await api.get(`${endpoint}?${params}`)
    if (data.success) {
      logs.value = data.data || []

      // Calculate stats
      const errors = logs.value.filter(l => l.level === 'error' || l.level === 'ERROR' || l.severity === 'error').length
      const warnings = logs.value.filter(l => l.level === 'warn' || l.level === 'WARN' || l.severity === 'warning').length
      stats.value = {
        total: logs.value.length,
        errors,
        warnings,
        rate: Math.round(logs.value.length / (parseTimeRange(timeRange.value) / 60000)) || 0
      }
    }
  } catch (e) {
    console.error('Failed to load logs:', e)
    toast.error('Failed to load logs')
  } finally {
    loading.value = false
  }
}

function parseTimeRange(range) {
  const match = range.match(/(\d+)([mhd])/)
  if (!match) return 3600000 // default 1 hour
  const value = parseInt(match[1])
  const unit = match[2]
  switch (unit) {
    case 'm': return value * 60000
    case 'h': return value * 3600000
    case 'd': return value * 86400000
    default: return 3600000
  }
}

function formatTime(timestamp) {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  return date.toLocaleString()
}

function getLevelClass(level) {
  const classes = {
    debug: 'text-gray-400',
    DEBUG: 'text-gray-400',
    info: 'text-blue-400',
    INFO: 'text-blue-400',
    warn: 'text-yellow-400',
    WARN: 'text-yellow-400',
    warning: 'text-yellow-400',
    WARNING: 'text-yellow-400',
    error: 'text-red-400',
    ERROR: 'text-red-400',
    fatal: 'text-red-600 font-bold',
    FATAL: 'text-red-600 font-bold',
    critical: 'text-red-600 font-bold',
    CRITICAL: 'text-red-600 font-bold'
  }
  return classes[level] || 'text-gray-400'
}

function getEventTypeClass(eventType) {
  if (eventType?.includes('FAILED') || eventType?.includes('DENIED')) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  if (eventType?.includes('LOGIN') || eventType?.includes('LOGOUT')) {
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
  }
  if (eventType?.includes('CONFIG')) {
    return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
  }
  if (eventType?.includes('SERVICE')) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

function getMethodClass(method) {
  const classes = {
    GET: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    POST: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    PUT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    PATCH: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    DELETE: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  return classes[method] || 'bg-gray-100 text-gray-800'
}

function getStatusClass(status) {
  if (status >= 500) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  if (status >= 400) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
  if (status >= 300) return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
  return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
}

function highlightSearch(text) {
  if (!searchQuery.value || !text) return text
  const regex = new RegExp(`(${searchQuery.value})`, 'gi')
  return text.replace(regex, '<mark class="bg-yellow-300 dark:bg-yellow-600 text-black dark:text-white">$1</mark>')
}

function showDetails(log) {
  selectedLog.value = log
}

async function exportLogs() {
  try {
    const data = JSON.stringify(filteredLogs.value, null, 2)
    const blob = new Blob([data], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `nswall-${activeTab.value}-logs-${new Date().toISOString().split('T')[0]}.json`
    a.click()
    URL.revokeObjectURL(url)
    toast.success('Logs exported')
  } catch (e) {
    toast.error('Failed to export logs')
  }
}

onMounted(() => {
  loadLogs()
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})

// Watch for auto-refresh changes
import { watch } from 'vue'
watch(autoRefresh, (enabled) => {
  if (enabled) {
    refreshInterval = setInterval(loadLogs, 5000)
  } else if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
})
</script>
