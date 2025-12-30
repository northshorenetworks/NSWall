<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="modal-overlay"
        @click.self="closeOnBackdrop && close()"
      >
        <Transition
          enter-active-class="transition-all duration-300"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition-all duration-200"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4"
        >
          <div
            v-if="modelValue"
            class="modal"
            :class="sizeClass"
            role="dialog"
            aria-modal="true"
          >
            <!-- Gradient border effect -->
            <div class="absolute inset-0 rounded-2xl p-px bg-gradient-to-br from-neon-cyan/20 via-transparent to-neon-purple/20">
              <div class="w-full h-full bg-cyber-dark rounded-2xl" />
            </div>

            <!-- Content -->
            <div class="relative">
              <!-- Header -->
              <div v-if="title || $slots.header" class="modal-header">
                <slot name="header">
                  <h3 class="modal-title flex items-center gap-2">
                    <component v-if="icon" :is="icon" class="w-5 h-5 text-neon-cyan" />
                    {{ title }}
                  </h3>
                </slot>

                <button
                  v-if="closable"
                  @click="close"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="modal-body" :class="bodyClass">
                <slot />
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer || showDefaultFooter" class="modal-footer">
                <slot name="footer">
                  <button
                    v-if="cancelText"
                    @click="close"
                    class="btn btn-secondary"
                  >
                    {{ cancelText }}
                  </button>
                  <button
                    v-if="confirmText"
                    @click="confirm"
                    class="btn btn-primary"
                    :disabled="confirmDisabled"
                  >
                    <span v-if="loading" class="spinner mr-2" />
                    {{ confirmText }}
                  </button>
                </slot>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  title: String,
  icon: [Object, Function],
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl', 'full'].includes(v)
  },
  closable: { type: Boolean, default: true },
  closeOnBackdrop: { type: Boolean, default: true },
  closeOnEsc: { type: Boolean, default: true },
  confirmText: String,
  cancelText: String,
  confirmDisabled: Boolean,
  loading: Boolean,
  bodyClass: String
})

const emit = defineEmits(['update:modelValue', 'confirm', 'close'])

const showDefaultFooter = computed(() => props.confirmText || props.cancelText)

const sizeClass = computed(() => ({
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  full: 'max-w-[calc(100vw-2rem)] max-h-[calc(100vh-2rem)]'
}[props.size]))

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function confirm() {
  emit('confirm')
}

function handleEsc(e) {
  if (e.key === 'Escape' && props.closeOnEsc && props.modelValue) {
    close()
  }
}

// Lock body scroll when modal is open
watch(() => props.modelValue, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
  document.addEventListener('keydown', handleEsc)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc)
  document.body.style.overflow = ''
})
</script>
