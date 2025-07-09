<template>
  <div :class="className">
    <slot />
  </div>
</template>

<script setup>
import { provide, ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    required: true
  },
  className: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const activeTab = ref(props.modelValue)

// Provide active tab and setter to child components
provide('activeTab', activeTab)
provide('setActiveTab', (value) => {
  activeTab.value = value
  emit('update:modelValue', value)
})

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  activeTab.value = newValue
})
</script>
