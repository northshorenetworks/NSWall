// WebSocket Store for real-time updates
import { defineStore } from 'pinia'
import { useAuthStore } from './auth'

export const useWebSocketStore = defineStore('websocket', {
  state: () => ({
    socket: null,
    connected: false,
    reconnecting: false,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    reconnectDelay: 1000,
    subscriptions: new Map(),
    lastMessage: null
  }),

  actions: {
    connect() {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return

      const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
      const wsUrl = `${protocol}//${window.location.host}/api/v1/ws`

      try {
        this.socket = new WebSocket(wsUrl)

        this.socket.onopen = () => {
          this.connected = true
          this.reconnecting = false
          this.reconnectAttempts = 0
          console.log('WebSocket connected')

          // Authenticate
          this.send({ type: 'auth', token: authStore.token })

          // Resubscribe to all topics
          this.subscriptions.forEach((callbacks, topic) => {
            this.send({ type: 'subscribe', topic })
          })
        }

        this.socket.onmessage = (event) => {
          try {
            const message = JSON.parse(event.data)
            this.lastMessage = message
            this.handleMessage(message)
          } catch (err) {
            console.error('Failed to parse WebSocket message:', err)
          }
        }

        this.socket.onclose = () => {
          this.connected = false
          console.log('WebSocket disconnected')
          this.attemptReconnect()
        }

        this.socket.onerror = (error) => {
          console.error('WebSocket error:', error)
        }
      } catch (err) {
        console.error('Failed to create WebSocket:', err)
      }
    },

    disconnect() {
      if (this.socket) {
        this.socket.close()
        this.socket = null
      }
      this.connected = false
      this.subscriptions.clear()
    },

    attemptReconnect() {
      if (this.reconnecting || this.reconnectAttempts >= this.maxReconnectAttempts) {
        return
      }

      this.reconnecting = true
      this.reconnectAttempts++

      const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1)
      console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`)

      setTimeout(() => {
        this.reconnecting = false
        this.connect()
      }, delay)
    },

    send(data) {
      if (this.socket && this.connected) {
        this.socket.send(JSON.stringify(data))
      }
    },

    subscribe(topic, callback) {
      if (!this.subscriptions.has(topic)) {
        this.subscriptions.set(topic, [])
        if (this.connected) {
          this.send({ type: 'subscribe', topic })
        }
      }
      this.subscriptions.get(topic).push(callback)

      // Return unsubscribe function
      return () => {
        const callbacks = this.subscriptions.get(topic)
        const index = callbacks.indexOf(callback)
        if (index > -1) {
          callbacks.splice(index, 1)
        }
        if (callbacks.length === 0) {
          this.subscriptions.delete(topic)
          if (this.connected) {
            this.send({ type: 'unsubscribe', topic })
          }
        }
      }
    },

    handleMessage(message) {
      const { topic, data } = message
      if (topic && this.subscriptions.has(topic)) {
        this.subscriptions.get(topic).forEach(callback => {
          try {
            callback(data)
          } catch (err) {
            console.error('Error in WebSocket callback:', err)
          }
        })
      }
    }
  }
})
