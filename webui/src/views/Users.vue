<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">User Management</h1>
      <button @click="showAddUser = true" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add User
      </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Username</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Last Login</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="user in users" :key="user.username" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium">
                    {{ user.username.charAt(0).toUpperCase() }}
                  </div>
                  <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ user.username }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ user.email || '-' }}</td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="getRoleBadgeClass(user.role)">
                  {{ user.role }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                {{ user.last_login ? formatDate(user.last_login) : 'Never' }}
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="user.enabled ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                  {{ user.enabled ? 'Active' : 'Disabled' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm space-x-3">
                <button @click="editUser(user)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Edit</button>
                <button @click="resetPassword(user)" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400">Reset Password</button>
                <button v-if="user.username !== authStore.username" @click="toggleUser(user)"
                  :class="user.enabled ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'">
                  {{ user.enabled ? 'Disable' : 'Enable' }}
                </button>
              </td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                No users found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- API Keys Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">API Keys</h2>
        <button @click="showAddApiKey = true" class="btn-secondary text-sm">Generate New Key</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Key Prefix</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Created</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Last Used</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="key in apiKeys" :key="key.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ key.name }}</td>
              <td class="px-6 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">{{ key.prefix }}...</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(key.created_at) }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ key.last_used ? formatDate(key.last_used) : 'Never' }}</td>
              <td class="px-6 py-4 text-sm">
                <button @click="revokeApiKey(key)" class="text-red-600 hover:text-red-800">Revoke</button>
              </td>
            </tr>
            <tr v-if="apiKeys.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                No API keys configured
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add User Modal -->
    <div v-if="showAddUser" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ editingUser ? 'Edit User' : 'Add User' }}</h3>
        </div>
        <form @submit.prevent="saveUser" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
            <input v-model="userForm.username" type="text" required :disabled="editingUser"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
            <input v-model="userForm.email" type="email"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div v-if="!editingUser">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
            <input v-model="userForm.password" type="password" required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
            <select v-model="userForm.role"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option value="viewer">Viewer</option>
              <option value="operator">Operator</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="showAddUser = false; editingUser = null" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary">{{ editingUser ? 'Save' : 'Create' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()

const users = ref([])
const apiKeys = ref([])
const showAddUser = ref(false)
const showAddApiKey = ref(false)
const editingUser = ref(null)

const userForm = ref({
  username: '',
  email: '',
  password: '',
  role: 'viewer'
})

async function fetchData() {
  try {
    const [usersRes, keysRes] = await Promise.all([
      api.get('/users'),
      api.get('/auth/keys')
    ])

    if (usersRes.data.success) {
      users.value = usersRes.data.data || []
    }

    if (keysRes.data.success) {
      apiKeys.value = keysRes.data.data || []
    }
  } catch (err) {
    console.error('Failed to fetch users:', err)
  }
}

async function saveUser() {
  try {
    if (editingUser.value) {
      await api.put(`/users/${editingUser.value.username}`, userForm.value)
      toast.success('User updated')
    } else {
      await api.post('/users', userForm.value)
      toast.success('User created')
    }
    showAddUser.value = false
    editingUser.value = null
    resetForm()
    await fetchData()
  } catch (err) {
    toast.error(err.response?.data?.error || 'Failed to save user')
  }
}

function editUser(user) {
  editingUser.value = user
  userForm.value = {
    username: user.username,
    email: user.email || '',
    password: '',
    role: user.role
  }
  showAddUser.value = true
}

async function toggleUser(user) {
  const action = user.enabled ? 'disable' : 'enable'
  if (!confirm(`Are you sure you want to ${action} user "${user.username}"?`)) return

  try {
    await api.post(`/users/${user.username}/${action}`)
    toast.success(`User ${action}d`)
    await fetchData()
  } catch (err) {
    toast.error(`Failed to ${action} user`)
  }
}

async function resetPassword(user) {
  const newPassword = prompt(`Enter new password for ${user.username}:`)
  if (!newPassword) return

  try {
    await api.post(`/users/${user.username}/password`, { password: newPassword })
    toast.success('Password reset successfully')
  } catch (err) {
    toast.error('Failed to reset password')
  }
}

async function revokeApiKey(key) {
  if (!confirm(`Revoke API key "${key.name}"?`)) return

  try {
    await api.delete(`/auth/keys/${key.id}`)
    toast.success('API key revoked')
    await fetchData()
  } catch (err) {
    toast.error('Failed to revoke API key')
  }
}

function resetForm() {
  userForm.value = {
    username: '',
    email: '',
    password: '',
    role: 'viewer'
  }
}

function getRoleBadgeClass(role) {
  const classes = {
    admin: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    operator: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    viewer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
  }
  return classes[role] || classes.viewer
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString()
}

onMounted(() => {
  fetchData()
})
</script>
