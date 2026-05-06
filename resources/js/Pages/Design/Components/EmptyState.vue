<template>
  <div class="flex flex-col items-center justify-center text-center px-6 py-12">
    <div class="w-14 h-14 rounded-full bg-stone-100 flex items-center justify-center mb-4">
      <component :is="iconComponent" class="h-6 w-6 text-stone-400" />
    </div>
    <h3 class="text-base font-semibold text-stone-900">{{ title }}</h3>
    <p v-if="description" class="text-sm text-stone-500 mt-1 max-w-md">{{ description }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue'
import { Inbox, Search, FileX, Users, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: [String, Object, Function], default: 'Inbox' },
})

const iconMap = { Inbox, Search, FileX, Users, AlertCircle }

const iconComponent = computed(() => {
  if (typeof props.icon === 'string') return iconMap[props.icon] || Inbox
  return props.icon
})
</script>
