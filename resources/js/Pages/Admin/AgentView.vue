<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Agents</span> / <span class="text-stone-900 font-medium">View Agent</span>
    </nav>
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold text-forest-dark">Agent Details</h1>
      <div class="flex space-x-2">
        <button @click="goBack" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-medium transition-colors">
          Back to List
        </button>
        <button @click="goToEdit" class="bg-gold hover:bg-amber-700 text-white px-4 py-2 rounded font-medium transition-colors">
          Edit Agent
        </button>
      </div>
    </div>

    <div v-if="!agent" class="text-accent-red">Agent not found.</div>
    <div v-else class="space-y-6">
      <!-- Agent Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-forest-dark mb-4">{{ isIndividual ? 'Individual Agent' : 'Company Agent' }}</h2>

        <div v-if="isIndividual" class="space-y-3">
          <div><span class="font-medium text-gray-700">Name:</span> {{ agent.individual_name }}</div>
          <div><span class="font-medium text-gray-700">Phone:</span> {{ agent.individual_phone }}</div>
          <div><span class="font-medium text-gray-700">Address:</span> {{ agent.individual_address }}</div>
          <div v-if="agent.individual_id_number">
            <span class="font-medium text-gray-700">NRIC/Passport Number:</span> {{ agent.individual_id_number }}
          </div>
          <div v-if="agent.individual_id_file">
            <span class="font-medium text-gray-700">Copy of IC/Passport:</span>
            <a :href="`/admin/agents/${agent.id}/file/individual_id_file`" target="_blank" class="text-gold hover:text-amber-700 ml-2">
              View File
            </a>
          </div>
        </div>
        <div v-else-if="isCompany" class="space-y-3">
          <div><span class="font-medium text-gray-700">Company Name:</span> {{ agent.company_name }}</div>
          <div><span class="font-medium text-gray-700">Representative:</span> {{ agent.company_representative_name }}</div>
          <div><span class="font-medium text-gray-700">Registration Number:</span> {{ agent.company_registration_number }}</div>
          <div><span class="font-medium text-gray-700">Company Address:</span> {{ agent.company_address }}</div>
          <div><span class="font-medium text-gray-700">Company Phone:</span> {{ agent.company_phone }}</div>
          <div v-if="agent.company_reg_file">
            <span class="font-medium text-gray-700">Business Registration Certificate:</span>
            <a :href="`/admin/agents/${agent.id}/file/company_reg_file`" target="_blank" class="text-gold hover:text-amber-700 ml-2">
              View File
            </a>
          </div>
        </div>

        <div class="mt-6 space-y-2">
          <div><span class="font-medium text-gray-700">Status:</span> <span class="capitalize">{{ agent.status }}</span></div>
          <div><span class="font-medium text-gray-700">User Email:</span> {{ agent.user_email }}</div>
          <div><span class="font-medium text-gray-700">Created:</span> {{ agent.created_at }}</div>
        </div>
      </div>

      <!-- Bank Account Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Bank Account Information</h3>
        <div v-if="agent.bank_account" class="space-y-3">
          <div><span class="font-medium text-gray-700">Account Name:</span> {{ agent.bank_account.account_name }}</div>
          <div><span class="font-medium text-gray-700">Account Number:</span> {{ agent.bank_account.account_number }}</div>
          <div><span class="font-medium text-gray-700">Bank Name:</span> {{ agent.bank_account.bank_name }}</div>
          <div><span class="font-medium text-gray-700">IBAN:</span> {{ agent.bank_account.iban }}</div>
          <div><span class="font-medium text-gray-700">SWIFT Code:</span> {{ agent.bank_account.swift_code }}</div>
        </div>
        <div v-else class="text-gray-500">No bank account information available.</div>
      </div>

      <!-- Referral Code Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Referral Code Information</h3>
        <div v-if="agent.referral_code" class="space-y-3">
          <div><span class="font-medium text-gray-700">Referral Code:</span> <span class="font-mono bg-gray-100 px-2 py-1 rounded">{{ agent.referral_code.code }}</span></div>
          <div><span class="font-medium text-gray-700">Commission Rate:</span> {{ agent.referral_code.commission_rate }}%</div>
          <div><span class="font-medium text-gray-700">Used Count:</span> {{ agent.referral_code.used_count }}</div>
          <div><span class="font-medium text-gray-700">Status:</span>
            <span :class="agent.referral_code.is_active ? 'text-accent-green' : 'text-accent-red'">
              {{ agent.referral_code.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <div><span class="font-medium text-gray-700">Expires:</span> {{ new Date(agent.referral_code.expires_at).toLocaleDateString() }}</div>
        </div>
        <div v-else class="text-gray-500">No referral code information available.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  agent: {
    type: Object,
    default: null
  }
})

const goToEdit = () => {
  router.visit(`/admin/agents/${props.agent.id}/update`)
}

const goBack = () => {
  router.visit('/admin/agents/list')
}

const isIndividual = computed(() => props.agent && props.agent.profile_type === 'individual')
const isCompany = computed(() => props.agent && props.agent.profile_type === 'company')
</script>
