<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Badge from '../Design/Components/Badge.vue'
import Button from '../Design/Components/Button.vue'
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

// Get status badge variant
const getStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'pending': return 'warning'
    case 'approved': return 'success'
    case 'paid': return 'default'
    default: return 'secondary'
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
    <PageHeader
      title="My Payouts"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Payouts' }]"
    >
      <template #actions>
        <a href="/agent/request-payout">
          <Button variant="default" size="default">
            <Plus class="w-4 h-4 mr-1" />
            Create Payout
          </Button>
        </a>
      </template>
    </PageHeader>

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
                <Badge :variant="getStatusVariant(payout.status)">
                  {{ payout.status.charAt(0).toUpperCase() + payout.status.slice(1) }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ payout.paid_at ? formatDateTime(payout.paid_at) : '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <Button variant="ghost" size="sm" @click.stop="viewPayout(payout.id)">
                  <Eye class="w-4 h-4 mr-1" />
                  View
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

