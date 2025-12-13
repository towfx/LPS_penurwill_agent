<template>
  <div>
    <!-- Breadcrumbs -->
    <nav class="text-sm text-stone-500 mb-2">
      <span>Partner</span> / <span class="text-stone-900 font-medium">Dashboard</span>
    </nav>
    <!-- Title & Description -->
    <h1 class="text-2xl font-bold text-forest-dark mb-1 flex items-center gap-2">
      <Settings class="inline text-gold" size="28" /> Partner Dashboard
    </h1>
    <p class="text-stone-700 mb-6">Overview of your agents, analytics, and management tools.</p>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatsCard
        title="Total Revenue This Month"
        :value="formatCurrency('RM', stats.revenueThisMonth)"
        :change="trendText(stats.revenueChange, true)"
        icon="DollarSign"
        :trend="trendType(stats.revenueChange)"
      />
      <StatsCard
        title="Active Agents"
        :value="stats.activeAgents"
        :change="trendText(stats.agentsChange, true)"
        icon="Users"
        :trend="trendType(stats.agentsChange)"
      />
      <StatsCard
        title="Commissions Paid"
        :value="formatCurrency('RM', stats.commissionsPaid)"
        :change="trendText(stats.commissionsChange, true)"
        icon="TrendingUp"
        :trend="trendType(stats.commissionsChange)"
      />
      <StatsCard
        title="System Conversion Rate"
        :value="stats.conversionRate ? stats.conversionRate.toFixed(1) + '%' : '—'"
        :change="trendText(stats.conversionChange, true)"
        icon="Target"
        :trend="trendType(stats.conversionChange)"
      />
    </div>

    <!-- Row 2: Revenue Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2 flex items-center gap-2">
          <DollarSign class="inline text-gold" size="20" /> Monthly Revenue (Last 12 Months)
        </h2>
        <LineChart :labels="revenueLabels" :data="revenueData" label="Revenue" color="#bc9c5f" :height="300" />
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2 flex items-center gap-2">
          <Users class="inline text-forest-light" size="20" /> Top Performing Agents
        </h2>
        <BarChart :labels="agentLabels" :data="agentData" label="Revenue" color="#5d775f" :height="300" />
      </div>
    </div>

    <!-- Row 3: System Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2 flex items-center gap-2">
          <DollarSign class="inline text-accent-orange" size="20" /> Commission Distribution
        </h2>
        <PieChart :labels="commissionLabels" :data="commissionData" :height="300" />
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-2 flex items-center gap-2">
          <Target class="inline text-accent-red" size="20" /> Referrals vs Sales (Last 30 Days)
        </h2>
        <div class="grid grid-cols-1 gap-4">
          <div>
            <h3 class="text-sm font-medium text-stone-600 mb-2">Referrals</h3>
            <BarChart :labels="referralsLabels" :data="referralsData" label="Referrals" color="#5d775f" :height="120" />
          </div>
          <div>
            <h3 class="text-sm font-medium text-stone-600 mb-2">Sales</h3>
            <LineChart :labels="referralsLabels" :data="salesData" label="Sales" color="#d4423f" :height="120" />
          </div>
        </div>
      </div>
    </div>

    <!-- Row 4: Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
      <!-- Recent System Activity -->
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-4 flex items-center gap-2">
          <Activity class="inline text-accent-blue" size="20" /> Recent Activity
        </h2>
        <ActivityTimeline :activities="recentActivity" />
      </div>

      <!-- Quick Actions & System Health -->
      <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
        <h2 class="text-lg font-semibold text-forest-dark mb-4 flex items-center gap-2">
          <Settings class="inline text-accent-orange" size="20" /> Quick Actions & System Health
        </h2>

        <!-- Quick Actions -->
        <div class="space-y-4 mb-6">
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-cream rounded-lg p-3">
              <div class="flex items-center gap-2 mb-1">
                <DollarSign class="text-accent-orange" size="16" />
                <span class="text-sm font-medium text-forest-dark">Pending Payouts</span>
              </div>
              <p class="text-lg font-bold text-forest-dark">{{ quickActions.pendingPayouts }}</p>
              <p class="text-xs text-stone-600">{{ formatCurrency('RM', quickActions.pendingPayoutsAmount) }}</p>
            </div>
            <div class="bg-cream rounded-lg p-3">
              <div class="flex items-center gap-2 mb-1">
                <Users class="text-accent-green" size="16" />
                <span class="text-sm font-medium text-forest-dark">Active Agents</span>
              </div>
              <p class="text-lg font-bold text-forest-dark">{{ quickActions.activeAgentsCount }}</p>
              <p class="text-xs text-stone-600">of {{ quickActions.totalAgents }} total</p>
            </div>
          </div>
        </div>

        <!-- System Health -->
        <div class="space-y-3">
          <h3 class="text-sm font-semibold text-forest-dark flex items-center gap-2">
            <AlertTriangle class="text-accent-orange" size="16" /> System Health
          </h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-stone-600">Avg Conversion Rate:</span>
              <span class="font-medium">{{ systemHealth.avgConversionRate.toFixed(1) }}%</span>
            </div>
            <div class="flex justify-between">
              <span class="text-stone-600">Default Commission Rate:</span>
              <span class="font-medium">{{ systemHealth.avgCommissionRate }}%</span>
            </div>
            <div class="flex justify-between">
              <span class="text-stone-600">Total Referrals:</span>
              <span class="font-medium">{{ systemHealth.totalReferrals }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-stone-600">Total Sales:</span>
              <span class="font-medium">{{ systemHealth.totalSales }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- System Health Dashboard -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6">
      <h2 class="text-lg font-semibold text-forest-dark mb-4 flex items-center gap-2">
        <AlertTriangle class="inline text-accent-orange" size="20" /> System Insights
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-accent-green/10 border border-accent-green/20 rounded-lg p-4">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-2 h-2 bg-accent-green rounded-full"></div>
            <span class="text-sm font-medium text-accent-green">System Status</span>
          </div>
          <p class="text-sm text-stone-700">All systems running optimally</p>
        </div>
        <div class="bg-accent-orange/10 border border-accent-orange/20 rounded-lg p-4">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-2 h-2 bg-accent-orange rounded-full"></div>
            <span class="text-sm font-medium text-accent-orange">Pending Actions</span>
          </div>
          <p class="text-sm text-stone-700">{{ quickActions.pendingPayouts }} payouts need approval</p>
        </div>
        <div class="bg-accent-blue/10 border border-accent-blue/20 rounded-lg p-4">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-2 h-2 bg-accent-blue rounded-full"></div>
            <span class="text-sm font-medium text-accent-blue">Performance</span>
          </div>
          <p class="text-sm text-stone-700">Avg conversion rate: {{ systemHealth.avgConversionRate.toFixed(1) }}%</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import PartnerLayout from '../Design/PartnerLayout.vue'
import StatsCard from '../Design/Components/StatsCard.vue'
import LineChart from '../Design/Components/LineChart.vue'
import BarChart from '../Design/Components/BarChart.vue'
import PieChart from '../Design/Components/PieChart.vue'
import ActivityTimeline from '../Design/Components/ActivityTimeline.vue'
import { formatCurrency } from '../../lib/utils.js'
import { TrendingUp, Users, DollarSign, Target, Settings, Activity, AlertTriangle } from 'lucide-vue-next'

defineOptions({ layout: PartnerLayout })

const page = usePage()
const stats = computed(() => page.props.stats)
const monthlyRevenue = computed(() => page.props.monthlyRevenue)
const topAgents = computed(() => page.props.topAgents)
const commissionDistribution = computed(() => page.props.commissionDistribution)
const referralsByDay = computed(() => page.props.referralsByDay)
const salesByDay = computed(() => page.props.salesByDay)
const recentActivity = computed(() => page.props.recentActivity)
const quickActions = computed(() => page.props.quickActions)
const systemHealth = computed(() => page.props.systemHealth)

const revenueLabels = computed(() => Object.keys(monthlyRevenue.value))
const revenueData = computed(() => Object.values(monthlyRevenue.value))
const agentLabels = computed(() => topAgents.value.map(agent => agent.name))
const agentData = computed(() => topAgents.value.map(agent => agent.revenue))
const commissionLabels = computed(() => ['Pending', 'Completed', 'Cancelled'])
const commissionData = computed(() => [
  commissionDistribution.value.pending,
  commissionDistribution.value.completed,
  commissionDistribution.value.cancelled
])
const referralsLabels = computed(() => Object.keys(referralsByDay.value))
const referralsData = computed(() => Object.values(referralsByDay.value))
const salesData = computed(() => Object.values(salesByDay.value))

function trendType(val) {
  if (val === null) return 'neutral'
  return val > 0 ? 'up' : val < 0 ? 'down' : 'neutral'
}
function trendText(val, isPercent = false) {
  if (val === null) return '—'
  return (val > 0 ? '+' : '') + val.toFixed(1) + (isPercent ? '%' : '')
}
</script>

