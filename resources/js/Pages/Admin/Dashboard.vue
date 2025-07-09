<template>
  <div class="py-8">
    <h1 class="text-3xl font-bold text-forest-dark mb-4">Admin Dashboard</h1>
    <p class="text-stone-700 text-lg">Welcome to the admin dashboard! Here you can manage users, view analytics, and configure system settings.</p>
  </div>
</template>

<script setup>
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({
  layout: AdminLayout
})
</script>
