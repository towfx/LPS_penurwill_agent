<template>
  <div :class="`space-y-1.5 ${className}`">
    <label v-if="label" :for="forId" class="block text-sm font-medium text-stone-700">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <slot />
    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="text-xs text-stone-500">{{ hint }}</p>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, default: '' },
  forId: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  className: { type: String, default: '' },
})
</script>
