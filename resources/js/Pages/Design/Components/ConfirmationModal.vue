<template>
  <Modal
    :model-value="modelValue"
    :title="title"
    :description="description"
    size="sm"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <p v-if="body" class="text-sm text-stone-600">{{ body }}</p>
    <slot />
    <template #footer>
      <Button variant="outline" size="sm" @click="$emit('update:modelValue', false)">
        {{ cancelLabel }}
      </Button>
      <Button :variant="confirmVariant" size="sm" @click="$emit('confirm')">
        {{ confirmLabel }}
      </Button>
    </template>
  </Modal>
</template>

<script setup>
import Modal from './Modal.vue'
import Button from './Button.vue'

defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Are you sure?' },
  description: { type: String, default: '' },
  body: { type: String, default: '' },
  cancelLabel: { type: String, default: 'Cancel' },
  confirmLabel: { type: String, default: 'Confirm' },
  confirmVariant: { type: String, default: 'destructive' },
})

defineEmits(['update:modelValue', 'confirm'])
</script>
