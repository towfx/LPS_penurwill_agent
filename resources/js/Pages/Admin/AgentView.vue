<template>
  <div>
    <PageHeader
      title="Agent Details"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Agents', href: '/admin/agents/list' }, { label: 'View Agent' }]"
    >
      <template #actions>
        <Button variant="secondary" @click="goBack">Back to List</Button>
        <Button v-if="agent.status !== 'active' && agent.status !== 'rejected'" variant="default" @click="showApproveDialog">Approve Agent</Button>
        <Button v-if="agent.status !== 'rejected' && agent.status !== 'active'" variant="destructive" @click="showRejectDialogFn">Reject Agent</Button>
        <Button variant="default" @click="goToEdit">Edit Agent</Button>
      </template>
    </PageHeader>

    <div v-if="!agent" class="text-accent-red">Agent not found.</div>
    <div v-else class="space-y-6">
      <!-- Agent Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-forest-dark mb-4">{{ isIndividual ? 'Individual Agent' : 'Company Agent' }}</h2>

        <div v-if="isIndividual" class="space-y-3">
          <div><span class="font-medium text-gray-700">Name:</span> {{ agent.individual_name }}</div>
          <div><span class="font-medium text-gray-700">Phone:</span> {{ agent.individual_phone }}</div>
          <div><span class="font-medium text-gray-700">Address:</span> {{ agent.individual_address }}</div>
          <div v-if="agent.individual_id_number">
            <span class="font-medium text-gray-700">NRIC/Passport Number:</span> {{ agent.individual_id_number }}
          </div>
          <div v-if="agent.individual_id_file">
            <span class="font-medium text-gray-700">Copy of IC/Passport:</span>
            <a :href="getFileUrl('individual_id_file')" target="_blank" class="text-gold hover:text-amber-700 ml-2">
              View File
            </a>
          </div>
        </div>
        <div v-else-if="isCompany" class="space-y-3">
          <div><span class="font-medium text-gray-700">Company Name:</span> {{ agent.company_name }}</div>
          <div><span class="font-medium text-gray-700">Representative:</span> {{ agent.company_representative_name }}</div>
          <div><span class="font-medium text-gray-700">Representative ID Number:</span> {{ agent.company_representative_id_number }}</div>
          <div><span class="font-medium text-gray-700">Registration Number:</span> {{ agent.company_registration_number }}</div>
          <div><span class="font-medium text-gray-700">Company Address:</span> {{ agent.company_address }}</div>
          <div><span class="font-medium text-gray-700">Company Phone:</span> {{ agent.company_phone }}</div>
          <div v-if="agent.company_reg_file">
            <span class="font-medium text-gray-700">Business Registration Certificate:</span>
            <a :href="getFileUrl('company_reg_file')" target="_blank" class="text-gold hover:text-amber-700 ml-2">
              View File
            </a>
          </div>
          <div v-if="agent.company_representative_id_file">
            <span class="font-medium text-gray-700">Company Representative ID:</span>
            <a :href="getFileUrl('company_representative_id_file')" target="_blank" class="text-gold hover:text-amber-700 ml-2">
              View File
            </a>
          </div>
        </div>

        <div v-if="agent.about" class="mt-6 space-y-2">
          <div>
            <span class="font-medium text-gray-700">{{ isIndividual ? 'About Me' : 'About Company' }}:</span>
            <p class="mt-1 text-gray-600 whitespace-pre-wrap">{{ agent.about }}</p>
          </div>
        </div>

        <div class="mt-6 space-y-2">
          <div>
            <span class="font-medium text-gray-700">Status:</span>
            <span :class="getStatusPillClass(agent.status)" class="ml-2">
              {{ agent.status.charAt(0).toUpperCase() + agent.status.slice(1) }}
            </span>
          </div>
          <div><span class="font-medium text-gray-700">User Email:</span> {{ agent.user_email }}</div>
          <div><span class="font-medium text-gray-700">Created:</span> {{ agent.created_at }}</div>

          <!-- Suspension / Rejection reasons -->
          <div v-if="agent.status === 'suspended' && agent.suspension_reason" class="mt-3 rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3">
            <div class="flex items-center gap-2 mb-1">
              <AlertTriangle class="w-4 h-4 text-yellow-700" />
              <span class="text-sm font-semibold text-yellow-900">Suspension Reason</span>
            </div>
            <p class="text-sm text-yellow-800">{{ agent.suspension_reason }}</p>
          </div>
          <div v-if="agent.status === 'rejected' && agent.rejection_reason" class="mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
            <div class="flex items-center gap-2 mb-1">
              <AlertTriangle class="w-4 h-4 text-red-700" />
              <span class="text-sm font-semibold text-red-900">Rejection Reason</span>
            </div>
            <p class="text-sm text-red-800">{{ agent.rejection_reason }}</p>
          </div>

          <!-- Status Override -->
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-sm font-medium text-gray-700 mb-2">Override Status</p>
            <div class="flex items-center gap-3">
              <select
                v-model="overrideStatus"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-gold focus:border-gold"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
                <option value="banned">Banned</option>
                <option value="pending">Pending</option>
                <option value="expired">Expired</option>
              </select>
              <Button
                variant="outline"
                size="sm"
                :disabled="overrideStatus === agent.status || isOverridingStatus"
                @click="applyStatusOverride"
              >
                <span v-if="isOverridingStatus">Saving…</span>
                <span v-else>Apply</span>
              </Button>
            </div>
            <p class="text-xs text-gray-400 mt-1">To reject, use the Reject button above.</p>
          </div>
        </div>
      </div>

      <!-- Fee Payment Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-forest-dark">Fee Payment Information</h3>
          <Button
            v-if="agent.latest_fee_payment"
            variant="outline"
            size="sm"
            @click="router.visit(`/admin/fee-payments/${agent.latest_fee_payment.id}`)"
            className="flex items-center gap-2"
          >
            <Eye class="w-4 h-4" />
            View Detail
          </Button>
        </div>
        
        <div v-if="agent.latest_fee_payment" class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <span class="font-medium text-gray-700">Fee Type:</span>
              <span class="ml-2 capitalize">{{ agent.latest_fee_payment.fee_type }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Amount:</span>
              <span class="ml-2">{{ agent.latest_fee_payment.amount }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Payment Method:</span>
              <span class="ml-2 capitalize">{{ agent.latest_fee_payment.payment_method.replace('_', ' ') }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Paid At:</span>
              <span class="ml-2">{{ agent.latest_fee_payment.paid_at || '—' }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Reference:</span>
              <span class="ml-2 font-mono text-xs">{{ agent.latest_fee_payment.payment_reference || '—' }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">Fee Payment Status:</span>
              <span :class="getFeeStatusPillClass(agent.fee_payment_status)" class="ml-2">
                {{ agent.fee_payment_status || 'pending' }}
              </span>
            </div>
          </div>
          
          <div v-if="agent.latest_fee_payment.receipt_file" class="mt-2">
            <span class="font-medium text-gray-700">Receipt:</span>
            <a :href="getFileUrl('receipt_file')" target="_blank" class="text-gold hover:text-amber-700 ml-2">
              View Receipt
            </a>
          </div>
        </div>
        <div v-else class="flex items-center gap-2 text-gray-500 italic">
          <AlertTriangle class="w-4 h-4" />
          <span>No payment received</span>
        </div>
      </div>

      <!-- Hierarchy & Membership -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Hierarchy &amp; Membership</h3>
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <span class="font-medium text-gray-700">Role:</span>
            <span class="ml-2">{{ roleLabel(agent.agent_role) }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Parent Agent:</span>
            <span v-if="agent.parent_agent" class="ml-2">
              <a :href="`/admin/agents/${agent.parent_agent.id}/view`" class="text-gold hover:text-amber-700">
                {{ agent.parent_agent.name || `#${agent.parent_agent.id}` }}
                ({{ roleLabel(agent.parent_agent.agent_role) }})
              </a>
            </span>
            <span v-else class="ml-2 text-gray-500">— top level —</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Registered At:</span>
            <span class="ml-2">{{ agent.registered_at || '—' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Expires At:</span>
            <span class="ml-2">{{ agent.expires_at || '—' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Renewal Due:</span>
            <span class="ml-2">{{ agent.renewal_due_at || '—' }}</span>
          </div>
          <div>
            <span class="font-medium text-gray-700">Fee Status:</span>
            <span :class="getFeeStatusPillClass(agent.fee_payment_status)" class="ml-2">
              {{ agent.fee_payment_status || 'pending' }}
            </span>
          </div>
        </div>

        <div v-if="agent.subordinates && agent.subordinates.length" class="mt-6">
          <h4 class="text-md font-semibold text-forest-dark mb-2">Direct Subordinates ({{ agent.subordinates.length }})</h4>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded">
              <thead class="bg-cream">
                <tr>
                  <th class="px-3 py-2 text-left">ID</th>
                  <th class="px-3 py-2 text-left">Name</th>
                  <th class="px-3 py-2 text-left">Role</th>
                  <th class="px-3 py-2 text-left">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="sub in agent.subordinates" :key="sub.id" class="hover:bg-gray-50">
                  <td class="px-3 py-2">
                    <a :href="`/admin/agents/${sub.id}/view`" class="text-gold hover:text-amber-700">#{{ sub.id }}</a>
                  </td>
                  <td class="px-3 py-2">{{ sub.name || sub.individual_name || sub.company_name }}</td>
                  <td class="px-3 py-2">{{ roleLabel(sub.agent_role) }}</td>
                  <td class="px-3 py-2">
                    <span :class="getStatusPillClass(sub.status)">{{ sub.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-else class="mt-4 text-sm text-gray-500">No direct subordinates.</div>
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
          <div><span class="font-medium text-gray-700">Used Count:</span> {{ agent.referral_code.used_count }}</div>
        </div>
        <div v-else class="text-gray-500">No referral code information available.</div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <Modal :show="showRejectDialogModal" max-width="md" @close="showRejectDialogModal = false">
      <div class="px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Reject Agent Application</h3>

        <!-- Fee paid warning -->
        <div v-if="rejectHasPaidFee" class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3 flex items-start gap-2">
          <AlertTriangle class="w-5 h-5 text-yellow-700 mt-0.5" />
          <div>
            <p class="text-sm font-semibold text-yellow-900">⚠ This agent has a fee payment on record.</p>
            <p class="text-xs text-yellow-800 mt-1">Please process a refund via the Stripe dashboard before confirming rejection.</p>
          </div>
        </div>

        <FormField label="Rejection Reason (required)">
          <Textarea v-model="rejectReason" placeholder="Reason for rejection..." rows="3" />
        </FormField>
        <div class="flex justify-end gap-3 mt-4">
          <Button variant="outline" @click="showRejectDialogModal = false">Cancel</Button>
          <Button variant="destructive" @click="confirmReject" :disabled="isRejecting || !rejectReason.trim()">
            <span v-if="isRejecting">Rejecting...</span>
            <span v-else>Confirm Rejection</span>
          </Button>
        </div>
      </div>
    </Modal>

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
          <Button variant="secondary" @click="closeApproveDialog">Cancel</Button>
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
import { computed, ref } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import Modal from '../../Components/Modal.vue'
import FormField from '../Design/Components/FormField.vue'
import Textarea from '../Design/Components/Textarea.vue'
import Badge from '../Design/Components/Badge.vue'
import { AlertTriangle, Eye } from 'lucide-vue-next'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  agent: {
    type: Object,
    default: null
  }
})

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))

const roleLabel = (role) => roleNames.value[role] || role || '—'

const getFeeStatusPillClass = (status) => {
  switch (status) {
    case 'paid':
      return 'bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium'
    case 'overdue':
      return 'bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium'
    case 'waived':
      return 'bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium'
    default:
      return 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium'
  }
}

const showApproveDialogModal = ref(false)
const isApproving = ref(false)

const showRejectDialogModal = ref(false)
const isRejecting = ref(false)
const rejectReason = ref('')
const rejectHasPaidFee = ref(false)

const showRejectDialogFn = () => {
  rejectHasPaidFee.value = false
  rejectReason.value = ''
  showRejectDialogModal.value = true
}

const confirmReject = () => {
  if (!rejectReason.value.trim()) return
  isRejecting.value = true
  router.post(`/admin/agents/${props.agent.id}/reject`, { rejection_reason: rejectReason.value, confirm: true }, {
    onSuccess: () => {
      showRejectDialogModal.value = false
      isRejecting.value = false
      router.visit('/admin/agents/list')
    },
    onError: (errors) => {
      isRejecting.value = false
      if (errors.has_paid_fee) {
        rejectHasPaidFee.value = true
      }
    },
  })
}

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
  return `/admin/agents/${props.agent.id}/file/${field}?t=${timestamp}`
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
    await router.post(`/admin/agents/${props.agent.id}/approve`, {}, {
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

const goToEdit = () => {
  router.visit(`/admin/agents/${props.agent.id}/update`)
}

const goBack = () => {
  router.visit('/admin/agents/list')
}

const isIndividual = computed(() => props.agent && props.agent.profile_type === 'individual')
const isCompany = computed(() => props.agent && props.agent.profile_type === 'company')

const overrideStatus = ref(props.agent?.status || 'active')
const isOverridingStatus = ref(false)

const applyStatusOverride = () => {
  if (!overrideStatus.value || overrideStatus.value === props.agent.status) return
  isOverridingStatus.value = true
  router.post(`/admin/agents/${props.agent.id}/update`, { status: overrideStatus.value, _method: 'PUT' }, {
    preserveScroll: true,
    onSuccess: () => { isOverridingStatus.value = false },
    onError: () => { isOverridingStatus.value = false },
  })
}
</script>
