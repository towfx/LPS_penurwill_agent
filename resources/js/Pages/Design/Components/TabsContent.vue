<template>
  <div v-if="activeTab === value" :class="className">
    <slot />
  </div>
</template>

<script setup>
import { inject } from 'vue'

const props = defineProps({
  value: {
    type: String,
    required: true
  },
  className: {
    type: String,
    default: ''
  }
})

const activeTab = inject('activeTab')
</script>
