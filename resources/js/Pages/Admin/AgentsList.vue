<template>
  <div>
    <PageHeader
      title="Agents List"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Agents' }, { label: 'List' }]"
    >
      <template #actions>
        <Button @click="addAgent">
          <Plus size="16" class="mr-2" />
          Add Agent
        </Button>
      </template>
    </PageHeader>

    <AgentsTable />
  </div>
</template>

<script setup>
import { Plus } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import AgentsTable from '../Design/Components/AgentsTable.vue'
import Button from '../Design/Components/Button.vue'
import PageHeader from '../Design/Components/PageHeader.vue'

defineOptions({ layout: AdminLayout })

const addAgent = () => {
  router.visit('/admin/agents/add')
}
</script>
