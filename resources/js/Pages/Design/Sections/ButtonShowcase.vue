<template>
  <Showcase
    title="Buttons"
    description="6 variants × 4 sizes. Use Button.vue with `variant` and `size` props."
  >
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Variants</p>
      <div class="flex flex-wrap gap-2">
        <Button variant="default">Default</Button>
        <Button variant="secondary">Secondary</Button>
        <Button variant="destructive">Destructive</Button>
        <Button variant="outline">Outline</Button>
        <Button variant="ghost">Ghost</Button>
        <Button variant="link">Link</Button>
      </div>
    </div>

    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Sizes</p>
      <div class="flex flex-wrap items-center gap-2">
        <Button size="sm">Small</Button>
        <Button size="default">Default</Button>
        <Button size="lg">Large</Button>
        <Button size="icon" aria-label="Settings"><Settings class="h-4 w-4" /></Button>
      </div>
    </div>

    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">With icons</p>
      <div class="flex flex-wrap gap-2">
        <Button><Plus class="h-4 w-4 mr-1.5" /> Create</Button>
        <Button variant="outline"><Download class="h-4 w-4 mr-1.5" /> Export</Button>
        <Button variant="destructive"><Trash2 class="h-4 w-4 mr-1.5" /> Delete</Button>
      </div>
    </div>

    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Disabled</p>
      <div class="flex flex-wrap gap-2">
        <Button disabled>Default</Button>
        <Button variant="secondary" disabled>Secondary</Button>
        <Button variant="outline" disabled>Outline</Button>
      </div>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import Button from '../Components/Button.vue'
import { Plus, Download, Trash2, Settings } from 'lucide-vue-next'
</script>
