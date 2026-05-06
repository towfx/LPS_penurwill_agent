<template>
  <div :class="`animate-pulse bg-stone-200 rounded ${className}`" :style="style" />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  width: { type: [String, Number], default: '100%' },
  height: { type: [String, Number], default: '1rem' },
  className: { type: String, default: '' },
})

const toCss = (v) => (typeof v === 'number' ? `${v}px` : v)
const style = computed(() => ({ width: toCss(props.width), height: toCss(props.height) }))
</script>
