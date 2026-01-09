<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import Badge from '../Design/Components/Badge.vue'

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
  individual_id_number: props.agent?.individual_id_number || '',
  individual_id_file: null,
  company_representative_name: props.agent?.company_representative_name || '',
  company_name: props.agent?.company_name || '',
  company_registration_number: props.agent?.company_registration_number || '',
  company_address: props.agent?.company_address || '',
  company_phone: props.agent?.company_phone || '',
  company_email_address: props.agent?.company_email_address || '',
  company_reg_file: null,
  about: props.agent?.about || '',
  // Bank account fields
  bank_account_name: props.agent?.bank_account?.account_name || '',
  bank_account_number: props.agent?.bank_account?.account_number || '',
  bank_name: props.agent?.bank_account?.bank_name || '',
  iban: props.agent?.bank_account?.iban || '',
  swift_code: props.agent?.bank_account?.swift_code || '',
})

const isIndividual = computed(() => form.value.profile_type === 'individual')
const isCompany = computed(() => form.value.profile_type === 'company')

// Reactive data
const isSaving = ref(false)
const errors = ref({})
const copyStatus = ref('Copy')

const aboutWordCount = computed(() => {
  if (!form.value.about || !form.value.about.trim()) {
    return 0
  }
  return form.value.about.trim().split(/\s+/).filter(word => word.length > 0).length
})

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

const handleIndividualIdFileChange = (event) => {
  form.value.individual_id_file = event.target.files[0]
}

const handleCompanyRegFileChange = (event) => {
  form.value.company_reg_file = event.target.files[0]
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

const getStatusVariant = (status) => {
  switch(status?.toLowerCase()) {
    case 'active':
      return 'success'
    case 'inactive':
      return 'destructive'
    case 'suspended':
      return 'warning'
    case 'banned':
      return 'destructive'
    default:
      return 'default'
  }
}

const saveProfile = async () => {
  isSaving.value = true
  errors.value = {}

  // Debug: Log form data before submission
  console.log('Form data before submission:', JSON.parse(JSON.stringify(form.value)))

  // Check if we have files to upload
  const hasFiles = form.value.individual_id_file || form.value.company_reg_file

  try {
    if (hasFiles) {
      // Use FormData for file uploads
      const formData = new FormData()
      Object.keys(form.value).forEach(key => {
        // Skip file fields if they're null (only append if file exists)
        if (key === 'individual_id_file' || key === 'company_reg_file') {
          if (form.value[key]) {
            formData.append(key, form.value[key])
            console.log(`Added file ${key}:`, form.value[key].name)
          }
        } else {
          // Append all other fields, including empty strings
          // Handle boolean values properly for FormData
          if (typeof form.value[key] === 'boolean') {
            formData.append(key, form.value[key] ? '1' : '0')
          } else if (form.value[key] === null || form.value[key] === undefined) {
            // Convert null/undefined to empty string for FormData
            formData.append(key, '')
          } else {
            formData.append(key, form.value[key])
          }
        }
      })
      formData.append('_method', 'PUT')

      // Debug: Log FormData contents
      console.log('FormData entries:')
      for (const [key, value] of formData.entries()) {
        if (value instanceof File) {
          console.log(`${key}:`, value.name, `(${value.size} bytes)`)
        } else {
          console.log(`${key}:`, value)
        }
      }

      await router.post('/agent/profile/edit', formData, {
        onSuccess: () => {
          console.log('Profile updated successfully')
          router.visit('/agent/profile')
        },
        onError: (e) => {
          console.error('Error updating profile:', e)
          errors.value = e
        },
      })
    } else {
      // No files, use regular PUT request
      console.log('Submitting without files:', form.value)

      await router.put('/agent/profile/edit', form.value, {
        onSuccess: () => {
          console.log('Profile updated successfully')
          router.visit('/agent/profile')
        },
        onError: (e) => {
          console.error('Error updating profile:', e)
          errors.value = e
        },
      })
    }
  } catch (error) {
    console.error('Unexpected error:', error)
    errors.value = { error: 'An unexpected error occurred. Please try again.' }
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
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold text-forest-dark">Edit Agent Profile</h1>
      <Badge v-if="agent?.status" :variant="getStatusVariant(agent.status)" class="capitalize text-xl px-8 py-3">
        {{ agent.status }}
      </Badge>
    </div>

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
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">NRIC/Passport Number</label>
            <input v-model="form.individual_id_number" type="text" class="w-full px-3 py-2 border rounded" placeholder="National registration identification number or Passport Number" />
            <p class="text-sm text-gray-500 mt-1">National registration identification number or Passport Number</p>
            <p v-if="errors.individual_id_number" class="text-accent-red text-sm mt-1">{{ errors.individual_id_number }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Copy of IC/Passport</label>
            <div v-if="agent?.individual_id_file" class="mb-2">
              <span class="text-sm text-gray-600">Current file: </span>
              <a :href="getFileUrl('individual_id_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">
                View Current File
              </a>
            </div>
            <input
              @change="handleIndividualIdFileChange"
              type="file"
              accept=".pdf,.jpeg,.jpg,.png"
              class="w-full px-3 py-2 border rounded"
            />
            <p class="text-sm text-gray-500 mt-1">Upload copy of national registration identity card or Passport file</p>
            <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            <p v-if="errors.individual_id_file" class="text-accent-red text-sm mt-1">{{ errors.individual_id_file }}</p>
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
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Business Registration Certificate</label>
            <div v-if="agent?.company_reg_file" class="mb-2">
              <span class="text-sm text-gray-600">Current file: </span>
              <a :href="getFileUrl('company_reg_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">
                View Current File
              </a>
            </div>
            <input
              @change="handleCompanyRegFileChange"
              type="file"
              accept=".pdf,.jpeg,.jpg,.png"
              class="w-full px-3 py-2 border rounded"
            />
            <p class="text-sm text-gray-500 mt-1">Company SSM document/certificate</p>
            <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            <p v-if="errors.company_reg_file" class="text-accent-red text-sm mt-1">{{ errors.company_reg_file }}</p>
          </div>
        </div>

        <!-- About Me / About Company -->
        <div class="space-y-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ form.profile_type === 'individual' ? 'About Me' : 'About Company' }}
            </label>
            <textarea
              v-model="form.about"
              rows="4"
              maxlength="1000"
              class="w-full px-3 py-2 border rounded"
              :placeholder="form.profile_type === 'individual' ? 'Tell us about yourself in 100 words' : 'Tell us about your company in 100 words'"
            ></textarea>
            <p class="text-sm text-gray-500 mt-1">Tell us about yourself / your company in 100 words</p>
            <p class="text-sm text-gray-400 mt-1">Word count: {{ aboutWordCount }} / 100 words</p>
            <p v-if="errors.about" class="text-accent-red text-sm mt-1">{{ errors.about }}</p>
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
