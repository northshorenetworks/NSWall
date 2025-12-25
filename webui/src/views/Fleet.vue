<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fleet Management</h1>
      <div class="flex items-center space-x-4">
        <span class="px-3 py-1 rounded-full text-sm font-medium" :class="modeClass">
          {{ fleetMode.toUpperCase() }}
        </span>
        <button @click="showModeDialog = true" class="btn-secondary">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Configure Mode
        </button>
      </div>
    </div>

    <!-- Mode: Standalone -->
    <div v-if="fleetMode === 'standalone'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Standalone Mode</h2>
      <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-lg mx-auto">
        This appliance is running in standalone mode. Configure it as a Manager to control multiple boxes,
        or as an Agent to be managed by a central manager.
      </p>
      <button @click="showModeDialog = true" class="btn-primary">
        Configure Fleet Mode
      </button>
    </div>

    <!-- Mode: Manager -->
    <template v-else-if="fleetMode === 'manager'">
      <!-- Fleet Summary -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Total Agents</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ summary.total_agents || 0 }}</p>
            </div>
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Online</p>
              <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ summary.online || 0 }}</p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Offline</p>
              <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ summary.offline || 0 }}</p>
            </div>
            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Total States</p>
              <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ formatNumber(summary.total_states || 0) }}</p>
            </div>
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Agents Table -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Managed Agents</h2>
          <div class="flex items-center space-x-4">
            <input v-model="agentFilter" type="text" placeholder="Filter agents..."
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm w-64" />
            <button @click="loadAgents" class="btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
            <button @click="showBroadcastDialog = true" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
              </svg>
              Broadcast Config
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hostname</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Version</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">PF States</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Load</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Last Seen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="agent in filteredAgents" :key="agent.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4">
                  <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(agent.status)">
                    {{ agent.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                  {{ agent.hostname }}
                </td>
                <td class="px-6 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">
                  {{ agent.id }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ agent.version }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                  <span v-if="agent.pf_enabled" class="flex items-center">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    {{ formatNumber(agent.pf_states) }}
                  </span>
                  <span v-else class="text-gray-400">PF disabled</span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ agent.cpu_load?.toFixed(2) || '-' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ formatTime(agent.last_seen) }}
                </td>
                <td class="px-6 py-4 text-sm">
                  <div class="flex items-center space-x-2">
                    <button @click="selectAgent(agent)"
                      class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="pushConfigToAgent(agent)"
                      class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="agents.length === 0">
                <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                  No agents registered yet. Configure appliances as agents to see them here.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Events -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Events</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-64 overflow-auto">
          <div v-for="event in events" :key="event.id"
            class="px-6 py-3 flex items-start space-x-3">
            <div class="flex-shrink-0 mt-1">
              <span class="w-2 h-2 rounded-full" :class="getEventSeverityClass(event.severity)"></span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900 dark:text-white">{{ event.message }}</p>
              <div class="flex items-center space-x-4 mt-1">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ event.agent_id }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatTime(event.timestamp) }}</span>
                <span class="text-xs px-2 py-0.5 rounded" :class="getEventTypeClass(event.type)">
                  {{ event.type }}
                </span>
              </div>
            </div>
          </div>
          <div v-if="events.length === 0" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
            No events yet
          </div>
        </div>
      </div>
    </template>

    <!-- Mode: Agent -->
    <template v-else-if="fleetMode === 'agent'">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center space-x-4 mb-6">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Agent Mode</h2>
            <p class="text-gray-600 dark:text-gray-400">This appliance is managed by a central manager</p>
          </div>
        </div>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Agent ID</dt>
            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ agentId }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Manager URL</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ managerUrl || 'Not configured' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="mt-1">
              <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                Connected
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Sync</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatTime(lastSync) }}</dd>
          </div>
        </dl>
      </div>
    </template>

    <!-- Mode Configuration Dialog -->
    <div v-if="showModeDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Configure Fleet Mode</h3>
        </div>
        <div class="p-6 space-y-6">
          <div class="grid grid-cols-3 gap-4">
            <button @click="newMode = 'standalone'"
              class="p-4 rounded-lg border-2 text-center transition-all"
              :class="newMode === 'standalone' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'">
              <svg class="w-8 h-8 mx-auto mb-2" :class="newMode === 'standalone' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <div class="text-sm font-medium" :class="newMode === 'standalone' ? 'text-blue-600' : 'text-gray-600 dark:text-gray-400'">Standalone</div>
            </button>
            <button @click="newMode = 'manager'"
              class="p-4 rounded-lg border-2 text-center transition-all"
              :class="newMode === 'manager' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'">
              <svg class="w-8 h-8 mx-auto mb-2" :class="newMode === 'manager' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              <div class="text-sm font-medium" :class="newMode === 'manager' ? 'text-blue-600' : 'text-gray-600 dark:text-gray-400'">Manager</div>
            </button>
            <button @click="newMode = 'agent'"
              class="p-4 rounded-lg border-2 text-center transition-all"
              :class="newMode === 'agent' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700'">
              <svg class="w-8 h-8 mx-auto mb-2" :class="newMode === 'agent' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <div class="text-sm font-medium" :class="newMode === 'agent' ? 'text-blue-600' : 'text-gray-600 dark:text-gray-400'">Agent</div>
            </button>
          </div>

          <div v-if="newMode === 'agent'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Manager URL</label>
              <input v-model="newManagerUrl" type="text" placeholder="https://manager.example.com:8443"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key</label>
              <input v-model="newApiKey" type="password" placeholder="Enter API key from manager"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
          </div>

          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              <strong>Standalone:</strong> This appliance operates independently.<br>
              <strong>Manager:</strong> Control multiple agents from this appliance.<br>
              <strong>Agent:</strong> This appliance reports to a central manager.
            </p>
          </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-4">
          <button @click="showModeDialog = false" class="btn-secondary">Cancel</button>
          <button @click="saveMode" class="btn-primary" :disabled="savingMode">
            {{ savingMode ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Agent Details Drawer -->
    <div v-if="selectedAgent" class="fixed inset-y-0 right-0 w-96 bg-white dark:bg-gray-800 shadow-xl z-40 overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ selectedAgent.hostname }}</h3>
          <button @click="selectedAgent = null" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Agent ID</dt>
            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ selectedAgent.id }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="mt-1">
              <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(selectedAgent.status)">
                {{ selectedAgent.status }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ selectedAgent.version }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Uptime</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatUptime(selectedAgent.uptime) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PF States</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatNumber(selectedAgent.pf_states) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Load Average</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ selectedAgent.cpu_load?.toFixed(2) || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Interfaces</dt>
            <dd class="mt-1 flex flex-wrap gap-1">
              <span v-for="iface in selectedAgent.interfaces" :key="iface"
                class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">
                {{ iface }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Config Hash</dt>
            <dd class="mt-1 text-sm font-mono text-gray-500 dark:text-gray-400">{{ selectedAgent.config_hash || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Seen</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatTime(selectedAgent.last_seen) }}</dd>
          </div>
        </dl>

        <div class="mt-6 space-y-3">
          <button @click="pushConfigToAgent(selectedAgent)" class="w-full btn-primary">
            Push Configuration
          </button>
          <button @click="viewAgentLogs(selectedAgent)" class="w-full btn-secondary">
            View Logs
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api'

const toast = useToast()

const fleetMode = ref('standalone')
const agentId = ref('')
const managerUrl = ref('')
const lastSync = ref(null)
const showModeDialog = ref(false)
const showBroadcastDialog = ref(false)
const savingMode = ref(false)

const newMode = ref('standalone')
const newManagerUrl = ref('')
const newApiKey = ref('')

const agents = ref([])
const events = ref([])
const summary = reactive({})
const agentFilter = ref('')
const selectedAgent = ref(null)

let refreshInterval = null

const modeClass = computed(() => {
  const classes = {
    standalone: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    manager: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    agent: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  }
  return classes[fleetMode.value] || classes.standalone
})

const filteredAgents = computed(() => {
  if (!agentFilter.value) return agents.value
  const filter = agentFilter.value.toLowerCase()
  return agents.value.filter(a =>
    a.hostname?.toLowerCase().includes(filter) ||
    a.id?.toLowerCase().includes(filter)
  )
})

async function loadFleetMode() {
  try {
    const { data } = await api.get('/fleet/mode')
    if (data.success) {
      fleetMode.value = data.data.mode
      agentId.value = data.data.agent_id
      newMode.value = data.data.mode
    }
  } catch (err) {
    console.error('Failed to load fleet mode')
  }
}

async function loadAgents() {
  if (fleetMode.value !== 'manager') return
  try {
    const { data } = await api.get('/fleet/agents')
    if (data.success) {
      agents.value = data.data || []
    }
  } catch (err) {
    console.error('Failed to load agents')
  }
}

async function loadSummary() {
  if (fleetMode.value !== 'manager') return
  try {
    const { data } = await api.get('/fleet/summary')
    if (data.success) {
      Object.assign(summary, data.data)
    }
  } catch (err) {
    console.error('Failed to load summary')
  }
}

async function loadEvents() {
  if (fleetMode.value !== 'manager') return
  try {
    const { data } = await api.get('/fleet/events?limit=50')
    if (data.success) {
      events.value = data.data || []
    }
  } catch (err) {
    console.error('Failed to load events')
  }
}

async function saveMode() {
  savingMode.value = true
  try {
    const { data } = await api.put('/fleet/mode', {
      mode: newMode.value,
      manager_url: newManagerUrl.value,
      api_key: newApiKey.value
    })
    if (data.success) {
      fleetMode.value = newMode.value
      toast.success('Fleet mode updated')
      showModeDialog.value = false

      // Reload data if now manager
      if (newMode.value === 'manager') {
        loadAgents()
        loadSummary()
        loadEvents()
      }
    }
  } catch (err) {
    toast.error('Failed to update fleet mode')
  } finally {
    savingMode.value = false
  }
}

function selectAgent(agent) {
  selectedAgent.value = agent
}

async function pushConfigToAgent(agent) {
  // For now, just show a placeholder
  toast.info(`Push config to ${agent.hostname} - coming soon`)
}

function viewAgentLogs(agent) {
  toast.info(`View logs for ${agent.hostname} - coming soon`)
}

function getStatusClass(status) {
  const classes = {
    online: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    offline: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    degraded: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    maintenance: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
  }
  return classes[status] || classes.offline
}

function getEventSeverityClass(severity) {
  const classes = {
    info: 'bg-blue-500',
    warning: 'bg-yellow-500',
    error: 'bg-red-500',
    critical: 'bg-red-700'
  }
  return classes[severity] || classes.info
}

function getEventTypeClass(type) {
  const classes = {
    config_apply: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    config_applied: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    config_failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    heartbeat_failed: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
  }
  return classes[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

function formatNumber(num) {
  if (!num) return '0'
  return num.toLocaleString()
}

function formatTime(time) {
  if (!time) return '-'
  const d = new Date(time)
  return d.toLocaleString()
}

function formatUptime(seconds) {
  if (!seconds) return '-'
  const days = Math.floor(seconds / 86400)
  const hours = Math.floor((seconds % 86400) / 3600)
  const mins = Math.floor((seconds % 3600) / 60)
  if (days > 0) return `${days}d ${hours}h`
  if (hours > 0) return `${hours}h ${mins}m`
  return `${mins}m`
}

onMounted(async () => {
  await loadFleetMode()
  if (fleetMode.value === 'manager') {
    await Promise.all([loadAgents(), loadSummary(), loadEvents()])
    refreshInterval = setInterval(() => {
      loadAgents()
      loadSummary()
      loadEvents()
    }, 30000)
  }
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>
