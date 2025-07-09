<template>
  <div :class="`px-6 py-4 border-b border-stone-200 ${className}`">
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
