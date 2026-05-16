<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import StatsCard from '../Design/Components/StatsCard.vue'
import LineChart from '../Design/Components/LineChart.vue'
import BarChart from '../Design/Components/BarChart.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Badge from '../Design/Components/Badge.vue'
import Button from '../Design/Components/Button.vue'
import { formatCurrency } from '../../lib/utils.js'
import { TrendingUp, Users, DollarSign, Target, AlertTriangle, Link as LinkIcon } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { useRoleNames } from '../../composables/useRoleNames.js'

const { roleNames, roleLabel } = useRoleNames()

defineOptions({ layout: AgentLayout })

const page = usePage()
const agent = computed(() => page.props.agent)
const stats = computed(() => page.props.stats)
const salesByDay = computed(() => page.props.salesByDay)
const referralsByDay = computed(() => page.props.referralsByDay)
const conversionRateByDay = computed(() => page.props.conversionRateByDay)
const recentSales = computed(() => page.props.recentSales)
const performance = computed(() => page.props.performance)

// roleNames from composable used instead of local definition

const commissionBreakdown = computed(() => {
  const b = page.props.commissionBreakdown || {}
  return {
    own_sales: b.own_sales ?? 0,
    override_agent: b.override_agent ?? 0,
    override_leader: b.override_leader ?? 0,
  }
})

const subordinatesCount = computed(() => page.props.subordinatesCount ?? 0)

const payoutProgress = computed(() => {
  const minimum = Number(
    page.props.minPayoutAmount
    ?? page.props.systemSettings?.min_payout_amount
    ?? 1
  )
  const available = Number(page.props.availableForPayout ?? 0)
  const percent = minimum > 0 ? (available / minimum) * 100 : 100
  return {
    minimum,
    available,
    percent,
    canRequest: available >= minimum && available > 0,
  }
})

const lifecycle = computed(() => {
  const a = agent.value || {}
  const today = new Date()
  if (a.expires_at) {
    const exp = new Date(a.expires_at)
    const days = Math.floor((exp - today) / (1000 * 60 * 60 * 24))
    if (days < 0) {
      return {
        alert: {
          severity: 'critical',
          title: 'Membership expired',
          message: `Your membership expired ${-days} day(s) ago. Renew now to keep earning commissions.`,
        },
      }
    }
    if (a.fee_payment_status === 'overdue') {
      return {
        alert: {
          severity: 'critical',
          title: 'Renewal fee overdue',
          message: 'Settle your renewal fee to avoid suspension.',
        },
      }
    }
    if (days <= 30) {
      return {
        alert: {
          severity: 'warning',
          title: 'Renewal due soon',
          message: `Your membership expires in ${days} day(s) on ${a.expires_at}.`,
        },
      }
    }
  }
  return { alert: null }
})

const agentContext = computed(() => page.props.agentContext || {})
const agentStatus = computed(() => agentContext.value.agent_status || agent.value?.status)
const feePaymentStatus = computed(() => agentContext.value.fee_payment_status || agent.value?.fee_payment_status)

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

function getStatusVariant(status) {
  switch (status?.toLowerCase()) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    case 'suspended': return 'warning'
    case 'banned': return 'destructive'
    default: return 'secondary'
  }
}
</script>

