<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Agents</span> / <span class="text-stone-900 font-medium">Add</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-forest-dark">Add Agent</h1>
      <Button variant="outline" @click="goBack">
        <ArrowLeft size="16" class="mr-2" />
        Back to List
      </Button>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Agent Information</CardTitle>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Agent Type Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Agent Type *</label>
            <div class="flex space-x-4">
              <label class="flex items-center">
                <input
                  v-model="form.profile_type"
                  type="radio"
                  value="individual"
                  class="mr-2"
                />
                Individual
              </label>
              <label class="flex items-center">
                <input
                  v-model="form.profile_type"
                  type="radio"
                  value="company"
                  class="mr-2"
                />
                Company
              </label>
            </div>
            <p v-if="errors.profile_type" class="text-red-500 text-sm mt-1">{{ errors.profile_type }}</p>
          </div>

          <!-- Individual Fields -->
          <div v-if="isIndividual" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Individual Name *</label>
              <input
                v-model="form.individual_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter individual name"
              />
              <p v-if="errors.individual_name" class="text-red-500 text-sm mt-1">{{ errors.individual_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
              <input
                v-model="form.individual_phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter phone number"
              />
              <p v-if="errors.individual_phone" class="text-red-500 text-sm mt-1">{{ errors.individual_phone }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Alternative E-Mail Address</label>
              <input
                v-model="form.individual_email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter alternative email address (optional)"
              />
              <p v-if="errors.individual_email" class="text-red-500 text-sm mt-1">{{ errors.individual_email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
              <textarea
                v-model="form.individual_address"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter address"
              ></textarea>
              <p v-if="errors.individual_address" class="text-red-500 text-sm mt-1">{{ errors.individual_address }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">NRIC/Passport Number</label>
              <input
                v-model="form.individual_id_number"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="National registration identification number or Passport Number"
              />
              <p class="text-sm text-gray-500 mt-1">National registration identification number or Passport Number</p>
              <p v-if="errors.individual_id_number" class="text-red-500 text-sm mt-1">{{ errors.individual_id_number }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Copy of IC/Passport</label>
              <input
                @change="handleIndividualIdFileChange"
                type="file"
                accept=".pdf,.jpeg,.jpg,.png"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              />
              <p class="text-sm text-gray-500 mt-1">Upload copy of national registration identity card or Passport file</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
              <p v-if="errors.individual_id_file" class="text-red-500 text-sm mt-1">{{ errors.individual_id_file }}</p>
            </div>
          </div>

          <!-- Company Fields -->
          <div v-if="isCompany" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Representative Name *</label>
              <input
                v-model="form.company_representative_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter representative name"
              />
              <p v-if="errors.company_representative_name" class="text-red-500 text-sm mt-1">{{ errors.company_representative_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
              <input
                v-model="form.company_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company name"
              />
              <p v-if="errors.company_name" class="text-red-500 text-sm mt-1">{{ errors.company_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Registration Number *</label>
              <input
                v-model="form.company_registration_number"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter registration number"
              />
              <p v-if="errors.company_registration_number" class="text-red-500 text-sm mt-1">{{ errors.company_registration_number }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Address *</label>
              <textarea
                v-model="form.company_address"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company address"
              ></textarea>
              <p v-if="errors.company_address" class="text-red-500 text-sm mt-1">{{ errors.company_address }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Phone *</label>
              <input
                v-model="form.company_phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company phone"
              />
              <p v-if="errors.company_phone" class="text-red-500 text-sm mt-1">{{ errors.company_phone }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company E-Mail Address</label>
              <input
                v-model="form.company_email_address"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company email address"
              />
              <p v-if="errors.company_email_address" class="text-red-500 text-sm mt-1">{{ errors.company_email_address }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Business Registration Certificate</label>
              <input
                @change="handleCompanyRegFileChange"
                type="file"
                accept=".pdf,.jpeg,.jpg,.png"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              />
              <p class="text-sm text-gray-500 mt-1">Company SSM document/certificate</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
              <p v-if="errors.company_reg_file" class="text-red-500 text-sm mt-1">{{ errors.company_reg_file }}</p>
            </div>
          </div>

          <!-- About Me / About Company -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ form.profile_type === 'individual' ? 'About Me' : 'About Company' }} *
            </label>
            <textarea
              v-model="form.about"
              rows="4"
              maxlength="1000"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              :placeholder="form.profile_type === 'individual' ? 'Tell us about yourself in 100 words' : 'Tell us about your company in 100 words'"
            ></textarea>
            <p class="text-sm text-gray-500 mt-1">Tell us about yourself / your company in 100 words</p>
            <p class="text-sm text-gray-400 mt-1">Word count: {{ aboutWordCount }} / 100 words</p>
            <p v-if="errors.about" class="text-red-500 text-sm mt-1">{{ errors.about }}</p>
          </div>

          <!-- User Account Information -->
          <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">User Account Information</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                <input
                  v-model="form.user_email"
                  type="email"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                  placeholder="Enter email address"
                />
                <p v-if="errors.user_email" class="text-red-500 text-sm mt-1">{{ errors.user_email }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                <input
                  v-model="form.user_password"
                  type="password"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                  placeholder="Enter password"
                />
                <p v-if="errors.user_password" class="text-red-500 text-sm mt-1">{{ errors.user_password }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                <input
                  v-model="form.user_password_confirmation"
                  type="password"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                  placeholder="Confirm password"
                />
                <p v-if="errors.user_password_confirmation" class="text-red-500 text-sm mt-1">{{ errors.user_password_confirmation }}</p>
              </div>
            </div>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
              <option value="banned">Banned</option>
            </select>
            <p v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</p>
          </div>
        </form>
      </CardContent>
    </Card>

    <!-- Bank Account Information -->
    <Card class="mt-6">
      <CardHeader>
        <CardTitle>Bank Account Information</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
            <input
              v-model="form.bank_account_name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              placeholder="Enter account name"
            />
            <p v-if="errors.bank_account_name" class="text-red-500 text-sm mt-1">{{ errors.bank_account_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
            <input
              v-model="form.bank_account_number"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              placeholder="Enter account number"
            />
            <p v-if="errors.bank_account_number" class="text-red-500 text-sm mt-1">{{ errors.bank_account_number }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
            <input
              v-model="form.bank_name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              placeholder="Enter bank name"
            />
            <p v-if="errors.bank_name" class="text-red-500 text-sm mt-1">{{ errors.bank_name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">IBAN</label>
            <input
              v-model="form.iban"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              placeholder="Enter IBAN"
            />
            <p v-if="errors.iban" class="text-red-500 text-sm mt-1">{{ errors.iban }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">SWIFT Code</label>
            <input
              v-model="form.swift_code"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              placeholder="Enter SWIFT code"
            />
            <p v-if="errors.swift_code" class="text-red-500 text-sm mt-1">{{ errors.swift_code }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Referral Code Information -->
    <Card class="mt-6">
      <CardHeader>
        <CardTitle>Referral Code Information</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Referral Code</label>
            <div class="flex gap-2">
              <input
                v-model="form.referral_code"
                type="text"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent font-mono"
                placeholder="Enter unique referral code"
              />
              <Button type="button" variant="outline" @click="regenerateCode" class="shrink-0">
                Regenerate
              </Button>
            </div>
            <p v-if="errors.referral_code" class="text-red-500 text-sm mt-1">{{ errors.referral_code }}</p>
            <p class="text-sm text-gray-500 mt-1">Referral code must be unique across all agents</p>
            <p class="text-sm text-gray-500 mt-1">Prefix: {{ referralCodePrefix }} (auto-generated)</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%)</label>
            <input
              v-model="form.referral_commission_rate"
              type="number"
              step="0.01"
              min="0"
              max="100"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              placeholder="Enter commission rate"
            />
            <p v-if="errors.referral_commission_rate" class="text-red-500 text-sm mt-1">{{ errors.referral_commission_rate }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Form Actions -->
    <div class="flex justify-end space-x-4 pt-6 mt-6">
      <Button type="button" variant="outline" @click="goBack">
        Cancel
      </Button>
      <Button type="button" @click="submitForm" :disabled="isSubmitting">
        <span v-if="isSubmitting" class="flex items-center">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
          Creating...
        </span>
        <span v-else>Create Agent</span>
      </Button>
    </div>

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
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import AdminLayout from '../Design/AdminLayout.vue'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardContent from '../Design/Components/CardContent.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import Button from '../Design/Components/Button.vue'

defineOptions({ layout: AdminLayout })

// Props
const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  },
  referralCodePrefix: {
    type: String,
    default: 'REF'
  },
  commissionDefaultRate: {
    type: Number,
    default: 0
  }
})

// Generate random code suffix (8 uppercase alphanumeric characters)
const generateCodeSuffix = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let result = ''
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

// Generate full referral code
const generateReferralCode = () => {
  return props.referralCodePrefix + generateCodeSuffix()
}

// Reactive data
const isSubmitting = ref(false)
const showErrorDialog = ref(false)
const generalErrorMessage = ref('')
const showBankWarningDialog = ref(false)

const isIndividual = computed(() => form.profile_type === 'individual')
const isCompany = computed(() => form.profile_type === 'company')

const aboutWordCount = computed(() => {
  if (!form.about || !form.about.trim()) {
    return 0
  }
  return form.about.trim().split(/\s+/).filter(word => word.length > 0).length
})

const form = reactive({
  profile_type: 'individual',
  individual_name: '',
  individual_phone: '',
  individual_email: '',
  individual_address: '',
  individual_id_number: '',
  individual_id_file: null,
  company_representative_name: '',
  company_name: '',
  company_registration_number: '',
  company_address: '',
  company_phone: '',
  company_email_address: '',
  company_reg_file: null,
  about: '',
  user_email: '',
  user_password: '',
  user_password_confirmation: '',
  status: 'active',
  // Bank account fields
  bank_account_name: '',
  bank_account_number: '',
  bank_name: '',
  iban: '',
  swift_code: '',
  // Referral code fields
  referral_code: generateReferralCode(),
  referral_commission_rate: props.commissionDefaultRate || '',
  referral_is_active: true
})

// Methods
const handleIndividualIdFileChange = (event) => {
  form.individual_id_file = event.target.files[0]
}

const handleCompanyRegFileChange = (event) => {
  form.company_reg_file = event.target.files[0]
}

const submitForm = async () => {
  isSubmitting.value = true
  showErrorDialog.value = false
  showBankWarningDialog.value = false
  generalErrorMessage.value = ''

  // Check for incomplete bank account information
  const bankFields = [form.bank_account_name, form.bank_account_number, form.bank_name]
  const filledBankFields = bankFields.filter(field => field && field.trim() !== '')
  const hasPartialBankInfo = filledBankFields.length > 0 && filledBankFields.length < 3

  if (hasPartialBankInfo) {
    showBankWarningDialog.value = true
    isSubmitting.value = false
    return
  }

  // Check if we have files to upload
  const hasFiles = form.individual_id_file || form.company_reg_file

  try {
    if (hasFiles) {
      // Use FormData for file uploads
      const formData = new FormData()
      Object.keys(form).forEach(key => {
        // Skip file fields if they're null (only append if file exists)
        if (key === 'individual_id_file' || key === 'company_reg_file') {
          if (form[key]) {
            formData.append(key, form[key])
          }
        } else {
          // Append all other fields, including empty strings
          // Handle boolean values properly for FormData
          if (typeof form[key] === 'boolean') {
            formData.append(key, form[key] ? '1' : '0')
          } else if (form[key] === null || form[key] === undefined) {
            // Convert null/undefined to empty string for FormData
            formData.append(key, '')
          } else {
            formData.append(key, form[key])
          }
        }
      })

      await router.post('/admin/agents/store', formData, {
        onSuccess: () => {
          router.visit('/admin/agents/list')
        },
        onError: (e) => {
          // Check for general errors (not field-specific)
          if (e.error || (e.default && e.default.error)) {
            const errorMsg = e.error || e.default.error
            generalErrorMessage.value = Array.isArray(errorMsg) ? errorMsg.join(' ') : errorMsg
            showErrorDialog.value = true
          }
        }
      })
    } else {
      // No files, use regular POST request
      await router.post('/admin/agents/store', form, {
        onSuccess: () => {
          router.visit('/admin/agents/list')
        },
        onError: (e) => {
          // Check for general errors (not field-specific)
          if (e.error || (e.default && e.default.error)) {
            const errorMsg = e.error || e.default.error
            generalErrorMessage.value = Array.isArray(errorMsg) ? errorMsg.join(' ') : errorMsg
            showErrorDialog.value = true
          }
        }
      })
    }
  } catch (error) {
    console.error('Submission error:', error)
    generalErrorMessage.value = 'An unexpected error occurred. Please try again.'
    showErrorDialog.value = true
  } finally {
    isSubmitting.value = false
  }
}

const proceedWithSave = async () => {
  showBankWarningDialog.value = false
  isSubmitting.value = true
  showErrorDialog.value = false
  generalErrorMessage.value = ''

  // Check if we have files to upload
  const hasFiles = form.individual_id_file || form.company_reg_file

  try {
    if (hasFiles) {
      // Use FormData for file uploads
      const formData = new FormData()
      Object.keys(form).forEach(key => {
        // Skip file fields if they're null (only append if file exists)
        if (key === 'individual_id_file' || key === 'company_reg_file') {
          if (form[key]) {
            formData.append(key, form[key])
          }
        } else {
          // Append all other fields, including empty strings
          // Handle boolean values properly for FormData
          if (typeof form[key] === 'boolean') {
            formData.append(key, form[key] ? '1' : '0')
          } else if (form[key] === null || form[key] === undefined) {
            // Convert null/undefined to empty string for FormData
            formData.append(key, '')
          } else {
            formData.append(key, form[key])
          }
        }
      })

      await router.post('/admin/agents/store', formData, {
        onSuccess: () => {
          router.visit('/admin/agents/list')
        },
        onError: (e) => {
          // Check for general errors (not field-specific)
          if (e.error || (e.default && e.default.error)) {
            const errorMsg = e.error || e.default.error
            generalErrorMessage.value = Array.isArray(errorMsg) ? errorMsg.join(' ') : errorMsg
            showErrorDialog.value = true
          }
        }
      })
    } else {
      // No files, use regular POST request
      await router.post('/admin/agents/store', form, {
        onSuccess: () => {
          router.visit('/admin/agents/list')
        },
        onError: (e) => {
          // Check for general errors (not field-specific)
          if (e.error || (e.default && e.default.error)) {
            const errorMsg = e.error || e.default.error
            generalErrorMessage.value = Array.isArray(errorMsg) ? errorMsg.join(' ') : errorMsg
            showErrorDialog.value = true
          }
        }
      })
    }
  } catch (error) {
    console.error('Submission error:', error)
    generalErrorMessage.value = 'An unexpected error occurred. Please try again.'
    showErrorDialog.value = true
  } finally {
    isSubmitting.value = false
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

const regenerateCode = () => {
  form.referral_code = generateReferralCode()
}
</script>
