<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import { formatCurrency } from '../../lib/utils.js'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import lottie from 'lottie-web'
import { DollarSign, Calendar, CheckCircle } from 'lucide-vue-next'
import Modal from '../../Components/Modal.vue'
import axios from 'axios'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  commissions: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({
      start_date: null,
      end_date: null
    })
  },
  agent: {
    type: Object,
    required: true
  }
})

// Date range state
const dateRange = ref(
  props.filters.start_date && props.filters.end_date
    ? [new Date(props.filters.start_date), new Date(props.filters.end_date)]
    : null
)

// Selected commissions (default: all checked)
const selectedCommissions = ref(
  props.commissions.map(c => c.id)
)

// Success dialog state
const showSuccessDialog = ref(false)
const successMessage = ref('')
const isSubmitting = ref(false)
const lottieContainer = ref(null)
let lottieInstance = null

// Format date/time for display
const formatDateTime = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Format date as "DD MMM YYYY"
const formatDateRequest = () => {
  const now = new Date()
  const day = String(now.getDate()).padStart(2, '0')
  const month = now.toLocaleDateString('en-US', { month: 'short' })
  const year = now.getFullYear()
  return `${day} ${month} ${year}`
}

// Computed: Check if all commissions are selected
const allSelected = computed({
  get: () => {
    return props.commissions.length > 0 && 
           selectedCommissions.value.length === props.commissions.length
  },
  set: (value) => {
    if (value) {
      selectedCommissions.value = props.commissions.map(c => c.id)
    } else {
      selectedCommissions.value = []
    }
  }
})

// Computed: Selected commissions count
const selectedCount = computed(() => selectedCommissions.value.length)

// Computed: Total commission amount from selected commissions
const totalCommission = computed(() => {
  return props.commissions
    .filter(c => selectedCommissions.value.includes(c.id))
    .reduce((sum, c) => sum + parseFloat(c.commission_amount || 0), 0)
})

// Update filters when date range changes
const updateDateRange = (dates) => {
  dateRange.value = dates
  applyFilters()
}

// Apply filters and update URL
const applyFilters = () => {
  const params = {}
  
  if (dateRange.value && dateRange.value.length === 2) {
    params.start_date = dateRange.value[0].toISOString().split('T')[0]
    params.end_date = dateRange.value[1].toISOString().split('T')[0]
  }
  
  router.get('/agent/request-payout', params, {
    preserveState: true,
    preserveScroll: true,
    only: ['commissions', 'filters']
  })
}

// Toggle commission selection
const toggleCommission = (commissionId) => {
  const index = selectedCommissions.value.indexOf(commissionId)
  if (index > -1) {
    selectedCommissions.value.splice(index, 1)
  } else {
    selectedCommissions.value.push(commissionId)
  }
}

// Request payout
const requestPayout = async () => {
  if (selectedCommissions.value.length === 0) {
    alert('Please select at least one commission to include in the payout request.')
    return
  }

  isSubmitting.value = true

  try {
    const response = await axios.post('/agent/request_payout', {
      commissions: selectedCommissions.value
    })

    if (response.data.status === 'OK') {
      successMessage.value = response.data.message
      showSuccessDialog.value = true
      
      // Reload the page to refresh commissions list
      setTimeout(() => {
        router.reload()
      }, 2000)
    } else {
      successMessage.value = response.data.message || 'Failed, please contact support.'
      showSuccessDialog.value = true
    }
  } catch (error) {
    successMessage.value = error.response?.data?.message || 'Failed, please contact support.'
    showSuccessDialog.value = true
  } finally {
    isSubmitting.value = false
  }
}

// Close success dialog
const closeSuccessDialog = () => {
  showSuccessDialog.value = false
  successMessage.value = ''
}

