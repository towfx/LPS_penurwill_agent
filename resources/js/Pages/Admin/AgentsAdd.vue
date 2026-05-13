<template>
  <div>
    <PageHeader
      title="Add Agent"
      description="Create a new agent account with profile, account, and referral information."
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Agents', href: '/admin/agents/list' }, { label: 'Add Agent' }]"
    >
      <template #actions>
        <Button variant="outline" @click="goBack">
          <ArrowLeft size="16" class="mr-2" />
          Back to List
        </Button>
      </template>
    </PageHeader>

    <Card>
      <CardHeader>
        <CardTitle>Agent Information</CardTitle>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Hierarchy -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Hierarchy</h3>
            <div class="grid gap-4 md:grid-cols-2">
              <FormField label="Agent Role" :error="errors.agent_role" required>
                <Select
                  v-model="form.agent_role"
                  :options="[
                    { value: 'agent', label: roleNames.agent },
                    { value: 'agent_leader', label: roleNames.leader },
                    { value: 'business_partner', label: roleNames.business_partner },
                  ]"
                  :invalid="!!errors.agent_role"
                />
              </FormField>

              <FormField label="Parent Agent" :error="errors.parent_agent_id">
                <Select
                  v-model="form.parent_agent_id"
                  :options="parentOptions"
                  placeholder="Select a parent agent..."
                  :invalid="!!errors.parent_agent_id"
                />
                <p class="text-sm text-gray-500 mt-1">
                  Filter shows eligible parent agents for {{ form.agent_role.replace('_', ' ') }}.
                </p>
              </FormField>
            </div>
          </div>

          <!-- Agent Type Selection -->
          <div class="border-t pt-6">
            <FormField label="Agent Type" :error="errors.profile_type" required>
              <div class="flex space-x-4">
                <Radio v-model="form.profile_type" value="individual" name="profile_type" label="Individual" />
                <Radio v-model="form.profile_type" value="company" name="profile_type" label="Company" />
              </div>
            </FormField>
          </div>

          <!-- Individual Fields -->
          <div v-if="isIndividual" class="space-y-4">
            <FormField label="Individual Name" :error="errors.individual_name" required>
              <Input v-model="form.individual_name" type="text" placeholder="Enter individual name" :invalid="!!errors.individual_name" />
            </FormField>

            <FormField label="Phone Number" :error="errors.individual_phone" required>
              <Input v-model="form.individual_phone" type="text" placeholder="Enter phone number" :invalid="!!errors.individual_phone" />
            </FormField>

            <FormField label="Alternative E-Mail Address" :error="errors.individual_email">
              <Input v-model="form.individual_email" type="email" placeholder="Enter alternative email address (optional)" :invalid="!!errors.individual_email" />
            </FormField>

            <FormField label="Address" :error="errors.individual_address" required>
              <Textarea v-model="form.individual_address" :rows="3" placeholder="Enter address" :invalid="!!errors.individual_address" />
            </FormField>

            <FormField label="NRIC/Passport Number" :error="errors.individual_id_number">
              <Input v-model="form.individual_id_number" type="text" placeholder="National registration identification number or Passport Number" :invalid="!!errors.individual_id_number" />
              <p class="text-sm text-gray-500 mt-1">National registration identification number or Passport Number</p>
            </FormField>

            <FormField label="Copy of IC/Passport" :error="errors.individual_id_file">
              <FileInput @change="handleIndividualIdFileChange" accept=".pdf,.jpeg,.jpg,.png" />
              <p class="text-sm text-gray-500 mt-1">Upload copy of national registration identity card or Passport file</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            </FormField>
          </div>

          <!-- Company Fields -->
          <div v-if="isCompany" class="space-y-4">
            <FormField label="Company Representative Name" :error="errors.company_representative_name" required>
              <Input v-model="form.company_representative_name" type="text" placeholder="Enter representative name" :invalid="!!errors.company_representative_name" />
            </FormField>

            <FormField label="Company Representative ID Number" :error="errors.company_representative_id_number" required>
              <Input v-model="form.company_representative_id_number" type="text" placeholder="Enter representative ID number" :invalid="!!errors.company_representative_id_number" />
            </FormField>

            <FormField label="Company Name" :error="errors.company_name" required>
              <Input v-model="form.company_name" type="text" placeholder="Enter company name" :invalid="!!errors.company_name" />
            </FormField>

            <FormField label="Company Registration Number" :error="errors.company_registration_number" required>
              <Input v-model="form.company_registration_number" type="text" placeholder="Enter registration number" :invalid="!!errors.company_registration_number" />
            </FormField>

            <FormField label="Company Address" :error="errors.company_address" required>
              <Textarea v-model="form.company_address" :rows="3" placeholder="Enter company address" :invalid="!!errors.company_address" />
            </FormField>

            <FormField label="Company Phone" :error="errors.company_phone" required>
              <Input v-model="form.company_phone" type="text" placeholder="Enter company phone" :invalid="!!errors.company_phone" />
            </FormField>

            <FormField label="Company E-Mail Address" :error="errors.company_email_address">
              <Input v-model="form.company_email_address" type="email" placeholder="Enter company email address" :invalid="!!errors.company_email_address" />
            </FormField>

            <FormField label="Business Registration Certificate" :error="errors.company_reg_file">
              <FileInput @change="handleCompanyRegFileChange" accept=".pdf,.jpeg,.jpg,.png" />
              <p class="text-sm text-gray-500 mt-1">Company SSM document/certificate</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            </FormField>

            <FormField label="Company Representative ID (NRIC/Passport)" :error="errors.company_representative_id_file">
              <FileInput @change="handleCompanyRepIdFileChange" accept=".pdf,.jpeg,.jpg,.png" />
              <p class="text-sm text-gray-500 mt-1">Copy of the company representative's IC or Passport.</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            </FormField>
          </div>

          <!-- About Me / About Company -->
          <FormField :label="form.profile_type === 'individual' ? 'About Me' : 'About Company'" :error="errors.about" required>
            <Textarea
              v-model="form.about"
              :rows="4"
              :placeholder="form.profile_type === 'individual' ? 'Tell us about yourself in 100 words' : 'Tell us about your company in 100 words'"
              :invalid="!!errors.about"
            />
            <p class="text-sm text-gray-500 mt-1">Tell us about yourself / your company in 100 words</p>
            <p class="text-sm text-gray-400 mt-1">Word count: {{ aboutWordCount }} / 100 words</p>
          </FormField>

          <!-- User Account Information -->
          <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">User Account Information</h3>

            <div class="space-y-4">
              <FormField label="Email Address" :error="errors.user_email" required>
                <Input v-model="form.user_email" type="email" placeholder="Enter email address" :invalid="!!errors.user_email" />
              </FormField>

              <FormField label="Password" :error="errors.user_password" required>
                <Input v-model="form.user_password" type="password" placeholder="Enter password" :invalid="!!errors.user_password" />
              </FormField>

              <FormField label="Confirm Password" :error="errors.user_password_confirmation" required>
                <Input v-model="form.user_password_confirmation" type="password" placeholder="Confirm password" :invalid="!!errors.user_password_confirmation" />
              </FormField>
            </div>
          </div>

          <!-- Status -->
          <FormField label="Status" :error="errors.status">
            <Select
              v-model="form.status"
              :options="[
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
                { value: 'suspended', label: 'Suspended' },
                { value: 'banned', label: 'Banned' },
                { value: 'expired', label: 'Expired' },
              ]"
              :invalid="!!errors.status"
            />
          </FormField>


          <!-- Membership lifecycle -->
          <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Membership Lifecycle</h3>
            <div class="grid gap-4 md:grid-cols-2">
              <FormField label="Registered At" :error="errors.registered_at">
                <Input v-model="form.registered_at" type="date" :invalid="!!errors.registered_at" />
              </FormField>
              <FormField label="Expires At" :error="errors.expires_at">
                <Input v-model="form.expires_at" type="date" :invalid="!!errors.expires_at" />
              </FormField>
              <FormField label="Renewal Due At" :error="errors.renewal_due_at">
                <Input v-model="form.renewal_due_at" type="date" :invalid="!!errors.renewal_due_at" />
              </FormField>
              <FormField label="Fee Payment Status" :error="errors.fee_payment_status">
                <Select
                  v-model="form.fee_payment_status"
                  :options="[
                    { value: 'pending', label: 'Pending' },
                    { value: 'paid', label: 'Paid' },
                    { value: 'overdue', label: 'Overdue' },
                    { value: 'waived', label: 'Waived' },
                  ]"
                  :invalid="!!errors.fee_payment_status"
                />
              </FormField>
            </div>
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
          <FormField label="Account Name" :error="errors.bank_account_name">
            <Input v-model="form.bank_account_name" type="text" placeholder="Enter account name" :invalid="!!errors.bank_account_name" />
          </FormField>
          <FormField label="Account Number" :error="errors.bank_account_number">
            <Input v-model="form.bank_account_number" type="text" placeholder="Enter account number" :invalid="!!errors.bank_account_number" />
          </FormField>
          <FormField label="Bank Name" :error="errors.bank_name">
            <Input v-model="form.bank_name" type="text" placeholder="Enter bank name" :invalid="!!errors.bank_name" />
          </FormField>
          <FormField label="IBAN" :error="errors.iban">
            <Input v-model="form.iban" type="text" placeholder="Enter IBAN" :invalid="!!errors.iban" />
          </FormField>
          <FormField label="SWIFT Code" :error="errors.swift_code">
            <Input v-model="form.swift_code" type="text" placeholder="Enter SWIFT code" :invalid="!!errors.swift_code" />
          </FormField>
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
          <FormField label="Referral Code" :error="errors.referral_code">
            <div class="flex gap-2">
              <Input v-model="form.referral_code" type="text" placeholder="Enter unique referral code" :invalid="!!errors.referral_code" class="flex-1 font-mono" />
              <Button type="button" variant="outline" @click="regenerateCode" class="shrink-0">
                Regenerate
              </Button>
            </div>
            <p class="text-sm text-gray-500 mt-1">Referral code must be unique across all agents</p>
            <p class="text-sm text-gray-500 mt-1">Prefix: {{ referralCodePrefix }} (auto-generated)</p>
          </FormField>
          <FormField label="Commission Rate (%)" :error="errors.referral_commission_rate">
            <Input v-model="form.referral_commission_rate" type="number" placeholder="Enter commission rate" :invalid="!!errors.referral_commission_rate" />
          </FormField>
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
          <Button variant="outline" @click="closeBankWarningDialog">Cancel</Button>
          <Button variant="default" @click="proceedWithSave">Save Anyway</Button>
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
          <Button variant="destructive" @click="closeErrorDialog">Close</Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import AdminLayout from '../Design/AdminLayout.vue'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardContent from '../Design/Components/CardContent.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import Button from '../Design/Components/Button.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import Select from '../Design/Components/Select.vue'
