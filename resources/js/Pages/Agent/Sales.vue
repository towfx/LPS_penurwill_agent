<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Select from '../Design/Components/Select.vue'
import Badge from '../Design/Components/Badge.vue'
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

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))
const roleLabel = (role) => roleNames.value[role] || role || '—'

// Show source agent column only for leader / business partner viewing subordinate sales
const showsSourceAgent = computed(() => {
  const role = props.agent?.agent_role
  return role === 'agent_leader' || role === 'business_partner'
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

// Get commission status badge variant
const getStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'pending': return 'warning'
    case 'approved': return 'success'
    case 'paid': return 'default'
    default: return 'secondary'
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
    <PageHeader
      title="My Sales"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Sales' }]"
    />

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
          />
        </div>

        <!-- Commission Status Filter -->
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-2">
            Commission Status
          </label>
          <Select
            v-model="selectedStatus"
            :options="[
              { value: 'all', label: 'All' },
              { value: 'pending', label: 'Pending' },
              { value: 'approved', label: 'Approved' },
              { value: 'paid', label: 'Paid' },
            ]"
            @update:modelValue="updateStatus"
          />
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
              <th
                v-if="showsSourceAgent"
                class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider"
              >
                Source Agent
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
              <td :colspan="showsSourceAgent ? 7 : 6" class="px-6 py-4 text-center text-stone-500">
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
              <td v-if="showsSourceAgent" class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                <span v-if="sale.source_agent">
                  {{ sale.source_agent.name || sale.source_agent.individual_name || sale.source_agent.company_name }}
                  <span class="text-xs text-stone-500">({{ roleLabel(sale.source_agent.agent_role) }})</span>
                </span>
                <span v-else class="text-stone-400">—</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', sale.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', sale.commission?.amount ?? 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <Badge v-if="sale.commission" :variant="getStatusVariant(sale.commission.status)">
                  {{ sale.commission.status.charAt(0).toUpperCase() + sale.commission.status.slice(1) }}
                </Badge>
                <span v-else class="text-stone-400">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