// Initialize lottie animation when dialog opens
watch(showSuccessDialog, (show) => {
  if (show && lottieContainer.value) {
    // Load a simple success animation from lottiefiles CDN
    // Using a public success checkmark animation
    if (lottieInstance) {
      lottieInstance.destroy()
    }

    // Try to load from CDN, fallback to simple animation
    try {
      // Simple inline success animation
      const animationData = {
        "v": "5.7.4",
        "fr": 30,
        "ip": 0,
        "op": 60,
        "w": 200,
        "h": 200,
        "nm": "Success",
        "ddd": 0,
        "assets": [],
        "layers": [{
          "ddd": 0,
          "ind": 1,
          "ty": 4,
          "nm": "Success Circle",
          "sr": 1,
          "ks": {
            "o": {"a": 0, "k": 100},
            "r": {"a": 0, "k": 0},
            "p": {"a": 0, "k": [100, 100, 0]},
            "a": {"a": 0, "k": [0, 0, 0]},
            "s": {"a": 1, "k": [
              {"i": {"x": [0.667, 0.667, 0.667], "y": [1, 1, 1]}, "o": {"x": [0.333, 0.333, 0.333], "y": [0, 0, 0]}, "t": 0, "s": [0, 0, 100]},
              {"t": 20, "s": [100, 100, 100]}
            ]}
          },
          "ao": 0,
          "shapes": [{
            "ty": "gr",
            "it": [{
              "d": 1,
              "ty": "el",
              "s": {"a": 0, "k": [80, 80]},
              "p": {"a": 0, "k": [0, 0]},
              "nm": "Circle"
            }, {
              "ty": "fl",
              "c": {"a": 0, "k": [0.2, 0.8, 0.4, 1]},
              "o": {"a": 0, "k": 100},
              "r": 1,
              "bm": 0,
              "nm": "Fill"
            }, {
              "ty": "tr",
              "p": {"a": 0, "k": [0, 0]},
              "a": {"a": 0, "k": [0, 0]},
              "s": {"a": 0, "k": [100, 100]},
              "r": {"a": 0, "k": 0},
              "o": {"a": 0, "k": 100},
              "sk": {"a": 0, "k": 0},
              "sa": {"a": 0, "k": 0},
              "nm": "Transform"
            }],
            "nm": "Circle",
            "bm": 0
          }, {
            "ty": "gr",
            "it": [{
              "ind": 0,
              "ty": "sh",
              "ks": {
                "a": 0,
                "k": {
                  "i": [[0, 0], [0, 0], [0, 0]],
                  "o": [[0, 0], [0, 0], [0, 0]],
                  "v": [[-20, 0], [0, 20], [20, 0]],
                  "c": false
                }
              },
              "nm": "Checkmark"
            }, {
              "ty": "st",
              "c": {"a": 0, "k": [1, 1, 1, 1]},
              "o": {"a": 0, "k": 100},
              "w": {"a": 0, "k": 5},
              "lc": 2,
              "lj": 2,
              "nm": "Stroke"
            }, {
              "ty": "tr",
              "p": {"a": 0, "k": [0, 0]},
              "a": {"a": 0, "k": [0, 0]},
              "s": {"a": 0, "k": [100, 100]},
              "r": {"a": 0, "k": 0},
              "o": {"a": 0, "k": 100},
              "sk": {"a": 0, "k": 0},
              "sa": {"a": 0, "k": 0},
              "nm": "Transform"
            }],
            "nm": "Checkmark",
            "bm": 0
          }],
          "ip": 0,
          "op": 60,
          "st": 0,
          "bm": 0
        }]
      }

      lottieInstance = lottie.loadAnimation({
        container: lottieContainer.value,
        renderer: 'svg',
        loop: false,
        autoplay: true,
        animationData: animationData
      })
    } catch (error) {
      console.error('Failed to load lottie animation:', error)
    }
  } else if (!show && lottieInstance) {
    lottieInstance.destroy()
    lottieInstance = null
  }
})

// Update selected commissions when commissions list changes
watch(() => props.commissions, (newCommissions) => {
  // Only update if no commissions are selected (to preserve user selection)
  if (selectedCommissions.value.length === 0 && newCommissions.length > 0) {
    selectedCommissions.value = newCommissions.map(c => c.id)
  }
}, { immediate: true })
</script>

<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Request Payout</span>
    </nav>

    <!-- Title -->
    <h1 class="text-2xl font-bold text-forest-dark mb-2">Request Payout</h1>
    <p class="text-stone-600 mb-6">Select sales to include into this payout request</p>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
        <!-- Date Range Picker -->
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-2">
            Date Range
          </label>
          <VueDatePicker
            v-model="dateRange"
            range
            :enable-time-picker="false"
            placeholder="Select date range"
            @update:model-value="updateDateRange"
            class="w-full"
          />
        </div>
      </div>
    </div>

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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <!-- Sales Count -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-green/10 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-accent-green" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Sales Count</p>
              <p class="text-2xl font-bold text-forest-dark">{{ selectedCount }}</p>
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

          <!-- Total Commission -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gold/10 rounded-lg flex items-center justify-center">
              <DollarSign class="w-6 h-6 text-gold" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Total Commission</p>
              <p class="text-2xl font-bold text-forest-dark">{{ formatCurrency('RM', totalCommission) }}</p>
            </div>
          </div>
        </div>

        <!-- Request Payout Button -->
        <button
          @click="requestPayout"
          :disabled="selectedCount === 0 || isSubmitting"
          class="w-full md:w-auto px-6 py-3 bg-accent-green text-white rounded-md font-medium hover:bg-accent-green/90 focus:outline-none focus:ring-2 focus:ring-accent-green focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="isSubmitting">Processing...</span>
          <span v-else>Request Payout</span>
        </button>
      </div>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Commissions List
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="allSelected = $event.target.checked"
                  class="rounded border-stone-300 text-accent-green focus:ring-accent-green"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Date/Time
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Description
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Invoice Number
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Commission
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="commissions.length === 0" class="hover:bg-stone-50">
              <td colspan="6" class="px-6 py-4 text-center text-stone-500">
                No pending commissions found matching the selected filters.
              </td>
            </tr>
            <tr
              v-for="commission in commissions"
              :key="commission.id"
              class="hover:bg-stone-50"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :checked="selectedCommissions.includes(commission.id)"
                  @change="toggleCommission(commission.id)"
                  class="rounded border-stone-300 text-accent-green focus:ring-accent-green"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ formatDateTime(commission.sale_date) }}
              </td>
              <td class="px-6 py-4 text-sm text-stone-900">
                {{ commission.description || '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ commission.invoice_number || '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', commission.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', commission.commission_amount) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Success Dialog -->
    <Modal
      :show="showSuccessDialog"
      max-width="md"
      @close="closeSuccessDialog"
    >
      <div class="px-6 py-8 text-center">
        <!-- Lottie Animation Container -->
        <div ref="lottieContainer" class="w-32 h-32 mx-auto mb-4"></div>

        <!-- Success Message -->
        <p class="text-lg text-stone-900 mb-6">{{ successMessage }}</p>

        <!-- OK Button -->
        <button
          @click="closeSuccessDialog"
          class="px-6 py-2 bg-accent-green text-white rounded-md font-medium hover:bg-accent-green/90 focus:outline-none focus:ring-2 focus:ring-accent-green focus:ring-offset-2 transition-colors"
        >
          OK
        </button>
      </div>
    </Modal>
  </div>
</template>

