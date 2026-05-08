<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'
import { DollarSign, Calendar, CheckCircle, Upload, Download, Check, XCircle } from 'lucide-vue-next'
import Modal from '../../Components/Modal.vue'
import axios from 'axios'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import FormField from '../Design/Components/FormField.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  payout: { type: Object, required: true },
  agent: { type: Object, required: true },
  year: { type: Number, required: true },
  month: { type: Number, required: true },
  monthName: { type: String, required: true },
  reversalTimeLimit: { type: Number, default: 60 },
})

const page = usePage()

const breakdown = computed(() => {
  const items = props.payout?.payout_items || []
  const result = { own_sales: 0, override_agent: 0, override_leader: 0 }
  items.forEach((item) => {
    const type = item.commission_type || item.commission?.commission_type
    const category = item.commission_category || item.commission?.commission_category
    const amount = Number(item.amount || 0)
    if (type === 'own_sales') {
      result.own_sales += amount
    } else if (category === 'agent') {
      result.override_agent += amount
    } else if (category === 'agent_leader') {
      result.override_leader += amount
    } else {
      result.override_agent += amount
    }
  })
  return result
})

const formatType = (type, category) => {
  if (type === 'own_sales') return 'Own Sales'
  if (type === 'override') {
    const labels = {
      agent: page.props.systemSettings?.role_name_agent || 'Agent',
      agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
      business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
    }
    return `Override (${labels[category] || category || ''})`.trim()
  }
  return type || '—'
}

const getTypeBadgeClass = (type) => {
  if (type === 'own_sales') return 'inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800'
  if (type === 'override') return 'inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800'
  return 'inline-flex px-2 py-1 text-xs font-medium rounded-full bg-stone-100 text-stone-800'
}

const canMarkRefunded = (commission) => {
  if (!commission || commission.is_reversal) return false
  if (commission.status === 'cancelled') return false
  if (!commission.sale?.created_at) return true
  const saleDate = new Date(commission.sale.created_at)
  const limitMs = (props.reversalTimeLimit || 60) * 24 * 60 * 60 * 1000
  return Date.now() - saleDate.getTime() <= limitMs
}

const markRefunded = (commission) => {
  if (!commission?.sale?.id) return
  if (!confirm('Mark this sale as refunded? A negative reversal commission will be created.')) return
  router.post(`/admin/sales/${commission.sale.id}/refund`)
}

// File upload form
const fileForm = useForm({
  bank_transfer_file: null
})

const fileInput = ref(null)
const showMarkAsPaidDialog = ref(false)
const isUploading = ref(false)
const isMarkingAsPaid = ref(false)
const showSuccessDialog = ref(false)
const showErrorDialog = ref(false)
const dialogMessage = ref('')

// Format date/time for display
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Format date as "DD MMM YYYY"
const formatDateRequest = () => {
  if (!props.payout.created_at) return '—'
  const date = new Date(props.payout.created_at)
  const day = String(date.getDate()).padStart(2, '0')
  const month = date.toLocaleDateString('en-US', { month: 'short' })
  const year = date.getFullYear()
  return `${day} ${month} ${year}`
}

// Get status badge class
const getStatusClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 px-3 py-1.5 rounded-full text-xs font-medium'
    case 'approved':
      return 'bg-green-100 text-green-800 px-3 py-1.5 rounded-full text-xs font-medium'
    case 'paid':
      return 'bg-blue-100 text-blue-800 px-3 py-1.5 rounded-full text-xs font-medium'
    default:
      return 'bg-stone-100 text-stone-800 px-3 py-1.5 rounded-full text-xs font-medium'
  }
}

// Get agent name
const getAgentName = (agent) => {
  if (!agent) return 'Unknown Agent'
  return agent.profile_type === 'individual'
    ? agent.individual_name
    : agent.company_name
}

// Handle file selection
const handleFileSelect = (event) => {
  if (event.target.files && event.target.files[0]) {
    fileForm.bank_transfer_file = event.target.files[0]
  }
}

// Upload bank transfer file
const uploadBankTransfer = async () => {
  if (!fileForm.bank_transfer_file) {
    dialogMessage.value = 'Please select a file to upload'
    showErrorDialog.value = true
    return
  }

  isUploading.value = true
  const formData = new FormData()
  formData.append('bank_transfer_file', fileForm.bank_transfer_file)

  try {
    const response = await axios.post(`/admin/payout/${props.payout.id}/upload-bank-transfer`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    if (response.data.status === 'OK') {
      dialogMessage.value = response.data.message || 'Bank transfer file uploaded successfully.'
      showSuccessDialog.value = true
      fileForm.reset()
      if (fileInput.value) {
        fileInput.value.value = ''
      }
      // Reload the page to show the updated file
      router.reload({ only: ['payout'] })
    } else {
      dialogMessage.value = response.data.message || 'Failed to upload file. Please try again.'
      showErrorDialog.value = true
    }
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      dialogMessage.value = error.response.data.message
    } else if (error.response && error.response.data && error.response.data.status === 'fail') {
      dialogMessage.value = error.response.data.message || 'Failed to upload file. Please try again.'
    } else {
      dialogMessage.value = 'An error occurred while uploading the file. Please try again.'
    }
    showErrorDialog.value = true
  } finally {
    isUploading.value = false
  }
}

