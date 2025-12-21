<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Partners</span> / <span class="text-stone-900 font-medium">List</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-forest-dark">Business Partners List</h1>
      <Button @click="addPartner">
        <Plus size="16" class="mr-2" />
        Add Business Partner
      </Button>
    </div>

    <PartnersTable :initial-partners="partners" :initial-pagination="pagination" />
  </div>
</template>

<script setup>
import { Plus } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import PartnersTable from '../Design/Components/PartnersTable.vue'
import Button from '../Design/Components/Button.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  partners: {
    type: Array,
    default: () => []
  },
  pagination: {
    type: Object,
    default: () => null
  }
})

const addPartner = () => {
  router.visit('/admin/partners/add')
}
</script>

