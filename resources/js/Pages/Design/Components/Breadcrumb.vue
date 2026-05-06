<template>
  <nav class="flex items-center text-sm text-stone-500" aria-label="Breadcrumb">
    <ol class="flex items-center flex-wrap gap-1.5">
      <li v-for="(item, idx) in items" :key="idx" class="flex items-center gap-1.5">
        <component
          :is="item.href ? 'a' : 'span'"
          :href="item.href"
          :class="[
            idx === items.length - 1
              ? 'text-forest-dark font-medium'
              : 'text-stone-500 hover:text-forest-dark transition-colors',
          ]"
        >
          {{ item.label }}
        </component>
        <ChevronRight v-if="idx < items.length - 1" class="h-3.5 w-3.5 text-stone-300" />
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { ChevronRight } from 'lucide-vue-next'

defineProps({
  items: { type: Array, default: () => [] },
})
</script>
