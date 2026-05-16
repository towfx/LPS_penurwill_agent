<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Select from '../Design/Components/Select.vue'
import Badge from '../Design/Components/Badge.vue'
import Card from '../Design/Components/Card.vue'
import CardContent from '../Design/Components/CardContent.vue'
import { formatCurrency } from '../../lib/utils.js'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ShoppingCart, DollarSign, Wallet } from 'lucide-vue-next'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  commissions: { type: Array, default: () => [] },
  totals: {
    type: Object,
    default: () => ({ sales: 0, commission: 0, overrides: 0 })
  },
  filters: {
    type: Object,
    default: () => ({ start_date: null, end_date: null, status: 'all', agent_id: null })
  },
  agents: { type: Array, default: () => [] }
})

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))
const roleLabel = (role) => roleNames.value[role] || role || '—'

const dateRange = ref(
  props.filters.start_date && props.filters.end_date
    ? [new Date(props.filters.start_date), new Date(props.filters.end_date)]
    : null
)

const selectedStatus = ref(props.filters.status || 'all')
const selectedAgent = ref(props.filters.agent_id ? String(props.filters.agent_id) : '')

const formatDateTime = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const getStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'pending': return 'warning'
    case 'approved': return 'success'
    case 'paid': return 'default'
    default: return 'secondary'
  }
}

const commRateDisplay = (c) => {
  if (c.commission_calc_type === 'fixed') {
    const fixed = c.commission_fixed_amount ?? c.commission_rate
    return formatCurrency('RM', fixed)
  }
  const rate = c.commission_rate ?? 0
  return `${Number(rate)}%`
}

const commissionTypeLabel = (type) => {
  if (type === 'own_sales') return 'Own Sale'
  if (type === 'override') return 'Override'
  return type || '—'
}

const applyFilters = () => {
  const params = {}
  if (dateRange.value && dateRange.value.length === 2) {
    params.start_date = dateRange.value[0].toISOString().split('T')[0]
    params.end_date = dateRange.value[1].toISOString().split('T')[0]
  }
  if (selectedStatus.value && selectedStatus.value !== 'all') {
    params.status = selectedStatus.value
  }
  if (selectedAgent.value) {
    params.agent_id = selectedAgent.value
  }
  router.get('/admin/sales', params, {
    preserveState: true,
    preserveScroll: true,
    only: ['commissions', 'totals', 'filters']
  })
}

const updateDateRange = (dates) => {
  dateRange.value = dates
  applyFilters()
}

const colspan = computed(() => 8)
</script>

<template>
  <div>
    <PageHeader
      title="Sales"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Sales' }]"
    />

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <Card class="hover:shadow-md transition-shadow">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-stone-600">Total Sales</p>
              <p class="text-2xl font-bold text-forest-dark mt-1">
                {{ formatCurrency('RM', totals.sales) }}
              </p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-cream">
              <ShoppingCart :size="24" class="text-gold" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="hover:shadow-md transition-shadow">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-stone-600">Total Commission</p>
              <p class="text-2xl font-bold text-forest-dark mt-1">
                {{ formatCurrency('RM', totals.commission) }}
              </p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-cream">
              <DollarSign :size="24" class="text-gold" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="hover:shadow-md transition-shadow">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-stone-600">Total Overrides</p>
              <p class="text-2xl font-bold text-forest-dark mt-1">
                {{ formatCurrency('RM', totals.overrides) }}
              </p>
            </div>
            <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-cream">
              <Wallet :size="24" class="text-gold" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-2">Agent</label>
          <Select
            v-model="selectedAgent"
            :options="agents"
            @update:modelValue="applyFilters"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-2">Date Range</label>
          <VueDatePicker
            v-model="dateRange"
            range
            :enable-time-picker="false"
            placeholder="Select date range"
            @update:model-value="updateDateRange"
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-2">Commission Status</label>
          <Select
            v-model="selectedStatus"
            :options="[
              { value: 'all', label: 'All' },
              { value: 'pending', label: 'Pending' },
              { value: 'approved', label: 'Approved' },
              { value: 'paid', label: 'Paid' },
            ]"
            @update:modelValue="applyFilters"
          />
        </div>
      </div>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">Sales &amp; Commissions</h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Invoice / Date Time</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Description</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Sale Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Source Agent</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Earner</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Comm Rate</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Comm Amount</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="commissions.length === 0">
              <td :colspan="colspan" class="px-6 py-4 text-center text-stone-500">
                No commissions found matching the selected filters.
              </td>
            </tr>
            <tr v-for="c in commissions" :key="c.id" class="hover:bg-stone-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="font-medium text-stone-900">{{ c.invoice_number || '—' }}</div>
                <div class="text-xs text-stone-500">{{ formatDateTime(c.sale_date) }}</div>
              </td>
              <td class="px-6 py-4 text-sm text-stone-900">{{ c.description || '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', c.sale_amount ?? 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                <span v-if="c.source_agent">
                  {{ c.source_agent.name || '—' }}
                  <span class="text-xs text-stone-500">({{ roleLabel(c.source_agent.agent_role) }})</span>
                </span>
                <span v-else class="text-stone-400">—</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                <span v-if="c.earning_agent">
                  {{ c.earning_agent.name || '—' }}
                  <span class="text-xs text-stone-500">({{ roleLabel(c.earning_agent.agent_role) }})</span>
                </span>
                <span v-else class="text-stone-400">—</span>
                <div class="mt-1">
                  <Badge :variant="c.commission_type === 'override' ? 'secondary' : 'outline'">
                    {{ commissionTypeLabel(c.commission_type) }}
                  </Badge>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ commRateDisplay(c) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', c.commission_amount ?? 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <Badge :variant="getStatusVariant(c.status)">
                  {{ c.status ? c.status.charAt(0).toUpperCase() + c.status.slice(1) : '—' }}
                </Badge>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
