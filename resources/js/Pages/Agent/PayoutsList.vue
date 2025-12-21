<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import { formatCurrency } from '../../lib/utils.js'
import { DollarSign, Calendar, CheckCircle, Plus, Eye } from 'lucide-vue-next'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  payouts: {
    type: Array,
    default: () => []
  },
  summary: {
    type: Object,
    default: () => ({
      total_payouts: 0,
      total_amount: 0,
      status_breakdown: {
        pending: 0,
        approved: 0,
        paid: 0
      }
    })
  },
  agent: {
    type: Object,
    required: true
  }
})

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

// Get status text for summary
const getStatusText = () => {
  const breakdown = props.summary.status_breakdown
  const parts = []
  if (breakdown.pending > 0) parts.push(`${breakdown.pending} Pending`)
  if (breakdown.approved > 0) parts.push(`${breakdown.approved} Approved`)
  if (breakdown.paid > 0) parts.push(`${breakdown.paid} Paid`)
  return parts.length > 0 ? parts.join(', ') : 'No payouts'
}

// Navigate to payout detail
const viewPayout = (id) => {
  router.visit(`/agent/payout/${id}`)
}
</script>

<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Payouts</span>
    </nav>

    <!-- Title and Create Button -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-forest-dark">My Payouts</h1>
      <a
        href="/agent/request-payout"
        class="inline-flex items-center gap-2 px-4 py-2 bg-accent-green text-white rounded-md font-medium hover:bg-accent-green/90 focus:outline-none focus:ring-2 focus:ring-accent-green focus:ring-offset-2 transition-colors"
      >
        <Plus class="w-4 h-4" />
        Create Payout
      </a>
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <!-- Total Payouts -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-green/10 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-accent-green" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Total Payouts</p>
              <p class="text-2xl font-bold text-forest-dark">{{ summary.total_payouts }}</p>
            </div>
          </div>

          <!-- Date Request -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-blue/10 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-accent-blue" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Date</p>
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
              <p class="text-2xl font-bold text-forest-dark">{{ formatCurrency('RM', summary.total_amount) }}</p>
            </div>
          </div>

          <!-- Status -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-blue/10 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-accent-blue" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Status</p>
              <p class="text-lg font-bold text-forest-dark">{{ getStatusText() }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payouts Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Payouts List
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Date/Time
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Items Count
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Paid At
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="payouts.length === 0" class="hover:bg-stone-50">
              <td colspan="6" class="px-6 py-4 text-center text-stone-500">
                No payouts found.
              </td>
            </tr>
            <tr
              v-for="payout in payouts"
              :key="payout.id"
              class="hover:bg-stone-50 cursor-pointer"
              @click="viewPayout(payout.id)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ formatDateTime(payout.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', payout.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-center">
                {{ payout.items_count }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="getStatusClass(payout.status)">
                  {{ payout.status.charAt(0).toUpperCase() + payout.status.slice(1) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ payout.paid_at ? formatDateTime(payout.paid_at) : '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <button
                  @click.stop="viewPayout(payout.id)"
                  class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-accent-blue hover:text-accent-blue/80 focus:outline-none"
                >
                  <Eye class="w-4 h-4" />
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

