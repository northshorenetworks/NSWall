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
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'

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
