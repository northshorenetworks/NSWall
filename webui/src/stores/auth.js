// Authentication Store
import { defineStore } from 'pinia'
import api from '@/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false,
    error: null
  }),

  getters: {
    isAdmin: (state) => state.user?.role === 'admin',
    isOperator: (state) => ['admin', 'operator'].includes(state.user?.role),
    username: (state) => state.user?.username || 'Guest'
  },

  actions: {
    async login(username, password) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/auth/login', { username, password })
        if (response.data.success) {
          this.token = response.data.data.token
          this.user = response.data.data.user
          this.isAuthenticated = true
          api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
          return true
        }
      } catch (err) {
        this.error = err.response?.data?.error || 'Login failed'
        return false
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (err) {
        // Ignore logout errors
      }
      this.token = null
      this.user = null
      this.isAuthenticated = false
      delete api.defaults.headers.common['Authorization']
    },

    async checkAuth() {
      if (!this.token) return false
      try {
        api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        const response = await api.get('/auth/me')
        if (response.data.success) {
          this.user = response.data.data
          this.isAuthenticated = true
          return true
        }
      } catch (err) {
        this.logout()
      }
      return false
    },

    async updatePassword(currentPassword, newPassword) {
      try {
        const response = await api.post('/auth/password', {
          current_password: currentPassword,
          new_password: newPassword
        })
        return response.data.success
      } catch (err) {
        throw new Error(err.response?.data?.error || 'Password update failed')
      }
    }
  },

  persist: {
    paths: ['token', 'user']
  }
})
