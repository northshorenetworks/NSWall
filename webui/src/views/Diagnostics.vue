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
    <div v-if="activeTool === 'tcpdump'" class="space-y-6">
      <!-- Capture Controls -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Packet Capture</h2>
          <div class="flex items-center space-x-2">
            <span v-if="captureStats.packets > 0" class="text-sm text-gray-500 dark:text-gray-400">
              {{ captureStats.packets }} packets captured
            </span>
            <button v-if="!tcpdumpRunning" @click="startTcpdump" :disabled="running" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Start Capture
            </button>
            <button v-else @click="stopTcpdump" class="btn-secondary bg-red-600 hover:bg-red-700 text-white border-red-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
              </svg>
              Stop Capture
            </button>
            <button @click="clearCapture" class="btn-secondary">Clear</button>
            <button @click="downloadCapture" :disabled="capturedPackets.length === 0" class="btn-secondary">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Download
            </button>
          </div>
        </div>

        <!-- Quick Filter Presets -->
        <div class="mb-4">
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Quick Filters</label>
          <div class="flex flex-wrap gap-2">
            <button v-for="preset in filterPresets" :key="preset.name"
              @click="applyFilterPreset(preset)"
              class="px-3 py-1 text-xs font-medium rounded-full transition-colors"
              :class="activePreset === preset.name
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'">
              {{ preset.name }}
            </button>
          </div>
        </div>

        <!-- Filter Options Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Interface</label>
            <select v-model="tcpd.interface"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option value="">Any interface</option>
              <option v-for="iface in interfaces" :key="iface" :value="iface">{{ iface }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Protocol</label>
            <select v-model="tcpd.protocol"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option value="">All protocols</option>
              <option value="tcp">TCP</option>
              <option value="udp">UDP</option>
              <option value="icmp">ICMP</option>
              <option value="arp">ARP</option>
              <option value="ip6">IPv6</option>
              <option value="vrrp">VRRP/CARP</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Host</label>
            <input v-model="tcpd.host" type="text" placeholder="IP or hostname"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Port</label>
            <input v-model="tcpd.port" type="text" placeholder="e.g., 80 or 80,443"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>

        <!-- Advanced Options -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
          <button @click="showAdvanced = !showAdvanced"
            class="flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-4 h-4 mr-1 transition-transform" :class="{ 'rotate-90': showAdvanced }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            Advanced Options
          </button>

          <div v-if="showAdvanced" class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Direction</label>
              <select v-model="tcpd.direction"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                <option value="">Both</option>
                <option value="src">Source only</option>
                <option value="dst">Destination only</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Packet Count</label>
              <input v-model.number="tcpd.count" type="number" min="1" max="10000"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Snap Length</label>
              <select v-model="tcpd.snaplen"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                <option value="96">96 bytes (headers)</option>
                <option value="256">256 bytes</option>
                <option value="512">512 bytes</option>
                <option value="1500">1500 bytes (full MTU)</option>
                <option value="65535">65535 bytes (full packet)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Verbosity</label>
              <select v-model="tcpd.verbosity"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                <option value="">Normal</option>
                <option value="v">Verbose (-v)</option>
                <option value="vv">Very Verbose (-vv)</option>
                <option value="vvv">Maximum (-vvv)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Custom BPF Filter</label>
              <input v-model="tcpd.customFilter" type="text" placeholder="e.g., tcp[tcpflags] & tcp-syn != 0"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Network</label>
              <input v-model="tcpd.net" type="text" placeholder="e.g., 192.168.1.0/24"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div class="flex items-center space-x-4 col-span-2">
              <label class="flex items-center cursor-pointer">
                <input v-model="tcpd.hexDump" type="checkbox"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Hex Dump (-X)</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input v-model="tcpd.ascii" type="checkbox"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">ASCII (-A)</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input v-model="tcpd.noResolve" type="checkbox"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">No DNS (-n)</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input v-model="tcpd.timestamp" type="checkbox"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Absolute Time (-tttt)</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Generated Filter Display -->
        <div v-if="generatedFilter" class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
          <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">Generated filter:</span>
            <button @click="copyFilter" class="text-xs text-blue-600 hover:text-blue-800">Copy</button>
          </div>
          <code class="text-sm text-gray-800 dark:text-gray-200 font-mono">{{ generatedFilter }}</code>
        </div>
      </div>

      <!-- Capture Status -->
      <div v-if="tcpdumpRunning" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-yellow-600 animate-pulse mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span class="text-yellow-800 dark:text-yellow-200 font-medium">Capture in progress...</span>
          <span class="ml-4 text-sm text-yellow-600 dark:text-yellow-400">
            Captured: {{ captureStats.packets }} packets, {{ formatBytes(captureStats.bytes) }}
          </span>
        </div>
      </div>

      <!-- Captured Packets List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Captured Packets</h3>
          <div class="flex items-center space-x-4">
            <input v-model="packetFilter" type="text" placeholder="Filter packets..."
              class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white w-64" />
            <label class="flex items-center cursor-pointer">
              <input v-model="autoScroll" type="checkbox"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
              <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Auto-scroll</span>
            </label>
          </div>
        </div>

        <!-- Packet Table -->
        <div class="overflow-x-auto max-h-96" ref="packetContainer">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-16">#</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-32">Time</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Source</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Destination</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-20">Protocol</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-20">Length</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Info</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 font-mono text-xs">
              <tr v-for="(pkt, idx) in filteredPackets" :key="idx"
                @click="selectPacket(pkt)"
                :class="[
                  'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700',
                  selectedPacket === pkt ? 'bg-blue-50 dark:bg-blue-900/30' : '',
                  getPacketRowClass(pkt)
                ]">
                <td class="px-3 py-1.5 text-gray-500 dark:text-gray-400">{{ idx + 1 }}</td>
                <td class="px-3 py-1.5 text-gray-900 dark:text-white">{{ pkt.time }}</td>
                <td class="px-3 py-1.5 text-gray-900 dark:text-white">{{ pkt.src }}</td>
                <td class="px-3 py-1.5 text-gray-900 dark:text-white">{{ pkt.dst }}</td>
                <td class="px-3 py-1.5">
                  <span class="px-1.5 py-0.5 text-xs rounded" :class="getProtocolClass(pkt.protocol)">
                    {{ pkt.protocol }}
                  </span>
                </td>
                <td class="px-3 py-1.5 text-gray-500 dark:text-gray-400">{{ pkt.length }}</td>
                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300 truncate max-w-md">{{ pkt.info }}</td>
              </tr>
              <tr v-if="filteredPackets.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                  {{ tcpdumpRunning ? 'Waiting for packets...' : 'No packets captured. Click Start Capture to begin.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Packet Detail View -->
      <div v-if="selectedPacket" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Packet Details</h3>
          <button @click="selectedPacket = null" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Packet Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          <div>
            <span class="text-xs text-gray-500 dark:text-gray-400 block">Time</span>
            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ selectedPacket.time }}</span>
          </div>
          <div>
            <span class="text-xs text-gray-500 dark:text-gray-400 block">Protocol</span>
            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ selectedPacket.protocol }}</span>
          </div>
          <div>
            <span class="text-xs text-gray-500 dark:text-gray-400 block">Length</span>
            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ selectedPacket.length }} bytes</span>
          </div>
          <div>
            <span class="text-xs text-gray-500 dark:text-gray-400 block">Interface</span>
            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ selectedPacket.interface || 'N/A' }}</span>
          </div>
        </div>

        <!-- Raw Output -->
        <div>
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Raw Output</label>
          <pre class="bg-gray-900 text-gray-100 font-mono text-xs p-4 rounded-lg overflow-x-auto">{{ selectedPacket.raw }}</pre>
        </div>

        <!-- Hex Dump if available -->
        <div v-if="selectedPacket.hex" class="mt-4">
          <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">Hex Dump</label>
          <pre class="bg-gray-900 text-green-400 font-mono text-xs p-4 rounded-lg overflow-x-auto">{{ selectedPacket.hex }}</pre>
        </div>
      </div>

      <!-- Raw Output View -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Raw tcpdump Output</h3>
          <button @click="copyRawOutput" class="btn-secondary text-sm">Copy</button>
        </div>
        <div class="bg-gray-900 text-gray-100 font-mono text-xs p-4 rounded-lg h-64 overflow-auto">
          <pre>{{ output || 'No output yet' }}</pre>
        </div>
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

    <!-- AI Analysis Tool -->
    <div v-if="activeTool === 'ai'" class="space-y-6">
      <!-- AI Header -->
      <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg shadow p-6 text-white">
        <div class="flex items-center space-x-4">
          <div class="p-3 bg-white/20 rounded-lg">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <div>
            <h2 class="text-2xl font-bold">AI Security Analyst</h2>
            <p class="text-white/80">Intelligent analysis of network traffic, logs, and security events</p>
          </div>
        </div>
      </div>

      <!-- Data Source Selection -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Select Data Source</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <button
            v-for="source in [
              { id: 'logs', name: 'System Logs', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
              { id: 'tcpdump', name: 'Packet Capture', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
              { id: 'netstat', name: 'Connections', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
              { id: 'firewall', name: 'Firewall Logs', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
              { id: 'sysinfo', name: 'System Info', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2' }
            ]"
            :key="source.id"
            @click="aiContext = source.id"
            class="p-4 rounded-lg border-2 transition-all text-center"
            :class="aiContext === source.id
              ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
            <svg class="w-6 h-6 mx-auto mb-2" :class="aiContext === source.id ? 'text-purple-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="source.icon" />
            </svg>
            <span class="text-sm font-medium" :class="aiContext === source.id ? 'text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400'">
              {{ source.name }}
            </span>
          </button>
        </div>
      </div>

      <!-- Quick Analysis Buttons -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Analysis</h3>
        <div class="flex flex-wrap gap-3">
          <button @click="quickAnalyze('threats')" :disabled="aiAnalyzing"
            class="px-4 py-2 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Detect Threats
          </button>
          <button @click="quickAnalyze('summary')" :disabled="aiAnalyzing"
            class="px-4 py-2 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Summarize
          </button>
          <button @click="quickAnalyze('anomalies')" :disabled="aiAnalyzing"
            class="px-4 py-2 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            Find Anomalies
          </button>
          <button @click="quickAnalyze('recommendations')" :disabled="aiAnalyzing"
            class="px-4 py-2 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Get Recommendations
          </button>
          <button @click="quickAnalyze('explain')" :disabled="aiAnalyzing"
            class="px-4 py-2 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Explain Traffic
          </button>
        </div>
      </div>

      <!-- Chat Interface -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">AI Chat</h3>
          <button @click="clearAIHistory" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            Clear History
          </button>
        </div>

        <!-- Chat Messages -->
        <div class="h-96 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900">
          <div v-if="aiHistory.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">Start a conversation with the AI analyst</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Select a data source and ask questions about your network</p>
          </div>

          <div v-for="(msg, idx) in aiHistory" :key="idx"
            :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
            <div :class="[
              'max-w-3xl rounded-lg px-4 py-3',
              msg.role === 'user'
                ? 'bg-blue-600 text-white'
                : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700'
            ]">
              <div v-if="msg.role === 'assistant'" class="flex items-center mb-2">
                <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span class="text-xs font-medium text-purple-500">AI Analyst</span>
              </div>
              <div class="whitespace-pre-wrap text-sm" v-html="formatAIResponse(msg.content)"></div>
            </div>
          </div>

          <!-- Thinking indicator -->
          <div v-if="aiAnalyzing" class="flex justify-start">
            <div class="bg-white dark:bg-gray-800 rounded-lg px-4 py-3 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center space-x-2">
                <div class="flex space-x-1">
                  <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                  <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                  <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Analyzing...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex space-x-4">
            <div class="flex-1 relative">
              <input
                v-model="aiQuery"
                type="text"
                placeholder="Ask about security threats, traffic patterns, anomalies..."
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white pr-24"
                @keyup.enter="analyzeWithAI"
                :disabled="aiAnalyzing"
              />
              <div class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 dark:text-gray-500">
                {{ aiContext }}
              </div>
            </div>
            <button
              @click="analyzeWithAI"
              :disabled="aiAnalyzing || !aiQuery.trim()"
              class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
              <svg v-if="!aiAnalyzing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <svg v-else class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              {{ aiAnalyzing ? 'Analyzing...' : 'Analyze' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Data Preview -->
      <div v-if="aiContext === 'tcpdump' && capturedPackets.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          Data Preview: {{ capturedPackets.length }} Packets Captured
        </h3>
        <div class="bg-gray-900 text-gray-100 font-mono text-xs p-4 rounded-lg max-h-48 overflow-auto">
          <pre>{{ output.substring(0, 2000) }}{{ output.length > 2000 ? '\n... (truncated)' : '' }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
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
  { id: 'pftest', name: 'PF Test' },
  { id: 'ai', name: 'AI Analysis' }
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

// System Info
const sysinfo = ref({})

// PF Test
const pfTestSrc = ref('')
const pfTestDst = ref('')
const pfTestPort = ref('')
const pfTestProto = ref('tcp')
const pfTestInterface = ref('')

// Interfaces list
const interfaces = ref([])

// Comprehensive tcpdump state
const tcpdumpRunning = ref(false)
const showAdvanced = ref(false)
const activePreset = ref('')
const autoScroll = ref(true)
const packetFilter = ref('')
const packetContainer = ref(null)
const selectedPacket = ref(null)
const capturedPackets = ref([])
const captureStats = reactive({ packets: 0, bytes: 0 })

const tcpd = reactive({
  interface: '',
  protocol: '',
  host: '',
  port: '',
  direction: '',
  count: 100,
  snaplen: '1500',
  verbosity: '',
  customFilter: '',
  net: '',
  hexDump: false,
  ascii: false,
  noResolve: true,
  timestamp: false
})

const filterPresets = [
  { name: 'All Traffic', filter: {} },
  { name: 'HTTP/HTTPS', filter: { protocol: 'tcp', port: '80,443' } },
  { name: 'DNS', filter: { protocol: 'udp', port: '53' } },
  { name: 'SSH', filter: { protocol: 'tcp', port: '22' } },
  { name: 'ICMP', filter: { protocol: 'icmp' } },
  { name: 'SYN Packets', filter: { customFilter: 'tcp[tcpflags] & tcp-syn != 0' } },
  { name: 'ARP', filter: { protocol: 'arp' } },
  { name: 'DHCP', filter: { protocol: 'udp', port: '67,68' } },
  { name: 'NTP', filter: { protocol: 'udp', port: '123' } },
  { name: 'CARP/VRRP', filter: { protocol: 'vrrp' } }
]

// Generate BPF filter from options
const generatedFilter = computed(() => {
  const parts = []

  if (tcpd.protocol) {
    parts.push(tcpd.protocol)
  }

  if (tcpd.host) {
    const dir = tcpd.direction ? `${tcpd.direction} host` : 'host'
    parts.push(`${dir} ${tcpd.host}`)
  }

  if (tcpd.net) {
    parts.push(`net ${tcpd.net}`)
  }

  if (tcpd.port) {
    const ports = tcpd.port.split(',').map(p => p.trim())
    if (ports.length === 1) {
      parts.push(`port ${ports[0]}`)
    } else {
      parts.push(`(${ports.map(p => `port ${p}`).join(' or ')})`)
    }
  }

  if (tcpd.customFilter) {
    parts.push(`(${tcpd.customFilter})`)
  }

  return parts.join(' and ')
})

// Filter captured packets
const filteredPackets = computed(() => {
  if (!packetFilter.value) return capturedPackets.value
  const search = packetFilter.value.toLowerCase()
  return capturedPackets.value.filter(pkt =>
    pkt.src?.toLowerCase().includes(search) ||
    pkt.dst?.toLowerCase().includes(search) ||
    pkt.protocol?.toLowerCase().includes(search) ||
    pkt.info?.toLowerCase().includes(search)
  )
})

// AI Analysis state
const aiAnalyzing = ref(false)
const aiResponse = ref('')
const aiContext = ref('logs')
const aiQuery = ref('')
const aiHistory = ref([])

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

// Comprehensive tcpdump functions
function applyFilterPreset(preset) {
  activePreset.value = preset.name
  // Reset all filter options
  tcpd.protocol = ''
  tcpd.host = ''
  tcpd.port = ''
  tcpd.direction = ''
  tcpd.customFilter = ''
  tcpd.net = ''
  // Apply preset
  Object.assign(tcpd, preset.filter)
}

async function startTcpdump() {
  tcpdumpRunning.value = true
  capturedPackets.value = []
  captureStats.packets = 0
  captureStats.bytes = 0
  output.value = ''

  const params = {
    interface: tcpd.interface || undefined,
    filter: generatedFilter.value || undefined,
    count: tcpd.count,
    snaplen: parseInt(tcpd.snaplen),
    verbose: tcpd.verbosity,
    hex: tcpd.hexDump,
    ascii: tcpd.ascii,
    noResolve: tcpd.noResolve,
    absoluteTime: tcpd.timestamp
  }

  abortController = new AbortController()

  try {
    const response = await api.post('/diagnostics/tcpdump', params, {
      signal: abortController.signal
    })

    if (response.data.success) {
      output.value = response.data.data.output || ''
      // Parse packets from output
      parsePackets(output.value)
    } else {
      output.value = 'Error: ' + (response.data.error || 'Unknown error')
    }
  } catch (err) {
    if (err.name !== 'AbortError') {
      output.value = 'Error: ' + (err.response?.data?.error || err.message)
    }
  } finally {
    tcpdumpRunning.value = false
    abortController = null
  }
}

function stopTcpdump() {
  if (abortController) {
    abortController.abort()
  }
  tcpdumpRunning.value = false
}

function clearCapture() {
  capturedPackets.value = []
  captureStats.packets = 0
  captureStats.bytes = 0
  output.value = ''
  selectedPacket.value = null
}

function parsePackets(rawOutput) {
  const lines = rawOutput.split('\n').filter(l => l.trim())
  capturedPackets.value = lines.map((line, idx) => {
    // Basic tcpdump parsing - this is simplified
    const match = line.match(/^(\d+:\d+:\d+\.\d+)\s+(\S+)\s+>\s+(\S+):\s+(.*)$/)
    if (match) {
      const [, time, src, dst, info] = match
      const protocol = detectProtocol(info)
      const length = extractLength(info)
      return { time, src, dst, protocol, length, info, raw: line }
    }
    // Alternative format
    const altMatch = line.match(/^(\S+)\s+IP\s+(\S+)\s+>\s+(\S+):\s+(.*)$/)
    if (altMatch) {
      const [, time, src, dst, info] = altMatch
      return { time, src, dst, protocol: 'IP', length: 0, info, raw: line }
    }
    return { time: '', src: '', dst: '', protocol: 'Unknown', length: 0, info: line, raw: line }
  })
  captureStats.packets = capturedPackets.value.length
  captureStats.bytes = rawOutput.length
}

function detectProtocol(info) {
  if (info.includes('Flags [S]') || info.includes('Flags [S.]')) return 'TCP SYN'
  if (info.includes('Flags [F]') || info.includes('Flags [F.]')) return 'TCP FIN'
  if (info.includes('Flags [R]')) return 'TCP RST'
  if (info.includes('Flags [P.]')) return 'TCP PSH'
  if (info.includes('Flags [.]')) return 'TCP ACK'
  if (info.includes('UDP')) return 'UDP'
  if (info.includes('ICMP')) return 'ICMP'
  if (info.includes('ARP')) return 'ARP'
  if (info.includes('HTTP')) return 'HTTP'
  if (info.includes('DNS') || info.includes('domain')) return 'DNS'
  return 'TCP'
}

function extractLength(info) {
  const match = info.match(/length\s+(\d+)/)
  return match ? parseInt(match[1]) : 0
}

function selectPacket(pkt) {
  selectedPacket.value = pkt
}

function getPacketRowClass(pkt) {
  if (pkt.protocol?.includes('SYN')) return 'bg-green-50 dark:bg-green-900/10'
  if (pkt.protocol?.includes('FIN') || pkt.protocol?.includes('RST')) return 'bg-red-50 dark:bg-red-900/10'
  if (pkt.protocol === 'ICMP') return 'bg-pink-50 dark:bg-pink-900/10'
  if (pkt.protocol === 'DNS') return 'bg-blue-50 dark:bg-blue-900/10'
  if (pkt.protocol === 'ARP') return 'bg-yellow-50 dark:bg-yellow-900/10'
  return ''
}

function getProtocolClass(protocol) {
  const classes = {
    'TCP': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'TCP SYN': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'TCP FIN': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'TCP RST': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'TCP ACK': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'TCP PSH': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    'UDP': 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
    'ICMP': 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    'DNS': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    'ARP': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'HTTP': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
  }
  return classes[protocol] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function copyFilter() {
  navigator.clipboard.writeText(generatedFilter.value)
  toast.success('Filter copied to clipboard')
}

function copyRawOutput() {
  navigator.clipboard.writeText(output.value)
  toast.success('Output copied to clipboard')
}

function downloadCapture() {
  const blob = new Blob([output.value], { type: 'text/plain' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `capture-${new Date().toISOString().replace(/[:.]/g, '-')}.txt`
  a.click()
  URL.revokeObjectURL(url)
}

// AI Analysis functions
async function analyzeWithAI() {
  if (!aiQuery.value.trim()) {
    toast.error('Please enter a question or analysis request')
    return
  }

  aiAnalyzing.value = true
  const userMessage = aiQuery.value
  aiHistory.value.push({ role: 'user', content: userMessage })
  aiQuery.value = ''

  try {
    // Prepare context based on selected source
    let context = ''
    if (aiContext.value === 'tcpdump' && capturedPackets.value.length > 0) {
      context = `Packet Capture Data (${capturedPackets.value.length} packets):\n${output.value}`
    } else if (aiContext.value === 'logs') {
      // Would fetch recent logs
      context = 'System log analysis context'
    } else if (aiContext.value === 'netstat' && netstatConnections.value.length > 0) {
      context = `Network Connections:\n${JSON.stringify(netstatConnections.value, null, 2)}`
    } else if (aiContext.value === 'sysinfo') {
      context = `System Information:\n${JSON.stringify(sysinfo.value, null, 2)}`
    }

    const response = await api.post('/ai/analyze', {
      query: userMessage,
      context: context,
      type: aiContext.value,
      history: aiHistory.value.slice(-10) // Last 10 messages for context
    })

    if (response.data.success) {
      const aiMessage = response.data.data.response
      aiHistory.value.push({ role: 'assistant', content: aiMessage })
      aiResponse.value = aiMessage
    } else {
      throw new Error(response.data.error || 'Analysis failed')
    }
  } catch (err) {
    const errorMsg = err.response?.data?.error || err.message || 'Analysis failed'
    aiHistory.value.push({ role: 'assistant', content: `Error: ${errorMsg}` })
    toast.error(errorMsg)
  } finally {
    aiAnalyzing.value = false
  }
}

function clearAIHistory() {
  aiHistory.value = []
  aiResponse.value = ''
}

async function quickAnalyze(type) {
  const prompts = {
    threats: 'Analyze this data for security threats, suspicious activity, or potential attacks. Highlight any concerning patterns.',
    summary: 'Provide a concise summary of this data, including key observations and notable events.',
    anomalies: 'Identify any anomalies, unusual patterns, or deviations from expected behavior in this data.',
    recommendations: 'Based on this data, what security recommendations or optimizations would you suggest?',
    explain: 'Explain what this data shows in simple terms. What is happening on this network?'
  }
  aiQuery.value = prompts[type]
  await analyzeWithAI()
}

function formatAIResponse(content) {
  if (!content) return ''
  // Simple markdown-like formatting
  return content
    // Bold
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    // Inline code
    .replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-sm">$1</code>')
    // Headers
    .replace(/^### (.*$)/gm, '<h4 class="font-bold text-lg mt-3 mb-1">$1</h4>')
    .replace(/^## (.*$)/gm, '<h3 class="font-bold text-xl mt-4 mb-2">$1</h3>')
    // Lists
    .replace(/^- (.*$)/gm, '<li class="ml-4">• $1</li>')
    .replace(/^\d+\. (.*$)/gm, '<li class="ml-4">$&</li>')
    // Line breaks
    .replace(/\n/g, '<br>')
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
