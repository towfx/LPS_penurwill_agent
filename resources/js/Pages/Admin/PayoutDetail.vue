<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'
import { DollarSign, Calendar, CheckCircle, Upload, Download, Check, XCircle } from 'lucide-vue-next'
import Modal from '../../Components/Modal.vue'
import axios from 'axios'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  payout: {
    type: Object,
    required: true
  },
  agent: {
    type: Object,
    required: true
  },
  year: {
    type: Number,
    required: true
  },
  month: {
    type: Number,
    required: true
  },
  monthName: {
    type: String,
    required: true
  }
})

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
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <Link href="/admin/payouts" class="hover:text-forest-dark transition-colors">Admin</Link> /
      <Link href="/admin/payouts" class="hover:text-forest-dark transition-colors">Payouts</Link> /
      <span class="text-stone-900 font-medium">Payout Detail</span>
    </nav>

    <!-- Title -->
    <h1 class="text-2xl font-bold text-forest-dark mb-6">Payout Detail</h1>

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
        <button
          @click="downloadBankTransfer"
          class="inline-flex items-center gap-2 px-4 py-2 bg-accent-blue text-white rounded-md font-medium hover:bg-accent-blue/90 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition-colors"
        >
          <Download class="w-4 h-4" />
          Download File
        </button>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-2">
            Upload Bank Transfer File
          </label>
          <input
            ref="fileInput"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="handleFileSelect"
            class="block w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-accent-blue file:text-white hover:file:bg-accent-blue/90"
          />
          <p class="mt-1 text-xs text-stone-500">Accepted formats: PDF, JPG, JPEG, PNG (Max 5MB)</p>
        </div>
        <button
          @click="uploadBankTransfer"
          :disabled="!fileForm.bank_transfer_file || isUploading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-accent-green text-white rounded-md font-medium hover:bg-accent-green/90 focus:outline-none focus:ring-2 focus:ring-accent-green focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Upload class="w-4 h-4" />
          <span v-if="isUploading">Uploading...</span>
          <span v-else>{{ payout.bank_transfer_file ? 'Update File' : 'Upload File' }}</span>
        </button>
      </div>
    </div>

    <!-- Payout Items Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden mb-6">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Payout Items
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Sale Description
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Sale Amount
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Commission Rate
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Commission Amount
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Status
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="!payout.payout_items || payout.payout_items.length === 0" class="hover:bg-stone-50">
              <td colspan="6" class="px-6 py-4 text-center text-stone-500">
                No payout items found.
              </td>
            </tr>
            <tr
              v-for="item in (payout.payout_items || [])"
              :key="item.id"
              class="hover:bg-stone-50"
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
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', item.commission.sale?.amount || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-center">
                {{ item.commission.commission_rate }}%
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
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mark as Paid Button -->
    <div v-if="payout.status !== 'paid'" class="bg-white rounded-lg shadow-sm border border-stone-200 p-6">
      <button
        @click="showMarkAsPaidDialog = true"
        class="inline-flex items-center gap-2 px-6 py-3 bg-accent-green text-white rounded-md font-medium hover:bg-accent-green/90 focus:outline-none focus:ring-2 focus:ring-accent-green focus:ring-offset-2 transition-colors"
      >
        <Check class="w-5 h-5" />
        Mark as Paid
      </button>
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
          <button
            @click="showMarkAsPaidDialog = false"
            class="px-4 py-2 text-sm font-medium text-stone-700 bg-stone-100 rounded-md hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:ring-offset-2"
          >
            Cancel
          </button>
          <button
            @click="markAsPaid"
            :disabled="isMarkingAsPaid"
            class="px-4 py-2 text-sm font-medium text-white bg-accent-green rounded-md hover:bg-accent-green/90 focus:outline-none focus:ring-2 focus:ring-accent-green focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isMarkingAsPaid">Processing...</span>
            <span v-else>Confirm</span>
          </button>
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
          <button
            @click="showSuccessDialog = false"
            class="px-4 py-2 text-sm font-medium text-white bg-accent-green rounded-md hover:bg-accent-green/90 focus:outline-none focus:ring-2 focus:ring-accent-green focus:ring-offset-2"
          >
            OK
          </button>
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
          <button
            @click="showErrorDialog = false"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
          >
            OK
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

