<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <Link href="/agent/commissions" class="hover:text-forest-light">Agent</Link> /
      <Link href="/agent/commissions" class="hover:text-forest-light">Commissions</Link> /
      <span class="text-stone-900 font-medium">Detail</span>
    </nav>

    <!-- Title -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-forest-dark">Commission Details</h1>
        <p class="text-stone-600 mt-1">
          {{ getAgentName(agent) }} - {{ monthName }} {{ year }}
        </p>
      </div>
      <Link
        href="/agent/commissions"
        class="px-4 py-2 text-sm font-medium text-forest-light hover:text-forest-dark transition-colors"
      >
        ← Back to List
      </Link>
    </div>

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
            <Link
              :href="getPayoutUrl()"
              class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full"
              :class="getPayoutStatusClass(payout.status)"
            >
              <svg v-if="payout.paid_at" class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              {{ payout.paid_at ? 'Paid' : 'Created' }}
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
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Status
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="commissions.length === 0" class="hover:bg-stone-50">
              <td colspan="6" class="px-6 py-4 text-center text-stone-500">
                No commission records found for this period.
              </td>
            </tr>
            <tr
              v-for="commission in commissions"
              :key="commission.id"
              class="hover:bg-stone-50"
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
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', commission.sale?.amount || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-center">
                {{ commission.commission_rate }}%
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                <span class="font-medium text-forest-dark">
                  {{ formatCurrency('RM', commission.amount) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="`
                    inline-flex px-2 py-1 text-xs font-semibold rounded-full
                    ${getStatusClass(commission.status)}
                  `"
                >
                  {{ commission.status }}
                </span>
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

const getPayoutUrl = () => {
  return `/agent/payouts/detail?year=${props.year}&month=${props.month}`
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
