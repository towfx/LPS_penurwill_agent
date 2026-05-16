<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Badge from '../Design/Components/Badge.vue'
import Button from '../Design/Components/Button.vue'
import Input from '../Design/Components/Input.vue'
import Textarea from '../Design/Components/Textarea.vue'
import FormField from '../Design/Components/FormField.vue'
import { useRoleNames } from '../../composables/useRoleNames.js'

const { roleNames } = useRoleNames()
import Radio from '../Design/Components/Radio.vue'
import FileInput from '../Design/Components/FileInput.vue'

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
  company_representative_id_number: props.agent?.company_representative_id_number || '',
  company_name: props.agent?.company_name || '',
  company_registration_number: props.agent?.company_registration_number || '',
  company_address: props.agent?.company_address || '',
  company_phone: props.agent?.company_phone || '',
  company_email_address: props.agent?.company_email_address || '',
  company_reg_file: null,
  company_representative_id_file: null,
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
      return form.value.company_representative_name && form.value.company_representative_id_number && form.value.company_name &&
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

// File inputs are handled via v-model with FileInput component

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
  const hasFiles = form.value.individual_id_file || form.value.company_reg_file || form.value.company_representative_id_file

  try {
    if (hasFiles) {
      // Use FormData for file uploads
      const formData = new FormData()
      Object.keys(form.value).forEach(key => {
        // Skip file fields if they're null (only append if file exists)
        if (key === 'individual_id_file' || key === 'company_reg_file' || key === 'company_representative_id_file') {
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
    <PageHeader
      :title="`Edit ${roleNames.agent} Profile`"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Profile', href: '/agent/profile' }, { label: 'Edit Profile' }]"
    >
      <template #actions>
        <Badge v-if="agent?.status" :variant="getStatusVariant(agent.status)" class="capitalize text-xl px-8 py-3">
          {{ agent.status }}
        </Badge>
      </template>
    </PageHeader>

    <form @submit.prevent="saveProfile" class="space-y-6">
      <!-- Agent Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Agent Information</h3>

        <FormField label="Agent Type" class="mb-4">
          <div class="flex space-x-4">
            <Radio v-model="form.profile_type" value="individual" name="profile_type" label="Individual" />
            <Radio v-model="form.profile_type" value="company" name="profile_type" label="Company" />
          </div>
        </FormField>

        <div v-if="isIndividual" class="space-y-4">
          <FormField label="Name" :error="errors.individual_name" required>
            <Input v-model="form.individual_name" type="text" :invalid="!!errors.individual_name" />
          </FormField>
          <FormField label="Phone" :error="errors.individual_phone" required>
            <Input v-model="form.individual_phone" type="text" :invalid="!!errors.individual_phone" />
          </FormField>
          <FormField label="Alternative E-Mail Address" :error="errors.individual_email">
            <Input v-model="form.individual_email" type="email" placeholder="Enter alternative email address (optional)" :invalid="!!errors.individual_email" />
          </FormField>
          <FormField label="Address" :error="errors.individual_address" required>
            <Textarea v-model="form.individual_address" :invalid="!!errors.individual_address" />
          </FormField>
          <FormField label="NRIC/Passport Number" :error="errors.individual_id_number" required>
            <Input v-model="form.individual_id_number" type="text" placeholder="National registration identification number or Passport Number" :invalid="!!errors.individual_id_number" />
            <p class="text-sm text-gray-500 mt-1">National registration identification number or Passport Number</p>
          </FormField>
          <FormField label="Copy of IC/Passport" :error="errors.individual_id_file">
            <div v-if="agent?.individual_id_file" class="mb-2">
              <span class="text-sm text-gray-600">Current file: </span>
              <a :href="getFileUrl('individual_id_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">
                View Current File
              </a>
            </div>
            <FileInput v-model="form.individual_id_file" accept=".pdf,.jpeg,.jpg,.png" />
            <p class="text-sm text-gray-500 mt-1">Upload copy of national registration identity card or Passport file</p>
            <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
          </FormField>
        </div>

        <div v-if="isCompany" class="space-y-4">
          <FormField label="Company Name" :error="errors.company_name" required>
            <Input v-model="form.company_name" type="text" :invalid="!!errors.company_name" />
          </FormField>
          <FormField label="Representative" :error="errors.company_representative_name" required>
            <Input v-model="form.company_representative_name" type="text" :invalid="!!errors.company_representative_name" />
          </FormField>
          <FormField label="Representative ID Number" :error="errors.company_representative_id_number" required>
            <Input v-model="form.company_representative_id_number" type="text" placeholder="IC / Passport number" :invalid="!!errors.company_representative_id_number" />
          </FormField>
          <FormField label="Registration Number" :error="errors.company_registration_number" required>
            <Input v-model="form.company_registration_number" type="text" :invalid="!!errors.company_registration_number" />
          </FormField>
          <FormField label="Company Address" :error="errors.company_address" required>
            <Textarea v-model="form.company_address" :invalid="!!errors.company_address" />
          </FormField>
          <FormField label="Company Phone" :error="errors.company_phone" required>
            <Input v-model="form.company_phone" type="text" :invalid="!!errors.company_phone" />
          </FormField>
          <FormField label="Company E-Mail Address" :error="errors.company_email_address" required>
            <Input v-model="form.company_email_address" type="email" :invalid="!!errors.company_email_address" />
          </FormField>
          <FormField label="Business Registration Certificate" :error="errors.company_reg_file">
            <div v-if="agent?.company_reg_file" class="mb-2">
              <span class="text-sm text-gray-600">Current file: </span>
              <a :href="getFileUrl('company_reg_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">
                View Current File
              </a>
            </div>
            <FileInput v-model="form.company_reg_file" accept=".pdf,.jpeg,.jpg,.png" />
            <p class="text-sm text-gray-500 mt-1">Company SSM document/certificate</p>
            <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
          </FormField>
          <FormField label="Company Representative ID (NRIC/Passport)" :error="errors.company_representative_id_file">
            <div v-if="agent?.company_representative_id_file" class="mb-2">
              <span class="text-sm text-gray-600">Current file: </span>
              <a :href="getFileUrl('company_representative_id_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">
                View Current File
              </a>
            </div>
            <FileInput v-model="form.company_representative_id_file" accept=".pdf,.jpeg,.jpg,.png" />
            <p class="text-sm text-gray-500 mt-1">Copy of the company representative's IC or Passport.</p>
            <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
          </FormField>
        </div>

        <!-- About Me / About Company -->
        <div class="space-y-4 mt-4">
          <FormField :label="form.profile_type === 'individual' ? 'About Me' : 'About Company'" :error="errors.about">
            <Textarea
              v-model="form.about"
              :rows="4"
              :placeholder="form.profile_type === 'individual' ? 'Tell us about yourself in 100 words' : 'Tell us about your company in 100 words'"
              :invalid="!!errors.about"
            />
            <p class="text-sm text-gray-500 mt-1">Tell us about yourself / your company in 100 words</p>
            <p class="text-sm text-gray-400 mt-1">Word count: {{ aboutWordCount }} / 100 words</p>
          </FormField>
        </div>
      </div>

      <!-- Bank Account Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Bank Account Information</h3>

        <div class="space-y-4">
          <FormField label="Account Name" :error="errors.bank_account_name">
            <Input v-model="form.bank_account_name" type="text" :invalid="!!errors.bank_account_name" />
          </FormField>
          <FormField label="Account Number" :error="errors.bank_account_number">
            <Input v-model="form.bank_account_number" type="text" :invalid="!!errors.bank_account_number" />
          </FormField>
          <FormField label="Bank Name" :error="errors.bank_name">
            <Input v-model="form.bank_name" type="text" :invalid="!!errors.bank_name" />
          </FormField>
          <FormField label="IBAN" :error="errors.iban">
            <Input v-model="form.iban" type="text" :invalid="!!errors.iban" />
          </FormField>
          <FormField label="SWIFT Code" :error="errors.swift_code">
            <Input v-model="form.swift_code" type="text" :invalid="!!errors.swift_code" />
          </FormField>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end">
        <Button type="submit" variant="default" size="default" :disabled="isSaving">
          <span v-if="isSaving">Saving...</span>
          <span v-else>Save</span>
        </Button>
      </div>
    </form>
  </div>
</template>
