<template>
  <div :class="`w-full bg-stone-200 rounded-full h-2 ${className}`">
    <div
      class="bg-amber-600 h-2 rounded-full transition-all duration-300"
      :style="{ width: `${value}%` }"
    />
  </div>
</template>

<script setup>
defineProps({
  value: {
    type: Number,
    required: true
  },
  className: {
    type: String,
    default: ''
  }
})
</script>
