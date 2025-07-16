<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import StatsCard from '../Design/Components/StatsCard.vue'
import LineChart from '../Design/Components/LineChart.vue'
import BarChart from '../Design/Components/BarChart.vue'
import { formatCurrency } from '../../lib/utils.js'
import { TrendingUp, Users, DollarSign, Target } from 'lucide-vue-next'

defineOptions({ layout: AgentLayout })

const page = usePage()
const stats = computed(() => page.props.stats)
const salesByDay = computed(() => page.props.salesByDay)
const referralsByDay = computed(() => page.props.referralsByDay)
const conversionRateByDay = computed(() => page.props.conversionRateByDay)
const recentSales = computed(() => page.props.recentSales)
const performance = computed(() => page.props.performance)

const salesLabels = computed(() => Object.keys(salesByDay.value).map(day => day.toString()))
const salesData = computed(() => Object.values(salesByDay.value))
const referralsLabels = computed(() => Object.keys(referralsByDay.value))
const referralsData = computed(() => Object.values(referralsByDay.value))
const conversionData = computed(() => Object.values(conversionRateByDay.value))

function trendType(val) {
  if (val === null) return 'neutral'
  return val > 0 ? 'up' : val < 0 ? 'down' : 'neutral'
}
function trendText(val, isPercent = false) {
  if (val === null) return '—'
  return (val > 0 ? '+' : '') + val.toFixed(1) + (isPercent ? '%' : '')
}
</script>

<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-2">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Dashboard</span>
    </nav>
    <!-- Title & Description -->
    <h1 class="text-2xl font-bold text-forest-dark mb-1 flex items-center gap-2">
      <DollarSign class="inline text-gold" size="28" /> Agent Dashboard
    </h1>
    <p class="text-stone-700 mb-6">Your performance overview, sales, referrals, and more.</p>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatsCard
        title="Total Sales This Month"
        :value="formatCurrency('RM', stats.salesThisMonth)"
        :change="trendText(stats.salesChange, true)"
        icon="DollarSign"
        :trend="trendType(stats.salesChange)"
      />
      <StatsCard
        title="Total Commissions"
        :value="formatCurrency('RM', stats.commThisMonth)"
        :change="trendText(stats.commChange, true)"
        icon="TrendingUp"
        :trend="trendType(stats.commChange)"
      />
      <StatsCard
        title="Active Referrals (90d)"
        :value="stats.referrals90"
        :change="trendText(stats.refChange, true)"
        icon="Users"
        :trend="trendType(stats.refChange)"
      />
      <StatsCard
        title="Conversion Rate"
        :value="stats.conversionRate ? stats.conversionRate.toFixed(1) + '%' : '—'"
        :change="trendText(stats.conversionChange, true)"
        icon="Target"
        :trend="trendType(stats.conversionChange)"
      />
    </div>

    <!-- Row 2: Monthly Sales Line Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-forest-dark mb-2 flex items-center gap-2">
        <DollarSign class="inline text-gold" size="20" /> Monthly Sales
      </h2>
      <LineChart :labels="salesLabels" :data="salesData" label="Sales" color="#bc9c5f" :height="300" />
    </div>

    <!-- Row 3: Referral Analytics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2 flex items-center gap-2">
          <Users class="inline text-forest-light" size="20" /> Referrals (Last 90 Days)
        </h2>
        <BarChart :labels="referralsLabels" :data="referralsData" label="Referrals" color="#5d775f" :height="300" />
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2 flex items-center gap-2">
          <Target class="inline text-accent-red" size="20" /> Conversion Rate (Last 90 Days)
        </h2>
        <LineChart :labels="referralsLabels" :data="conversionData" label="Conversion Rate" color="#d4423f" :height="300" />
      </div>
    </div>

    <!-- Row 4: Recent Sales & Performance -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <!-- Recent Sales Table -->
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2">Recent Sales</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="bg-cream">
                <th class="px-2 py-1 text-left">Date</th>
                <th class="px-2 py-1 text-left">Amount</th>
                <th class="px-2 py-1 text-left">Commission</th>
                <th class="px-2 py-1 text-left">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sale in recentSales" :key="sale.id" class="border-b hover:bg-cream/50">
                <td class="px-2 py-1">{{ sale.sale_date ? new Date(sale.sale_date).toLocaleDateString() : '—' }}</td>
                <td class="px-2 py-1">{{ formatCurrency('RM', sale.amount) }}</td>
                <td class="px-2 py-1">{{ formatCurrency('RM', sale.commission?.amount ?? 0) }}</td>
                <td class="px-2 py-1">
                  <span class="inline-block rounded px-2 py-0.5 text-xs font-semibold"
                        :class="{
                          'bg-accent-green/20 text-accent-green': sale.commission?.status === 'completed',
                          'bg-accent-orange/20 text-accent-orange': sale.commission?.status === 'pending',
                          'bg-accent-gray/20 text-accent-gray': !sale.commission
                        }">
                    {{ sale.commission?.status ? sale.commission.status.charAt(0).toUpperCase() + sale.commission.status.slice(1) : '—' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!recentSales.length">
                <td colspan="4" class="text-center text-stone-400 py-4">No sales found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Performance Summary -->
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2">Performance Summary</h2>
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <DollarSign class="text-gold" size="18" />
            <span>Average Sale Value:</span>
            <span class="font-bold">{{ formatCurrency('RM', performance.avgSaleValue) }}</span>
          </div>
          <div class="flex items-center gap-2">
            <TrendingUp class="text-accent-blue" size="18" />
            <span>Best Day:</span>
            <span class="font-bold">{{ performance.bestDay || '—' }}</span>
          </div>
          <div class="flex items-center gap-2">
            <DollarSign class="text-accent-green" size="18" />
            <span>Total Payouts Received:</span>
            <span class="font-bold">{{ formatCurrency('RM', performance.totalPayouts) }}</span>
          </div>
          <div class="flex items-center gap-2">
            <DollarSign class="text-accent-orange" size="18" />
            <span>Pending Payouts:</span>
            <span class="font-bold">{{ formatCurrency('RM', performance.pendingPayouts) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
