<template>
  <!--
    Showcase: a reusable card wrapper for design-system reference sections.
    Use it inside Design01 to demonstrate a single component with title +
    description + body. Refactored out of repeated <Card><CardHeader>… markup.
  -->
  <Card :class="`mb-6 ${className}`">
    <CardHeader v-if="title || description">
      <CardTitle>{{ title }}</CardTitle>
      <p v-if="description" class="text-sm text-stone-500 mt-1">{{ description }}</p>
    </CardHeader>
    <CardContent class="space-y-4">
      <slot />
    </CardContent>
  </Card>
</template>

<script setup>
import Card from './Card.vue'
import CardHeader from './CardHeader.vue'
import CardTitle from './CardTitle.vue'
import CardContent from './CardContent.vue'

defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  className: { type: String, default: '' },
})
</script>
