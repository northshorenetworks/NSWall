<template>
  <div class="card group hover-lift" :class="{ 'card-glow': glow }">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center gap-3">
        <!-- Icon -->
        <div
          v-if="icon"
          class="p-2.5 rounded-lg transition-colors duration-300"
          :class="iconBgClass"
        >
          <component :is="icon" class="w-5 h-5" :class="iconColorClass" />
        </div>
        <div>
          <p class="text-sm text-gray-400">{{ label }}</p>
          <h4 v-if="sublabel" class="text-xs text-gray-500">{{ sublabel }}</h4>
        </div>
      </div>

      <!-- Status badge -->
      <span
        v-if="status"
        class="status-badge"
        :class="statusBadgeClass"
      >
        <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass" />
        {{ status }}
      </span>
    </div>

    <!-- Value -->
    <div class="flex items-baseline gap-2">
      <span
        class="text-3xl font-bold font-mono tracking-tight transition-all duration-300"
        :class="valueClass"
      >
        <span v-if="prefix" class="text-xl text-gray-500">{{ prefix }}</span>
        <span ref="valueEl">{{ formattedValue }}</span>
        <span v-if="suffix" class="text-lg text-gray-400 ml-1">{{ suffix }}</span>
      </span>
    </div>

    <!-- Trend -->
    <div v-if="trend !== undefined" class="flex items-center gap-2 mt-3">
      <span
        class="inline-flex items-center gap-1 text-sm"
        :class="trendClass"
      >
        <svg
          v-if="trend !== 0"
          class="w-4 h-4"
          :class="{ 'rotate-180': trend < 0 }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        <span v-if="trend === 0">-</span>
        <span v-else>{{ Math.abs(trend) }}%</span>
      </span>
      <span class="text-xs text-gray-500">{{ trendLabel || 'vs last period' }}</span>
    </div>

    <!-- Sparkline -->
    <div v-if="sparkline && sparkline.length" class="mt-4">
      <div class="sparkline">
        <div
          v-for="(val, i) in normalizedSparkline"
          :key="i"
          class="sparkline-bar"
          :style="{ height: `${val}%` }"
        />
      </div>
    </div>

    <!-- Progress bar -->
    <div v-if="progress !== undefined" class="mt-4">
      <div class="flex justify-between text-xs text-gray-500 mb-1">
        <span>{{ progressLabel || 'Usage' }}</span>
        <span>{{ progress }}%</span>
      </div>
      <div class="progress-bar">
        <div
          class="progress-bar-fill"
          :class="progressColorClass"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>

    <!-- Slot for custom content -->
    <slot />
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  sublabel: String,
  value: { type: [Number, String], default: 0 },
  prefix: String,
  suffix: String,
  format: {
    type: String,
    default: 'number',
    validator: (v) => ['number', 'bytes', 'currency', 'percent', 'duration'].includes(v)
  },
  color: {
    type: String,
    default: 'cyan',
    validator: (v) => ['cyan', 'purple', 'green', 'red', 'yellow', 'white'].includes(v)
  },
  icon: [Object, Function],
  status: String,
  statusType: {
    type: String,
    default: 'success',
    validator: (v) => ['success', 'warning', 'error', 'info'].includes(v)
  },
  trend: Number,
  trendLabel: String,
  sparkline: Array,
  progress: Number,
  progressLabel: String,
  glow: Boolean,
  animate: { type: Boolean, default: true }
})

const valueEl = ref(null)

const formattedValue = computed(() => {
  if (typeof props.value === 'string') return props.value

  switch (props.format) {
    case 'bytes':
      return formatBytes(props.value)
    case 'currency':
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(props.value)
    case 'percent':
      return `${props.value.toFixed(1)}`
    case 'duration':
      return formatDuration(props.value)
    default:
      return props.value.toLocaleString()
  }
})

function formatBytes(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${(bytes / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`
}

function formatDuration(seconds) {
  if (seconds < 60) return `${seconds}s`
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`
  return `${Math.floor(seconds / 86400)}d`
}

const normalizedSparkline = computed(() => {
  if (!props.sparkline?.length) return []
  const max = Math.max(...props.sparkline)
  if (max === 0) return props.sparkline.map(() => 10)
  return props.sparkline.map(v => Math.max(10, (v / max) * 100))
})

const valueClass = computed(() => ({
  cyan: 'text-white group-hover:text-neon-cyan',
  purple: 'text-white group-hover:text-neon-purple',
  green: 'text-white group-hover:text-neon-green',
  red: 'text-white group-hover:text-neon-red',
  yellow: 'text-white group-hover:text-neon-yellow',
  white: 'text-white'
}[props.color]))

const iconBgClass = computed(() => ({
  cyan: 'bg-neon-cyan/10 group-hover:bg-neon-cyan/20',
  purple: 'bg-neon-purple/10 group-hover:bg-neon-purple/20',
  green: 'bg-neon-green/10 group-hover:bg-neon-green/20',
  red: 'bg-neon-red/10 group-hover:bg-neon-red/20',
  yellow: 'bg-neon-yellow/10 group-hover:bg-neon-yellow/20',
  white: 'bg-white/10 group-hover:bg-white/20'
}[props.color]))

const iconColorClass = computed(() => ({
  cyan: 'text-neon-cyan',
  purple: 'text-neon-purple',
  green: 'text-neon-green',
  red: 'text-neon-red',
  yellow: 'text-neon-yellow',
  white: 'text-white'
}[props.color]))

const trendClass = computed(() => {
  if (props.trend === undefined || props.trend === 0) return 'text-gray-500'
  return props.trend > 0 ? 'text-neon-green' : 'text-neon-red'
})

const statusBadgeClass = computed(() => ({
  success: 'status-badge-success',
  warning: 'status-badge-warning',
  error: 'status-badge-error',
  info: 'status-badge-info'
}[props.statusType]))

const statusDotClass = computed(() => ({
  success: 'bg-neon-green',
  warning: 'bg-neon-yellow',
  error: 'bg-neon-red',
  info: 'bg-neon-cyan'
}[props.statusType]))

const progressColorClass = computed(() => {
  if (props.progress >= 90) return 'from-neon-red to-neon-red'
  if (props.progress >= 75) return 'from-neon-yellow to-neon-orange'
  return 'from-neon-cyan to-neon-purple'
})
</script>