<template>
  <div>
    <PageHeader
      :title="`${roleNames.agent} Dashboard`"
      description="Your performance overview, sales, referrals, and more."
      :breadcrumbs="[{ label: 'Dashboard' }]"
    >
      <template #actions>
        <Badge v-if="agent?.status" :variant="getStatusVariant(agent.status)" class="capitalize text-sm px-3 py-1">
          {{ agent.status.charAt(0).toUpperCase() + agent.status.slice(1) }}
        </Badge>
      </template>
    </PageHeader>

    <!-- Suspended Banner (GAP-05) -->
    <div
      v-if="agentStatus === 'suspended'"
      class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 mb-4 flex items-start gap-3"
    >
      <AlertTriangle class="w-5 h-5 mt-0.5 flex-shrink-0 text-yellow-700" />
      <div class="flex-1">
        <p class="font-semibold text-yellow-900">Your account has been suspended</p>
        <p class="text-sm text-yellow-800 mt-1">
          {{ agent?.suspension_reason || 'Please contact support for more information.' }}
        </p>
      </div>
      <Button variant="outline" size="sm" @click="() => router.post('/agent/appeal-suspension', { message: 'I would like to appeal my suspension.' })">
        Appeal
      </Button>
    </div>

    <!-- Rejected Banner (GAP-09) -->
    <div
      v-if="agentStatus === 'rejected'"
      class="rounded-lg border border-red-300 bg-red-50 p-4 mb-4 flex items-start gap-3"
    >
      <AlertTriangle class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-700" />
      <div class="flex-1">
        <p class="font-semibold text-red-900">Your application was rejected</p>
        <p class="text-sm text-red-800 mt-1">
          {{ agent?.rejection_reason || 'Please contact support for more information.' }}
        </p>
      </div>
      <Button variant="outline" size="sm" @click="() => router.post('/agent/request-approval')">
        Request Re-approval
      </Button>
    </div>

    <!-- Payment Pending Banner -->
    <div
      v-if="feePaymentStatus === 'pending' && agentStatus !== 'suspended' && agentStatus !== 'rejected'"
      class="rounded-lg border border-blue-300 bg-blue-50 p-4 mb-4 flex items-start gap-3"
    >
      <AlertTriangle class="w-5 h-5 mt-0.5 flex-shrink-0 text-blue-700" />
      <div class="flex-1">
        <p class="font-semibold text-blue-900">Registration fee payment pending</p>
        <p class="text-sm text-blue-800 mt-1">Complete your payment to activate full account access.</p>
      </div>
      <Button variant="default" size="sm" @click="() => router.visit('/agent/payment/complete')">
        Complete Payment
      </Button>
    </div>

    <!-- Renewal / Expiry Alert Banner -->
    <div
      v-if="lifecycle.alert"
      class="rounded-lg border p-4 mb-6"
      :class="lifecycle.alert.severity === 'critical'
        ? 'bg-red-50 border-red-300 text-red-900'
        : 'bg-yellow-50 border-yellow-300 text-yellow-900'"
    >
      <div class="flex items-start gap-3">
        <AlertTriangle class="w-5 h-5 mt-0.5 flex-shrink-0" />
        <div>
          <p class="font-semibold">{{ lifecycle.alert.title }}</p>
          <p class="text-sm mt-1">{{ lifecycle.alert.message }}</p>
        </div>
      </div>
    </div>

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

    <!-- Commission breakdown by type + subordinates count + payout progress -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatsCard
        title="Own Sales Commission"
        :value="formatCurrency('RM', commissionBreakdown.own_sales)"
        icon="DollarSign"
      />
      <StatsCard
        :title="`Override (${roleNames.agent})`"
        :value="formatCurrency('RM', commissionBreakdown.override_agent)"
        icon="TrendingUp"
      />
      <StatsCard
        :title="`Override (${roleNames.agent_leader})`"
        :value="formatCurrency('RM', commissionBreakdown.override_leader)"
        icon="TrendingUp"
      />
      <StatsCard
        title="Direct Subordinates"
        :value="String(subordinatesCount)"
        icon="Users"
      />
    </div>

    <!-- Payout progress -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold text-forest-dark">Payout Progress</h2>
        <Button
          v-if="payoutProgress.canRequest"
          variant="default"
          size="sm"
          @click="() => { window.location.href = '/agent/request-payout' }"
        >
          Request Payout
        </Button>
        <Button
          v-else
          variant="secondary"
          size="sm"
          disabled
          :title="`Minimum is ${formatCurrency('RM', payoutProgress.minimum)} — you have ${formatCurrency('RM', payoutProgress.available)}`"
        >
          Request Payout
        </Button>
      </div>
      <div class="flex items-center justify-between text-sm text-stone-600 mb-2">
        <span>Available: <span class="font-semibold text-forest-dark">{{ formatCurrency('RM', payoutProgress.available) }}</span></span>
        <span>Minimum: <span class="font-semibold text-forest-dark">{{ formatCurrency('RM', payoutProgress.minimum) }}</span></span>
      </div>
      <div class="w-full bg-stone-200 rounded-full h-3">
        <div
          class="h-3 rounded-full transition-all"
          :class="payoutProgress.canRequest ? 'bg-accent-green' : 'bg-gold'"
          :style="{ width: `${Math.min(100, payoutProgress.percent)}%` }"
        ></div>
      </div>
      <p v-if="!payoutProgress.canRequest" class="text-xs text-stone-500 mt-2">
        You need {{ formatCurrency('RM', Math.max(0, payoutProgress.minimum - payoutProgress.available)) }}
        more in pending commissions to request a payout.
      </p>
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
