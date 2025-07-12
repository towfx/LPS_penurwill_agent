<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <Link href="/admin/commissions/list" class="hover:text-forest-light">Admin</Link> /
      <Link href="/admin/commissions/list" class="hover:text-forest-light">Commissions</Link> /
      <span class="text-stone-900 font-medium">Update Payout</span>
    </nav>

    <!-- Title -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-forest-dark">Update Payout</h1>
        <p class="text-stone-600 mt-1">
          {{ getAgentName(agent) }} - {{ monthName }} {{ year }}
        </p>
      </div>
      <Link
        href="/admin/commissions/list"
        class="px-4 py-2 text-sm font-medium text-forest-light hover:text-forest-dark transition-colors"
      >
        ← Back to List
      </Link>
    </div>

    <form @submit.prevent="submitForm">
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

      <!-- Summary Card -->
      <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-4">Summary</h2>
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
            <div class="text-2xl font-bold text-forest-dark">{{ selectedSummary.total_sales }}</div>
            <div class="text-sm text-stone-500">Selected Sales</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-forest-dark">
              {{ formatCurrency('RM', selectedSummary.total_commission) }}
            </div>
            <div class="text-sm text-stone-500">Selected Commission</div>
          </div>
        </div>
      </div>

      <!-- Payout Details Card -->
      <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-4">Payout Details</h2>

        <!-- Amount Input -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-stone-700 mb-2">
            Payout Amount
          </label>
          <input
            v-model="form.amount"
            type="number"
            step="0.01"
            min="0"
            class="w-full px-3 py-2 border border-stone-300 rounded-md focus:outline-none focus:ring-2 focus:ring-forest-light focus:border-forest-light"
            :placeholder="formatCurrency('RM', selectedSummary.total_commission)"
            required
          />
          <p class="text-sm text-stone-500 mt-1">
            Format: 0.00 (e.g., 1234.56)
          </p>
        </div>

        <!-- Paid Checkbox -->
        <div class="mb-6">
          <label class="flex items-center">
            <input
              v-model="form.is_paid"
              type="checkbox"
              class="rounded border-stone-300 text-forest-light focus:ring-forest-light"
            />
            <span class="ml-2 text-sm font-medium text-stone-700">Mark as Paid</span>
          </label>
        </div>

        <!-- Paid Date Input -->
        <div v-if="form.is_paid" class="mb-6">
          <label class="block text-sm font-medium text-stone-700 mb-2">
            Payment Date
          </label>
          <input
            v-model="form.paid_at"
            type="date"
            class="w-full px-3 py-2 border border-stone-300 rounded-md focus:outline-none focus:ring-2 focus:ring-forest-light focus:border-forest-light"
            required
          />
        </div>
      </div>

      <!-- Commissions Table -->
      <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-stone-200">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-forest-dark">
              Commission Selection
            </h2>
            <div class="flex items-center space-x-4">
              <label class="flex items-center">
                <input
                  v-model="selectAll"
                  type="checkbox"
                  @change="toggleSelectAll"
                  class="rounded border-stone-300 text-forest-light focus:ring-forest-light"
                />
                <span class="ml-2 text-sm font-medium text-stone-700">Select All</span>
              </label>
            </div>
          </div>
          <p class="text-sm text-stone-600 mt-2">
            Check the boxes below to approve and include commissions in this payout
          </p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-stone-50">
              <tr>
                <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                  Select
                </th>
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
              <tr v-if="commissions.length === 0" class="hover:bg-stone-50">
                <td colspan="7" class="px-6 py-4 text-center text-stone-500">
                  No commission records found for this period.
                </td>
              </tr>
              <tr
                v-for="commission in commissions"
                :key="commission.id"
                class="hover:bg-stone-50"
              >
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <input
                    v-model="form.commission_ids"
                    :value="commission.id"
                    type="checkbox"
                    @change="updateTotals"
                    class="rounded border-stone-300 text-forest-light focus:ring-forest-light"
                  />
                </td>
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
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                        :class="getStatusClass(commission.status)">
                    {{ commission.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end">
        <button
          type="submit"
          class="px-6 py-2 bg-forest-dark text-white font-medium rounded-md hover:bg-forest-light transition-colors"
          :disabled="form.commission_ids.length === 0"
        >
          Update Payout
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  payout: {
    type: Object,
    required: true
  },
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

const form = useForm({
  amount: props.payout.amount,
  is_paid: !!props.payout.paid_at,
  paid_at: props.payout.paid_at ? new Date(props.payout.paid_at).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
  commission_ids: props.commissions.filter(c => c.is_in_payout).map(c => c.id)
})

const selectAll = ref(form.commission_ids.length === props.commissions.length)

// Computed property for selected summary
const selectedSummary = computed(() => {
  const selectedCommissions = props.commissions.filter(c =>
    form.commission_ids.includes(c.id)
  )

  return {
    total_sales: selectedCommissions.length,
    total_commission: selectedCommissions.reduce((sum, c) => sum + parseFloat(c.amount), 0)
  }
})

// Watch for changes in selected commissions and update amount
watch(() => form.commission_ids, (newSelectedIds) => {
  const selectedCommissions = props.commissions.filter(c => newSelectedIds.includes(c.id))
  const totalCommission = selectedCommissions.reduce((sum, c) => sum + parseFloat(c.amount), 0)
  form.amount = totalCommission
}, { deep: true })

const toggleSelectAll = () => {
  if (selectAll.value) {
    form.commission_ids = props.commissions.map(c => c.id)
  } else {
    form.commission_ids = []
  }
}

const updateTotals = () => {
  // Update selectAll based on current state
  selectAll.value = form.commission_ids.length === props.commissions.length
}

const submitForm = () => {
  // Set approved_commission_ids to be the same as commission_ids for backend compatibility
  form.approved_commission_ids = form.commission_ids
  form.put(`/admin/payout/${props.payout.id}/update`)
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
</script>
