<template>
  <div class="h-full flex flex-col">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Terminal</h1>
      <div class="flex space-x-2">
        <button @click="clearTerminal" class="btn-secondary text-sm">
          Clear
        </button>
        <button @click="reconnect" class="btn-secondary text-sm" :disabled="connecting">
          {{ connected ? 'Reconnect' : 'Connect' }}
        </button>
      </div>
    </div>

    <div class="flex-1 bg-gray-900 rounded-lg overflow-hidden relative">
      <!-- Connection Status -->
      <div v-if="!connected" class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-90 z-10">
        <div class="text-center">
          <svg v-if="connecting" class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-400">{{ connecting ? 'Connecting...' : 'Disconnected' }}</p>
          <button v-if="!connecting" @click="connect" class="mt-4 btn-primary">
            Connect to Terminal
          </button>
        </div>
      </div>

      <!-- Terminal Container -->
      <div ref="terminalContainer" class="h-full w-full"></div>
    </div>

    <!-- Command Help -->
    <div class="mt-4 bg-white dark:bg-gray-800 rounded-lg p-4">
      <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Quick Commands</h3>
      <div class="flex flex-wrap gap-2">
        <button v-for="cmd in quickCommands" :key="cmd.command" @click="sendCommand(cmd.command)"
          class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
          {{ cmd.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Terminal } from 'xterm'
import { FitAddon } from 'xterm-addon-fit'
import { WebLinksAddon } from 'xterm-addon-web-links'
import 'xterm/css/xterm.css'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const terminalContainer = ref(null)
const connected = ref(false)
const connecting = ref(false)

let terminal = null
let fitAddon = null
let socket = null
let resizeObserver = null

const quickCommands = [
  { label: 'show interface', command: 'show interface\n' },
  { label: 'show route', command: 'show route\n' },
  { label: 'show arp', command: 'show arp\n' },
  { label: 'show pf', command: 'show pf\n' },
  { label: 'show running-config', command: 'show running-config\n' }
]

function initTerminal() {
  terminal = new Terminal({
    cursorBlink: true,
    fontSize: 14,
    fontFamily: 'Menlo, Monaco, "Courier New", monospace',
    theme: {
      background: '#1a1b26',
      foreground: '#a9b1d6',
      cursor: '#c0caf5',
      selectionBackground: '#33467c',
      black: '#32344a',
      red: '#f7768e',
      green: '#9ece6a',
      yellow: '#e0af68',
      blue: '#7aa2f7',
      magenta: '#ad8ee6',
      cyan: '#449dab',
      white: '#787c99'
    },
    scrollback: 10000
  })

  fitAddon = new FitAddon()
  terminal.loadAddon(fitAddon)
  terminal.loadAddon(new WebLinksAddon())

  terminal.open(terminalContainer.value)
  fitAddon.fit()

  // Handle terminal input
  terminal.onData((data) => {
    if (socket && socket.readyState === WebSocket.OPEN) {
      socket.send(JSON.stringify({ type: 'input', data }))
    }
  })

  // Handle resize
  resizeObserver = new ResizeObserver(() => {
    if (fitAddon) {
      fitAddon.fit()
      if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({
          type: 'resize',
          cols: terminal.cols,
          rows: terminal.rows
        }))
      }
    }
  })
  resizeObserver.observe(terminalContainer.value)

  terminal.writeln('NSWall Terminal')
  terminal.writeln('Press "Connect to Terminal" to start a session')
  terminal.writeln('')
}

function connect() {
  if (connecting.value || connected.value) return

  connecting.value = true
  terminal.clear()
  terminal.writeln('Connecting...')

  const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
  const wsUrl = `${protocol}//${window.location.host}/api/v1/terminal`

  socket = new WebSocket(wsUrl)

  socket.onopen = () => {
    connected.value = true
    connecting.value = false
    terminal.clear()

    // Send authentication
    socket.send(JSON.stringify({
      type: 'auth',
      token: authStore.token
    }))

    // Send terminal size
    socket.send(JSON.stringify({
      type: 'resize',
      cols: terminal.cols,
      rows: terminal.rows
    }))

    terminal.focus()
  }

  socket.onmessage = (event) => {
    try {
      const msg = JSON.parse(event.data)
      if (msg.type === 'output') {
        terminal.write(msg.data)
      } else if (msg.type === 'error') {
        terminal.writeln(`\r\n\x1b[31mError: ${msg.message}\x1b[0m`)
      }
    } catch (err) {
      // Raw data
      terminal.write(event.data)
    }
  }

  socket.onclose = () => {
    connected.value = false
    connecting.value = false
    terminal.writeln('\r\n\x1b[33mConnection closed\x1b[0m')
  }

  socket.onerror = (error) => {
    connected.value = false
    connecting.value = false
    terminal.writeln('\r\n\x1b[31mConnection error\x1b[0m')
    console.error('WebSocket error:', error)
  }
}

function reconnect() {
  if (socket) {
    socket.close()
  }
  setTimeout(connect, 500)
}

function clearTerminal() {
  if (terminal) {
    terminal.clear()
  }
}

function sendCommand(command) {
  if (socket && socket.readyState === WebSocket.OPEN) {
    socket.send(JSON.stringify({ type: 'input', data: command }))
  }
}

onMounted(() => {
  nextTick(() => {
    initTerminal()
  })
})

onUnmounted(() => {
  if (socket) {
    socket.close()
  }
  if (terminal) {
    terminal.dispose()
  }
  if (resizeObserver) {
    resizeObserver.disconnect()
  }
})
</script>

<style scoped>
.h-full {
  height: calc(100vh - 200px);
}
</style>
