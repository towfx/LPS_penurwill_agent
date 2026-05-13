<template>
  <div>
    <PageHeader
      :title="pageTitle"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Agents' }, { label: pageTitle }]"
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
import { computed } from 'vue'
import { Plus } from 'lucide-vue-next'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import AgentsTable from '../Design/Components/AgentsTable.vue'
import Button from '../Design/Components/Button.vue'
import PageHeader from '../Design/Components/PageHeader.vue'

defineOptions({ layout: AdminLayout })

const page = usePage()

const pageTitle = computed(() => {
  const params = new URLSearchParams(page.url.split('?')[1] || '')
  const typeParam = params.get('type') || params.get('role')
  
  if (typeParam === 'business_partner') return 'Business Partners List'
  if (typeParam === 'agent_leader') return 'Leaders List'
  if (typeParam === 'agent') return 'Agents List'
  return 'Agents List'
})

const addAgent = () => {
  router.visit('/admin/agents/add')
}
</script>
