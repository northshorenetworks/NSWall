<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>

    <!-- Appearance -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Appearance</h2>
      </div>
      <div class="p-6 space-y-6">
        <!-- Dark Mode Toggle -->
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Dark Mode</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Use dark theme for the dashboard</p>
          </div>
          <button
            @click="settingsStore.toggleDarkMode()"
            :class="[
              settingsStore.darkMode ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                settingsStore.darkMode ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- Sidebar Collapsed -->
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Compact Sidebar</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Minimize the sidebar to save space</p>
          </div>
          <button
            @click="settingsStore.toggleSidebar()"
            :class="[
              settingsStore.sidebarCollapsed ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                settingsStore.sidebarCollapsed ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- AI Analysis Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">AI Analysis</h2>
          </div>
          <span v-if="aiConfig.has_api_key" class="flex items-center text-sm text-green-600 dark:text-green-400">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Configured
          </span>
          <span v-else class="flex items-center text-sm text-yellow-600 dark:text-yellow-400">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Not configured
          </span>
        </div>
      </div>
      <div class="p-6 space-y-6">
        <!-- Provider Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">AI Provider</label>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <button v-for="provider in providers" :key="provider.id"
              @click="selectProvider(provider)"
              class="p-3 rounded-lg border-2 transition-all text-center"
              :class="aiConfig.provider === provider.id
                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
              <div class="text-sm font-medium" :class="aiConfig.provider === provider.id ? 'text-purple-600 dark:text-purple-400' : 'text-gray-900 dark:text-white'">
                {{ provider.name }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ provider.requires_key ? 'API Key' : 'Local' }}
              </div>
            </button>
          </div>
        </div>

        <!-- Model Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Model</label>
          <div class="flex space-x-4">
            <select v-model="aiConfig.model"
              class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option v-for="model in availableModels" :key="model.id" :value="model.id">
                {{ model.name }} {{ model.description ? `- ${model.description}` : '' }}
              </option>
            </select>
            <button v-if="aiConfig.provider === 'ollama'" @click="refreshOllamaModels"
              class="btn-secondary" :disabled="loadingModels">
              <svg class="w-4 h-4" :class="{ 'animate-spin': loadingModels }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>
        </div>

        <!-- API Key (for cloud providers) -->
        <div v-if="selectedProvider?.requires_key">
          <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
            API Key
            <span class="text-gray-500 font-normal ml-2">(stored securely on server)</span>
          </label>
          <div class="flex space-x-4">
            <div class="flex-1 relative">
              <input
                v-model="apiKey"
                :type="showApiKey ? 'text' : 'password'"
                :placeholder="aiConfig.has_api_key ? '••••••••••••••••' : 'Enter your API key'"
                class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
              />
              <button @click="showApiKey = !showApiKey"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg v-if="showApiKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>
          </div>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Get your API key from:
            <a v-if="aiConfig.provider === 'anthropic'" href="https://console.anthropic.com" target="_blank" class="text-blue-600 hover:underline">console.anthropic.com</a>
            <a v-else-if="aiConfig.provider === 'openai'" href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:underline">platform.openai.com</a>
            <a v-else-if="aiConfig.provider === 'google'" href="https://makersuite.google.com/app/apikey" target="_blank" class="text-blue-600 hover:underline">makersuite.google.com</a>
            <span v-else>your provider's console</span>
          </p>
        </div>

        <!-- API Endpoint (for Ollama/custom) -->
        <div v-if="aiConfig.provider === 'ollama' || aiConfig.provider === 'custom' || aiConfig.provider === 'azure'">
          <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
            API Endpoint
          </label>
          <input
            v-model="aiConfig.api_endpoint"
            type="text"
            :placeholder="selectedProvider?.default_url || 'Enter API endpoint URL'"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          />
          <p v-if="aiConfig.provider === 'ollama'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Default: http://localhost:11434/api/chat. Make sure Ollama is running.
          </p>
        </div>

        <!-- Advanced Settings -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
          <button @click="showAdvancedAI = !showAdvancedAI"
            class="flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-4 h-4 mr-1 transition-transform" :class="{ 'rotate-90': showAdvancedAI }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            Advanced Settings
          </button>

          <div v-if="showAdvancedAI" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Max Tokens</label>
              <input v-model.number="aiConfig.max_tokens" type="number" min="256" max="32000"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maximum response length</p>
            </div>
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Temperature</label>
              <input v-model.number="aiConfig.temperature" type="number" min="0" max="2" step="0.1"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">0 = focused, 1 = creative</p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
          <button @click="testAIConnection" :disabled="testingConnection" class="btn-secondary">
            <svg v-if="testingConnection" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ testingConnection ? 'Testing...' : 'Test Connection' }}
          </button>
          <button @click="saveAIConfig" :disabled="savingConfig" class="btn-primary">
            <svg v-if="savingConfig" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ savingConfig ? 'Saving...' : 'Save Configuration' }}
          </button>
        </div>

        <!-- Test Result -->
        <div v-if="testResult" class="p-4 rounded-lg" :class="testResult.success ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
          <div class="flex items-start">
            <svg v-if="testResult.success" class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <div>
              <p class="font-medium" :class="testResult.success ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
                {{ testResult.success ? 'Connection Successful' : 'Connection Failed' }}
              </p>
              <p class="text-sm mt-1" :class="testResult.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                {{ testResult.message }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dashboard Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Dashboard</h2>
      </div>
      <div class="p-6 space-y-6">
        <!-- Refresh Interval -->
        <div>
          <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Auto-refresh Interval</label>
          <select v-model="refreshInterval" @change="settingsStore.setRefreshInterval(refreshInterval)"
            class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option :value="0">Disabled</option>
            <option :value="5000">5 seconds</option>
            <option :value="10000">10 seconds</option>
            <option :value="30000">30 seconds</option>
            <option :value="60000">1 minute</option>
            <option :value="300000">5 minutes</option>
          </select>
        </div>

        <!-- Notifications -->
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Desktop Notifications</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Show browser notifications for alerts</p>
          </div>
          <button
            @click="toggleNotifications()"
            :class="[
              settingsStore.notifications ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                settingsStore.notifications ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Profile Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Profile</h2>
      </div>
      <div class="p-6 space-y-6">
        <div class="flex items-center space-x-4">
          <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-medium">
            {{ authStore.username.charAt(0).toUpperCase() }}
          </div>
          <div>
            <p class="text-lg font-medium text-gray-900 dark:text-white">{{ authStore.username }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ authStore.user?.email || 'No email set' }}</p>
            <span class="inline-block mt-1 px-2 py-1 text-xs font-medium rounded-full"
              :class="getRoleBadgeClass(authStore.user?.role)">
              {{ authStore.user?.role }}
            </span>
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Change Password</h3>
          <form @submit.prevent="changePassword" class="space-y-4 max-w-md">
            <div>
              <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Current Password</label>
              <input v-model="passwordForm.current" type="password" required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">New Password</label>
              <input v-model="passwordForm.new" type="password" required minlength="8"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Confirm New Password</label>
              <input v-model="passwordForm.confirm" type="password" required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <button type="submit" class="btn-primary" :disabled="changingPassword">
              {{ changingPassword ? 'Changing...' : 'Change Password' }}
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Session Info -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Session</h2>
      </div>
      <div class="p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Session Started</dt>
            <dd class="text-sm text-gray-900 dark:text-white">{{ sessionStart }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
            <dd class="text-sm text-gray-900 dark:text-white">{{ sessionIP }}</dd>
          </div>
        </dl>
        <button @click="logout" class="mt-4 btn-secondary text-red-600 border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
          Sign Out
        </button>
      </div>
    </div>

    <!-- About -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">About NSWall</h2>
      </div>
      <div class="p-6">
        <dl class="space-y-2">
          <div class="flex justify-between">
            <dt class="text-sm text-gray-500 dark:text-gray-400">Version</dt>
            <dd class="text-sm text-gray-900 dark:text-white">2.0.0</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm text-gray-500 dark:text-gray-400">Based on</dt>
            <dd class="text-sm text-gray-900 dark:text-white">NSH (Network Shell)</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm text-gray-500 dark:text-gray-400">License</dt>
            <dd class="text-sm text-gray-900 dark:text-white">BSD</dd>
          </div>
        </dl>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
          NSWall transforms OpenBSD into an enterprise-ready network appliance with a CLI, REST API, and Web UI.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const router = useRouter()
const toast = useToast()
const settingsStore = useSettingsStore()
const authStore = useAuthStore()

const refreshInterval = ref(settingsStore.refreshInterval)
const changingPassword = ref(false)
const passwordForm = ref({
  current: '',
  new: '',
  confirm: ''
})

// AI Configuration
const providers = ref([])
const aiConfig = reactive({
  provider: 'anthropic',
  model: 'claude-3-haiku-20240307',
  api_endpoint: '',
  max_tokens: 4096,
  temperature: 0.7,
  has_api_key: false
})
const apiKey = ref('')
const showApiKey = ref(false)
const showAdvancedAI = ref(false)
const loadingModels = ref(false)
const testingConnection = ref(false)
const savingConfig = ref(false)
const testResult = ref(null)
const ollamaModels = ref([])

const selectedProvider = computed(() => providers.value.find(p => p.id === aiConfig.provider))

const availableModels = computed(() => {
  if (aiConfig.provider === 'ollama' && ollamaModels.value.length > 0) {
    return ollamaModels.value
  }
  return selectedProvider.value?.models || []
})

async function loadProviders() {
  try {
    const { data } = await api.get('/ai/providers')
    if (data.success) {
      providers.value = data.data
    }
  } catch (err) {
    console.error('Failed to load AI providers')
  }
}

async function loadAIConfig() {
  try {
    const { data } = await api.get('/ai/config')
    if (data.success) {
      Object.assign(aiConfig, data.data)
    }
  } catch (err) {
    console.error('Failed to load AI config')
  }
}

function selectProvider(provider) {
  aiConfig.provider = provider.id
  // Set default model for provider
  if (provider.models?.length > 0) {
    aiConfig.model = provider.models[0].id
  }
  // Set default endpoint
  if (provider.default_url) {
    aiConfig.api_endpoint = provider.default_url
  }
  testResult.value = null
}

async function refreshOllamaModels() {
  loadingModels.value = true
  try {
    const endpoint = aiConfig.api_endpoint || 'http://localhost:11434'
    const { data } = await api.get('/ai/ollama/models', { params: { endpoint } })
    if (data.success) {
      ollamaModels.value = data.data
      if (data.data.length > 0 && !data.data.find(m => m.id === aiConfig.model)) {
        aiConfig.model = data.data[0].id
      }
      toast.success(`Found ${data.data.length} models`)
    }
  } catch (err) {
    toast.error('Failed to fetch Ollama models. Is Ollama running?')
  } finally {
    loadingModels.value = false
  }
}

async function testAIConnection() {
  testingConnection.value = true
  testResult.value = null

  // Save config first if API key provided
  if (apiKey.value) {
    await saveAIConfig()
  }

  try {
    const { data } = await api.post('/ai/test')
    testResult.value = {
      success: data.success,
      message: data.success
        ? `Connected to ${data.model} via ${data.provider}`
        : data.error
    }
  } catch (err) {
    testResult.value = {
      success: false,
      message: err.response?.data?.error || err.message
    }
  } finally {
    testingConnection.value = false
  }
}

async function saveAIConfig() {
  savingConfig.value = true
  try {
    const config = {
      provider: aiConfig.provider,
      model: aiConfig.model,
      api_endpoint: aiConfig.api_endpoint,
      max_tokens: aiConfig.max_tokens,
      temperature: aiConfig.temperature
    }
    if (apiKey.value) {
      config.api_key = apiKey.value
    }

    const { data } = await api.put('/ai/config', config)
    if (data.success) {
      toast.success('AI configuration saved')
      apiKey.value = ''
      await loadAIConfig() // Refresh to get has_api_key status
    }
  } catch (err) {
    toast.error('Failed to save AI configuration')
  } finally {
    savingConfig.value = false
  }
}

onMounted(async () => {
  await loadProviders()
  await loadAIConfig()
})

const sessionStart = computed(() => {
  // In a real app, this would come from the auth store
  return new Date().toLocaleString()
})

const sessionIP = computed(() => {
  return 'Current session'
})

async function toggleNotifications() {
  if (!settingsStore.notifications && 'Notification' in window) {
    const permission = await Notification.requestPermission()
    if (permission === 'granted') {
      settingsStore.toggleNotifications()
    } else {
      toast.error('Notification permission denied')
    }
  } else {
    settingsStore.toggleNotifications()
  }
}

async function changePassword() {
  if (passwordForm.value.new !== passwordForm.value.confirm) {
    toast.error('Passwords do not match')
    return
  }

  if (passwordForm.value.new.length < 8) {
    toast.error('Password must be at least 8 characters')
    return
  }

  changingPassword.value = true
  try {
    await authStore.updatePassword(passwordForm.value.current, passwordForm.value.new)
    toast.success('Password changed successfully')
    passwordForm.value = { current: '', new: '', confirm: '' }
  } catch (err) {
    toast.error(err.message || 'Failed to change password')
  } finally {
    changingPassword.value = false
  }
}

function logout() {
  authStore.logout()
  router.push('/login')
}

function getRoleBadgeClass(role) {
  const classes = {
    admin: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    operator: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
  }
  return classes[role] || classes.viewer
}
</script>
