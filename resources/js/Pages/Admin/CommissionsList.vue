<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Commissions</span> / <span class="text-stone-900 font-medium">List</span>
    </nav>

    <!-- Title -->
    <h1 class="text-2xl font-bold text-forest-dark mb-6">Commissions List</h1>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Year Dropdown -->
        <div class="flex-1">
          <label class="block text-sm font-medium text-stone-700 mb-2">Year</label>
          <select
            v-model="selectedYear"
            @change="updateFilters"
            class="w-full px-3 py-2 border border-stone-300 rounded-md focus:outline-none focus:ring-2 focus:ring-forest-light focus:border-forest-light"
          >
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>

        <!-- Month Selection -->
        <div class="flex-1">
          <label class="block text-sm font-medium text-stone-700 mb-2">Month</label>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="(monthName, monthNum) in months"
              :key="monthNum"
              @click="selectedMonth = monthNum; updateFilters()"
              :class="`
                px-2 py-1 text-sm font-medium rounded cursor-pointer transition-colors
                ${selectedMonth === Number(monthNum)
                  ? 'bg-accent-blue text-white shadow-sm font-semibold'
                  : 'text-stone-700 hover:bg-stone-100'
                }
              `"
            >
              {{ monthName }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-stone-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-stone-200">
        <h2 class="text-lg font-semibold text-forest-dark">
          Commissions for {{ months[selectedMonth] }} {{ selectedYear }}
        </h2>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-stone-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Agent
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Total Sales
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">
                Total Commission
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-stone-500 uppercase tracking-wider">
                Payout
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-stone-200">
            <tr v-if="commissions.length === 0" class="hover:bg-stone-50">
              <td colspan="5" class="px-6 py-4 text-center text-stone-500">
                No commissions found for the selected period.
              </td>
            </tr>
            <tr
              v-for="commission in commissions"
              :key="commission.agent_id"
              class="hover:bg-stone-50"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-forest-light flex items-center justify-center">
                      <span class="text-white font-medium text-sm">
                        {{ getAgentInitials(commission.agent) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-forest-dark">
                      {{ getAgentName(commission.agent) }}
                    </div>
                    <div class="text-sm text-stone-500">
                      {{ commission.agent?.profile_type === 'individual' ? 'Individual' : 'Company' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900">
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
                    :href="`/admin/payout/${commission.payout.id}/update`"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full transition-colors"
                    :class="getPayoutStatusClass(commission.payout.paid_at ? 'paid' : 'unpaid')"
                  >
                    <svg v-if="commission.payout.paid_at" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    {{ commission.payout.paid_at ? 'Paid' : 'Unpaid' }}
                  </Link>
                </div>
                <div v-else>
                  <Link
                    :href="`/admin/payout/create?agent_id=${commission.agent_id}&year=${selectedYear}&month=${selectedMonth}`"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-stone-600 hover:text-forest-dark transition-colors"
                    title="Create Payout"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Create Payout
                  </Link>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <Link
                  :href="`/admin/commission/detail?year=${selectedYear}&month=${selectedMonth}&agent_id=${commission.agent_id}`"
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

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  commissions: {
    type: Array,
    default: () => []
  },
  years: {
    type: Array,
    default: () => []
  },
  months: {
    type: Object,
    default: () => ({})
  },
  selectedYear: {
    type: Number,
    default: new Date().getFullYear()
  },
  selectedMonth: {
    type: Number,
    default: new Date().getMonth() + 1
  }
})

const selectedYear = ref(props.selectedYear)
const selectedMonth = ref(props.selectedMonth)

const updateFilters = () => {
  window.location.href = `/admin/commissions/list?year=${selectedYear.value}&month=${selectedMonth.value}`
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

// Remove the local formatCurrency function since we're using the global helper
</script>
