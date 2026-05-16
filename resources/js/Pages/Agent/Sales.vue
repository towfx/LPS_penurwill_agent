<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Select from '../Design/Components/Select.vue'
import Badge from '../Design/Components/Badge.vue'
import Card from '../Design/Components/Card.vue'
import CardContent from '../Design/Components/CardContent.vue'
import { formatCurrency } from '../../lib/utils.js'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ShoppingCart, DollarSign, Wallet } from 'lucide-vue-next'
import Pagination from '../Design/Components/Pagination.vue'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  sales: {
    type: Object,
    default: () => ({ data: [] })
  },
  totals: {
    type: Object,
    default: () => ({ sales: 0, commission: 0, overrides: 0 })
  },
  filters: {
    type: Object,
    default: () => ({
      start_date: null,
      end_date: null,
      status: 'pending',
      agent_level: 'all'
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

const showsOverrides = computed(() => {
  const role = props.agent?.agent_role
  return role === 'agent_leader' || role === 'business_partner'
})

const agentLevelOptions = computed(() => {
  const role = props.agent?.agent_role
  if (role === 'business_partner') {
    return [
      { value: 'all', label: 'All Agents' },
      { value: 'own', label: 'Own Sales' },
      { value: 'leader', label: roleNames.value.agent_leader },
      { value: 'agent', label: roleNames.value.agent },
    ]
  }
  if (role === 'agent_leader') {
    return [
      { value: 'all', label: 'All' },
      { value: 'own', label: 'Own Sales' },
      { value: 'agent_under', label: 'Agent Under' },
    ]
  }
  return []
})

const dateRange = ref(
  props.filters.start_date && props.filters.end_date
    ? [new Date(props.filters.start_date), new Date(props.filters.end_date)]
    : null
)

const selectedStatus = ref(props.filters.status || 'pending')
const selectedAgentLevel = ref(props.filters.agent_level || 'all')

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

const updateDateRange = (dates) => {
  dateRange.value = dates
  applyFilters()
}

const updateStatus = () => {
  applyFilters()
}

const applyFilters = (extraParams = {}) => {
  const params = { ...extraParams }

  if (dateRange.value && dateRange.value.length === 2) {
    params.start_date = dateRange.value[0].toISOString().split('T')[0]
    params.end_date = dateRange.value[1].toISOString().split('T')[0]
  }

  if (selectedStatus.value && selectedStatus.value !== 'all') {
    params.status = selectedStatus.value
  } else if (selectedStatus.value === 'all') {
    // omit status param
  } else {
    params.status = 'pending'
  }

  if (selectedAgentLevel.value && selectedAgentLevel.value !== 'all') {
    params.agent_level = selectedAgentLevel.value
  }

  router.get('/agent/sales', params, {
    preserveState: true,
    preserveScroll: true,
    only: ['sales', 'totals', 'filters']
  })
}

const handlePageChange = (page) => {
  applyFilters({ page })
}

const colspan = computed(() => 6)
</script>

<template>
  <div>
    <PageHeader
      title="My Sales"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Sales' }]"
    />

    <!-- Summary Cards -->
    <div
      class="grid grid-cols-1 gap-4 mb-6"
      :class="showsOverrides ? 'md:grid-cols-3' : 'md:grid-cols-2'"
    >
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

      <Card v-if="showsOverrides" class="hover:shadow-md transition-shadow">
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
      <div class="grid grid-cols-1 gap-4" :class="showsOverrides ? 'md:grid-cols-3' : 'md:grid-cols-2'">
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

        <div v-if="showsOverrides">
          <label class="block text-sm font-medium text-stone-700 mb-2">
            Agent Level
          </label>
          <Select
            v-model="selectedAgentLevel"
            :options="agentLevelOptions"
            @update:modelValue="updateStatus"
          />
        </div>

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

    <!-- Commissions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Sales &amp; Commissions
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Sale Details</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Source</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Earner</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Comm Rate</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Comm Amount</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="sales.data.length === 0">
              <td :colspan="colspan" class="px-6 py-4 text-center text-stone-500">
                No sales found matching the selected filters.
              </td>
            </tr>
            <template v-for="sale in sales.data" :key="sale.id">
              <tr class="hover:bg-stone-50 border-t border-stone-200">
                <td :rowspan="sale.commissions.length || 1" class="px-6 py-4 text-sm align-top">
                  <div class="font-bold text-stone-900">{{ sale.invoice_number || '—' }}</div>
                  <div class="text-xs text-stone-500 mb-1">{{ formatDateTime(sale.sale_date) }}</div>
                  <div class="text-stone-600 italic mb-1">{{ sale.description || '—' }}</div>
                  <div class="text-forest-dark font-bold">
                    {{ formatCurrency('RM', sale.sale_amount ?? 0) }}
                  </div>
                </td>
                <td :rowspan="sale.commissions.length || 1" class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 align-top">
                  <span v-if="sale.source_agent">
                    {{ sale.source_agent.name || '—' }}
                    <div class="text-xs text-stone-500">{{ roleLabel(sale.source_agent.agent_role) }}</div>
                  </span>
                  <span v-else class="text-stone-400">—</span>
                </td>
                
                <!-- First commission -->
                <template v-if="sale.commissions.length > 0">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                    <span v-if="sale.commissions[0].earning_agent">
                      <div class="font-medium">
                        {{ sale.commissions[0].earning_agent.id === agent.id ? 'Me' : sale.commissions[0].earning_agent.name }}
                      </div>
                      <div class="text-xs text-stone-500">{{ roleLabel(sale.commissions[0].earning_agent.agent_role) }}</div>
                    </span>
                    <div class="mt-1">
                      <Badge :variant="sale.commissions[0].commission_type === 'override' ? 'secondary' : 'outline'" class="text-[10px] px-1.5 py-0">
                        {{ commissionTypeLabel(sale.commissions[0].commission_type) }}
                      </Badge>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                    {{ commRateDisplay(sale.commissions[0]) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right font-medium">
                    {{ formatCurrency('RM', sale.commissions[0].commission_amount ?? 0) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <Badge :variant="getStatusVariant(sale.commissions[0].status)">
                      {{ sale.commissions[0].status ? sale.commissions[0].status.charAt(0).toUpperCase() + sale.commissions[0].status.slice(1) : '—' }}
                    </Badge>
                  </td>
                </template>
                <template v-else>
                  <td colspan="4" class="px-6 py-4 text-center text-stone-400 text-xs">No commissions</td>
                </template>
              </tr>
              <!-- Remaining commissions (for leaders/BPs) -->
              <tr v-for="c in sale.commissions.slice(1)" :key="c.id" class="hover:bg-stone-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 border-l border-stone-100">
                  <span v-if="c.earning_agent">
                    <div class="font-medium">
                      {{ c.earning_agent.id === agent.id ? 'Me' : c.earning_agent.name }}
                    </div>
                    <div class="text-xs text-stone-500">{{ roleLabel(c.earning_agent.agent_role) }}</div>
                  </span>
                  <div class="mt-1">
                    <Badge :variant="c.commission_type === 'override' ? 'secondary' : 'outline'" class="text-[10px] px-1.5 py-0">
                      {{ commissionTypeLabel(c.commission_type) }}
                    </Badge>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                  {{ commRateDisplay(c) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right font-medium">
                  {{ formatCurrency('RM', c.commission_amount ?? 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <Badge :variant="getStatusVariant(c.status)">
                    {{ c.status ? c.status.charAt(0).toUpperCase() + c.status.slice(1) : '—' }}
                  </Badge>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div v-if="sales.total > sales.per_page" class="px-6 py-4 border-t border-stone-200">
        <Pagination
          :current-page="sales.current_page"
          :per-page="sales.per_page"
          :total="sales.total"
          @change="handlePageChange"
        />
      </div>
    </div>
  </div>
</template>
