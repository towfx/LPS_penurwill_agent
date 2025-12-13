<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Agents</span> / <span class="text-stone-900 font-medium">Edit Agent</span>
    </nav>
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold text-forest-dark">Edit Agent</h1>
      <button @click="goBack" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-medium transition-colors">
        Back to List
      </button>
    </div>

    <form @submit.prevent="saveAgent" class="space-y-6">
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
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select v-model="form.status" class="w-full px-3 py-2 border rounded">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
              <option value="banned">Banned</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">New Password (leave blank to keep current)</label>
            <input v-model="form.user_password" type="password" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.user_password" class="text-accent-red text-sm mt-1">{{ errors.user_password }}</p>
          </div>
          <div v-if="form.user_password">
            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
            <input v-model="form.user_password_confirmation" type="password" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.user_password_confirmation" class="text-accent-red text-sm mt-1">{{ errors.user_password_confirmation }}</p>
          </div>
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
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%)</label>
            <input v-model="form.referral_commission_rate" type="number" step="0.01" min="0" max="100" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.referral_commission_rate" class="text-accent-red text-sm mt-1">{{ errors.referral_commission_rate }}</p>
          </div>
          <div>
            <label class="flex items-center">
              <input v-model="form.referral_is_active" type="checkbox" class="mr-2" />
              <span class="text-sm font-medium text-gray-700">Active</span>
            </label>
            <p v-if="errors.referral_is_active" class="text-accent-red text-sm mt-1">{{ errors.referral_is_active }}</p>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end">
        <button type="submit" :disabled="isSaving" class="bg-gold hover:bg-amber-700 text-white px-6 py-2 rounded font-medium transition-colors">
          <span v-if="isSaving">Saving...</span>
          <span v-else>Save Changes</span>
        </button>
      </div>
    </form>

    <!-- Bank Warning Dialog -->
    <div v-if="showBankWarningDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeBankWarningDialog">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Incomplete Bank Information</h3>
          </div>
        </div>
        <div class="mb-4">
          <p class="text-sm text-gray-700">You have entered some bank account information but not all required fields. Please complete all bank details (Account Name, Account Number, and Bank Name) for proper bank account setup.</p>
          <p class="text-sm text-gray-600 mt-2">Do you want to save anyway?</p>
        </div>
        <div class="flex justify-end space-x-3">
          <button @click="closeBankWarningDialog" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded font-medium transition-colors">
            Cancel
          </button>
          <button @click="proceedWithSave" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded font-medium transition-colors">
            Save Anyway
          </button>
        </div>
      </div>
    </div>

    <!-- Error Dialog -->
    <div v-if="showErrorDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeErrorDialog">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Error</h3>
          </div>
        </div>
        <div class="mb-4">
          <p class="text-sm text-gray-700">{{ generalErrorMessage }}</p>
        </div>
        <div class="flex justify-end">
          <button @click="closeErrorDialog" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-medium transition-colors">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  },
  agent: {
    type: Object,
    default: null
  }
})

const form = ref({
  profile_type: props.agent?.profile_type || 'individual',
  individual_name: props.agent?.individual_name || '',
  individual_phone: props.agent?.individual_phone || '',
  individual_address: props.agent?.individual_address || '',
  company_representative_name: props.agent?.company_representative_name || '',
  company_name: props.agent?.company_name || '',
  company_registration_number: props.agent?.company_registration_number || '',
  company_address: props.agent?.company_address || '',
  company_phone: props.agent?.company_phone || '',
  user_password: '',
  user_password_confirmation: '',
  status: props.agent?.status || 'active',
  // Bank account fields
  bank_account_name: props.agent?.bank_account?.account_name || '',
  bank_account_number: props.agent?.bank_account?.account_number || '',
  bank_name: props.agent?.bank_account?.bank_name || '',
  iban: props.agent?.bank_account?.iban || '',
  swift_code: props.agent?.bank_account?.swift_code || '',
  // Referral code fields
  referral_code: props.agent?.referral_code?.code || '',
  referral_commission_rate: props.agent?.referral_code?.commission_rate || '',
  referral_is_active: props.agent?.referral_code?.is_active || true,
})

const isIndividual = computed(() => form.value.profile_type === 'individual')
const isCompany = computed(() => form.value.profile_type === 'company')

const isSaving = ref(false)
const errors = ref({})
const showErrorDialog = ref(false)
const generalErrorMessage = ref('')
const showBankWarningDialog = ref(false)

const saveAgent = async () => {
  isSaving.value = true
  errors.value = {}
  showErrorDialog.value = false
  showBankWarningDialog.value = false
  generalErrorMessage.value = ''

  // Check for incomplete bank account information
  const bankFields = [form.value.bank_account_name, form.value.bank_account_number, form.value.bank_name]
  const filledBankFields = bankFields.filter(field => field && field.trim() !== '')
  const hasPartialBankInfo = filledBankFields.length > 0 && filledBankFields.length < 3

  if (hasPartialBankInfo) {
    showBankWarningDialog.value = true
    isSaving.value = false
    return
  }

  try {
    await router.put(`/admin/agents/${props.id}/update`, form.value, {
      onError: (e) => {
        errors.value = e

        // Check for general errors (not field-specific)
        if (e.error || (e.default && e.default.error)) {
          const errorMsg = e.error || e.default.error
          generalErrorMessage.value = Array.isArray(errorMsg) ? errorMsg.join(' ') : errorMsg
          showErrorDialog.value = true
        }
      },
    })
  } finally {
    isSaving.value = false
  }
}

const goBack = () => {
  router.visit('/admin/agents/list')
}

const closeErrorDialog = () => {
  showErrorDialog.value = false
}

const closeBankWarningDialog = () => {
  showBankWarningDialog.value = false
}

const proceedWithSave = async () => {
  showBankWarningDialog.value = false
  isSaving.value = true
  errors.value = {}
  showErrorDialog.value = false
  generalErrorMessage.value = ''

  try {
    await router.put(`/admin/agents/${props.id}/update`, form.value, {
      onError: (e) => {
        errors.value = e

        // Check for general errors (not field-specific)
        if (e.error || (e.default && e.default.error)) {
          const errorMsg = e.error || e.default.error
          generalErrorMessage.value = Array.isArray(errorMsg) ? errorMsg.join(' ') : errorMsg
          showErrorDialog.value = true
        }
      },
    })
  } finally {
    isSaving.value = false
  }
}
</script>
