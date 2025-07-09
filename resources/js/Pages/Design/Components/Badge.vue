<template>
  <span :class="badgeClasses">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'secondary', 'destructive', 'success', 'warning', 'outline'].includes(value)
  },
  className: {
    type: String,
    default: ''
  }
})

const badgeClasses = computed(() => {
  const variants = {
    default: 'bg-stone-100 text-stone-800 border-stone-200',
    secondary: 'bg-amber-100 text-amber-800 border-amber-200',
    destructive: 'bg-red-100 text-red-800 border-red-200',
    success: 'bg-green-100 text-green-800 border-green-200',
    warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    outline: 'border-2 border-stone-300 text-stone-700 bg-transparent'
  }

  return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${variants[props.variant]} ${props.className}`
})
</script>
