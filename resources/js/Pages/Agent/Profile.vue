<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  agent: {
    type: Object,
    default: null
  }
})

const goToEdit = () => {
  router.visit('/agent/profile/edit')
}

const isIndividual = computed(() => props.agent && props.agent.profile_type === 'individual')
const isCompany = computed(() => props.agent && props.agent.profile_type === 'company')
</script>

<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Profile</span>
    </nav>
    <h1 class="text-2xl font-bold text-forest-dark mb-4">Agent Profile</h1>

    <div v-if="!agent" class="text-accent-red">No agent profile found.</div>
    <div v-else class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto">
      <div class="mb-6 flex justify-between items-center">
        <h2 class="text-xl font-semibold text-forest-dark">{{ isIndividual ? 'Individual Agent' : 'Company Agent' }}</h2>
        <button @click="goToEdit" class="bg-gold hover:bg-amber-700 text-white px-4 py-2 rounded font-medium transition-colors">
          Edit Agent Profile
        </button>
      </div>
      <div v-if="isIndividual" class="space-y-3">
        <div><span class="font-medium text-gray-700">Name:</span> {{ agent.individual_name }}</div>
        <div><span class="font-medium text-gray-700">Phone:</span> {{ agent.individual_phone }}</div>
        <div><span class="font-medium text-gray-700">Address:</span> {{ agent.individual_address }}</div>
      </div>
      <div v-else-if="isCompany" class="space-y-3">
        <div><span class="font-medium text-gray-700">Company Name:</span> {{ agent.company_name }}</div>
        <div><span class="font-medium text-gray-700">Representative:</span> {{ agent.company_representative_name }}</div>
        <div><span class="font-medium text-gray-700">Registration Number:</span> {{ agent.company_registration_number }}</div>
        <div><span class="font-medium text-gray-700">Company Address:</span> {{ agent.company_address }}</div>
        <div><span class="font-medium text-gray-700">Company Phone:</span> {{ agent.company_phone }}</div>
      </div>
      <div class="mt-6">
        <span class="font-medium text-gray-700">Status:</span> <span class="capitalize">{{ agent.status }}</span>
      </div>
    </div>
  </div>
</template>
