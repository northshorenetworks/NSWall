<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ title }}</h3>
    <div class="h-64">
      <Line :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { useSettingsStore } from '@/stores/settings'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps({
  title: {
    type: String,
    default: 'Traffic'
  },
  inData: {
    type: Array,
    default: () => []
  },
  outData: {
    type: Array,
    default: () => []
  },
  labels: {
    type: Array,
    default: () => []
  },
  inLabel: {
    type: String,
    default: 'Inbound'
  },
  outLabel: {
    type: String,
    default: 'Outbound'
  }
})

const settingsStore = useSettingsStore()

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      label: props.inLabel,
      data: props.inData,
      borderColor: 'rgb(34, 197, 94)',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
      fill: true,
      tension: 0.4
    },
    {
      label: props.outLabel,
      data: props.outData,
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: true,
      tension: 0.4
    }
  ]
}))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false
  },
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: settingsStore.darkMode ? '#9ca3af' : '#374151',
        usePointStyle: true
      }
    },
    tooltip: {
      callbacks: {
        label: (context) => {
          const value = context.raw
          return `${context.dataset.label}: ${formatBytes(value)}/s`
        }
      }
    }
  },
  scales: {
    x: {
      grid: {
        color: settingsStore.darkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'
      },
      ticks: {
        color: settingsStore.darkMode ? '#9ca3af' : '#374151'
      }
    },
    y: {
      grid: {
        color: settingsStore.darkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'
      },
      ticks: {
        color: settingsStore.darkMode ? '#9ca3af' : '#374151',
        callback: (value) => formatBytes(value) + '/s'
      }
    }
  }
}))

function formatBytes(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>
