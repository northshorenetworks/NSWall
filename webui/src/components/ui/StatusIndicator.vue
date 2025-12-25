<template>
  <div class="flex items-center gap-2">
    <!-- Animated dot with ring -->
    <div class="relative">
      <span
        class="status-dot"
        :class="statusClasses"
      />
      <span
        v-if="pulse && status !== 'offline'"
        class="absolute inset-0 rounded-full animate-ping"
        :class="pingClasses"
      />
    </div>

    <!-- Label -->
    <span v-if="label" class="text-sm" :class="labelClasses">
      {{ label }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    default: 'offline',
    validator: (v) => ['online', 'offline', 'warning', 'error', 'syncing'].includes(v)
  },
  label: String,
  pulse: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  }
})

const statusClasses = computed(() => {
  const base = {
    sm: 'w-2 h-2',
    md: 'w-2.5 h-2.5',
    lg: 'w-3 h-3'
  }[props.size]

  const colors = {
    online: 'bg-neon-green shadow-neon-green',
    offline: 'bg-gray-500',
    warning: 'bg-neon-yellow shadow-[0_0_10px_rgba(251,191,36,0.5)]',
    error: 'bg-neon-red shadow-neon-red',
    syncing: 'bg-neon-cyan shadow-neon-cyan animate-pulse'
  }

  return [base, colors[props.status]]
})

const pingClasses = computed(() => ({
  online: 'bg-neon-green/50',
  warning: 'bg-neon-yellow/50',
  error: 'bg-neon-red/50',
  syncing: 'bg-neon-cyan/50'
}[props.status]))

const labelClasses = computed(() => ({
  online: 'text-neon-green',
  offline: 'text-gray-500',
  warning: 'text-neon-yellow',
  error: 'text-neon-red',
  syncing: 'text-neon-cyan'
}[props.status]))
</script>
