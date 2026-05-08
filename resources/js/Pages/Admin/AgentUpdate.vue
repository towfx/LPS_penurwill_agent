<template>
  <div>
    <PageHeader
      title="Edit Agent"
      description="Update agent profile, account, hierarchy, and referral information."
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Agents', href: '/admin/agents/list' }, { label: 'Edit Agent' }]"
    >
      <template #actions>
        <Button v-if="agent && agent.status !== 'active'" variant="default" @click="showApproveDialog">
          Approve Agent
        </Button>
        <Button variant="outline" @click="goBack">Back to List</Button>
      </template>
    </PageHeader>

    <form @submit.prevent="saveAgent" class="space-y-6">
      <!-- Agent Information -->
      <Card>
        <CardHeader>
          <CardTitle>Agent Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <FormField label="Agent Type">
            <div class="flex space-x-4">
              <Radio v-model="form.profile_type" value="individual" name="profile_type" label="Individual" />
              <Radio v-model="form.profile_type" value="company" name="profile_type" label="Company" />
            </div>
          </FormField>

          <div v-if="isIndividual" class="space-y-4">
            <FormField label="Name" :error="errors.individual_name">
              <Input v-model="form.individual_name" type="text" :invalid="!!errors.individual_name" />
            </FormField>
            <FormField label="Phone" :error="errors.individual_phone">
              <Input v-model="form.individual_phone" type="text" :invalid="!!errors.individual_phone" />
            </FormField>
            <FormField label="Alternative E-Mail Address" :error="errors.individual_email">
              <Input v-model="form.individual_email" type="email" placeholder="Enter alternative email address (optional)" :invalid="!!errors.individual_email" />
            </FormField>
            <FormField label="Address" :error="errors.individual_address">
              <Textarea v-model="form.individual_address" :invalid="!!errors.individual_address" />
            </FormField>
            <FormField label="NRIC/Passport Number" :error="errors.individual_id_number">
              <Input v-model="form.individual_id_number" type="text" placeholder="National registration identification number or Passport Number" :invalid="!!errors.individual_id_number" />
              <p class="text-sm text-gray-500 mt-1">National registration identification number or Passport Number</p>
            </FormField>
            <FormField label="Copy of IC/Passport" :error="errors.individual_id_file">
              <div v-if="agent?.individual_id_file" class="mb-2">
                <span class="text-sm text-gray-600">Current file: </span>
                <a :href="getFileUrl('individual_id_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">View Current File</a>
              </div>
              <FileInput @change="handleIndividualIdFileChange" accept=".pdf,.jpeg,.jpg,.png" />
              <p class="text-sm text-gray-500 mt-1">Upload copy of national registration identity card or Passport file</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            </FormField>
          </div>

          <div v-if="isCompany" class="space-y-4">
            <FormField label="Company Name" :error="errors.company_name">
              <Input v-model="form.company_name" type="text" :invalid="!!errors.company_name" />
            </FormField>
            <FormField label="Representative" :error="errors.company_representative_name">
              <Input v-model="form.company_representative_name" type="text" :invalid="!!errors.company_representative_name" />
            </FormField>
            <FormField label="Registration Number" :error="errors.company_registration_number">
              <Input v-model="form.company_registration_number" type="text" :invalid="!!errors.company_registration_number" />
            </FormField>
            <FormField label="Company Address" :error="errors.company_address">
              <Textarea v-model="form.company_address" :invalid="!!errors.company_address" />
            </FormField>
            <FormField label="Company Phone" :error="errors.company_phone">
              <Input v-model="form.company_phone" type="text" :invalid="!!errors.company_phone" />
            </FormField>
            <FormField label="Company E-Mail Address" :error="errors.company_email_address">
              <Input v-model="form.company_email_address" type="email" :invalid="!!errors.company_email_address" />
            </FormField>
            <FormField label="Business Registration Certificate" :error="errors.company_reg_file">
              <div v-if="agent?.company_reg_file" class="mb-2">
                <span class="text-sm text-gray-600">Current file: </span>
                <a :href="getFileUrl('company_reg_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">View Current File</a>
              </div>
              <FileInput @change="handleCompanyRegFileChange" accept=".pdf,.jpeg,.jpg,.png" />
              <p class="text-sm text-gray-500 mt-1">Company SSM document/certificate</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            </FormField>
            <FormField label="Company Representative ID (NRIC/Passport)" :error="errors.company_representative_id_file">
              <div v-if="agent?.company_representative_id_file" class="mb-2">
                <span class="text-sm text-gray-600">Current file: </span>
                <a :href="getFileUrl('company_representative_id_file')" target="_blank" class="text-gold hover:text-amber-700 text-sm">View Current File</a>
              </div>
              <FileInput @change="handleCompanyRepIdFileChange" accept=".pdf,.jpeg,.jpg,.png" />
              <p class="text-sm text-gray-500 mt-1">Copy of the company representative's IC or Passport.</p>
              <p class="text-sm text-gray-500">Accepted formats: PDF, JPEG, JPG, PNG (Max 10MB)</p>
            </FormField>
          </div>

          <!-- About Me / About Company -->
          <div class="mt-4">
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

          <FormField label="Status" :error="errors.status">
            <div class="flex items-center gap-3">
              <Select
                v-model="form.status"
                :options="[
                  { value: 'active', label: 'Active' },
                  { value: 'inactive', label: 'Inactive' },
                  { value: 'suspended', label: 'Suspended' },
                  { value: 'banned', label: 'Banned' },
                  { value: 'expired', label: 'Expired' },
                ]"
                class="flex-1"
              />
              <span v-if="agent" :class="getStatusPillClass(agent.status)" class="shrink-0">
                {{ agent.status.charAt(0).toUpperCase() + agent.status.slice(1) }}
              </span>
            </div>
          </FormField>
        </CardContent>
      </Card>

      <!-- Hierarchy & Membership -->
      <Card>
        <CardHeader>
          <CardTitle>Hierarchy &amp; Membership</CardTitle>
        </CardHeader>
        <CardContent>
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
              <Input
                v-model="parentSearchQuery"
                @input="searchParents"
                type="text"
                list="parent-options"
                placeholder="Search by name or ID..."
              />
              <datalist id="parent-options">
                <option
                  v-for="p in parentOptions"
                  :key="p.id"
                  :value="`${p.name} (#${p.id} — ${p.agent_role})`"
                />
              </datalist>
              <p class="text-sm text-gray-500 mt-1">Filter shows agents with role ≥ child role.</p>
            </FormField>

            <FormField label="Registered At">
              <Input v-model="form.registered_at" type="date" />
            </FormField>

            <FormField label="Expires At">
              <Input v-model="form.expires_at" type="date" />
            </FormField>

            <FormField label="Renewal Due At">
              <Input v-model="form.renewal_due_at" type="date" />
            </FormField>

            <FormField label="Fee Payment Status">
              <Select
                v-model="form.fee_payment_status"
                :options="[
                  { value: 'pending', label: 'Pending' },
                  { value: 'paid', label: 'Paid' },
                  { value: 'overdue', label: 'Overdue' },
                  { value: 'waived', label: 'Waived' },
                ]"
              />
            </FormField>
          </div>
        </CardContent>
      </Card>

      <!-- Downgrade warning modal -->
      <div v-if="showDowngradeWarning" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
          <div class="flex items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">⚠ Confirm Role Downgrade</h3>
          </div>
          <p class="text-sm text-gray-700 mb-4">
            This agent has <span class="font-bold">{{ downgradeSubordinateCount }}</span> subordinates.
            After downgrade they will no longer earn override commissions from those agents.
            Subordinates must be manually reassigned if desired.
          </p>
          <div class="flex justify-end space-x-3">
            <Button variant="outline" @click="showDowngradeWarning = false">Cancel</Button>
            <Button variant="destructive" @click="confirmDowngradeAndSave">Continue Anyway</Button>
          </div>
        </div>
      </div>

      <!-- Login Information -->
      <Card>
        <CardHeader>
          <CardTitle>Login Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <FormField label="Login Email">
            <Input :value="agent?.user_email || ''" type="email" disabled />
          </FormField>
          <FormField label="New Password (leave blank to keep current)" :error="errors.user_password">
            <Input v-model="form.user_password" type="password" :invalid="!!errors.user_password" />
          </FormField>
          <FormField v-if="form.user_password" label="Confirm Password" :error="errors.user_password_confirmation">
            <Input v-model="form.user_password_confirmation" type="password" :invalid="!!errors.user_password_confirmation" />
          </FormField>
        </CardContent>
      </Card>

      <!-- Bank Account Information -->
      <Card>
        <CardHeader>
          <CardTitle>Bank Account Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
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
        </CardContent>
      </Card>

      <!-- Referral Code Information -->
      <Card>
        <CardHeader>
          <CardTitle>Referral Code Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <FormField label="Referral Code" :error="errors.referral_code">
            <Input v-model="form.referral_code" type="text" placeholder="Enter unique referral code" :invalid="!!errors.referral_code" class="font-mono" />
            <p class="text-sm text-gray-500 mt-1">Referral code must be unique across all agents</p>
          </FormField>
          <FormField label="Commission Rate (%)" :error="errors.referral_commission_rate">
            <Input v-model="form.referral_commission_rate" type="number" :invalid="!!errors.referral_commission_rate" />
          </FormField>
        </CardContent>
      </Card>

      <!-- Form Actions -->
      <div class="flex justify-end">
        <Button type="submit" :disabled="isSaving">
          <span v-if="isSaving">Saving...</span>
          <span v-else>Save Changes</span>
        </Button>
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
          <Button variant="outline" @click="closeApproveDialog">Cancel</Button>
          <Button variant="default" @click="approveAgent" :disabled="isApproving">
            <span v-if="isApproving">Approving...</span>
            <span v-else>Confirm</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
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

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))

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
  company_representative_id_file: null,
  about: props.agent?.about || '',
  user_password: '',
  user_password_confirmation: '',
  status: props.agent?.status || 'active',
  agent_role: props.agent?.agent_role || 'agent',
  parent_agent_id: props.agent?.parent_agent_id || null,
  registered_at: props.agent?.registered_at || '',
  expires_at: props.agent?.expires_at || '',
  renewal_due_at: props.agent?.renewal_due_at || '',
  fee_payment_status: props.agent?.fee_payment_status || 'pending',
  // Confirmation flag for downgrade modal
  confirm_downgrade: false,
  // Bank account fields
  bank_account_name: props.agent?.bank_account?.account_name || '',
  bank_account_number: props.agent?.bank_account?.account_number || '',
  bank_name: props.agent?.bank_account?.bank_name || '',
  iban: props.agent?.bank_account?.iban || '',
  swift_code: props.agent?.bank_account?.swift_code || '',
  // Referral code fields
  referral_code: props.agent?.referral_code?.code || '',
  referral_commission_rate: props.agent?.referral_code?.commission_rate || '',
  referral_is_active: true,
})

