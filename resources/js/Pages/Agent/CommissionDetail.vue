<template>
  <div>
    <PageHeader
      title="Commission Details"
      :description="`${getAgentName(agent)} - ${monthName} ${year}`"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Commissions', href: '/agent/commissions' }, { label: 'Detail' }]"
    >
      <template #actions>
        <Link href="/agent/commissions">
          <Button variant="outline" size="sm">← Back to List</Button>
        </Link>
      </template>
    </PageHeader>

    <!-- Summary Card -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-forest-dark">{{ year }}</div>
          <div class="text-sm text-stone-500">Year</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-forest-dark">{{ monthName }}</div>
          <div class="text-sm text-stone-500">Month</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-forest-dark">{{ summary?.total_sales || 0 }}</div>
          <div class="text-sm text-stone-500">Total Sales</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-forest-dark">
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
            <Link :href="getPayoutUrl()">
              <Badge :variant="getPayoutStatusVariant(payout.status)">
                {{ payout.paid_at ? 'Paid' : 'Created' }}
              </Badge>
            </Link>
            <span v-if="payout.paid_at" class="text-sm text-stone-500">
              ({{ formatDate(payout.paid_at) }})
            </span>
          </div>
          <div v-else class="text-stone-500">
            No payout created yet
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

    <!-- Commissions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Commission Details
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Sale Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Calc Type</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Sale Amount</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">Rate / Fixed</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Commission</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="commissions.length === 0" class="hover:bg-stone-50">
              <td colspan="8" class="px-6 py-4 text-center text-stone-500">
                No commission records found for this period.
              </td>
            </tr>
            <tr
              v-for="commission in commissions"
              :key="commission.id"
              class="hover:bg-stone-50"
              :class="{ 'bg-red-50': commission.is_reversal }"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ formatDate(commission.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm text-stone-900">
                <div class="max-w-xs truncate">
                  {{ commission.sale?.description || 'N/A' }}
                </div>
                <div class="text-xs text-stone-500">
                  Invoice: {{ commission.sale?.invoice_number || 'N/A' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <Badge :variant="commission.commission_type === 'own_sales' ? 'success' : 'default'">
                  {{ commission.commission_type || 'own_sales' }}
                </Badge>
                <span v-if="commission.is_reversal" class="ml-1 text-xs text-accent-red">↩ reversal</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                {{ commission.commission_calc_type || 'percentage' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', commission.sale?.amount || commission.source_sale_amount || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-center">
                <span v-if="commission.commission_calc_type === 'fixed'">
                  {{ formatCurrency('RM', commission.commission_fixed_amount || 0) }}
                </span>
                <span v-else>{{ commission.commission_rate }}%</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                <span class="font-medium text-forest-dark">
                  {{ formatCurrency('RM', commission.amount) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="getStatusVariant(commission.status)">
                  {{ commission.status }}
                </Badge>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Badge from '../Design/Components/Badge.vue'
import Button from '../Design/Components/Button.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  agent: {
    type: Object,
    required: true
  },
  summary: {
    type: Object,
    default: () => ({})
  },
  commissions: {
    type: Array,
    default: () => []
  },
  payout: {
    type: Object,
    default: null
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

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'pending': return 'warning'
    case 'approved': return 'success'
    case 'paid': return 'default'
    case 'cancelled': return 'destructive'
    default: return 'secondary'
  }
}

const getPayoutUrl = () => {
  return `/agent/payouts/detail?year=${props.year}&month=${props.month}`
}

const getPayoutStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'paid': return 'success'
    case 'pending': return 'warning'
    default: return 'secondary'
  }
}
</script>
