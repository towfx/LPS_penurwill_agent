<template>
  <Showcase
    title="Loading / Skeleton"
    description="Use skeletons in place of spinners while content is loading. Keep shapes close to the real layout."
  >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="rounded-lg border border-stone-200 p-4 space-y-3">
        <div class="flex items-center gap-3">
          <Skeleton width="40px" height="40px" class-name="!rounded-full" />
          <div class="flex-1 space-y-2">
            <Skeleton width="40%" height="0.75rem" />
            <Skeleton width="60%" height="0.75rem" />
          </div>
        </div>
        <Skeleton height="0.75rem" />
        <Skeleton width="80%" height="0.75rem" />
      </div>
      <div class="rounded-lg border border-stone-200 p-4 space-y-2">
        <Skeleton v-for="n in 5" :key="n" height="2rem" />
      </div>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import Skeleton from '../Components/Skeleton.vue'
</script>
