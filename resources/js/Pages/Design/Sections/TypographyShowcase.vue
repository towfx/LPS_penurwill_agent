<template>
  <Showcase
    title="Typography"
    description="Use the Tailwind class strings shown on the right — these are the canonical sizes used across the app."
  >
    <div class="space-y-3">
      <Row tag="h1" cls="text-3xl font-bold text-forest-dark">Heading 1 — Page title</Row>
      <Row tag="h2" cls="text-2xl font-semibold text-forest-dark">Heading 2 — Section</Row>
      <Row tag="h3" cls="text-xl font-semibold text-stone-800">Heading 3 — Subsection</Row>
      <Row tag="h4" cls="text-lg font-semibold text-stone-800">Heading 4 — Card title</Row>
      <Row tag="p"  cls="text-sm text-stone-600">Body text — default paragraph copy used across forms and content blocks.</Row>
      <Row tag="p"  cls="text-sm font-medium text-stone-700">Label — used by FormField above inputs.</Row>
      <Row tag="p"  cls="text-xs text-stone-500">Muted / hint — secondary supporting text.</Row>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'

const Row = {
  props: ['tag', 'cls'],
  template: `
    <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-6 border-b border-stone-100 pb-2 last:border-0 last:pb-0">
      <component :is="tag" :class="[cls, 'flex-1 min-w-0']"><slot /></component>
      <code class="text-[11px] text-stone-400 shrink-0">{{ cls }}</code>
    </div>
  `,
}
</script>
