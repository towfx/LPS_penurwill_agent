<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'

defineOptions({ layout: AgentLayout })

// Props
const props = defineProps({
  agent: {
    type: Object,
    default: null
  },
  penurwillWebsiteUrl: {
    type: String,
    default: 'https://penurwill.com'
  }
})

const goToEdit = () => {
  router.visit('/agent/profile/edit')
}

const isIndividual = computed(() => props.agent && props.agent.profile_type === 'individual')
const isCompany = computed(() => props.agent && props.agent.profile_type === 'company')

// Computed properties for shareable URL
const shareableUrl = computed(() => {
  if (!props.agent?.referral_code?.code) return ''
  return `${props.penurwillWebsiteUrl}?ref=${props.agent.referral_code.code}`
})

const copyShareableUrl = async () => {
  try {
    await navigator.clipboard.writeText(shareableUrl.value)
    // You could add a toast notification here
  } catch (err) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = shareableUrl.value
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
  }
}
</script>

<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Profile</span>
    </nav>
    <h1 class="text-2xl font-bold text-forest-dark mb-4">Agent Profile</h1>

    <div v-if="!agent" class="text-accent-red">No agent profile found.</div>
    <div v-else class="space-y-6">
      <!-- Agent Information -->
      <div class="bg-white rounded-lg shadow p-6">
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
          <div><span class="font-medium text-gray-700">Usage Limit:</span> {{ agent.referral_code.usage_limit }}</div>
          <div><span class="font-medium text-gray-700">Used Count:</span> {{ agent.referral_code.used_count }}</div>
          <div><span class="font-medium text-gray-700">Status:</span>
            <span :class="agent.referral_code.is_active ? 'text-accent-green' : 'text-accent-red'">
              {{ agent.referral_code.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <div><span class="font-medium text-gray-700">Expires:</span> {{ new Date(agent.referral_code.expires_at).toLocaleDateString() }}</div>

          <!-- Shareable URL -->
          <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <span class="font-medium text-gray-700">Shareable URL:</span>
              <button
                @click="copyShareableUrl"
                class="px-3 py-1 bg-gold hover:bg-amber-700 text-white text-sm rounded font-medium transition-colors"
              >
                Copy
              </button>
            </div>
            <div class="mt-2 p-3 bg-gray-50 rounded border font-mono text-sm break-all">
              {{ shareableUrl }}
            </div>
            <p class="text-sm text-gray-500 mt-2">
              Note: You may append any landing page with ?ref={{ agent.referral_code.code }}
            </p>
          </div>

          <!-- Usage Instructions -->
          <div class="mt-4 pt-4 border-t border-gray-200">
            <h4 class="font-medium text-gray-900 mb-3">How to use your referral code:</h4>

            <div class="space-y-4">
              <div>
                <h5 class="font-medium text-gray-800 mb-2">Option 1</h5>
                <p class="text-sm text-gray-700">
                  Ask your client to use code <span class="font-mono bg-gray-100 px-1 rounded">{{ agent.referral_code.code }}</span> during checkout
                </p>
              </div>

              <div>
                <h5 class="font-medium text-gray-800 mb-2">Option 2</h5>
                <p class="text-sm text-gray-700 mb-2">
                  Ask your client to click shareable link <span class="font-mono bg-gray-100 px-1 rounded">{{ shareableUrl }}</span>
                </p>
                <p class="text-sm text-gray-700">
                  You may use customized link like <span class="font-mono bg-gray-100 px-1 rounded">{{ penurwillWebsiteUrl }}/[any page]/?ref={{ agent.referral_code.code }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500">No referral code information available.</div>
      </div>
    </div>
  </div>
</template>
