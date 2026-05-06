<template>
  <ol class="flex items-center w-full">
    <li
      v-for="(step, idx) in steps"
      :key="idx"
      class="flex items-center flex-1 last:flex-none"
    >
      <div class="flex flex-col items-center">
        <div
          :class="[
            'h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold border-2 transition-colors',
            idx < activeIndex
              ? 'bg-accent-green border-accent-green text-white'
              : idx === activeIndex
                ? 'bg-forest-dark border-forest-dark text-white'
                : 'bg-white border-stone-300 text-stone-400',
          ]"
        >
          <Check v-if="idx < activeIndex" class="h-4 w-4" />
          <span v-else>{{ idx + 1 }}</span>
        </div>
        <div class="mt-2 text-center">
          <p
            :class="[
              'text-xs font-medium whitespace-nowrap',
              idx <= activeIndex ? 'text-forest-dark' : 'text-stone-400',
            ]"
          >
            {{ step.label }}
          </p>
          <p v-if="step.meta" class="text-[11px] text-stone-400 mt-0.5">{{ step.meta }}</p>
        </div>
      </div>
      <div
        v-if="idx < steps.length - 1"
        :class="[
          'flex-1 h-0.5 mx-2 mt-[-1.75rem]',
          idx < activeIndex ? 'bg-accent-green' : 'bg-stone-200',
        ]"
      />
    </li>
  </ol>
</template>

<script setup>
import { Check } from 'lucide-vue-next'

defineProps({
  steps: { type: Array, required: true },
  activeIndex: { type: Number, default: 0 },
})
</script>
