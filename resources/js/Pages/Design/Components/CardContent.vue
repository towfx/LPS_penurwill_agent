<template>
  <div :class="`px-6 py-4 ${className}`">
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
