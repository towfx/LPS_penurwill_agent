<template>
  <div class="mb-6">
    <Breadcrumb v-if="breadcrumbs?.length" :items="breadcrumbs" class="mb-3" />
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-forest-dark">{{ title }}</h1>
        <p v-if="description" class="text-sm sm:text-base text-stone-600 mt-1">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup>
import Breadcrumb from './Breadcrumb.vue'

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  breadcrumbs: { type: Array, default: () => [] },
})
</script>
