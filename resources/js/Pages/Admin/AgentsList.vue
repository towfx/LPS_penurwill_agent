<template>
  <div>
    <PageHeader
      :title="pageTitle"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: roleNamesPlural.agent }, { label: pageTitle }]"
    >
      <template #actions>
        <Button @click="addAgent">
          <Plus size="16" class="mr-2" />
          Add {{ roleNames.agent }}
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
import { useRoleNames } from '../../composables/useRoleNames.js'

defineOptions({ layout: AdminLayout })

const page = usePage()
const { roleNames, roleNamesPlural } = useRoleNames()

const pageTitle = computed(() => {
  const params = new URLSearchParams(page.url.split('?')[1] || '')
  const typeParam = params.get('type') || params.get('role')

  if (typeParam === 'business_partner') return `${roleNamesPlural.value.business_partner} List`
  if (typeParam === 'agent_leader') return `${roleNamesPlural.value.agent_leader} List`
  if (typeParam === 'agent') return `${roleNamesPlural.value.agent} List`
  return `${roleNamesPlural.value.agent} List`
})

const addAgent = () => {
  router.visit('/admin/agents/add')
}
</script>
