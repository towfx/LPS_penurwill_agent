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
        <button v-if="agent.status !== 'active'" @click="showApproveDialog" class="bg-accent-green hover:bg-green-700 text-white px-4 py-2 rounded font-medium transition-colors">
          Approve Agent
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
            <a :href="getFileUrl('individual_id_file')" target="_blank" class="text-gold hover:text-amber-700 ml-2">
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
            <a :href="getFileUrl('company_reg_file')" target="_blank" class="text-gold hover:text-amber-700 ml-2">
              View File
            </a>
          </div>
        </div>

        <div v-if="agent.about" class="mt-6 space-y-2">
          <div>
            <span class="font-medium text-gray-700">{{ isIndividual ? 'About Me' : 'About Company' }}:</span>
            <p class="mt-1 text-gray-600 whitespace-pre-wrap">{{ agent.about }}</p>
          </div>
        </div>

        <div class="mt-6 space-y-2">
          <div>
            <span class="font-medium text-gray-700">Status:</span>
            <span :class="getStatusPillClass(agent.status)" class="ml-2">
              {{ agent.status.charAt(0).toUpperCase() + agent.status.slice(1) }}
            </span>
          </div>
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
        </div>
        <div v-else class="text-gray-500">No referral code information available.</div>
      </div>
    </div>

    <!-- Approval Confirmation Dialog -->
    <div v-if="showApproveDialogModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeApproveDialog">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Approve Agent Application</h3>
          </div>
        </div>
        <div class="mb-4">
          <p class="text-sm text-gray-700 mb-2">Are you sure to approve this agent application?</p>
          <p class="text-sm font-medium text-forest-dark">Agent Name: {{ agentName }}</p>
        </div>
        <div class="flex justify-end space-x-3">
          <button @click="closeApproveDialog" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded font-medium transition-colors">
            Cancel
          </button>
          <button @click="approveAgent" :disabled="isApproving" class="bg-accent-green hover:bg-green-700 text-white px-4 py-2 rounded font-medium transition-colors">
            <span v-if="isApproving">Approving...</span>
            <span v-else>Confirm</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  agent: {
    type: Object,
    default: null
  }
})

const showApproveDialogModal = ref(false)
const isApproving = ref(false)

const agentName = computed(() => {
  if (!props.agent) return ''
  return props.agent.profile_type === 'individual'
    ? props.agent.individual_name
    : props.agent.company_name
})

const getStatusPillClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'active':
      return 'bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm font-medium'
    case 'inactive':
      return 'bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-sm font-medium'
    case 'suspended':
      return 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-sm font-medium'
    case 'banned':
      return 'bg-red-100 text-red-800 px-2 py-1 rounded-full text-sm font-medium'
    default:
      return 'bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-sm font-medium'
  }
}

// Helper function to generate file URL with cache-busting parameter
const getFileUrl = (field) => {
  if (!props.agent) return ''
  // Use updated_at timestamp for cache-busting, or current timestamp as fallback
  const timestamp = props.agent.updated_at 
    ? new Date(props.agent.updated_at).getTime() 
    : Date.now()
  return `/admin/agents/${props.agent.id}/file/${field}?t=${timestamp}`
}

const showApproveDialog = () => {
  showApproveDialogModal.value = true
}

const closeApproveDialog = () => {
  showApproveDialogModal.value = false
}

const approveAgent = async () => {
  isApproving.value = true
  try {
    await router.post(`/admin/agents/${props.agent.id}/approve`, {}, {
      onSuccess: () => {
        router.visit('/admin/agents/list')
      },
      onError: (errors) => {
        console.error('Approval errors:', errors)
      }
    })
  } catch (error) {
    console.error('Approval error:', error)
  } finally {
    isApproving.value = false
  }
}

const goToEdit = () => {
  router.visit(`/admin/agents/${props.agent.id}/update`)
}

const goBack = () => {
  router.visit('/admin/agents/list')
}

const isIndividual = computed(() => props.agent && props.agent.profile_type === 'individual')
const isCompany = computed(() => props.agent && props.agent.profile_type === 'company')
</script>