// Mark as paid
const markAsPaid = () => {
  isMarkingAsPaid.value = true
  router.post(`/admin/payout/${props.payout.id}/mark-as-paid`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      showMarkAsPaidDialog.value = false
      isMarkingAsPaid.value = false
    },
    onError: () => {
      isMarkingAsPaid.value = false
    }
  })
}

// Download bank transfer file
const downloadBankTransfer = () => {
  window.open(`/admin/payout/${props.payout.id}/download-bank-transfer`, '_blank')
}
</script>

<template>
  <div>
    <PageHeader
      title="Payout Detail"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Payouts', href: '/admin/payouts' }, { label: 'Payout Detail' }]"
    />

    <!-- Summary Card -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden mb-6">
      <!-- Card Header -->
      <div class="bg-accent-green px-6 py-4 border-b border-accent-green/20">
        <div class="flex items-center gap-3">
          <DollarSign class="w-6 h-6 text-white" />
          <h2 class="text-lg font-semibold text-white">Payout Summary</h2>
        </div>
      </div>

      <!-- Card Content -->
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <!-- Total Payouts -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-green/10 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-accent-green" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Total Payouts</p>
              <p class="text-2xl font-bold text-forest-dark">1</p>
            </div>
          </div>

          <!-- Date Request -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-blue/10 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-accent-blue" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Date Request</p>
              <p class="text-2xl font-bold text-forest-dark">{{ formatDateRequest() }}</p>
            </div>
          </div>

          <!-- Total Amount -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gold/10 rounded-lg flex items-center justify-center">
              <DollarSign class="w-6 h-6 text-gold" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Total Amount</p>
              <p class="text-2xl font-bold text-forest-dark">{{ formatCurrency('RM', payout.amount) }}</p>
            </div>
          </div>

          <!-- Status -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-blue/10 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-accent-blue" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Status</p>
              <span :class="getStatusClass(payout.status)" class="inline-flex px-3 py-1.5 rounded-full text-xs font-medium">
                {{ payout.status.charAt(0).toUpperCase() + payout.status.slice(1) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Agent Bank Information Card -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <h3 class="text-lg font-semibold text-forest-dark mb-4">Agent Bank Information</h3>
      <div v-if="agent.bank_account" class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <span class="text-sm font-medium text-stone-600">Account Name:</span>
            <p class="text-stone-900">{{ agent.bank_account.account_name }}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-stone-600">Account Number:</span>
            <p class="text-stone-900">{{ agent.bank_account.account_number }}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-stone-600">Bank Name:</span>
            <p class="text-stone-900">{{ agent.bank_account.bank_name }}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-stone-600">IBAN:</span>
            <p class="text-stone-900">{{ agent.bank_account.iban || '—' }}</p>
          </div>
          <div>
            <span class="text-sm font-medium text-stone-600">SWIFT Code:</span>
            <p class="text-stone-900">{{ agent.bank_account.swift_code || '—' }}</p>
          </div>
        </div>
      </div>
      <div v-else class="text-stone-500">
        No bank account information available.
      </div>
    </div>

    <!-- Bank Transfer File Upload Section -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <h3 class="text-lg font-semibold text-forest-dark mb-4">Bank Transfer File</h3>
      
      <div v-if="payout.bank_transfer_file" class="mb-4 space-y-3">
        <div class="flex items-center gap-3">
          <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            File Uploaded
          </span>
          <span class="text-sm text-stone-600">{{ payout.bank_transfer_file }}</span>
        </div>
        <Button variant="outline" @click="downloadBankTransfer">
          <Download class="w-4 h-4 mr-2" />
          Download File
        </Button>
      </div>

      <div class="space-y-4">
        <FormField label="Upload Bank Transfer File">
          <input
            ref="fileInput"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="handleFileSelect"
            class="block w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-accent-blue file:text-white hover:file:bg-accent-blue/90"
          />
          <p class="mt-1 text-xs text-stone-500">Accepted formats: PDF, JPG, JPEG, PNG (Max 5MB)</p>
        </FormField>
        <Button
          @click="uploadBankTransfer"
          :disabled="!fileForm.bank_transfer_file || isUploading"
        >
          <Upload class="w-4 h-4 mr-2" />
          <span v-if="isUploading">Uploading...</span>
          <span v-else>{{ payout.bank_transfer_file ? 'Update File' : 'Upload File' }}</span>
        </Button>
      </div>
    </div>

    <!-- Payout Items Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden mb-6">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Payout Items
        </h2>
      </div>

      <!-- Breakdown summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-6 py-4 border-b border-stone-200 bg-stone-50">
        <div class="text-center">
          <p class="text-xs text-stone-500 uppercase">Own Sales</p>
          <p class="text-lg font-bold text-forest-dark">{{ formatCurrency('RM', breakdown.own_sales) }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs text-stone-500 uppercase">Override (Agent)</p>
          <p class="text-lg font-bold text-forest-dark">{{ formatCurrency('RM', breakdown.override_agent) }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs text-stone-500 uppercase">Override (Leader)</p>
          <p class="text-lg font-bold text-forest-dark">{{ formatCurrency('RM', breakdown.override_leader) }}</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Sale Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Calc</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Sale Amount</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Rate / Fixed</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Commission</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="!payout.payout_items || payout.payout_items.length === 0" class="hover:bg-stone-50">
              <td colspan="9" class="px-6 py-4 text-center text-stone-500">
                No payout items found.
              </td>
            </tr>
            <tr
              v-for="item in (payout.payout_items || [])"
              :key="item.id"
              class="hover:bg-stone-50"
              :class="{ 'bg-red-50': item.commission?.is_reversal }"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ formatDate(item.commission.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm text-stone-900">
                <div class="max-w-xs truncate">
                  {{ item.commission.sale?.description || 'N/A' }}
                </div>
                <div class="text-xs text-stone-500">
                  Invoice: {{ item.commission.sale?.invoice_number || 'N/A' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span :class="getTypeBadgeClass(item.commission_type || item.commission?.commission_type)">
                  {{ formatType(item.commission_type || item.commission?.commission_type, item.commission_category || item.commission?.commission_category) }}
                </span>
                <span v-if="item.commission?.is_reversal" class="ml-1 text-xs font-medium text-accent-red">↩</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                {{ item.commission?.commission_calc_type || 'percentage' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', item.commission.sale?.amount || item.commission?.source_sale_amount || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-center">
                <span v-if="item.commission?.commission_calc_type === 'fixed'">
                  {{ formatCurrency('RM', item.commission?.commission_fixed_amount || 0) }}
                </span>
                <span v-else>{{ item.commission.commission_rate }}%</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                <span class="font-medium text-forest-dark">
                  {{ formatCurrency('RM', item.amount) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="getStatusClass(item.commission.status)">
                  {{ item.commission.status.charAt(0).toUpperCase() + item.commission.status.slice(1) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <Button
                  v-if="canMarkRefunded(item.commission)"
                  variant="destructive"
                  size="sm"
                  @click="markRefunded(item.commission)"
                >
                  Mark as Refunded
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mark as Paid Button -->
    <div v-if="payout.status !== 'paid'" class="bg-white rounded-lg shadow-sm border border-stone-200 p-6">
      <Button @click="showMarkAsPaidDialog = true">
        <Check class="w-5 h-5 mr-2" />
        Mark as Paid
      </Button>
    </div>

    <!-- Mark as Paid Confirmation Dialog -->
    <Modal
      :show="showMarkAsPaidDialog"
      max-width="md"
      @close="showMarkAsPaidDialog = false"
    >
      <div class="px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Mark as Paid</h3>
        <p class="text-sm text-gray-600 mb-6">
          Are you sure you want to mark this payout as paid? This action will set the status to "paid" and record the current date as the paid date.
        </p>
        <div class="flex justify-end gap-3">
          <Button variant="outline" @click="showMarkAsPaidDialog = false">Cancel</Button>
          <Button @click="markAsPaid" :disabled="isMarkingAsPaid">
            <span v-if="isMarkingAsPaid">Processing...</span>
            <span v-else>Confirm</span>
          </Button>
        </div>
      </div>
    </Modal>

    <!-- Success Dialog -->
    <Modal
      :show="showSuccessDialog"
      max-width="md"
      @close="showSuccessDialog = false"
    >
      <div class="px-6 py-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <CheckCircle class="h-6 w-6 text-green-600" />
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Success</h3>
          </div>
        </div>
        <div class="mb-4">
          <p class="text-sm text-gray-700">{{ dialogMessage }}</p>
        </div>
        <div class="flex justify-end">
          <Button @click="showSuccessDialog = false">OK</Button>
        </div>
      </div>
    </Modal>

    <!-- Error Dialog -->
    <Modal
      :show="showErrorDialog"
      max-width="md"
      @close="showErrorDialog = false"
    >
      <div class="px-6 py-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <XCircle class="h-6 w-6 text-red-600" />
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Error</h3>
          </div>
        </div>
        <div class="mb-4">
          <p class="text-sm text-gray-700">{{ dialogMessage }}</p>
        </div>
        <div class="flex justify-end">
          <Button variant="destructive" @click="showErrorDialog = false">OK</Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

