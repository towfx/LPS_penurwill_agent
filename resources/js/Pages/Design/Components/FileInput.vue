<template>
  <div>
    <label
      class="flex items-center gap-3 rounded-lg border-2 border-dashed border-stone-300 bg-stone-50 px-4 py-3 text-sm text-stone-600 cursor-pointer hover:border-amber-400 hover:bg-amber-50 transition-colors"
      :class="{ 'opacity-50 cursor-not-allowed': disabled }"
    >
      <UploadCloud class="h-5 w-5 text-stone-400" />
      <span class="flex-1">
        <span v-if="!fileName">Click to choose a file or drag &amp; drop</span>
        <span v-else class="text-stone-900 font-medium">{{ fileName }}</span>
      </span>
      <input
        ref="inputRef"
        type="file"
        :accept="accept"
        :disabled="disabled"
        class="hidden"
        @change="onChange"
      />
    </label>
    <button
      v-if="fileName"
      type="button"
      class="mt-2 text-xs text-red-600 hover:text-red-700"
      @click="clear"
    >
      Remove file
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { UploadCloud } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: [File, Object, null], default: null },
  accept: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
const inputRef = ref(null)

const fileName = computed(() => props.modelValue?.name || '')

const onChange = (e) => {
  const file = e.target.files?.[0] || null
  emit('update:modelValue', file)
}

const clear = () => {
  if (inputRef.value) inputRef.value.value = ''
  emit('update:modelValue', null)
}
</script>
