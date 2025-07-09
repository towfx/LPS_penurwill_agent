<template>
  <div :class="`bg-white rounded-xl shadow-sm border border-stone-200 ${className}`">
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
