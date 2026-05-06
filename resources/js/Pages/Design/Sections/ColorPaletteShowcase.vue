<template>
  <Showcase
    title="Color Palette"
    description="Brand + accent + neutrals. Use the Tailwind utility names listed below — they are wired in app.css."
  >
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Brand</p>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <Swatch name="forest-dark" hex="#162d25" class-name="bg-forest-dark text-white" />
        <Swatch name="forest-light" hex="#5d775f" class-name="bg-forest-light text-white" />
        <Swatch name="gold" hex="#bc9c5f" class-name="bg-gold text-white" />
        <Swatch name="cream" hex="#eae1d0" class-name="bg-cream text-forest-dark" />
      </div>
    </div>

    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Accents</p>
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <Swatch name="accent-red" hex="#d4423f" class-name="bg-accent-red text-white" />
        <Swatch name="accent-orange" hex="#e07b39" class-name="bg-accent-orange text-white" />
        <Swatch name="accent-green" hex="#7a9b7d" class-name="bg-accent-green text-white" />
        <Swatch name="accent-blue" hex="#4a6b73" class-name="bg-accent-blue text-white" />
        <Swatch name="accent-gray" hex="#8a9ba8" class-name="bg-accent-gray text-white" />
      </div>
    </div>

    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Stone scale</p>
      <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
        <div class="bg-stone-50 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-stone-700">50</div>
        <div class="bg-stone-100 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-stone-700">100</div>
        <div class="bg-stone-200 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-stone-700">200</div>
        <div class="bg-stone-300 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-stone-700">300</div>
        <div class="bg-stone-400 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-stone-700">400</div>
        <div class="bg-stone-500 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-white">500</div>
        <div class="bg-stone-600 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-white">600</div>
        <div class="bg-stone-700 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-white">700</div>
        <div class="bg-stone-800 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-white">800</div>
        <div class="bg-stone-900 h-12 rounded-md flex items-end justify-center p-1 text-[10px] text-white">900</div>
      </div>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'

const Swatch = {
  props: ['name', 'hex', 'className'],
  template: `
    <div class="rounded-lg overflow-hidden border border-stone-200">
      <div :class="['h-14 flex items-end p-2 text-xs font-medium', className]">
        {{ name }}
      </div>
      <div class="px-2 py-1.5 text-[11px] text-stone-500 font-mono">{{ hex }}</div>
    </div>
  `,
}
</script>
