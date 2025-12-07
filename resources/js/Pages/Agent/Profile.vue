<script setup>
import { computed, ref } from 'vue'
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

// Dialog state
const showCopyDialog = ref(false)
const copiedValue = ref('')

// Computed properties for shareable URL
const shareableUrl = computed(() => {
  if (!props.agent?.referral_code?.code) return ''
  return `${props.penurwillWebsiteUrl}?ref=${props.agent.referral_code.code}`
})

const copyShareableUrl = async () => {
  try {
    await navigator.clipboard.writeText(shareableUrl.value)
    copiedValue.value = shareableUrl.value
    showCopyDialog.value = true
  } catch (err) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = shareableUrl.value
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    copiedValue.value = shareableUrl.value
    showCopyDialog.value = true
  }
}

const copyReferralCode = async () => {
  const code = props.agent?.referral_code?.code || 'YOUR_CODE'
  try {
    await navigator.clipboard.writeText(code)
    copiedValue.value = code
    showCopyDialog.value = true
  } catch (err) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = code
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    copiedValue.value = code
    showCopyDialog.value = true
  }
}

const copyCustomLink = async () => {
  const customLink = `${props.penurwillWebsiteUrl}/products?ref=${props.agent?.referral_code?.code || 'YOUR_CODE'}`
  try {
    await navigator.clipboard.writeText(customLink)
    copiedValue.value = customLink
    showCopyDialog.value = true
  } catch (err) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = customLink
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    copiedValue.value = customLink
    showCopyDialog.value = true
  }
}
</script>

