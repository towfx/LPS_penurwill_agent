<template>
  <Showcase
    title="Pagination"
    description="Use under tables. Emits update:currentPage — wire to Inertia router.get with the new page param."
  >
    <Pagination
      v-model:current-page="page"
      :per-page="10"
      :total="247"
    />
  </Showcase>
</template>

<script setup>
import { ref } from 'vue'
import Showcase from '../Components/Showcase.vue'
import Pagination from '../Components/Pagination.vue'

const page = ref(3)
</script>
