<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  commissions: {
    type: Array,
    default: () => []
  },
  years: {
    type: Array,
    default: () => []
  },
  selectedYear: {
    type: Number,
    default: new Date().getFullYear()
  },
  agent: {
    type: Object,
    required: true
  }
})

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

    <!-- Commissions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Commissions for {{ selectedYear }}
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Month
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Total Sales
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Total Commission
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Payout
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Actions
              </th>
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
    </div>
  </div>
</template>
