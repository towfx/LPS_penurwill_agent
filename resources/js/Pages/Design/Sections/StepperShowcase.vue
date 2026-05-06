<template>
  <Showcase
    title="Status Stepper"
    description="Payout lifecycle: Pending → Approved → Processing → Paid. Click a step to preview each state."
  >
    <Stepper :steps="steps" :active-index="active" />
    <div class="flex flex-wrap gap-2 pt-2">
      <Button
        v-for="(step, idx) in steps"
        :key="idx"
        variant="outline"
        size="sm"
        @click="active = idx"
      >
        {{ step.label }}
      </Button>
    </div>
  </Showcase>
</template>

<script setup>
import { ref } from 'vue'
import Showcase from '../Components/Showcase.vue'
import Stepper from '../Components/Stepper.vue'
import Button from '../Components/Button.vue'

const active = ref(2)
const steps = [
  { label: 'Pending', meta: '06 May' },
  { label: 'Approved', meta: '07 May' },
  { label: 'Processing', meta: '07 May' },
  { label: 'Paid', meta: '—' },
]
</script>
