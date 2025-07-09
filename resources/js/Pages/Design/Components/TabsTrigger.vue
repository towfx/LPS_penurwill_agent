<template>
  <button
    @click="setActiveTab(value)"
    :class="`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${
      activeTab === value
        ? 'bg-white text-stone-900 shadow-sm'
        : 'text-stone-600 hover:text-stone-900'
    } ${className}`"
  >
    <slot />
  </button>
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
const setActiveTab = inject('setActiveTab')
</script>
