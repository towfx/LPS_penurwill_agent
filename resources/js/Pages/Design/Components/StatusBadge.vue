<template>
  <span :class="`status-badge ${variantClass}`">
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: 'pending' },
  label: { type: String, default: '' },
})

const variantClass = computed(() => {
  const map = {
    pending: 'status-pending',
    paid: 'status-paid',
    cancelled: 'status-cancelled',
    rejected: 'status-cancelled',
    expired: 'status-cancelled',
    suspended: 'status-cancelled',
    active: 'status-paid',
    inactive: 'bg-stone-100 text-stone-600',
    approved: 'status-paid',
    processing: 'bg-blue-100 text-blue-800',
  }
  return map[props.status] || 'status-pending'
})
</script>
