<template>
  <div :class="`flex space-x-1 bg-stone-100 p-1 rounded-lg ${className}`">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  className: {
    type: String,
    default: ''
  }
})
</script>
