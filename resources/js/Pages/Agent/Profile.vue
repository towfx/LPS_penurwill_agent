<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import { useRoleNames } from '../../composables/useRoleNames.js'
import { 
  User, Phone, Mail, MapPin, FileText, CreditCard, Building, 
  CheckCircle, ExternalLink, Image as ImageIcon, Briefcase, 
  Award, Hash, Percent, Link as LinkIcon, Share2, Info
} from 'lucide-vue-next'

const { roleNames } = useRoleNames()

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
  return `https://penurwill.com/write/guest/start?rc=${props.agent.referral_code.code}`
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

// Helper function to generate file URL with cache-busting parameter
const getFileUrl = (field) => {
  if (!props.agent) return ''
  // Use updated_at timestamp for cache-busting, or current timestamp as fallback
  const timestamp = props.agent.updated_at 
    ? new Date(props.agent.updated_at).getTime() 
    : Date.now()
  return `/agent/profile/file/${field}?t=${timestamp}`
}

const isImage = (path) => {
  if (!path) return false
  const ext = path.split('.').pop().toLowerCase()
  return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)
}
</script>

<template>
  <div>
    <PageHeader
      :title="`${roleNames.agent} Profile`"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Profile' }]"
    >
      <template #actions>
        <Button variant="default" size="default" @click="goToEdit">
          Edit {{ roleNames.agent }} Profile
        </Button>
      </template>
    </PageHeader>

    <div v-if="!agent" class="text-accent-red">No agent profile found.</div>
    <div v-else class="space-y-6">
      <!-- Agent Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6 flex items-center">
          <div class="w-8 h-8 bg-forest-dark rounded-full flex items-center justify-center mr-3">
            <User v-if="isIndividual" class="w-4 h-4 text-white" />
            <Building v-else class="w-4 h-4 text-white" />
          </div>
          <h2 class="text-xl font-semibold text-forest-dark">{{ isIndividual ? `Individual ${roleNames.agent}` : `Company ${roleNames.agent}` }}</h2>
        </div>

        <div v-if="isIndividual" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <User class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Name</div>
                <div class="text-gray-900">{{ agent.individual_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Phone class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Phone</div>
                <div class="text-gray-900">{{ agent.individual_phone }}</div>
              </div>
            </div>

            <div v-if="agent.individual_email" class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Mail class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Alternative E-Mail Address</div>
                <div class="text-gray-900">{{ agent.individual_email }}</div>
              </div>
            </div>

            <div class="flex items-start md:col-span-3">
              <div class="w-6 h-6 bg-accent-orange rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                <MapPin class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Address</div>
                <div class="text-gray-900">{{ agent.individual_address }}</div>
              </div>
            </div>

            <div class="flex items-center md:col-span-2">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <FileText class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">NRIC/Passport Number</div>
                <div class="text-gray-900">{{ agent.individual_id_number }}</div>
              </div>
            </div>

            <div v-if="agent.individual_id_file" class="flex items-center">
              <div class="w-6 h-6 bg-accent-red rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Copy of IC/Passport</div>
                <div v-if="isImage(agent.individual_id_file)" class="mt-2 mb-1">
                  <img :src="getFileUrl('individual_id_file')" class="w-32 h-auto max-h-32 object-cover rounded border shadow-sm" />
                </div>
                <a :href="getFileUrl('individual_id_file')" target="_blank" class="text-gold hover:text-amber-700 font-medium flex items-center gap-1">
                  <ExternalLink class="w-3 h-3" />
                  View {{ isImage(agent.individual_id_file) ? 'Full Image' : 'File' }}
                </a>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="isCompany" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Building class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Company Name</div>
                <div class="text-gray-900">{{ agent.company_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <User class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Representative</div>
                <div class="text-gray-900">{{ agent.company_representative_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <FileText class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Representative ID Number</div>
                <div class="text-gray-900">{{ agent.company_representative_id_number }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Hash class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Registration Number</div>
                <div class="text-gray-900">{{ agent.company_registration_number }}</div>
              </div>
            </div>

            <div class="flex items-start md:col-span-2">
              <div class="w-6 h-6 bg-accent-orange rounded-full flex items-center justify-center mr-3 flex-shrink-0 mt-1">
                <MapPin class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Company Address</div>
                <div class="text-gray-900">{{ agent.company_address }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Phone class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Company Phone</div>
                <div class="text-gray-900">{{ agent.company_phone }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Mail class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Company E-Mail Address</div>
                <div class="text-gray-900">{{ agent.company_email_address }}</div>
              </div>
            </div>

            <div v-if="agent.company_reg_file" class="flex items-center">
              <div class="w-6 h-6 bg-accent-red rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Business Registration Certificate</div>
                <div v-if="isImage(agent.company_reg_file)" class="mt-2 mb-1">
                  <img :src="getFileUrl('company_reg_file')" class="w-32 h-auto max-h-32 object-cover rounded border shadow-sm" />
                </div>
                <a :href="getFileUrl('company_reg_file')" target="_blank" class="text-gold hover:text-amber-700 font-medium flex items-center gap-1">
                  <ExternalLink class="w-3 h-3" />
                  View {{ isImage(agent.company_reg_file) ? 'Full Image' : 'File' }}
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200">
          <div class="flex items-center">
            <div class="w-6 h-6 bg-accent-red rounded-full flex items-center justify-center mr-3 flex-shrink-0">
              <CheckCircle class="w-3.5 h-3.5 text-white" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium text-gray-500">Status</div>
              <div class="text-gray-900 capitalize">{{ agent.status }}</div>
            </div>
          </div>
        </div>

        <div v-if="agent.about" class="mt-6 space-y-2">
          <div>
            <span class="font-medium text-gray-700">{{ isIndividual ? 'About Me' : 'About Company' }}:</span>
            <p class="mt-1 text-gray-600 whitespace-pre-wrap">{{ agent.about }}</p>
          </div>
        </div>
      </div>

      <!-- Referral Code Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center mb-4">
          <div class="w-8 h-8 bg-accent-green rounded-full flex items-center justify-center mr-3">
            <Award class="w-4 h-4 text-white" />
          </div>
          <h3 class="text-lg font-semibold text-forest-dark">Referral Code Information</h3>
        </div>
        <div v-if="agent.referral_code" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Hash class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Referral Code</div>
                <div class="text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">{{ agent.referral_code.code }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Percent class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Commission Rate</div>
                <div class="text-gray-900">{{ agent.referral_code.commission_rate }}%</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <CheckCircle class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Used Count</div>
                <div class="text-gray-900">{{ agent.referral_code.used_count }}</div>
              </div>
            </div>
          </div>

          <!-- Shareable URL -->
          <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center">
                <div class="w-6 h-6 bg-accent-blue rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                  <LinkIcon class="w-3.5 h-3.5 text-white" />
                </div>
                <span class="font-medium text-gray-700">Shareable URL</span>
              </div>
              <Button variant="default" size="sm" @click="copyShareableUrl">
                Copy
              </Button>
            </div>
            <div class="p-3 bg-gray-50 rounded border font-mono text-sm break-all">
              {{ shareableUrl }}
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500">No referral code information available.</div>
      </div>

      <!-- How to Share Referral Code Card -->
      <div class="bg-gradient-to-br from-accent-green/10 to-accent-blue/10 rounded-lg shadow p-6 border border-accent-green/20">
        <div class="flex items-center mb-4">
          <div class="w-10 h-10 bg-accent-green rounded-full flex items-center justify-center mr-3">
            <Share2 class="w-5 h-5 text-white" />
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
                <Button variant="default" size="sm" @click="copyReferralCode">
                  Copy Code
                </Button>
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
                  <Button variant="outline" size="sm" @click="copyShareableUrl">
                    Copy
                  </Button>
                </div>
                <div class="font-mono text-sm text-gray-600 break-all">
                  {{ shareableUrl }}
                </div>
              </div>
            </div>
          </div>

          <!-- Tips Section -->
          <div class="bg-accent-orange/10 rounded-lg p-4 border border-accent-orange/20">
            <div class="flex items-center mb-3">
              <div class="w-8 h-8 bg-accent-orange rounded-full flex items-center justify-center mr-3">
                <Info class="w-4 h-4 text-white" />
              </div>
              <h4 class="font-semibold text-forest-dark">Pro Tips</h4>
            </div>
            <ul class="text-sm text-gray-700 space-y-2">
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
                Commission rate: <strong>{{ agent.referral_code?.commission_rate || 0 }}%</strong>&nbsp;on successful referrals
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Bank Account Information (Moved to bottom) -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center mb-4">
          <div class="w-8 h-8 bg-accent-blue rounded-full flex items-center justify-center mr-3">
            <CreditCard class="w-4 h-4 text-white" />
          </div>
          <h3 class="text-lg font-semibold text-forest-dark">Bank Account Information</h3>
        </div>
        <div v-if="agent.bank_account" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <User class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Account Name</div>
                <div class="text-gray-900">{{ agent.bank_account.account_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-green rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <FileText class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Account Number</div>
                <div class="text-gray-900 font-mono">{{ agent.bank_account.account_number }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-orange rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Building class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">Bank Name</div>
                <div class="text-gray-900">{{ agent.bank_account.bank_name }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-gray rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <Hash class="w-3.5 h-3.5 text-white" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-gray-500">IBAN</div>
                <div class="text-gray-900 font-mono">{{ agent.bank_account.iban }}</div>
              </div>
            </div>

            <div class="flex items-center">
              <div class="w-6 h-6 bg-accent-red rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                <CheckCircle class="w-3.5 h-3.5 text-white" />
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
            <CreditCard class="w-8 h-8 text-gray-400" />
          </div>
          <h4 class="text-lg font-medium text-gray-900 mb-2">No Bank Account Information</h4>
          <p class="text-gray-500 mb-4">Add your bank account details to receive commission payouts.</p>
          <Button variant="default" size="default" @click="goToEdit">
            Add Bank Account
          </Button>
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
          <Button variant="default" size="default" @click="showCopyDialog = false">
            OK
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
