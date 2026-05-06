<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import Tabs from '../Design/Components/Tabs.vue'
import TabsList from '../Design/Components/TabsList.vue'
import TabsTrigger from '../Design/Components/TabsTrigger.vue'
import TabsContent from '../Design/Components/TabsContent.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  commissions: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
  selectedYear: { type: Number, default: new Date().getFullYear() },
  agent: { type: Object, required: true },
  report: { type: Object, default: () => ({ by_type: [], by_source: [], by_period: [], transactions: [] }) },
})

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))
const roleLabel = (role) => roleNames.value[role] || role || '—'

const formatDate = (s) => {
  if (!s) return '—'
  return new Date(s).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const activeTab = ref('period')
const selectedYear = ref(props.selectedYear)

const updateYear = () => {
  window.location.href = `/agent/commissions?year=${selectedYear.value}`
}

const getPayoutUrl = (payout) => {
  return `/agent/payout/${payout.id}/detail`
}

const getPayoutStatusClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'paid':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    default:
      return 'bg-stone-100 text-stone-800'
  }
}
</script>

<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Commissions</span>
    </nav>

    <!-- Title -->
    <h1 class="text-2xl font-bold text-forest-dark mb-6">My Commissions</h1>

    <!-- Year Filter -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="flex items-center space-x-4">
        <label class="text-sm font-medium text-stone-700">Year:</label>
        <select
          v-model="selectedYear"
          @change="updateYear"
          class="px-3 py-2 border border-stone-300 rounded-md focus:outline-none focus:ring-2 focus:ring-forest-light focus:border-forest-light"
        >
          <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>
    </div>

    <!-- Commissions Tabbed View (Decision 10) -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark mb-4">Commissions for {{ selectedYear }}</h2>

        <Tabs v-model="activeTab" default-value="period" class="w-full">
          <TabsList>
            <TabsTrigger value="type">By Commission Type</TabsTrigger>
            <TabsTrigger value="source">By Sales Source</TabsTrigger>
            <TabsTrigger value="period">By Time Period</TabsTrigger>
            <TabsTrigger value="detail">Detailed Transactions</TabsTrigger>
          </TabsList>

          <TabsContent value="type" class="pt-4">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-stone-50">
                  <tr>
                    <th class="px-4 py-2 text-left">Type</th>
                    <th class="px-4 py-2 text-left">Category</th>
                    <th class="px-4 py-2 text-left">Calc</th>
                    <th class="px-4 py-2 text-right">Count</th>
                    <th class="px-4 py-2 text-right">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-stone-200">
                  <tr v-for="row in (report?.by_type || [])" :key="`${row.commission_type}-${row.commission_category}-${row.commission_calc_type}`">
                    <td class="px-4 py-2">{{ row.commission_type }}</td>
                    <td class="px-4 py-2">{{ roleLabel(row.commission_category) }}</td>
                    <td class="px-4 py-2">{{ row.commission_calc_type || '—' }}</td>
                    <td class="px-4 py-2 text-right">{{ row.count }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ formatCurrency('RM', row.total) }}</td>
                  </tr>
                  <tr v-if="!(report?.by_type || []).length">
                    <td colspan="5" class="px-4 py-6 text-center text-stone-500">No data.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </TabsContent>

          <TabsContent value="source" class="pt-4">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-stone-50">
                  <tr>
                    <th class="px-4 py-2 text-left">Source Agent</th>
                    <th class="px-4 py-2 text-left">Role</th>
                    <th class="px-4 py-2 text-right">Sales</th>
                    <th class="px-4 py-2 text-right">Commission</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-stone-200">
                  <tr v-for="row in (report?.by_source || [])" :key="row.source_agent_id">
                    <td class="px-4 py-2">{{ row.source_agent_name || `#${row.source_agent_id}` }}</td>
                    <td class="px-4 py-2">{{ roleLabel(row.source_agent_role) }}</td>
                    <td class="px-4 py-2 text-right">{{ row.sales_count }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ formatCurrency('RM', row.total) }}</td>
                  </tr>
                  <tr v-if="!(report?.by_source || []).length">
                    <td colspan="4" class="px-4 py-6 text-center text-stone-500">No data.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </TabsContent>

          <TabsContent value="period" class="pt-4">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-stone-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Month</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Total Sales</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Total Commission</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Payout</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="commissions.length === 0" class="hover:bg-stone-50">
              <td colspan="5" class="px-6 py-4 text-center text-stone-500">
                No commissions found for the selected year.
              </td>
            </tr>
            <tr
              v-for="commission in commissions"
              :key="commission.month"
              class="hover:bg-stone-50"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-forest-dark">
                {{ commission.month_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ commission.total_sales }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                <span class="font-medium text-forest-dark">
                  {{ formatCurrency('RM', commission.total_commission) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <div v-if="commission.payout">
                  <span class="text-sm font-medium text-forest-dark mr-2">
                    {{ formatCurrency('RM', commission.payout.amount) }}
                  </span>
                  <Link
                    :href="getPayoutUrl(commission.payout)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                    :class="getPayoutStatusClass(commission.payout.status)"
                  >
                    <svg v-if="commission.payout.paid_at" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    {{ commission.payout.paid_at ? 'Paid' : 'Unpaid' }}
                  </Link>
                </div>
                <div v-else>
                  <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                    Pending
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                <Link
                  :href="`/agent/commissions/detail?year=${selectedYear}&month=${commission.month}`"
                  class="text-forest-light hover:text-forest-dark transition-colors"
                >
                  View Details
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
          </TabsContent>

          <TabsContent value="detail" class="pt-4">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-stone-50">
                  <tr>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Sale</th>
                    <th class="px-4 py-2 text-left">Type</th>
                    <th class="px-4 py-2 text-left">Calc</th>
                    <th class="px-4 py-2 text-right">Sale Amount</th>
                    <th class="px-4 py-2 text-center">Rate / Fixed</th>
                    <th class="px-4 py-2 text-right">Commission</th>
                    <th class="px-4 py-2 text-left">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-stone-200">
                  <tr
                    v-for="t in (report?.transactions || [])"
                    :key="t.id"
                    :class="{ 'bg-red-50': t.is_reversal }"
                  >
                    <td class="px-4 py-2">{{ formatDate(t.created_at) }}</td>
                    <td class="px-4 py-2">{{ t.sale?.invoice_number || `#${t.sale?.id || '—'}` }}</td>
                    <td class="px-4 py-2">
                      {{ t.commission_type || 'own_sales' }}
                      <span v-if="t.is_reversal" class="ml-1 text-xs text-accent-red">↩</span>
                    </td>
                    <td class="px-4 py-2">{{ t.commission_calc_type || 'percentage' }}</td>
                    <td class="px-4 py-2 text-right">{{ formatCurrency('RM', t.sale?.amount || t.source_sale_amount || 0) }}</td>
                    <td class="px-4 py-2 text-center">
                      <span v-if="t.commission_calc_type === 'fixed'">
                        {{ formatCurrency('RM', t.commission_fixed_amount || 0) }}
                      </span>
                      <span v-else>{{ t.commission_rate }}%</span>
                    </td>
                    <td class="px-4 py-2 text-right font-medium">{{ formatCurrency('RM', t.amount) }}</td>
                    <td class="px-4 py-2">{{ t.status }}</td>
                  </tr>
                  <tr v-if="!(report?.transactions || []).length">
                    <td colspan="8" class="px-4 py-6 text-center text-stone-500">No transactions.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  </div>
</template>
