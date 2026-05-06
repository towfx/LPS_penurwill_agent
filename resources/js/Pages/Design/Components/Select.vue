<template>
  <div class="relative">
    <select
      :value="modelValue"
      :disabled="disabled"
      :class="selectClasses"
      @change="$emit('update:modelValue', $event.target.value)"
      v-bind="$attrs"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option v-for="opt in normalizedOptions" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
      <slot />
    </select>
    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  className: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const normalizedOptions = computed(() =>
  props.options.map(o => (typeof o === 'object' ? o : { value: o, label: o }))
)

const selectClasses = computed(() => {
  const base = 'w-full appearance-none rounded-lg border bg-white pl-3 pr-9 py-2 text-sm text-stone-900 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:bg-stone-50 disabled:text-stone-400 disabled:cursor-not-allowed'
  const state = props.invalid
    ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
    : 'border-stone-300 focus:border-amber-500 focus:ring-amber-200'
  return `${base} ${state} ${props.className}`
})
</script>
