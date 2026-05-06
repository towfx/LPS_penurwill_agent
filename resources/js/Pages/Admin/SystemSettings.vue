<template>
  <div class="space-y-6">
    <nav class="flex items-center space-x-2 text-sm text-gray-600">
      <Link href="/admin/dashboard" class="hover:text-forest-dark transition-colors">Dashboard</Link>
      <span class="text-gray-400">/</span>
      <span class="text-forest-dark font-medium">System Settings</span>
    </nav>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-forest-dark">System Settings</h1>
        <p class="text-gray-600 mt-2">
          Read-only summary of the active commission, fee, and lifecycle configuration.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Link
          href="/admin/commission-rate-preview"
          class="inline-flex items-center px-4 py-2 bg-cream text-forest-dark border border-gold/30 font-medium rounded-lg hover:bg-gold/10 transition-colors"
        >
          <Eye class="w-4 h-4 mr-2" />
          Live Preview
        </Link>
        <Link
          href="/admin/system-settings/update"
          class="inline-flex items-center px-4 py-2 bg-gold text-forest-dark font-medium rounded-lg hover:bg-gold/90 transition-colors"
        >
          <Settings class="w-4 h-4 mr-2" />
          Update Settings
        </Link>
      </div>
    </div>

    <!-- Commission Configuration -->
    <Card class="bg-white shadow-sm border border-gray-200">
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <Percent class="w-5 h-5 text-gold" />
          <span>Commission Configuration</span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-cream">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Tier &amp; Type</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Percentage</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Fixed (RM)</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Calc Type</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="row in commissionRows" :key="row.key">
                <td class="px-4 py-2">{{ row.label }}</td>
                <td class="px-4 py-2 text-right">{{ Number(settings[row.key + '_percentage'] ?? 0).toFixed(2) }}%</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency('RM', settings[row.key + '_fixed_amount'] ?? 0) }}</td>
                <td class="px-4 py-2">
                  <Badge :variant="settings[row.key + '_calc_type'] === 'fixed' ? 'secondary' : 'default'">
                    {{ settings[row.key + '_calc_type'] || 'percentage' }}
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex items-center mt-4 text-sm text-gray-700">
          <CheckCircle2 v-if="settings.skip_zero_commissions" class="w-4 h-4 text-accent-green mr-2" />
          <XCircle v-else class="w-4 h-4 text-gray-400 mr-2" />
          Skip zero commissions: <span class="ml-1 font-medium">{{ settings.skip_zero_commissions ? 'Enabled' : 'Disabled' }}</span>
        </div>
      </CardContent>
    </Card>

    <!-- Fee Configuration -->
    <Card class="bg-white shadow-sm border border-gray-200">
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <Receipt class="w-5 h-5 text-accent-green" />
          <span>Fee Configuration</span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-cream">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Role</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Entry Fee</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Renewal Fee</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Renewal Enabled</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="row in feeRows" :key="row.role">
                <td class="px-4 py-2">{{ row.label }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency('RM', settings[row.entryKey] ?? 0) }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency('RM', settings[row.renewalKey] ?? 0) }}</td>
                <td class="px-4 py-2">
                  <Badge v-if="row.enabledKey === null" variant="default">Always</Badge>
                  <Badge v-else :variant="settings[row.enabledKey] ? 'success' : 'secondary'">
                    {{ settings[row.enabledKey] ? 'Yes' : 'No' }}
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <!-- Role Names + Policies -->
    <div class="grid gap-6 md:grid-cols-2">
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Users class="w-5 h-5 text-accent-blue" />
            <span>Role Names</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Agent</span>
            <span class="font-medium text-forest-dark">{{ settings.role_name_agent || 'Agent' }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Leader</span>
            <span class="font-medium text-forest-dark">{{ settings.role_name_leader || 'Leader' }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Business Partner</span>
            <span class="font-medium text-forest-dark">{{ settings.role_name_business_partner || 'Business Partner' }}</span>
          </div>
        </CardContent>
      </Card>

      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Clock class="w-5 h-5 text-accent-orange" />
            <span>Lifecycle &amp; Policy</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Renewal reminder (days before)</span>
            <span class="font-medium">{{ settings.renewal_reminder_days_before ?? '—' }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Reversal time limit (days)</span>
            <span class="font-medium">{{ settings.reversal_time_limit ?? '—' }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Email verification max retry</span>
            <span class="font-medium">{{ settings.email_verification_max_retry ?? '—' }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Minimum payout amount</span>
            <span class="font-medium">{{ formatCurrency('RM', settings.min_payout_amount ?? 0) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600">Referral code prefix</span>
            <span class="font-medium font-mono">{{ settings.referral_code_prefix || '—' }}</span>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card class="bg-gradient-to-r from-cream to-white border border-gold/20">
      <CardContent class="pt-6">
        <div class="flex items-start space-x-3">
          <Info class="w-5 h-5 text-accent-blue mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="font-semibold text-forest-dark mb-2">About System Settings</h3>
            <p class="text-sm text-gray-700 leading-relaxed">
              These settings drive commission generation, fee handling, and the renewal lifecycle.
              Changes apply to new commissions and renewal cycles created after the update; past records remain immutable.
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Settings, Percent, Receipt, Users, Clock, Info, Eye, CheckCircle2, XCircle,
} from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import CardContent from '../Design/Components/CardContent.vue'
import Badge from '../Design/Components/Badge.vue'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  settings: { type: Object, required: true },
})

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || props.settings.role_name_agent || 'Agent',
  leader: page.props.systemSettings?.role_name_leader || props.settings.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || props.settings.role_name_business_partner || 'Business Partner',
}))

const commissionRows = computed(() => [
  { key: 'agent_own_sales', label: `${roleNames.value.agent} — Own Sales` },
  { key: 'agent_leader_own_sales', label: `${roleNames.value.leader} — Own Sales` },
  { key: 'agent_leader_override_agent', label: `${roleNames.value.leader} — Override on ${roleNames.value.agent}` },
  { key: 'business_partner_own_sales', label: `${roleNames.value.business_partner} — Own Sales` },
  { key: 'business_partner_override_agent', label: `${roleNames.value.business_partner} — Override on ${roleNames.value.agent}` },
  { key: 'business_partner_override_agent_leader', label: `${roleNames.value.business_partner} — Override on ${roleNames.value.leader}` },
])

const feeRows = computed(() => [
  { role: 'business_partner', label: roleNames.value.business_partner, entryKey: 'entry_fee_business_partner', renewalKey: 'renewal_fee_business_partner', enabledKey: null },
  { role: 'leader', label: roleNames.value.leader, entryKey: 'entry_fee_leader', renewalKey: 'renewal_fee_leader', enabledKey: 'renewal_fee_leader_enabled' },
  { role: 'agent', label: roleNames.value.agent, entryKey: 'entry_fee_agent', renewalKey: 'renewal_fee_agent', enabledKey: 'renewal_fee_agent_enabled' },
])
</script>
