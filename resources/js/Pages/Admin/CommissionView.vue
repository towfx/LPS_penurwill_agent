<template>
  <div>
    <PageHeader
      title="View Commission"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Commissions', href: '/admin/commissions/list' }, { label: 'View' }]"
    />
  </div>
</template>

<script setup>
import AdminLayout from '../Design/AdminLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'

defineOptions({ layout: AdminLayout })
</script>
