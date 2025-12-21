<script setup>
import { ref, computed } from 'vue'
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

const form = ref({
  profile_type: props.agent?.profile_type || 'individual',
  individual_name: props.agent?.individual_name || '',
  individual_phone: props.agent?.individual_phone || '',
  individual_email: props.agent?.individual_email || '',
  individual_address: props.agent?.individual_address || '',
  company_representative_name: props.agent?.company_representative_name || '',
  company_name: props.agent?.company_name || '',
  company_registration_number: props.agent?.company_registration_number || '',
  company_address: props.agent?.company_address || '',
  company_phone: props.agent?.company_phone || '',
  company_email_address: props.agent?.company_email_address || '',
  status: props.agent?.status || 'active',
  // Bank account fields
  bank_account_name: props.agent?.bank_account?.account_name || '',
  bank_account_number: props.agent?.bank_account?.account_number || '',
  bank_name: props.agent?.bank_account?.bank_name || '',
  iban: props.agent?.bank_account?.iban || '',
  swift_code: props.agent?.bank_account?.swift_code || '',
  // Referral code fields
  referral_code: props.agent?.referral_code?.code || '',
})

const isIndividual = computed(() => form.value.profile_type === 'individual')
const isCompany = computed(() => form.value.profile_type === 'company')

// Reactive data
const isSaving = ref(false)
const errors = ref({})
const copyStatus = ref('Copy')

// Computed properties for shareable URL
const shareableUrl = computed(() => {
  if (!form.value.referral_code) return ''
  return `${props.penurwillWebsiteUrl}?ref=${form.value.referral_code}`
})

const canProceedToNext = computed(() => {
  if (currentStep.value === 0) {
    if (form.value.profile_type === 'individual') {
      return form.value.individual_name && form.value.individual_phone && form.value.individual_address
    } else {
      return form.value.company_representative_name && form.value.company_name &&
             form.value.company_registration_number && form.value.company_address && form.value.company_phone
    }
  }
  return true
})

const canSubmit = computed(() => {
  return form.value.password &&
         form.value.password_confirmation &&
         form.value.terms &&
         passwordValidation.value.length &&
         passwordValidation.value.numbers &&
         passwordValidation.value.special &&
         form.value.password === form.value.password_confirmation
})

// Methods
const copyShareableUrl = async () => {
  try {
    await navigator.clipboard.writeText(shareableUrl.value)
    copyStatus.value = 'Copied!'
    setTimeout(() => {
      copyStatus.value = 'Copy'
    }, 2000)
  } catch (err) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea')
    textArea.value = shareableUrl.value
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)

    copyStatus.value = 'Copied!'
    setTimeout(() => {
      copyStatus.value = 'Copy'
    }, 2000)
  }
}

