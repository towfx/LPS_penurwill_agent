<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import { formatCurrency } from '../../lib/utils.js'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  sales: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({
      start_date: null,
      end_date: null,
      status: 'pending'
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

// Status filter state
const selectedStatus = ref(props.filters.status || 'pending')

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

// Get commission status pill class
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

// Update filters when date range changes
const updateDateRange = (dates) => {
  dateRange.value = dates
  applyFilters()
}

// Update filters when status changes
const updateStatus = () => {
  applyFilters()
}

// Apply filters and update URL
const applyFilters = () => {
  const params = {}
  
  if (dateRange.value && dateRange.value.length === 2) {
    params.start_date = dateRange.value[0].toISOString().split('T')[0]
    params.end_date = dateRange.value[1].toISOString().split('T')[0]
  }
  
  if (selectedStatus.value && selectedStatus.value !== 'all') {
    params.status = selectedStatus.value
  } else if (selectedStatus.value === 'all') {
    // Don't include status param for 'all'
  } else {
    params.status = 'pending' // Default
  }
  
  router.get('/agent/sales', params, {
    preserveState: true,
    preserveScroll: true,
    only: ['sales', 'filters']
  })
}
</script>

<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Sales</span>
    </nav>

    <!-- Title -->
    <h1 class="text-2xl font-bold text-forest-dark mb-6">My Sales</h1>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            :class="'px-3 py-2 border border-stone-300 rounded-md focus:outline-none focus:ring-2 focus:ring-forest-light focus:border-forest-light'"
          />
        </div>

        <!-- Commission Status Filter -->
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-2">
            Commission Status
          </label>
          <select
            v-model="selectedStatus"
            @change="updateStatus"
            class="w-full px-3 py-2 border border-stone-300 rounded-md focus:outline-none focus:ring-2 focus:ring-forest-light focus:border-forest-light"
          >
            <option value="all">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Sales List
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
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
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Commission Status
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="sales.length === 0" class="hover:bg-stone-50">
              <td colspan="6" class="px-6 py-4 text-center text-stone-500">
                No sales found matching the selected filters.
              </td>
            </tr>
            <tr
              v-for="sale in sales"
              :key="sale.id"
              class="hover:bg-stone-50"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ formatDateTime(sale.sale_date) }}
              </td>
              <td class="px-6 py-4 text-sm text-stone-900">
                {{ sale.description || '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ sale.invoice_number || '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', sale.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', sale.commission?.amount ?? 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span
                  v-if="sale.commission"
                  :class="getStatusClass(sale.commission.status)"
                >
                  {{ sale.commission.status.charAt(0).toUpperCase() + sale.commission.status.slice(1) }}
                </span>
                <span v-else class="text-stone-400">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
