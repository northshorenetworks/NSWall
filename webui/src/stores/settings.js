// Settings Store
import { defineStore } from 'pinia'

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    darkMode: false,
    sidebarCollapsed: false,
    refreshInterval: 5000, // 5 seconds
    dateFormat: 'YYYY-MM-DD HH:mm:ss',
    notifications: true,
    soundEnabled: false
  }),

  getters: {
    theme: (state) => state.darkMode ? 'dark' : 'light'
  },

  actions: {
    toggleDarkMode() {
      this.darkMode = !this.darkMode
      this.applyTheme()
    },

    setDarkMode(value) {
      this.darkMode = value
      this.applyTheme()
    },

    applyTheme() {
      if (this.darkMode) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    },

    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
    },

    setRefreshInterval(interval) {
      this.refreshInterval = interval
    },

    toggleNotifications() {
      this.notifications = !this.notifications
    },

    toggleSound() {
      this.soundEnabled = !this.soundEnabled
    }
  },

  persist: true
})
