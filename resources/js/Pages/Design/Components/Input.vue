<template>
  <input
    :type="type"
    :value="modelValue"
    :disabled="disabled"
    :placeholder="placeholder"
    :class="inputClasses"
    @input="$emit('update:modelValue', $event.target.value)"
    v-bind="$attrs"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  className: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const inputClasses = computed(() => {
  const base = 'w-full rounded-lg border bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:bg-stone-50 disabled:text-stone-400 disabled:cursor-not-allowed'
  const state = props.invalid
    ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
    : 'border-stone-300 focus:border-amber-500 focus:ring-amber-200'
  return `${base} ${state} ${props.className}`
})
</script>
