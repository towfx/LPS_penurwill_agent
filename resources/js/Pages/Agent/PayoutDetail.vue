<template>
  <div>
    <PageHeader
      title="Payout Detail"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Payouts', href: '/agent/payouts' }, { label: 'Payout Detail' }]"
    >
      <template #actions>
        <Link href="/agent/payouts">
          <Button variant="outline" size="sm">← Back to Payouts</Button>
        </Link>
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <!-- Total Payouts -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-green/10 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-accent-green" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Total Payouts</p>
              <p class="text-2xl font-bold text-forest-dark">1</p>
            </div>
          </div>

          <!-- Date Request -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-blue/10 rounded-lg flex items-center justify-center">
              <Calendar class="w-6 h-6 text-accent-blue" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Date Request</p>
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
              <p class="text-2xl font-bold text-forest-dark">{{ formatCurrency('RM', payout.amount) }}</p>
            </div>
          </div>

          <!-- Status -->
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-accent-blue/10 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-6 h-6 text-accent-blue" />
            </div>
            <div>
              <p class="text-sm text-stone-500">Status</p>
              <Badge :variant="getPayoutStatusVariant(payout.status)">
                {{ payout.status.charAt(0).toUpperCase() + payout.status.slice(1) }}
              </Badge>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bank Transfer File Section -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <h3 class="text-lg font-semibold text-forest-dark mb-4">Bank Transfer File</h3>
      <div v-if="payout.bank_transfer_file" class="space-y-3">
        <div class="flex items-center gap-3">
          <Badge variant="success">Available</Badge>
          <span class="text-sm text-stone-600">{{ payout.bank_transfer_file }}</span>
        </div>
        <a :href="`/agent/payout/${payout.id}/download-bank-transfer`">
          <Button variant="outline" size="sm">
            <Download class="w-4 h-4 mr-1" />
            Download Bank Transfer File
          </Button>
        </a>
      </div>
      <div v-else class="text-stone-500">
        <Badge variant="secondary">Not Available</Badge>
        <p class="mt-2 text-sm">Bank transfer file has not been uploaded yet.</p>
      </div>
    </div>

    <!-- Payout Summary Card (Old) -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Agent Info -->
        <div>
          <h3 class="text-lg font-semibold text-forest-dark mb-4">Agent Information</h3>
          <div class="space-y-3">
            <div>
              <span class="text-sm font-medium text-stone-600">Name:</span>
              <p class="text-stone-900">{{ getAgentName(agent) }}</p>
            </div>
            <div>
              <span class="text-sm font-medium text-stone-600">Type:</span>
              <p class="text-stone-900">{{ agent?.profile_type === 'individual' ? 'Individual' : 'Company' }}</p>
            </div>
            <div>
              <span class="text-sm font-medium text-stone-600">Period:</span>
              <p class="text-stone-900">{{ monthName }} {{ year }}</p>
            </div>
          </div>
        </div>

        <!-- Payout Summary -->
        <div>
          <h3 class="text-lg font-semibold text-forest-dark mb-4">Payout Summary</h3>
          <div class="space-y-3">
            <div>
              <span class="text-sm font-medium text-stone-600">Total Amount:</span>
              <p class="text-lg font-bold text-forest-dark">{{ formatCurrency('RM', payout.amount) }}</p>
            </div>
            <div>
              <span class="text-sm font-medium text-stone-600">Status:</span>
              <Badge :variant="getPayoutStatusVariant(payout.status)" class="ml-1">
                {{ payout.status.charAt(0).toUpperCase() + payout.status.slice(1) }}
              </Badge>
            </div>
            <div v-if="payout.paid_at">
              <span class="text-sm font-medium text-stone-600">Paid Date:</span>
              <p class="text-stone-900">{{ formatDate(payout.paid_at) }}</p>
            </div>
            <div>
              <span class="text-sm font-medium text-stone-600">Created Date:</span>
              <p class="text-stone-900">{{ formatDate(payout.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Commission Summary -->
        <div>
          <h3 class="text-lg font-semibold text-forest-dark mb-4">Commission Summary</h3>
          <div class="space-y-3">
            <div>
              <span class="text-sm font-medium text-stone-600">Total Commissions:</span>
              <p class="text-lg font-bold text-forest-dark">{{ formatCurrency('RM', (payout.payout_items || []).reduce((sum, item) => sum + parseFloat(item.amount || 0), 0)) }}</p>
            </div>
            <div>
              <span class="text-sm font-medium text-stone-600">Number of Items:</span>
              <p class="text-stone-900">{{ (payout.payout_items || []).length }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payout Items Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Payout Items
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
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Status
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="!payout.payout_items || payout.payout_items.length === 0" class="hover:bg-stone-50">
              <td colspan="6" class="px-6 py-4 text-center text-stone-500">
                No payout items found.
              </td>
            </tr>
            <tr
              v-for="item in (payout.payout_items || [])"
              :key="item.id"
              class="hover:bg-stone-50"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
                {{ formatDate(item.commission.created_at) }}
              </td>
              <td class="px-6 py-4 text-sm text-stone-900">
                <div class="max-w-xs truncate">
                  {{ item.commission.sale?.description || 'N/A' }}
                </div>
                <div class="text-xs text-stone-500">
                  Invoice: {{ item.commission.sale?.invoice_number || 'N/A' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                {{ formatCurrency('RM', item.commission.sale?.amount || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-center">
                {{ item.commission.commission_rate }}%
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 text-right">
                <span class="font-medium text-forest-dark">
                  {{ formatCurrency('RM', item.amount) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <Badge :variant="getStatusVariant(item.commission.status)">
                  {{ item.commission.status }}
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
import { DollarSign, Calendar, CheckCircle, Download } from 'lucide-vue-next'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  payout: {
    type: Object,
    required: true
  },
  agent: {
    type: Object,
    required: true
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

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getPayoutStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'paid': return 'default'
    case 'approved': return 'success'
    case 'pending': return 'warning'
    default: return 'secondary'
  }
}

// Format date as "DD MMM YYYY"
const formatDateRequest = () => {
  if (!props.payout.created_at) return '—'
  const date = new Date(props.payout.created_at)
  const day = String(date.getDate()).padStart(2, '0')
  const month = date.toLocaleDateString('en-US', { month: 'short' })
  const year = date.getFullYear()
  return `${day} ${month} ${year}`
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
</script>
