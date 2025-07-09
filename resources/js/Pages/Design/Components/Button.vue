<template>
  <button
    :class="buttonClasses"
    v-bind="$attrs"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'secondary', 'destructive', 'outline', 'ghost', 'link'].includes(value)
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'sm', 'lg', 'icon'].includes(value)
  },
  className: {
    type: String,
    default: ''
  }
})

const buttonClasses = computed(() => {
  const variants = {
    default: 'bg-stone-900 text-white hover:bg-stone-800',
    secondary: 'bg-amber-600 text-white hover:bg-amber-700',
    destructive: 'bg-red-600 text-white hover:bg-red-700',
    outline: 'border-2 border-stone-300 text-stone-700 hover:bg-stone-50',
    ghost: 'text-stone-700 hover:bg-stone-100',
    link: 'text-amber-600 hover:text-amber-700 underline-offset-4 hover:underline'
  }

  const sizes = {
    default: 'px-4 py-2 text-sm',
    sm: 'px-3 py-1.5 text-xs',
    lg: 'px-6 py-3 text-base',
    icon: 'p-2'
  }

  return `inline-flex items-center justify-center rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 ${variants[props.variant]} ${sizes[props.size]} ${props.className}`
})
</script>