// Parent agent search
const parentSearchQuery = ref(
  props.agent?.parent_agent
    ? `${props.agent.parent_agent.name || ''} (#${props.agent.parent_agent.id} — ${props.agent.parent_agent.agent_role})`
    : ''
)
const parentOptions = ref([])
let parentSearchTimer = null

const searchParents = () => {
  clearTimeout(parentSearchTimer)
  parentSearchTimer = setTimeout(async () => {
    try {
      const params = new URLSearchParams({
        search: parentSearchQuery.value,
        child_role: form.value.agent_role,
        exclude: props.id,
      })
      const res = await fetch(`/admin/agents/parents?${params}`, {
        headers: { Accept: 'application/json' },
      })
      if (!res.ok) return
      const data = await res.json()
      parentOptions.value = data.parents || data.data || data || []
    } catch (e) {
      parentOptions.value = []
    }
  }, 250)
}

watch(parentSearchQuery, (val) => {
  const match = String(val).match(/#(\d+)/)
  if (match) {
    form.value.parent_agent_id = parseInt(match[1], 10)
  } else if (!val) {
    form.value.parent_agent_id = null
  }
})

watch(() => form.value.agent_role, () => {
  parentOptions.value = []
})

// Role rank helper for downgrade detection
const roleRank = { agent: 1, agent_leader: 2, business_partner: 3 }
const showDowngradeWarning = ref(false)
const downgradeSubordinateCount = ref(0)

const isIndividual = computed(() => form.value.profile_type === 'individual')
const isCompany = computed(() => form.value.profile_type === 'company')

const aboutWordCount = computed(() => {
  if (!form.value.about || !form.value.about.trim()) {
    return 0
  }
  return form.value.about.trim().split(/\s+/).filter(word => word.length > 0).length
})

const isSaving = ref(false)
const errors = ref({})
const showErrorDialog = ref(false)
const generalErrorMessage = ref('')
const showBankWarningDialog = ref(false)
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
  return `/admin/agents/${props.id}/file/${field}?t=${timestamp}`
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
    await router.post(`/admin/agents/${props.id}/approve`, {}, {
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

const handleIndividualIdFileChange = (event) => {
  form.value.individual_id_file = event.target.files[0]
}

const handleCompanyRegFileChange = (event) => {
  form.value.company_reg_file = event.target.files[0]
}

const handleCompanyRepIdFileChange = (event) => {
  form.value.company_representative_id_file = event.target.files[0]
}

const confirmDowngradeAndSave = () => {
  showDowngradeWarning.value = false
  form.value.confirm_downgrade = true
  saveAgent(true)
}

const saveAgent = async (skipDowngradeCheck = false) => {
  isSaving.value = true
  errors.value = {}
  showErrorDialog.value = false
  showBankWarningDialog.value = false
  generalErrorMessage.value = ''

  // Detect role downgrade (Decision 20)
  if (!skipDowngradeCheck && props.agent) {
    const oldRank = roleRank[props.agent.agent_role] || 1
    const newRank = roleRank[form.value.agent_role] || 1
    const subCount = props.agent.subordinates_count ?? props.agent.subordinates?.length ?? 0
    if (newRank < oldRank && subCount > 0) {
      downgradeSubordinateCount.value = subCount
      showDowngradeWarning.value = true
      isSaving.value = false
      return
    }
  }

  // Check for incomplete bank account information
  const bankFields = [form.value.bank_account_name, form.value.bank_account_number, form.value.bank_name]
  const filledBankFields = bankFields.filter(field => field && field.trim() !== '')
  const hasPartialBankInfo = filledBankFields.length > 0 && filledBankFields.length < 3

  if (hasPartialBankInfo) {
    showBankWarningDialog.value = true
    isSaving.value = false
    return
  }

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

      await router.post(`/admin/agents/${props.id}/update`, formData, {
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
    } else {
      // No files, use regular PUT request
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
    }
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

      await router.post(`/admin/agents/${props.id}/update`, formData, {
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
    } else {
      // No files, use regular PUT request
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
    }
  } finally {
    isSaving.value = false
  }
}
</script>
