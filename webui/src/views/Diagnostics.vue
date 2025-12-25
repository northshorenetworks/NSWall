<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Diagnostics</h1>
    </div>

    <!-- Tool Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="flex space-x-8 overflow-x-auto">
        <button
          v-for="tool in tools"
          :key="tool.id"
          @click="activeTool = tool.id"
          :class="[
            'py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
            activeTool === tool.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
          ]"
        >
          {{ tool.name }}
        </button>
      </nav>
    </div>

    <!-- Ping Tool -->
    <div v-if="activeTool === 'ping'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ping</h2>
      <div class="flex space-x-4 mb-4">
        <input
          v-model="pingHost"
          type="text"
          placeholder="Hostname or IP address"
          class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          @keyup.enter="runPing"
        />
        <input
          v-model.number="pingCount"
          type="number"
          min="1"
          max="100"
          placeholder="Count"
          class="w-24 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
        />
        <button @click="runPing" :disabled="running" class="btn-primary">
          {{ running ? 'Running...' : 'Ping' }}
        </button>
        <button v-if="running" @click="stopCommand" class="btn-secondary">Stop</button>
      </div>
      <div class="bg-gray-900 text-gray-100 font-mono text-sm p-4 rounded-lg h-80 overflow-auto">
        <pre>{{ output || 'Enter a host and click Ping to start' }}</pre>
      </div>
    </div>

    <!-- Traceroute Tool -->
    <div v-if="activeTool === 'traceroute'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Traceroute</h2>
      <div class="flex space-x-4 mb-4">
        <input
          v-model="tracerouteHost"
          type="text"
          placeholder="Hostname or IP address"
          class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          @keyup.enter="runTraceroute"
        />
        <select v-model="tracerouteProto"
          class="w-32 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
          <option value="icmp">ICMP</option>
          <option value="udp">UDP</option>
        </select>
        <button @click="runTraceroute" :disabled="running" class="btn-primary">
          {{ running ? 'Running...' : 'Trace' }}
        </button>
        <button v-if="running" @click="stopCommand" class="btn-secondary">Stop</button>
      </div>
      <div class="bg-gray-900 text-gray-100 font-mono text-sm p-4 rounded-lg h-80 overflow-auto">
        <pre>{{ output || 'Enter a host and click Trace to start' }}</pre>
      </div>
    </div>

    <!-- DNS Lookup Tool -->
    <div v-if="activeTool === 'dns'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">DNS Lookup</h2>
      <div class="flex space-x-4 mb-4">
        <input
          v-model="dnsHost"
          type="text"
          placeholder="Hostname"
          class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          @keyup.enter="runDNS"
        />
        <select v-model="dnsType"
          class="w-32 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
          <option value="A">A</option>
          <option value="AAAA">AAAA</option>
          <option value="MX">MX</option>
          <option value="NS">NS</option>
          <option value="TXT">TXT</option>
          <option value="SOA">SOA</option>
          <option value="PTR">PTR</option>
          <option value="ANY">ANY</option>
        </select>
        <input
          v-model="dnsServer"
          type="text"
          placeholder="DNS Server (optional)"
          class="w-48 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
        />
        <button @click="runDNS" :disabled="running" class="btn-primary">
          {{ running ? 'Looking up...' : 'Lookup' }}
        </button>
      </div>
      <div class="bg-gray-900 text-gray-100 font-mono text-sm p-4 rounded-lg h-80 overflow-auto">
        <pre>{{ output || 'Enter a hostname and click Lookup to start' }}</pre>
      </div>
    </div>

    <!-- Port Scan Tool -->
    <div v-if="activeTool === 'portscan'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Port Scan</h2>
      <div class="flex space-x-4 mb-4">
        <input
          v-model="scanHost"
          type="text"
          placeholder="Hostname or IP address"
          class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
        />
        <input
          v-model="scanPorts"
          type="text"
          placeholder="Ports (e.g., 22,80,443 or 1-1024)"
          class="w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
        />
        <button @click="runPortScan" :disabled="running" class="btn-primary">
          {{ running ? 'Scanning...' : 'Scan' }}
        </button>
        <button v-if="running" @click="stopCommand" class="btn-secondary">Stop</button>
      </div>
      <div class="bg-gray-900 text-gray-100 font-mono text-sm p-4 rounded-lg h-80 overflow-auto">
        <pre>{{ output || 'Enter a host and ports to scan' }}</pre>
      </div>
    </div>

    <!-- ARP Table -->
    <div v-if="activeTool === 'arp'" class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">ARP Table</h2>
        <div class="flex space-x-2">
          <button @click="flushARP" class="btn-secondary text-sm">Flush</button>
          <button @click="loadARP" class="btn-primary text-sm">Refresh</button>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">MAC Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Interface</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Flags</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="entry in arpTable" :key="entry.ip" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-sm font-mono text-gray-900 dark:text-white">{{ entry.ip }}</td>
              <td class="px-6 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">{{ entry.mac }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ entry.interface }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ entry.flags }}</td>
              <td class="px-6 py-4 text-sm">
                <button @click="deleteARP(entry.ip)" class="text-red-600 hover:text-red-800">Delete</button>
              </td>
            </tr>
            <tr v-if="arpTable.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                No ARP entries
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Netstat Tool -->
    <div v-if="activeTool === 'netstat'" class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Network Connections</h2>
        <div class="flex space-x-2">
          <select v-model="netstatFilter"
            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm">
            <option value="all">All</option>
            <option value="tcp">TCP</option>
            <option value="udp">UDP</option>
            <option value="listening">Listening</option>
            <option value="established">Established</option>
          </select>
          <button @click="loadNetstat" class="btn-primary text-sm">Refresh</button>
        </div>
      </div>
      <div class="overflow-x-auto max-h-96">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Protocol</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Local Address</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Remote Address</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">State</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">PID/Program</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(conn, idx) in netstatConnections" :key="idx" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ conn.protocol }}</td>
              <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-white">{{ conn.local }}</td>
              <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ conn.remote || '*' }}</td>
              <td class="px-4 py-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStateClass(conn.state)">
                  {{ conn.state }}
                </span>
              </td>
              <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ conn.program || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Packet Capture Tool -->
    <div v-if="activeTool === 'tcpdump'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Packet Capture</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Interface</label>
          <select v-model="tcpdumpInterface"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="">Any</option>
            <option v-for="iface in interfaces" :key="iface" :value="iface">{{ iface }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Filter</label>
          <input
            v-model="tcpdumpFilter"
            type="text"
            placeholder="e.g., port 80, host 192.168.1.1"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          />
        </div>
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Count</label>
          <input
            v-model.number="tcpdumpCount"
            type="number"
            min="1"
            max="1000"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          />
        </div>
        <div class="flex items-end">
          <button @click="runTcpdump" :disabled="running" class="btn-primary w-full">
            {{ running ? 'Capturing...' : 'Start Capture' }}
          </button>
        </div>
      </div>
      <div v-if="running" class="mb-4">
        <button @click="stopCommand" class="btn-secondary">Stop Capture</button>
      </div>
      <div class="bg-gray-900 text-gray-100 font-mono text-xs p-4 rounded-lg h-96 overflow-auto">
        <pre>{{ output || 'Configure options and click Start Capture' }}</pre>
      </div>
    </div>

    <!-- System Info Tool -->
    <div v-if="activeTool === 'sysinfo'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Memory -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Memory Usage</h3>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400">Used</span>
                <span class="text-gray-900 dark:text-white">{{ sysinfo.memory?.used || 0 }} / {{ sysinfo.memory?.total || 0 }} MB</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" :style="{ width: sysinfo.memory?.percent + '%' }"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400">Swap</span>
                <span class="text-gray-900 dark:text-white">{{ sysinfo.swap?.used || 0 }} / {{ sysinfo.swap?.total || 0 }} MB</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-yellow-600 h-2 rounded-full" :style="{ width: sysinfo.swap?.percent + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Disk -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Disk Usage</h3>
          <div class="space-y-3">
            <div v-for="disk in sysinfo.disks" :key="disk.mount">
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400">{{ disk.mount }}</span>
                <span class="text-gray-900 dark:text-white">{{ disk.used }} / {{ disk.total }}</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="h-2 rounded-full"
                  :class="disk.percent > 90 ? 'bg-red-600' : disk.percent > 70 ? 'bg-yellow-600' : 'bg-green-600'"
                  :style="{ width: disk.percent + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- CPU -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">CPU Usage</h3>
          <div class="text-center">
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
              {{ sysinfo.cpu?.percent || 0 }}%
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              Load: {{ sysinfo.load?.join(' ') || 'N/A' }}
            </div>
          </div>
        </div>

        <!-- Uptime -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">System</h3>
          <dl class="space-y-2">
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500 dark:text-gray-400">Uptime</dt>
              <dd class="text-sm text-gray-900 dark:text-white">{{ sysinfo.uptime || 'N/A' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500 dark:text-gray-400">Hostname</dt>
              <dd class="text-sm text-gray-900 dark:text-white">{{ sysinfo.hostname || 'N/A' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500 dark:text-gray-400">Version</dt>
              <dd class="text-sm text-gray-900 dark:text-white">{{ sysinfo.version || 'N/A' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-sm text-gray-500 dark:text-gray-400">Kernel</dt>
              <dd class="text-sm text-gray-900 dark:text-white">{{ sysinfo.kernel || 'N/A' }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="flex justify-end">
        <button @click="loadSysinfo" class="btn-primary">Refresh</button>
      </div>
    </div>

    <!-- PF Test Tool -->
    <div v-if="activeTool === 'pftest'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">PF Rule Test</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Test which PF rule would match a given packet
      </p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Source IP</label>
          <input
            v-model="pfTestSrc"
            type="text"
            placeholder="192.168.1.100"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          />
        </div>
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Destination IP</label>
          <input
            v-model="pfTestDst"
            type="text"
            placeholder="10.0.0.1"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          />
        </div>
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Port</label>
          <input
            v-model="pfTestPort"
            type="text"
            placeholder="80"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
          />
        </div>
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Protocol</label>
          <select v-model="pfTestProto"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="tcp">TCP</option>
            <option value="udp">UDP</option>
            <option value="icmp">ICMP</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Interface</label>
          <select v-model="pfTestInterface"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option v-for="iface in interfaces" :key="iface" :value="iface">{{ iface }}</option>
          </select>
        </div>
        <div class="flex items-end">
          <button @click="runPFTest" :disabled="running" class="btn-primary w-full">Test</button>
        </div>
      </div>
      <div class="bg-gray-900 text-gray-100 font-mono text-sm p-4 rounded-lg">
        <pre>{{ output || 'Configure packet details and click Test' }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useToast } from 'vue-toastification'

const toast = useToast()

const tools = [
  { id: 'ping', name: 'Ping' },
  { id: 'traceroute', name: 'Traceroute' },
  { id: 'dns', name: 'DNS Lookup' },
  { id: 'portscan', name: 'Port Scan' },
  { id: 'arp', name: 'ARP Table' },
  { id: 'netstat', name: 'Connections' },
  { id: 'tcpdump', name: 'Packet Capture' },
  { id: 'sysinfo', name: 'System Info' },
  { id: 'pftest', name: 'PF Test' }
]

const activeTool = ref('ping')
const running = ref(false)
const output = ref('')

// Ping
const pingHost = ref('')
const pingCount = ref(4)

// Traceroute
const tracerouteHost = ref('')
const tracerouteProto = ref('icmp')

// DNS
const dnsHost = ref('')
const dnsType = ref('A')
const dnsServer = ref('')

// Port Scan
const scanHost = ref('')
const scanPorts = ref('22,80,443')

// ARP
const arpTable = ref([])

// Netstat
const netstatFilter = ref('all')
const netstatConnections = ref([])

// Tcpdump
const tcpdumpInterface = ref('')
const tcpdumpFilter = ref('')
const tcpdumpCount = ref(100)
const interfaces = ref([])

// System Info
const sysinfo = ref({})

// PF Test
const pfTestSrc = ref('')
const pfTestDst = ref('')
const pfTestPort = ref('')
const pfTestProto = ref('tcp')
const pfTestInterface = ref('')

let abortController = null

async function runCommand(endpoint, params = {}) {
  running.value = true
  output.value = 'Running...\n'
  abortController = new AbortController()

  try {
    const response = await api.post(endpoint, params, {
      signal: abortController.signal
    })

    if (response.data.success) {
      output.value = response.data.data.output || response.data.data
    } else {
      output.value = 'Error: ' + (response.data.error || 'Unknown error')
    }
  } catch (err) {
    if (err.name === 'AbortError') {
      output.value += '\n\nCommand stopped by user'
    } else {
      output.value = 'Error: ' + (err.response?.data?.error || err.message)
    }
  } finally {
    running.value = false
    abortController = null
  }
}

function stopCommand() {
  if (abortController) {
    abortController.abort()
  }
}

async function runPing() {
  if (!pingHost.value) {
    toast.error('Please enter a hostname or IP')
    return
  }
  await runCommand('/diagnostics/ping', {
    host: pingHost.value,
    count: pingCount.value
  })
}

async function runTraceroute() {
  if (!tracerouteHost.value) {
    toast.error('Please enter a hostname or IP')
    return
  }
  await runCommand('/diagnostics/traceroute', {
    host: tracerouteHost.value,
    protocol: tracerouteProto.value
  })
}

async function runDNS() {
  if (!dnsHost.value) {
    toast.error('Please enter a hostname')
    return
  }
  await runCommand('/diagnostics/dns', {
    host: dnsHost.value,
    type: dnsType.value,
    server: dnsServer.value || undefined
  })
}

async function runPortScan() {
  if (!scanHost.value) {
    toast.error('Please enter a hostname or IP')
    return
  }
  await runCommand('/diagnostics/portscan', {
    host: scanHost.value,
    ports: scanPorts.value
  })
}

async function loadARP() {
  try {
    const response = await api.get('/arp')
    if (response.data.success) {
      arpTable.value = response.data.data || []
    }
  } catch (err) {
    toast.error('Failed to load ARP table')
  }
}

async function deleteARP(ip) {
  if (!confirm(`Delete ARP entry for ${ip}?`)) return
  try {
    await api.delete(`/arp/${ip}`)
    toast.success('ARP entry deleted')
    await loadARP()
  } catch (err) {
    toast.error('Failed to delete ARP entry')
  }
}

async function flushARP() {
  if (!confirm('Flush entire ARP table?')) return
  try {
    await api.post('/arp/flush')
    toast.success('ARP table flushed')
    await loadARP()
  } catch (err) {
    toast.error('Failed to flush ARP table')
  }
}

async function loadNetstat() {
  try {
    const response = await api.get('/diagnostics/netstat', {
      params: { filter: netstatFilter.value }
    })
    if (response.data.success) {
      netstatConnections.value = response.data.data || []
    }
  } catch (err) {
    toast.error('Failed to load connections')
  }
}

async function runTcpdump() {
  await runCommand('/diagnostics/tcpdump', {
    interface: tcpdumpInterface.value || undefined,
    filter: tcpdumpFilter.value || undefined,
    count: tcpdumpCount.value
  })
}

async function loadSysinfo() {
  try {
    const response = await api.get('/system/info')
    if (response.data.success) {
      sysinfo.value = response.data.data
    }
  } catch (err) {
    toast.error('Failed to load system info')
  }
}

async function loadInterfaces() {
  try {
    const response = await api.get('/interfaces')
    if (response.data.success) {
      interfaces.value = response.data.data.map(i => i.name)
    }
  } catch (err) {
    console.error('Failed to load interfaces')
  }
}

async function runPFTest() {
  if (!pfTestSrc.value || !pfTestDst.value) {
    toast.error('Please enter source and destination IPs')
    return
  }
  await runCommand('/diagnostics/pftest', {
    src: pfTestSrc.value,
    dst: pfTestDst.value,
    port: pfTestPort.value || undefined,
    protocol: pfTestProto.value,
    interface: pfTestInterface.value || undefined
  })
}

function getStateClass(state) {
  const classes = {
    LISTEN: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    ESTABLISHED: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    TIME_WAIT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    CLOSE_WAIT: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    CLOSED: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
  }
  return classes[state] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

onMounted(() => {
  loadInterfaces()
  loadSysinfo()
})
</script>
