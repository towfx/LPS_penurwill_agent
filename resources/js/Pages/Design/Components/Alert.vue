<template>
  <div :class="`p-4 rounded-lg ${alertClasses} ${className}`">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'destructive', 'success', 'warning'].includes(value)
  },
  className: {
    type: String,
    default: ''
  }
})

const alertClasses = computed(() => {
  const variants = {
    default: 'border-l-4 border-blue-500 bg-blue-50 text-blue-900',
    destructive: 'border-l-4 border-red-500 bg-red-50 text-red-900',
    success: 'border-l-4 border-green-500 bg-green-50 text-green-900',
    warning: 'border-l-4 border-yellow-500 bg-yellow-50 text-yellow-900'
  }

  return variants[props.variant]
})
</script>
