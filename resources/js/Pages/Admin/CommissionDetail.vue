<template>
  <div>
    <PageHeader
      title="Commission Details"
      :description="`${getAgentName(agent)} - ${monthName} ${year}`"
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Commissions', href: '/admin/commissions/list' }, { label: 'Detail' }]"
    >
      <template #actions>
        <Button variant="ghost" @click="() => router.visit('/admin/commissions/list')">← Back to List</Button>
      </template>
    </PageHeader>

    <!-- Summary Card -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center relative">
          <!-- Year Navigation -->
          <div class="flex items-center justify-center space-x-2">
            <Link
              :href="`/admin/commission/detail?year=${year - 1}&month=${month}&agent_id=${agent.id}`"
              class="nav-arrow"
              title="Previous Year"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </Link>
            <div>
              <div class="text-2xl font-bold text-forest-dark">{{ year }}</div>
              <div class="text-sm text-stone-500">Year</div>
            </div>
            <Link
              :href="`/admin/commission/detail?year=${year + 1}&month=${month}&agent_id=${agent.id}`"
              class="nav-arrow"
              title="Next Year"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </Link>
          </div>
        </div>
        <div class="text-center relative">
          <!-- Month Navigation -->
          <div class="flex items-center justify-center space-x-2">
            <Link
              :href="getPreviousMonthLink()"
              class="nav-arrow"
              title="Previous Month"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </Link>
            <div>
              <div class="text-2xl font-bold text-forest-dark">{{ monthName }}</div>
              <div class="text-sm text-stone-500">Month</div>
            </div>
            <Link
              :href="getNextMonthLink()"
              class="nav-arrow"
              title="Next Month"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </Link>
          </div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-forest-dark">{{ summary?.total_sales || 0 }}</div>
          <div class="text-sm text-stone-500">Total Sales</div>
        </div>
      </div>

      <div class="mt-6 pt-6 border-t border-stone-200">
        <div class="text-center">
          <div class="text-3xl font-bold text-forest-dark">
            {{ formatCurrency('RM', summary?.total_commission || 0) }}
          </div>
          <div class="text-sm text-stone-500">Total Commission</div>
        </div>
      </div>

      <!-- Payout Status -->
      <div class="mt-6 pt-6 border-t border-stone-200">
        <div class="text-center">
          <div v-if="payout" class="inline-flex items-center space-x-2">
            <span class="text-lg font-medium text-forest-dark">Payout Status:</span>
            <Link
              :href="`/admin/payout/${payout.id}/update`"
              class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full transition-colors"
              :class="getPayoutStatusClass(payout.paid_at ? 'paid' : 'unpaid')"
            >
              <svg v-if="payout.paid_at" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              {{ payout.paid_at ? 'Paid' : 'Unpaid' }}
            </Link>
            <span v-if="payout.paid_at" class="text-sm text-stone-500">
              ({{ formatDate(payout.paid_at) }})
            </span>
          </div>
          <div v-else class="inline-flex items-center space-x-2">
            <span class="text-lg font-medium text-forest-dark">Payout Status:</span>
            <Link
              :href="`/admin/payout/create?agent_id=${agent.id}&year=${year}&month=${month}`"
              class="inline-flex items-center px-3 py-1 text-sm font-medium bg-stone-100 text-stone-700 hover:bg-stone-200 rounded-full transition-colors"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
              Create Payout
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Agent Info Card -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-forest-dark mb-4">Agent Information</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <div class="flex items-center mb-4">
            <div class="flex-shrink-0 h-12 w-12">
              <div class="h-12 w-12 rounded-full bg-forest-light flex items-center justify-center">
                <span class="text-white font-medium text-lg">
                  {{ getAgentInitials(agent) }}
                </span>
              </div>
            </div>
            <div class="ml-4">
              <div class="text-lg font-medium text-forest-dark">
                {{ getAgentName(agent) }}
              </div>
              <div class="text-sm text-stone-500">
                {{ agent?.profile_type === 'individual' ? 'Individual Agent' : 'Company Agent' }}
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-2">
          <div v-if="agent?.profile_type === 'individual'">
            <div class="text-sm text-stone-500">Phone</div>
            <div class="text-sm font-medium text-forest-dark">{{ agent?.individual_phone || 'N/A' }}</div>
          </div>
          <div v-else>
            <div class="text-sm text-stone-500">Company Phone</div>
            <div class="text-sm font-medium text-forest-dark">{{ agent?.company_phone || 'N/A' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabbed Report (Decision 10) -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <Tabs v-model="activeTab" default-value="type" class="w-full">
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
                    <th class="px-4 py-2 text-left font-medium text-stone-600">Type</th>
                    <th class="px-4 py-2 text-left font-medium text-stone-600">Category</th>
                    <th class="px-4 py-2 text-left font-medium text-stone-600">Calc</th>
                    <th class="px-4 py-2 text-right font-medium text-stone-600">Count</th>
                    <th class="px-4 py-2 text-right font-medium text-stone-600">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-stone-200">
                  <tr v-for="row in (report?.by_type || [])" :key="`${row.commission_type}-${row.commission_category}-${row.commission_calc_type}`">
                    <td class="px-4 py-2">
                      <Badge :variant="row.commission_type === 'own_sales' ? 'success' : 'secondary'">
                        {{ row.commission_type }}
                      </Badge>
                    </td>
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
                    <th class="px-4 py-2 text-left font-medium text-stone-600">Source Agent</th>
                    <th class="px-4 py-2 text-left font-medium text-stone-600">Role</th>
                    <th class="px-4 py-2 text-right font-medium text-stone-600">Sales</th>
                    <th class="px-4 py-2 text-right font-medium text-stone-600">Commission</th>
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
              <table class="w-full text-sm">
                <thead class="bg-stone-50">
                  <tr>
                    <th class="px-4 py-2 text-left font-medium text-stone-600">Period</th>
                    <th class="px-4 py-2 text-right font-medium text-stone-600">Count</th>
                    <th class="px-4 py-2 text-right font-medium text-stone-600">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-stone-200">
                  <tr v-for="row in (report?.by_period || [])" :key="row.period">
                    <td class="px-4 py-2">{{ row.period }}</td>
                    <td class="px-4 py-2 text-right">{{ row.count }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ formatCurrency('RM', row.total) }}</td>
                  </tr>
                  <tr v-if="!(report?.by_period || []).length">
                    <td colspan="3" class="px-4 py-6 text-center text-stone-500">No data.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </TabsContent>

          <TabsContent value="detail" class="pt-4">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-stone-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Sale</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Category</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Calc</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-stone-500 uppercase">Sale Amount</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-stone-500 uppercase">Rate</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-stone-500 uppercase">Commission</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Status</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-stone-200">
                  <tr v-if="commissions.length === 0">
                    <td colspan="9" class="px-4 py-4 text-center text-stone-500">No commission records found for this period.</td>
                  </tr>
                  <tr
                    v-for="commission in commissions"
                    :key="commission.id"
                    class="hover:bg-stone-50"
                    :class="{ 'bg-red-50': commission.is_reversal }"
                  >
                    <td class="px-4 py-2 whitespace-nowrap text-sm">{{ formatDate(commission.created_at) }}</td>
                    <td class="px-4 py-2 text-sm">
                      <div class="max-w-xs truncate">{{ commission.sale?.description || 'N/A' }}</div>
                      <div class="text-xs text-stone-500">Invoice: {{ commission.sale?.invoice_number || 'N/A' }}</div>
                    </td>
                    <td class="px-4 py-2">
                      <Badge :variant="commission.commission_type === 'own_sales' ? 'success' : 'secondary'">
                        {{ commission.commission_type || 'own_sales' }}
                      </Badge>
                      <span v-if="commission.is_reversal" class="ml-1 text-xs font-medium text-accent-red">↩ reversal</span>
                    </td>
                    <td class="px-4 py-2 text-sm">{{ roleLabel(commission.commission_category) }}</td>
                    <td class="px-4 py-2 text-sm">{{ commission.commission_calc_type || 'percentage' }}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-right">
                      {{ formatCurrency('RM', commission.sale?.amount || commission.source_sale_amount || 0) }}
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-center">
                      <span v-if="commission.commission_calc_type === 'fixed'">
                        {{ formatCurrency('RM', commission.commission_fixed_amount || 0) }}
                      </span>
                      <span v-else>{{ commission.commission_rate }}%</span>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-right">
                      <span class="font-medium text-forest-dark">
                        {{ formatCurrency('RM', commission.amount) }}
                      </span>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap">
                      <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusClass(commission.status)}`">
                        {{ commission.status }}
                      </span>
                      <Button
                        v-if="commission.status === 'paid' && !commission.is_reversal && canMarkRefunded(commission)"
                        variant="link"
                        size="sm"
                        @click="markRefunded(commission)"
                        class="ml-2 text-xs text-accent-red"
                      >
                        Mark as Refunded
                      </Button>
                    </td>
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

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import Tabs from '../Design/Components/Tabs.vue'
import TabsList from '../Design/Components/TabsList.vue'
import TabsTrigger from '../Design/Components/TabsTrigger.vue'
import TabsContent from '../Design/Components/TabsContent.vue'
import Badge from '../Design/Components/Badge.vue'
import Button from '../Design/Components/Button.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  agent: { type: Object, required: true },
  summary: { type: Object, default: () => ({}) },
  commissions: { type: Array, default: () => [] },
  report: { type: Object, default: () => ({ by_type: [], by_source: [], by_period: [] }) },
  payout: { type: Object, default: null },
  year: { type: Number, required: true },
  month: { type: Number, required: true },
  monthName: { type: String, required: true },
  reversalTimeLimit: { type: Number, default: 60 },
})

const activeTab = ref('type')

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))

const roleLabel = (role) => roleNames.value[role] || role || '—'

const canMarkRefunded = (commission) => {
  if (!commission?.sale?.created_at) return true
  const saleDate = new Date(commission.sale.created_at)
  const limitMs = (props.reversalTimeLimit || 60) * 24 * 60 * 60 * 1000
  return Date.now() - saleDate.getTime() <= limitMs
}

const markRefunded = (commission) => {
  if (!commission?.sale?.id) return
  if (!confirm('Mark this sale as refunded? A negative reversal commission will be created.')) return
  router.post(`/admin/sales/${commission.sale.id}/refund`)
}

const getAgentName = (agent) => {
  if (!agent) return 'Unknown Agent'
  return agent.profile_type === 'individual'
    ? agent.individual_name
    : agent.company_name
}

const getAgentInitials = (agent) => {
  if (!agent) return 'UA'
  const name = getAgentName(agent)
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// Remove the local formatCurrency function since we're using the global helper

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'approved':
      return 'bg-green-100 text-green-800'
    case 'paid':
      return 'bg-blue-100 text-blue-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-stone-100 text-stone-800'
  }
}

const getPayoutStatusClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'paid':
      return 'bg-green-100 text-green-800 hover:bg-green-200'
    case 'unpaid':
      return 'bg-orange-100 text-orange-800 hover:bg-orange-200'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'
    default:
      return 'bg-stone-100 text-stone-800 hover:bg-stone-200'
  }
}

const getPreviousMonthLink = () => {
  let prevMonth = props.month - 1
  let prevYear = props.year

  if (prevMonth < 1) {
    prevMonth = 12
    prevYear = props.year - 1
  }

  return `/admin/commission/detail?year=${prevYear}&month=${prevMonth}&agent_id=${props.agent.id}`
}

const getNextMonthLink = () => {
  let nextMonth = props.month + 1
  let nextYear = props.year

  if (nextMonth > 12) {
    nextMonth = 1
    nextYear = props.year + 1
  }

  return `/admin/commission/detail?year=${nextYear}&month=${nextMonth}&agent_id=${props.agent.id}`
}
</script>