import Radio from '../Design/Components/Radio.vue'
import FileInput from '../Design/Components/FileInput.vue'
import Textarea from '../Design/Components/Textarea.vue'

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

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))

const form = reactive({
  profile_type: 'individual',
  individual_name: '',
  individual_phone: '',
  individual_email: '',
  individual_address: '',
  individual_id_number: '',
  individual_id_file: null,
  company_representative_name: '',
  company_representative_id_number: '',
  company_name: '',
  company_registration_number: '',
  company_address: '',
  company_phone: '',
  company_email_address: '',
  company_reg_file: null,
  company_representative_id_file: null,
  about: '',
  user_email: '',
  user_password: '',
  user_password_confirmation: '',
  status: 'active',
  agent_role: 'agent',
  parent_agent_id: null,
  registered_at: '',
  expires_at: '',
  renewal_due_at: '',
  fee_payment_status: 'pending',
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

const handleCompanyRepIdFileChange = (event) => {
  form.company_representative_id_file = event.target.files[0]
}

// Parent agent search
const parentOptions = ref([])

const fetchParents = async () => {
  try {
    const params = new URLSearchParams({
      child_role: form.agent_role,
    })
    const res = await fetch(`/admin/agents/parents?${params}`, {
      headers: { Accept: 'application/json' },
    })
    if (!res.ok) return
    const data = await res.json()
    const agents = data.parents || data.data || data || []
    parentOptions.value = agents.map(p => ({
      value: p.id,
      label: `${p.name} (#${p.id} — ${p.agent_role.replace('_', ' ')})`
    }))
  } catch (e) {
    parentOptions.value = []
  }
}

// Fetch initial parents and on role change
fetchParents()
watch(() => form.agent_role, () => {
  form.parent_agent_id = null
  fetchParents()
})

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