const saveProfile = async () => {
  isSaving.value = true
  errors.value = {}
  try {
    await router.put('/agent/profile/edit', form.value, {
      onSuccess: () => router.visit('/agent/profile'),
      onError: (e) => { errors.value = e },
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Edit Profile</span>
    </nav>
    <h1 class="text-2xl font-bold text-forest-dark mb-4">Edit Agent Profile</h1>

    <form @submit.prevent="saveProfile" class="space-y-6">
      <!-- Agent Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Agent Information</h3>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Agent Type</label>
          <div class="flex space-x-4">
            <label class="flex items-center">
              <input type="radio" value="individual" v-model="form.profile_type" class="mr-2" /> Individual
            </label>
            <label class="flex items-center">
              <input type="radio" value="company" v-model="form.profile_type" class="mr-2" /> Company
            </label>
          </div>
        </div>

        <div v-if="isIndividual" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
            <input v-model="form.individual_name" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.individual_name" class="text-accent-red text-sm mt-1">{{ errors.individual_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
            <input v-model="form.individual_phone" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.individual_phone" class="text-accent-red text-sm mt-1">{{ errors.individual_phone }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Alternative E-Mail Address</label>
            <input v-model="form.individual_email" type="email" class="w-full px-3 py-2 border rounded" placeholder="Enter alternative email address (optional)" />
            <p v-if="errors.individual_email" class="text-accent-red text-sm mt-1">{{ errors.individual_email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
            <textarea v-model="form.individual_address" class="w-full px-3 py-2 border rounded"></textarea>
            <p v-if="errors.individual_address" class="text-accent-red text-sm mt-1">{{ errors.individual_address }}</p>
          </div>
        </div>

        <div v-if="isCompany" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
            <input v-model="form.company_name" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_name" class="text-accent-red text-sm mt-1">{{ errors.company_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Representative</label>
            <input v-model="form.company_representative_name" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_representative_name" class="text-accent-red text-sm mt-1">{{ errors.company_representative_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Registration Number</label>
            <input v-model="form.company_registration_number" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_registration_number" class="text-accent-red text-sm mt-1">{{ errors.company_registration_number }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
            <textarea v-model="form.company_address" class="w-full px-3 py-2 border rounded"></textarea>
            <p v-if="errors.company_address" class="text-accent-red text-sm mt-1">{{ errors.company_address }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Phone</label>
            <input v-model="form.company_phone" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_phone" class="text-accent-red text-sm mt-1">{{ errors.company_phone }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company E-Mail Address</label>
            <input v-model="form.company_email_address" type="email" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_email_address" class="text-accent-red text-sm mt-1">{{ errors.company_email_address }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select v-model="form.status" class="w-full px-3 py-2 border rounded">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="banned">Banned</option>
          </select>
        </div>
      </div>

      <!-- Bank Account Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Bank Account Information</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
            <input v-model="form.bank_account_name" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.bank_account_name" class="text-accent-red text-sm mt-1">{{ errors.bank_account_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
            <input v-model="form.bank_account_number" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.bank_account_number" class="text-accent-red text-sm mt-1">{{ errors.bank_account_number }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
            <input v-model="form.bank_name" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.bank_name" class="text-accent-red text-sm mt-1">{{ errors.bank_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">IBAN</label>
            <input v-model="form.iban" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.iban" class="text-accent-red text-sm mt-1">{{ errors.iban }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">SWIFT Code</label>
            <input v-model="form.swift_code" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.swift_code" class="text-accent-red text-sm mt-1">{{ errors.swift_code }}</p>
          </div>
        </div>
      </div>

      <!-- Referral Code Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Referral Code Information</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Referral Code</label>
            <input v-model="form.referral_code" type="text" class="w-full px-3 py-2 border rounded font-mono" placeholder="Enter unique referral code" />
            <p v-if="errors.referral_code" class="text-accent-red text-sm mt-1">{{ errors.referral_code }}</p>
            <p class="text-sm text-gray-500 mt-1">Referral code must be unique across all agents</p>
          </div>

          <!-- Shareable URL -->
          <div v-if="form.referral_code">
            <label class="block text-sm font-medium text-gray-700 mb-2">Shareable URL</label>
            <div class="flex items-center space-x-2">
              <input
                :value="shareableUrl"
                type="text"
                readonly
                class="flex-1 px-3 py-2 border rounded font-mono bg-gray-50 text-gray-700"
              />
              <button
                @click="copyShareableUrl"
                type="button"
                class="px-4 py-2 bg-gold hover:bg-amber-700 text-white rounded font-medium transition-colors"
              >
                {{ copyStatus }}
              </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">
              Note: You may append any landing page with ?ref={{ form.referral_code }}
            </p>
          </div>

          <!-- Explanation -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-medium text-blue-900 mb-3">How to use your referral code:</h4>

            <div class="space-y-4">
              <div>
                <h5 class="font-medium text-blue-800 mb-2">Option 1</h5>
                <p class="text-sm text-blue-700">
                  Ask your client to use code <span class="font-mono bg-blue-100 px-1 rounded">{{ form.referral_code }}</span> during checkout
                </p>
              </div>

              <div>
                <h5 class="font-medium text-blue-800 mb-2">Option 2</h5>
                <p class="text-sm text-blue-700 mb-2">
                  Ask your client to click shareable link <span class="font-mono bg-blue-100 px-1 rounded">{{ shareableUrl }}</span>
                </p>
                <p class="text-sm text-blue-700">
                  You may use customized link like <span class="font-mono bg-blue-100 px-1 rounded">{{ penurwillWebsiteUrl }}/[any page]/?ref={{ form.referral_code }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end">
        <button type="submit" :disabled="isSaving" class="bg-gold hover:bg-amber-700 text-white px-6 py-2 rounded font-medium transition-colors">
          <span v-if="isSaving">Saving...</span>
          <span v-else>Save</span>
        </button>
      </div>
    </form>
  </div>
</template>