<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Profile</span>
    </nav>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-forest-dark">Agent Profile</h1>
      <button @click="goToEdit" class="bg-gold hover:bg-amber-700 text-white px-4 py-2 rounded font-medium transition-colors">
        Edit Agent Profile
      </button>
    </div>

    <div v-if="!agent" class="text-accent-red">No agent profile found.</div>
    <div v-else class="space-y-6">
      <!-- Agent Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6 flex items-center">
          <div class="w-8 h-8 bg-forest-dark rounded-full flex items-center justify-center mr-3">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-forest-dark">{{ isIndividual ? 'Individual Agent' : 'Company Agent' }}</h2>
        </div>

        <div v-if="isIndividual" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Name</div>
                <div class="text-gray-900">{{ agent.individual_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Phone</div>
                <div class="text-gray-900">{{ agent.individual_phone }}</div>
              </div>
            </div>

            <div class="flex items-start md:col-span-3">
              <div class="w-6 h-6 bg-accent-orange rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Address</div>
                <div class="text-gray-900">{{ agent.individual_address }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="isCompany" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Company Name</div>
                <div class="text-gray-900">{{ agent.company_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Representative</div>
                <div class="text-gray-900">{{ agent.company_representative_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Registration Number</div>
                <div class="text-gray-900">{{ agent.company_registration_number }}</div>
              </div>
            </div>

            <div class="flex items-start md:col-span-2">
              <div class="w-6 h-6 bg-accent-orange rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Company Address</div>
                <div class="text-gray-900">{{ agent.company_address }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Company Phone</div>
                <div class="text-gray-900">{{ agent.company_phone }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200">
          <div class="flex items-center">
            <div class="w-6 h-6 bg-accent-red rounded-full flex items-center justify-center mr-3 flex-shrink-0">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium text-gray-500">Status</div>
              <div class="text-gray-900 capitalize">{{ agent.status }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Referral Code Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center mb-4">
          <div class="w-8 h-8 bg-accent-green rounded-full flex items-center justify-center mr-3">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-forest-dark">Referral Code Information</h3>
        </div>
        <div v-if="agent.referral_code" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Referral Code</div>
                <div class="text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">{{ agent.referral_code.code }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Commission Rate</div>
                <div class="text-gray-900">{{ agent.referral_code.commission_rate }}%</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-orange rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Usage Limit</div>
                <div class="text-gray-900">{{ agent.referral_code.usage_limit }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Used Count</div>
                <div class="text-gray-900">{{ agent.referral_code.used_count }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-red rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Status</div>
                <div class="text-gray-900">
                  <span :class="agent.referral_code.is_active ? 'text-accent-green' : 'text-accent-red'">
                    {{ agent.referral_code.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Expires</div>
                <div class="text-gray-900">{{ new Date(agent.referral_code.expires_at).toLocaleDateString() }}</div>
              </div>
            </div>
          </div>

          <!-- Shareable URL -->
          <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center">
                <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                  </svg>
                </div>
                <span class="font-medium text-gray-700">Shareable URL</span>
              </div>
              <button
                @click="copyShareableUrl"
                class="px-3 py-1 bg-gold hover:bg-amber-700 text-white text-sm rounded font-medium transition-colors"
              >
                Copy
              </button>
            </div>
            <div class="p-3 bg-gray-50 rounded border font-mono text-sm break-all">
              {{ shareableUrl }}
            </div>
            <p class="text-sm text-gray-500 mt-2">
              Note: You may append any landing page with ?ref={{ agent.referral_code.code }}
            </p>
          </div>
        </div>
        <div v-else class="text-gray-500">No referral code information available.</div>
      </div>

      <!-- How to Share Referral Code Card -->
      <div class="bg-gradient-to-br from-accent-green/10 to-accent-blue/10 rounded-lg shadow p-6 border border-accent-green/20">
        <div class="flex items-center mb-4">
          <div class="w-10 h-10 bg-accent-green rounded-full flex items-center justify-center mr-3">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-forest-dark">How to Share & Earn Commissions</h3>
        </div>

        <div class="space-y-6">
          <!-- Method 1: Direct Code Sharing -->
          <div class="bg-white rounded-lg p-4 border border-accent-green/30">
            <div class="flex items-center mb-3">
              <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center mr-3">
                <span class="text-white font-bold text-sm">1</span>
              </div>
              <h4 class="font-semibold text-forest-dark">Share Your Referral Code</h4>
            </div>
            <p class="text-gray-700 mb-3">Ask your clients to enter your referral code during checkout:</p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
              <div class="flex items-center justify-between">
                <span class="font-mono text-lg font-bold text-amber-800">{{ agent.referral_code?.code || 'YOUR_CODE' }}</span>
                <button @click="copyReferralCode" class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded font-medium transition-colors">
                  Copy Code
                </button>
              </div>
            </div>
          </div>

          <!-- Method 2: Shareable Links -->
          <div class="bg-white rounded-lg p-4 border border-accent-green/30">
            <div class="flex items-center mb-3">
              <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center mr-3">
                <span class="text-white font-bold text-sm">2</span>
              </div>
              <h4 class="font-semibold text-forest-dark">Share Direct Links</h4>
            </div>
            <p class="text-gray-700 mb-3">Share these pre-configured links with your clients:</p>

            <div class="space-y-3">
              <div class="bg-gray-50 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700">Main Website:</span>
                  <button @click="copyShareableUrl" class="px-2 py-1 bg-accent-blue hover:bg-accent-blue/80 text-white text-xs rounded transition-colors">
                    Copy
                  </button>
                </div>
                <div class="font-mono text-sm text-gray-600 break-all">
                  {{ shareableUrl }}
                </div>
              </div>

              <div class="bg-gray-50 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700">Custom Page Example:</span>
                  <button @click="copyCustomLink" class="px-2 py-1 bg-accent-blue hover:bg-accent-blue/80 text-white text-xs rounded transition-colors">
                    Copy
                  </button>
                </div>
                <div class="font-mono text-sm text-gray-600 break-all">
                  {{ penurwillWebsiteUrl }}/products?ref={{ agent.referral_code?.code || 'YOUR_CODE' }}
                </div>
              </div>
            </div>
          </div>

          <!-- Tips Section -->
          <div class="bg-accent-orange/10 rounded-lg p-4 border border-accent-orange/20">
            <div class="flex items-center mb-3">
              <div class="w-8 h-8 bg-accent-orange rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <h4 class="font-semibold text-forest-dark">Pro Tips</h4>
            </div>
            <ul class="text-sm text-gray-700 space-y-2">
              <li class="flex items-start">
                <span class="text-accent-orange mr-2">•</span>
                You can append <code class="bg-gray-100 px-1 rounded text-xs">?ref={{ agent.referral_code?.code || 'YOUR_CODE' }}</code> to any page URL
              </li>
              <li class="flex items-start">
                <span class="text-accent-orange mr-2">•</span>
                Share on social media, WhatsApp, or email with your personalized links
              </li>
              <li class="flex items-start">
                <span class="text-accent-orange mr-2">•</span>
                Track your earnings in the Commissions section
              </li>
              <li class="flex items-start">
                <span class="text-accent-orange mr-2">•</span>
                Commission rate: <strong>{{ agent.referral_code?.commission_rate || 0 }}%</strong> on successful referrals
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Bank Account Information (Moved to bottom) -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center mb-4">
          <div class="w-8 h-8 bg-accent-blue rounded-full flex items-center justify-center mr-3">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-forest-dark">Bank Account Information</h3>
        </div>
        <div v-if="agent.bank_account" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Account Name</div>
                <div class="text-gray-900">{{ agent.bank_account.account_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Account Number</div>
                <div class="text-gray-900 font-mono">{{ agent.bank_account.account_number }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-orange rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Bank Name</div>
                <div class="text-gray-900">{{ agent.bank_account.bank_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">IBAN</div>
                <div class="text-gray-900 font-mono">{{ agent.bank_account.iban }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-red rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">SWIFT Code</div>
                <div class="text-gray-900 font-mono">{{ agent.bank_account.swift_code }}</div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
          </div>
          <h4 class="text-lg font-medium text-gray-900 mb-2">No Bank Account Information</h4>
          <p class="text-gray-500 mb-4">Add your bank account details to receive commission payouts.</p>
          <button @click="goToEdit" class="bg-accent-blue hover:bg-accent-blue/80 text-white px-6 py-2 rounded font-medium transition-colors">
            Add Bank Account
          </button>
        </div>
      </div>
    </div>

    <!-- Copy Success Dialog -->
    <div v-if="showCopyDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showCopyDialog = false">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Copied!</h3>
          </div>
        </div>
        <div class="mb-4">
          <p class="text-sm text-gray-700 break-all">{{ copiedValue }}</p>
        </div>
        <div class="flex justify-end">
          <button @click="showCopyDialog = false" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-medium transition-colors">
            OK
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
