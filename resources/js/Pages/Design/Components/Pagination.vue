<template>
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <p class="text-xs text-stone-500">
      Showing <span class="font-medium text-stone-700">{{ from }}</span>–<span class="font-medium text-stone-700">{{ to }}</span>
      of <span class="font-medium text-stone-700">{{ total }}</span>
    </p>
    <nav class="inline-flex items-center gap-1">
      <button
        type="button"
        class="px-2 py-1.5 rounded-md border border-stone-200 text-stone-600 hover:bg-stone-50 disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="currentPage <= 1"
        @click="go(currentPage - 1)"
      >
        <ChevronLeft class="h-4 w-4" />
      </button>
      <button
        v-for="p in pages"
        :key="p.key"
        type="button"
        :disabled="p.value === '...'"
        :class="[
          'min-w-8 px-2 py-1 rounded-md text-sm transition-colors',
          p.value === currentPage
            ? 'bg-forest-dark text-white'
            : p.value === '...'
              ? 'text-stone-400 cursor-default'
              : 'text-stone-600 hover:bg-stone-100',
        ]"
        @click="p.value !== '...' && go(p.value)"
      >
        {{ p.value }}
      </button>
      <button
        type="button"
        class="px-2 py-1.5 rounded-md border border-stone-200 text-stone-600 hover:bg-stone-50 disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="currentPage >= totalPages"
        @click="go(currentPage + 1)"
      >
        <ChevronRight class="h-4 w-4" />
      </button>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  currentPage: { type: Number, default: 1 },
  perPage: { type: Number, default: 10 },
  total: { type: Number, default: 0 },
  siblingCount: { type: Number, default: 1 },
})

const emit = defineEmits(['update:currentPage', 'change'])

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))
const from = computed(() => props.total === 0 ? 0 : (props.currentPage - 1) * props.perPage + 1)
const to = computed(() => Math.min(props.currentPage * props.perPage, props.total))

const pages = computed(() => {
  const tp = totalPages.value
  const cp = props.currentPage
  const s = props.siblingCount
  const range = (a, b) => Array.from({ length: b - a + 1 }, (_, i) => a + i)
  if (tp <= 7) return range(1, tp).map(n => ({ key: n, value: n }))
  const left = Math.max(2, cp - s)
  const right = Math.min(tp - 1, cp + s)
  const items = [1]
  if (left > 2) items.push('...L')
  for (let i = left; i <= right; i++) items.push(i)
  if (right < tp - 1) items.push('...R')
  items.push(tp)
  return items.map((v, i) => ({ key: `${v}-${i}`, value: typeof v === 'string' ? '...' : v }))
})

const go = (page) => {
  if (page < 1 || page > totalPages.value) return
  emit('update:currentPage', page)
  emit('change', page)
}
</script>
