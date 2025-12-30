// Notifications Store
import { defineStore } from 'pinia'

let notificationId = 0

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    maxNotifications: 50
  }),

  getters: {
    unreadCount: (state) => state.notifications.filter(n => !n.read).length,
    recent: (state) => state.notifications.slice(0, 10)
  },

  actions: {
    add(notification) {
      const id = ++notificationId
      const newNotification = {
        id,
        timestamp: new Date(),
        read: false,
        ...notification
      }

      this.notifications.unshift(newNotification)

      // Trim old notifications
      if (this.notifications.length > this.maxNotifications) {
        this.notifications = this.notifications.slice(0, this.maxNotifications)
      }

      return id
    },

    success(message, title = 'Success') {
      return this.add({ type: 'success', title, message })
    },

    error(message, title = 'Error') {
      return this.add({ type: 'error', title, message })
    },

    warning(message, title = 'Warning') {
      return this.add({ type: 'warning', title, message })
    },

    info(message, title = 'Info') {
      return this.add({ type: 'info', title, message })
    },

    markRead(id) {
      const notification = this.notifications.find(n => n.id === id)
      if (notification) {
        notification.read = true
      }
    },

    markAllRead() {
      this.notifications.forEach(n => n.read = true)
    },

    remove(id) {
      const index = this.notifications.findIndex(n => n.id === id)
      if (index > -1) {
        this.notifications.splice(index, 1)
      }
    },

    clear() {
      this.notifications = []
    }
  }
})
