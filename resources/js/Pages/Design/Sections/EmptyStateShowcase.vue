<template>
  <Showcase
    title="Empty State"
    description="Use on every list screen when no records exist. Keep messaging actionable — point to next step."
  >
    <EmptyState
      title="No payouts yet"
      description="When agents request payouts, they'll show up here for your review."
      icon="Inbox"
    >
      <template #action>
        <Button size="sm">
          <Plus class="h-4 w-4 mr-1.5" /> Create payout
        </Button>
      </template>
    </EmptyState>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import EmptyState from '../Components/EmptyState.vue'
import Button from '../Components/Button.vue'
import { Plus } from 'lucide-vue-next'
</script>
