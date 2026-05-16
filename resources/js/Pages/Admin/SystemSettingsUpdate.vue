<template>
  <div class="space-y-6">
    <PageHeader
      title="Update System Settings"
      description="Configure global commission rates, fee structure, and lifecycle policies. Changes affect new commissions and renewal cycles going forward."
      :breadcrumbs="[{ label: 'Dashboard', href: '/admin/dashboard' }, { label: 'System Settings', href: '/admin/system-settings' }, { label: 'Update Settings' }]"
    >
      <template #actions>
        <Link
          href="/admin/commission-rate-preview"
          class="inline-flex items-center px-4 py-2 text-sm bg-cream text-forest-dark border border-gold/30 rounded-lg hover:bg-gold/10 transition-colors whitespace-nowrap"
        >
          <Eye class="w-4 h-4 mr-2" />
          Live Commission Preview
        </Link>
      </template>
    </PageHeader>

    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Commission Configuration -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Percent class="w-5 h-5 text-gold" />
            <span>Commission Configuration</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <p class="text-sm text-gray-600">
            6 commission rows across {{ roleNames.agent }}, {{ roleNames.leader }} and {{ roleNames.business_partner }} tiers.
            For each row, set a percentage <em>or</em> a fixed RM amount and pick the calc type that should apply.
          </p>

          <div
            v-for="row in commissionRows"
            :key="row.key"
            class="grid gap-4 md:grid-cols-12 items-end p-4 rounded-lg border border-gray-200 bg-stone-50/50"
          >
            <div class="md:col-span-4">
              <div class="text-sm font-semibold text-forest-dark">{{ row.label }}</div>
              <div class="text-xs text-gray-500 mt-1">{{ row.description }}</div>
            </div>

            <div class="md:col-span-3">
              <FormField label="Percentage (%)" :error="errors[row.key + '_percentage']">
                <Input
                  v-model="form[row.key + '_percentage']"
                  type="number"
                  :invalid="!!errors[row.key + '_percentage']"
                />
              </FormField>
            </div>

            <div class="md:col-span-3">
              <FormField label="Fixed (RM)" :error="errors[row.key + '_fixed_amount']">
                <Input
                  v-model="form[row.key + '_fixed_amount']"
                  type="number"
                  :invalid="!!errors[row.key + '_fixed_amount']"
                />
              </FormField>
            </div>

            <div class="md:col-span-2">
              <FormField label="Calc Type" :error="errors[row.key + '_calc_type']">
                <Select
                  v-model="form[row.key + '_calc_type']"
                  :options="[{ value: 'percentage', label: 'Percentage' }, { value: 'fixed', label: 'Fixed' }]"
                  :invalid="!!errors[row.key + '_calc_type']"
                />
              </FormField>
            </div>
          </div>

          <div class="flex items-start gap-3 p-3 bg-cream/50 rounded-lg">
            <input
              id="skip_zero_commissions"
              v-model="form.skip_zero_commissions"
              type="checkbox"
              class="mt-1 h-4 w-4 text-gold rounded focus:ring-gold"
            />
            <label for="skip_zero_commissions" class="text-sm text-gray-700">
              <span class="font-medium block">Skip zero commissions</span>
              <span class="text-xs text-gray-500">
                When enabled, rows with both percentage = 0 and fixed = 0 are not persisted.
              </span>
            </label>
          </div>
        </CardContent>
      </Card>

      <!-- Fee Configuration (Decision 13) -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Receipt class="w-5 h-5 text-accent-green" />
            <span>Fee Configuration</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <p class="text-sm text-gray-600">
            Entry fees are charged on registration. Renewal fees apply per cycle when enabled.
          </p>

          <div
            v-for="row in feeRows"
            :key="row.role"
            class="grid gap-4 md:grid-cols-12 items-end p-4 rounded-lg border border-gray-200 bg-stone-50/50"
          >
            <div class="md:col-span-3">
              <div class="text-sm font-semibold text-forest-dark">{{ row.label }}</div>
            </div>

            <div class="md:col-span-3">
              <FormField label="Entry Fee (RM)" :error="errors[row.entryKey]">
                <Input v-model="form[row.entryKey]" type="number" :invalid="!!errors[row.entryKey]" />
              </FormField>
            </div>

            <div class="md:col-span-3">
              <FormField label="Renewal Fee (RM)" :error="errors[row.renewalKey]">
                <Input v-model="form[row.renewalKey]" type="number" :invalid="!!errors[row.renewalKey]" />
              </FormField>
            </div>

            <div class="md:col-span-3"></div>
          </div>
        </CardContent>
      </Card>

      <!-- Role Names (Decision 15) -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Users class="w-5 h-5 text-accent-blue" />
            <span>Role Names</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <p class="text-sm text-gray-600">
            Override the default labels shown across the application. These do not change role permissions.
          </p>
          <div class="grid gap-4 md:grid-cols-3">
            <FormField label="Agent Label" :error="errors.role_name_agent">
              <Input v-model="form.role_name_agent" type="text" :invalid="!!errors.role_name_agent" />
            </FormField>
            <FormField label="Leader Label" :error="errors.role_name_leader">
              <Input v-model="form.role_name_leader" type="text" :invalid="!!errors.role_name_leader" />
            </FormField>
            <FormField label="Business Partner Label" :error="errors.role_name_business_partner">
              <Input v-model="form.role_name_business_partner" type="text" :invalid="!!errors.role_name_business_partner" />
            </FormField>
          </div>
        </CardContent>
      </Card>

      <!-- Lifecycle & Policy -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Clock class="w-5 h-5 text-accent-orange" />
            <span>Lifecycle &amp; Policy</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <FormField label="Renewal Reminder (days before)" :error="errors.renewal_reminder_days_before">
              <Input v-model="form.renewal_reminder_days_before" type="number" :invalid="!!errors.renewal_reminder_days_before" />
              <p class="text-xs text-gray-500 mt-1">Days before expiry to send the renewal reminder email.</p>
            </FormField>

            <FormField label="Reversal Time Limit (days)" :error="errors.reversal_time_limit">
              <Input v-model="form.reversal_time_limit" type="number" :invalid="!!errors.reversal_time_limit" />
              <p class="text-xs text-gray-500 mt-1">Sales older than this can no longer be refunded (Decision 18).</p>
            </FormField>

            <FormField label="Email Verification Max Retry" :error="errors.email_verification_max_retry">
              <Input v-model="form.email_verification_max_retry" type="number" :invalid="!!errors.email_verification_max_retry" />
              <p class="text-xs text-gray-500 mt-1">
                Max attempts per code &amp; per day before lockout (Decision 27). Default: 10.
              </p>
            </FormField>

            <FormField label="Minimum Payout Amount (RM)" :error="errors.min_payout_amount">
              <Input v-model="form.min_payout_amount" type="number" :invalid="!!errors.min_payout_amount" />
              <p class="text-xs text-gray-500 mt-1">Agents cannot request payouts below this threshold.</p>
            </FormField>

            <div class="md:col-span-2">
              <FormField label="Referral Code Prefix" :error="errors.referral_code_prefix">
                <Input v-model="form.referral_code_prefix" type="text" :invalid="!!errors.referral_code_prefix" />
                <p class="text-xs text-gray-500 mt-1">Prefix prepended to auto-generated referral codes.</p>
              </FormField>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Live Commission Preview (admin) — Decision 17 -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Eye class="w-5 h-5 text-forest-dark" />
            <span>Live Commission Preview</span>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <p class="text-sm text-gray-600">
            Enter a hypothetical sale and pick an earning + source agent to see what commission rows the current
            configuration would generate. This calls
            <code class="px-1 py-0.5 bg-stone-100 rounded text-xs">CommissionGenerator::regenerateConfigPreview()</code>
            without persisting anything.
          </p>

          <div class="grid gap-4 md:grid-cols-3">
            <FormField label="Sale Amount (RM)">
              <Input v-model="preview.sale_amount" type="number" />
            </FormField>
            <FormField label="Earning Agent ID">
              <Input v-model="preview.earning_agent_id" type="number" />
            </FormField>
            <FormField label="Source Agent ID">
              <Input v-model="preview.source_agent_id" type="number" />
            </FormField>
          </div>

          <div class="flex items-center justify-between">
            <Button
              type="button"
              @click="runPreview"
              :disabled="previewLoading"
            >
              <Loader2 v-if="previewLoading" class="w-4 h-4 mr-2 animate-spin" />
              <Play v-else class="w-4 h-4 mr-2" />
              {{ previewLoading ? 'Calculating...' : 'Run Preview' }}
            </Button>
            <p v-if="previewError" class="text-sm text-red-600">{{ previewError }}</p>
          </div>

          <div v-if="previewRows.length" class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="w-full text-sm">
              <thead class="bg-cream">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Earner Role</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Type</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Rate Source</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Calc Type</th>
                  <th class="px-3 py-2 text-right font-medium text-gray-700">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(r, i) in previewRows" :key="i">
                  <td class="px-3 py-2">{{ r.earner_role || r.beneficiary_role || '—' }}</td>
                  <td class="px-3 py-2">{{ r.commission_type }}</td>
                  <td class="px-3 py-2 text-xs text-gray-500">{{ r.rate_source || '—' }}</td>
                  <td class="px-3 py-2">{{ r.commission_calc_type }}</td>
                  <td class="px-3 py-2 text-right font-medium">{{ formatCurrency('RM', r.amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Warning -->
      <Card class="bg-gradient-to-r from-accent-orange/10 to-accent-red/10 border border-accent-orange/20">
        <CardContent class="pt-6">
          <div class="flex items-start space-x-3">
            <AlertTriangle class="w-5 h-5 text-accent-orange mt-0.5 flex-shrink-0" />
            <div>
              <h3 class="font-semibold text-forest-dark mb-2">Important Notice</h3>
              <p class="text-sm text-gray-700 leading-relaxed">
                Updates take effect for new commissions and renewal cycles created after saving.
                Past commissions remain immutable per Decision 4.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
        <Button variant="outline" as="a" href="/admin/system-settings">Cancel</Button>
        <Button type="submit" :disabled="isSubmitting">
          <Save v-if="!isSubmitting" class="w-4 h-4 mr-2" />
          <Loader2 v-else class="w-4 h-4 mr-2 animate-spin" />
          {{ isSubmitting ? 'Updating...' : 'Update Settings' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
  Percent, Receipt, Users, Clock, AlertTriangle, Save, Loader2, Eye, Play
} from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import CardContent from '../Design/Components/CardContent.vue'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import Select from '../Design/Components/Select.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  settings: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) }
})

const page = usePage()
const sharedSettings = computed(() => page.props.systemSettings || props.settings || {})
const roleNames = computed(() => ({
  agent: sharedSettings.value.role_name_agent || 'Agent',
  leader: sharedSettings.value.role_name_leader || 'Leader',
  business_partner: sharedSettings.value.role_name_business_partner || 'Business Partner',
}))

const commissionRows = computed(() => [
  { key: 'agent_own_sales', label: `${roleNames.value.agent} — Own Sales`, description: 'Earned by an agent on sales they generate.' },
  { key: 'agent_leader_own_sales', label: `${roleNames.value.leader} — Own Sales`, description: 'Earned by a leader on sales they generate directly.' },
  { key: 'agent_leader_override_agent', label: `${roleNames.value.leader} — Override on ${roleNames.value.agent}`, description: 'Override on subordinate agent sales.' },
  { key: 'business_partner_own_sales', label: `${roleNames.value.business_partner} — Own Sales`, description: 'Earned on sales generated directly by the BP.' },
  { key: 'business_partner_override_agent', label: `${roleNames.value.business_partner} — Override on ${roleNames.value.agent}`, description: 'Override on agent sales below the BP.' },
  { key: 'business_partner_override_agent_leader', label: `${roleNames.value.business_partner} — Override on ${roleNames.value.leader}`, description: 'Override on leader sales below the BP.' },
])

const feeRows = [
  { role: 'business_partner', label: 'Business Partner', entryKey: 'entry_fee_business_partner', renewalKey: 'renewal_fee_business_partner' },
  { role: 'leader', label: 'Leader', entryKey: 'entry_fee_leader', renewalKey: 'renewal_fee_leader' },
  { role: 'agent', label: 'Agent', entryKey: 'entry_fee_agent', renewalKey: 'renewal_fee_agent' },
]

const isSubmitting = ref(false)

const num = (v, fallback = 0) => (v === null || v === undefined || v === '' ? fallback : v)

const form = reactive({
  // Commission rates (12 fields + 6 calc_type)
  agent_own_sales_percentage: num(props.settings.agent_own_sales_percentage),
  agent_own_sales_fixed_amount: num(props.settings.agent_own_sales_fixed_amount),
  agent_own_sales_calc_type: props.settings.agent_own_sales_calc_type || 'percentage',

  agent_leader_own_sales_percentage: num(props.settings.agent_leader_own_sales_percentage),
  agent_leader_own_sales_fixed_amount: num(props.settings.agent_leader_own_sales_fixed_amount),
  agent_leader_own_sales_calc_type: props.settings.agent_leader_own_sales_calc_type || 'percentage',

  agent_leader_override_agent_percentage: num(props.settings.agent_leader_override_agent_percentage),
  agent_leader_override_agent_fixed_amount: num(props.settings.agent_leader_override_agent_fixed_amount),
  agent_leader_override_agent_calc_type: props.settings.agent_leader_override_agent_calc_type || 'percentage',

  business_partner_own_sales_percentage: num(props.settings.business_partner_own_sales_percentage),
  business_partner_own_sales_fixed_amount: num(props.settings.business_partner_own_sales_fixed_amount),
  business_partner_own_sales_calc_type: props.settings.business_partner_own_sales_calc_type || 'percentage',

  business_partner_override_agent_percentage: num(props.settings.business_partner_override_agent_percentage),
  business_partner_override_agent_fixed_amount: num(props.settings.business_partner_override_agent_fixed_amount),
  business_partner_override_agent_calc_type: props.settings.business_partner_override_agent_calc_type || 'percentage',

  business_partner_override_agent_leader_percentage: num(props.settings.business_partner_override_agent_leader_percentage),
  business_partner_override_agent_leader_fixed_amount: num(props.settings.business_partner_override_agent_leader_fixed_amount),
  business_partner_override_agent_leader_calc_type: props.settings.business_partner_override_agent_leader_calc_type || 'percentage',

  // Fee config
  entry_fee_business_partner: num(props.settings.entry_fee_business_partner),
  renewal_fee_business_partner: num(props.settings.renewal_fee_business_partner),
  entry_fee_leader: num(props.settings.entry_fee_leader),
  renewal_fee_leader: num(props.settings.renewal_fee_leader),
  entry_fee_agent: num(props.settings.entry_fee_agent),
  renewal_fee_agent: num(props.settings.renewal_fee_agent),

  // Role names
  role_name_agent: props.settings.role_name_agent || 'Agent',
  role_name_leader: props.settings.role_name_leader || 'Leader',
  role_name_business_partner: props.settings.role_name_business_partner || 'Business Partner',

  // Lifecycle & policy
  renewal_reminder_days_before: num(props.settings.renewal_reminder_days_before, 30),
  reversal_time_limit: num(props.settings.reversal_time_limit, 60),
  email_verification_max_retry: num(props.settings.email_verification_max_retry, 10),
  min_payout_amount: num(props.settings.min_payout_amount, 1),
  referral_code_prefix: props.settings.referral_code_prefix || 'PENURWILL-',
  skip_zero_commissions: props.settings.skip_zero_commissions ?? true,
})

// Live preview
const preview = reactive({
  sale_amount: 1000,
  earning_agent_id: null,
  source_agent_id: null,
})
const previewRows = ref([])
const previewLoading = ref(false)
const previewError = ref('')

const runPreview = async () => {
  previewError.value = ''
  previewLoading.value = true
  try {
    const params = new URLSearchParams({
      sale_amount: preview.sale_amount ?? 0,
      earning_agent_id: preview.earning_agent_id ?? '',
      source_agent_id: preview.source_agent_id ?? '',
    })
    const res = await fetch(`/admin/commission-rate-preview/run?${params}`, {
      headers: { Accept: 'application/json' }
    })
    if (!res.ok) {
      throw new Error(`Preview failed (${res.status})`)
    }
    const data = await res.json()
    previewRows.value = data.rows || []
  } catch (e) {
    previewError.value = e.message || 'Preview unavailable.'
    previewRows.value = []
  } finally {
    previewLoading.value = false
  }
}

const submitForm = () => {
  isSubmitting.value = true
  router.put('/admin/system-settings/update', form, {
    onFinish: () => { isSubmitting.value = false },
  })
}
</script>
