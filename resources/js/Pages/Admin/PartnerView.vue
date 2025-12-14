<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Partners</span> / <span class="text-stone-900 font-medium">View Partner</span>
    </nav>
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold text-forest-dark">Partner Details</h1>
      <div class="flex space-x-2">
        <button @click="goBack" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-medium transition-colors">
          Back to List
        </button>
        <button @click="goToEdit" class="bg-gold hover:bg-amber-700 text-white px-4 py-2 rounded font-medium transition-colors">
          Edit Partner
        </button>
      </div>
    </div>

    <div v-if="!partner" class="text-accent-red">Partner not found.</div>
    <div v-else class="space-y-6">
      <!-- Partner Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-forest-dark mb-4">Partner Information</h2>

        <div class="space-y-3">
          <div><span class="font-medium text-gray-700">Company Name:</span> {{ partner.company_name }}</div>
          <div><span class="font-medium text-gray-700">Company Registration Number:</span> {{ partner.company_registration_number }}</div>
          <div><span class="font-medium text-gray-700">Company Address:</span> {{ partner.company_address }}</div>
          <div><span class="font-medium text-gray-700">Company Phone:</span> {{ partner.company_phone }}</div>
          <div><span class="font-medium text-gray-700">Company Email:</span> {{ partner.company_email }}</div>
          <div><span class="font-medium text-gray-700">Partner Code:</span> <span class="font-mono bg-gray-100 px-2 py-1 rounded">{{ partner.code }}</span></div>
          <div><span class="font-medium text-gray-700">Status:</span> <span class="capitalize">{{ partner.status }}</span></div>
          <div><span class="font-medium text-gray-700">Created:</span> {{ partner.created_at }}</div>
        </div>

        <div v-if="partner.company_profile_file" class="mt-6">
          <span class="font-medium text-gray-700">Company Profile File:</span>
          <a :href="`/storage/${partner.company_profile_file}`" target="_blank" class="text-gold hover:text-amber-700 ml-2">
            View File
          </a>
        </div>
      </div>

      <!-- Linked Users -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Linked Users</h3>
        <div v-if="partner.users && partner.users.length > 0" class="space-y-3">
          <div v-for="user in partner.users" :key="user.id" class="flex justify-between">
            <div>
              <div class="font-medium text-gray-900">{{ user.name }}</div>
              <div class="text-sm text-gray-500">{{ user.email }}</div>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500">No users linked to this partner.</div>
      </div>

      <!-- Statistics -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Statistics</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-gray-600">Total Agents:</span>
            <span class="font-medium text-forest-dark ml-2">{{ partner.agents_count }}</span>
          </div>
          <div>
            <span class="text-gray-600">Child Partners:</span>
            <span class="font-medium text-forest-dark ml-2">{{ partner.children_count }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  partner: {
    type: Object,
    default: null
  }
})

const goToEdit = () => {
  router.visit(`/admin/partners/${props.partner.id}/update`)
}

const goBack = () => {
  router.visit('/admin/partners/list')
}
</script>

