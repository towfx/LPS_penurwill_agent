<template>
  <h3 :class="`text-lg font-semibold text-stone-900 ${className}`">
    <slot />
  </h3>
</template>

<script setup>
defineProps({
  className: {
    type: String,
    default: ''
  }
})
</script>
