<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="onBackdrop">
        <div class="absolute inset-0 bg-black/50" />
        <div :class="`relative w-full ${sizeClass} bg-white rounded-xl shadow-xl border border-stone-200 max-h-[90vh] flex flex-col`">
          <div v-if="title || $slots.header" class="flex items-start justify-between p-5 border-b border-stone-200">
            <div>
              <h3 v-if="title" class="text-lg font-semibold text-forest-dark">{{ title }}</h3>
              <p v-if="description" class="text-sm text-stone-500 mt-1">{{ description }}</p>
              <slot name="header" />
            </div>
            <button
              v-if="dismissible"
              type="button"
              class="text-stone-400 hover:text-stone-600 p-1 rounded-md hover:bg-stone-100"
              @click="$emit('update:modelValue', false)"
            >
              <X class="h-5 w-5" />
            </button>
          </div>
          <div class="p-5 overflow-y-auto">
            <slot />
          </div>
          <div v-if="$slots.footer" class="flex items-center justify-end gap-2 p-5 border-t border-stone-200 bg-stone-50 rounded-b-xl">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  size: { type: String, default: 'md' },
  dismissible: { type: Boolean, default: true },
  closeOnBackdrop: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue'])

const sizeClass = computed(() => ({
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}[props.size] || 'max-w-md'))

const onBackdrop = () => {
  if (props.closeOnBackdrop) emit('update:modelValue', false)
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
