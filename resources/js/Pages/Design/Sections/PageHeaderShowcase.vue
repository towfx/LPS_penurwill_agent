<template>
  <Showcase
    title="Page Header & Breadcrumb"
    description="Standard page top: breadcrumb → title row with right-side actions. Use PageHeader.vue at the top of every admin/agent page."
  >
    <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
      <PageHeader
        title="Agents"
        description="Manage your registered agents and their permissions."
        :breadcrumbs="[
          { label: 'Home', href: '/' },
          { label: 'Admin', href: '/admin' },
          { label: 'Agents' },
        ]"
      >
        <template #actions>
          <Button variant="outline" size="sm">
            <Filter class="h-4 w-4 mr-1.5" /> Filter
          </Button>
          <Button size="sm">
            <Plus class="h-4 w-4 mr-1.5" /> New agent
          </Button>
        </template>
      </PageHeader>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import PageHeader from '../Components/PageHeader.vue'
import Button from '../Components/Button.vue'
import { Filter, Plus } from 'lucide-vue-next'
</script>
