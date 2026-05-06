<template>
  <div :class="`flex items-start gap-3 p-3 rounded-lg shadow-sm border ${variantClass}`">
    <component :is="iconComponent" class="h-5 w-5 mt-0.5 shrink-0" />
    <div class="flex-1 min-w-0">
      <p v-if="title" class="text-sm font-semibold">{{ title }}</p>
      <p v-if="message" class="text-sm">{{ message }}</p>
      <slot />
    </div>
    <button
      v-if="dismissible"
      type="button"
      class="text-current opacity-60 hover:opacity-100 p-0.5"
      @click="$emit('dismiss')"
    >
      <X class="h-4 w-4" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CheckCircle, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next'

const props = defineProps({
  variant: {
    type: String,
    default: 'info',
    validator: v => ['success', 'error', 'warning', 'info'].includes(v),
  },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  dismissible: { type: Boolean, default: true },
})

defineEmits(['dismiss'])

const variantClass = computed(() => ({
  success: 'bg-green-50 border-green-200 text-green-900',
  error: 'bg-red-50 border-red-200 text-red-900',
  warning: 'bg-yellow-50 border-yellow-200 text-yellow-900',
  info: 'bg-blue-50 border-blue-200 text-blue-900',
}[props.variant]))

const iconComponent = computed(() => ({
  success: CheckCircle,
  error: AlertCircle,
  warning: AlertTriangle,
  info: Info,
}[props.variant]))
</script>
