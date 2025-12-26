<template>
  <div class="card overflow-hidden">
    <!-- Header -->
    <div v-if="title || $slots.actions" class="card-header">
      <h3 v-if="title" class="card-title">{{ title }}</h3>
      <div class="flex items-center gap-3">
        <!-- Search -->
        <div v-if="searchable" class="relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="input py-2 pl-10 pr-4 w-64 text-sm"
          />
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <slot name="actions" />
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="table-cyber">
        <thead>
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[col.headerClass, col.sortable && 'cursor-pointer select-none hover:text-neon-cyan']"
              :style="{ width: col.width }"
              @click="col.sortable && toggleSort(col.key)"
            >
              <div class="flex items-center gap-2">
                {{ col.label }}
                <template v-if="col.sortable">
                  <svg
                    v-if="sortKey === col.key"
                    class="w-4 h-4 transition-transform"
                    :class="{ 'rotate-180': sortDir === 'desc' }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                  </svg>
                  <svg
                    v-else
                    class="w-4 h-4 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                  </svg>
                </template>
              </div>
            </th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading -->
          <tr v-if="loading">
            <td :colspan="columns.length" class="py-12">
              <div class="flex flex-col items-center justify-center gap-3">
                <div class="spinner w-8 h-8" />
                <span class="text-sm text-gray-500">Loading...</span>
              </div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-else-if="!sortedData.length">
            <td :colspan="columns.length" class="py-12">
              <div class="flex flex-col items-center justify-center gap-3 text-center">
                <div class="p-4 bg-cyber-lighter/50 rounded-full">
                  <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                  </svg>
                </div>
                <p class="text-gray-400">{{ emptyText }}</p>
                <slot name="empty" />
              </div>
            </td>
          </tr>

          <!-- Data rows -->
          <template v-else>
            <tr
              v-for="(row, idx) in paginatedData"
              :key="row[rowKey] || idx"
              :class="[rowClass, { 'cursor-pointer': clickable }]"
              @click="clickable && $emit('rowClick', row)"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                :class="col.cellClass"
              >
                <slot :name="`cell-${col.key}`" :row="row" :value="getNestedValue(row, col.key)">
                  <component
                    v-if="col.component"
                    :is="col.component"
                    v-bind="col.componentProps?.(row) || {}"
                  />
                  <span v-else :class="col.valueClass">
                    {{ formatValue(getNestedValue(row, col.key), col.format) }}
                  </span>
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="paginated && sortedData.length > pageSize" class="flex items-center justify-between px-6 py-4 border-t border-white/5">
      <span class="text-sm text-gray-500">
        Showing {{ (currentPage - 1) * pageSize + 1 }} to {{ Math.min(currentPage * pageSize, sortedData.length) }} of {{ sortedData.length }}
      </span>

      <div class="flex items-center gap-2">
        <button
          class="btn btn-ghost btn-icon"
          :disabled="currentPage === 1"
          @click="currentPage--"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <div class="flex gap-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            class="btn btn-ghost px-3 py-1.5 text-sm"
            :class="{ 'bg-neon-cyan/10 text-neon-cyan': page === currentPage }"
            @click="currentPage = page"
          >
            {{ page }}
          </button>
        </div>

        <button
          class="btn btn-ghost btn-icon"
          :disabled="currentPage === totalPages"
          @click="currentPage++"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  title: String,
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  loading: Boolean,
  searchable: Boolean,
  searchPlaceholder: { type: String, default: 'Search...' },
  searchKeys: { type: Array, default: () => [] },
  paginated: Boolean,
  pageSize: { type: Number, default: 10 },
  emptyText: { type: String, default: 'No data available' },
  rowClass: String,
  clickable: Boolean
})

defineEmits(['rowClick'])

const searchQuery = ref('')
const sortKey = ref('')
const sortDir = ref('asc')
const currentPage = ref(1)

function getNestedValue(obj, path) {
  return path.split('.').reduce((o, k) => o?.[k], obj)
}

function formatValue(value, format) {
  if (value === null || value === undefined) return '-'
  if (!format) return value
  if (typeof format === 'function') return format(value)

  switch (format) {
    case 'date':
      return new Date(value).toLocaleDateString()
    case 'datetime':
      return new Date(value).toLocaleString()
    case 'bytes':
      return formatBytes(value)
    case 'number':
      return value.toLocaleString()
    default:
      return value
  }
}

function formatBytes(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${(bytes / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`
}

function toggleSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
  currentPage.value = 1
}

const filteredData = computed(() => {
  if (!searchQuery.value) return props.data

  const query = searchQuery.value.toLowerCase()
  const keys = props.searchKeys.length ? props.searchKeys : props.columns.map(c => c.key)

  return props.data.filter(row =>
    keys.some(key => {
      const val = getNestedValue(row, key)
      return val && String(val).toLowerCase().includes(query)
    })
  )
})

const sortedData = computed(() => {
  if (!sortKey.value) return filteredData.value

  return [...filteredData.value].sort((a, b) => {
    const aVal = getNestedValue(a, sortKey.value)
    const bVal = getNestedValue(b, sortKey.value)

    if (aVal === bVal) return 0
    if (aVal === null || aVal === undefined) return 1
    if (bVal === null || bVal === undefined) return -1

    const cmp = aVal < bVal ? -1 : 1
    return sortDir.value === 'asc' ? cmp : -cmp
  })
})

const totalPages = computed(() => Math.ceil(sortedData.value.length / props.pageSize))

const paginatedData = computed(() => {
  if (!props.paginated) return sortedData.value

  const start = (currentPage.value - 1) * props.pageSize
  return sortedData.value.slice(start, start + props.pageSize)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 5) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else if (current <= 3) {
    pages.push(1, 2, 3, 4, 5)
  } else if (current >= total - 2) {
    for (let i = total - 4; i <= total; i++) pages.push(i)
  } else {
    for (let i = current - 2; i <= current + 2; i++) pages.push(i)
  }

  return pages
})
</script>
