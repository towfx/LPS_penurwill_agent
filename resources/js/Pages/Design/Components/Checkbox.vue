<template>
  <label class="inline-flex items-center gap-2 cursor-pointer select-none" :class="{ 'opacity-50 cursor-not-allowed': disabled }">
    <input
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-2 focus:ring-amber-200 focus:ring-offset-1"
      @change="$emit('update:modelValue', $event.target.checked)"
      v-bind="$attrs"
    />
    <span v-if="$slots.default || label" class="text-sm text-stone-700">
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>
