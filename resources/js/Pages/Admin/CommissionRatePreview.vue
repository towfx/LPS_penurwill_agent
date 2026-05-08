<template>
  <div class="space-y-6">
    <PageHeader
      title="Commission Rate Preview"
      description="Simulate a sale to see exactly which commission rows the current configuration would generate. No data is persisted — this is a dry run against CommissionGenerator::regenerateConfigPreview()."
      :breadcrumbs="[{ label: 'Dashboard', href: '/admin/dashboard' }, { label: 'System Settings', href: '/admin/system-settings' }, { label: 'Commission Rate Preview' }]"
    />

    <Card class="bg-white shadow-sm border border-gray-200">
      <CardHeader>
        <CardTitle>Hypothetical Sale</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="grid gap-4 md:grid-cols-3">
          <FormField label="Sale Amount (RM)">
            <Input
              v-model="form.sale_amount"
              type="number"
              step="0.01"
              min="0"
            />
          </FormField>
          <FormField label="Earning Agent ID">
            <Input
              v-model="form.earning_agent_id"
              type="number"
              min="1"
              placeholder="Agent earning the commission"
            />
            <p class="text-xs text-gray-500 mt-1">The agent whose hierarchy is walked for overrides.</p>
          </FormField>
          <FormField label="Sale Source Agent ID">
            <Input
              v-model="form.source_agent_id"
              type="number"
              min="1"
              placeholder="Agent who generated the sale"
            />
            <p class="text-xs text-gray-500 mt-1">May equal the earning agent for own sales scenarios.</p>
          </FormField>
        </div>

        <div class="flex items-center justify-between pt-2">
          <Button
            variant="default"
            @click="runPreview"
            :disabled="loading || !canRun"
          >
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            <Play v-else class="w-4 h-4 mr-2" />
            {{ loading ? 'Calculating...' : 'Run Preview' }}
          </Button>
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        </div>
      </CardContent>
    </Card>

    <Card v-if="rows.length || ranOnce" class="bg-white shadow-sm border border-gray-200">
      <CardHeader>
        <CardTitle class="flex items-center justify-between">
          <span>Preview Result</span>
          <span v-if="rows.length" class="text-sm font-normal text-gray-500">
            Total: {{ formatCurrency('RM', total) }}
          </span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="!rows.length" class="py-8 text-center text-sm text-gray-500">
          No commission rows would be generated for this configuration.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-cream">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Earner Role</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Type</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Category</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Rate Source</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Calc Type</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Rate / Fixed</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(r, i) in rows" :key="i">
                <td class="px-4 py-2">{{ r.earner_role || r.beneficiary_role || '—' }}</td>
                <td class="px-4 py-2">
                  <Badge :variant="r.commission_type === 'own_sales' ? 'success' : 'secondary'">
                    {{ r.commission_type }}
                  </Badge>
                </td>
                <td class="px-4 py-2">{{ r.commission_category || '—' }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ r.rate_source || '—' }}</td>
                <td class="px-4 py-2">{{ r.commission_calc_type }}</td>
                <td class="px-4 py-2 text-right">
                  <span v-if="r.commission_calc_type === 'fixed'">
                    {{ formatCurrency('RM', r.commission_fixed_amount ?? r.fixed_amount ?? 0) }}
                  </span>
                  <span v-else>{{ Number(r.applied_rate ?? r.percentage ?? 0).toFixed(2) }}%</span>
                </td>
                <td class="px-4 py-2 text-right font-semibold">
                  {{ formatCurrency('RM', r.amount) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <Card class="bg-cream/40 border border-gold/20">
      <CardContent class="pt-6">
        <div class="flex items-start space-x-3">
          <Info class="w-5 h-5 text-accent-blue mt-0.5 flex-shrink-0" />
          <div class="text-sm text-gray-700 space-y-1">
            <p class="font-semibold text-forest-dark">How this works</p>
            <p>
              The preview walks the configured hierarchy for the earning agent and applies the per-role rates from
              <Link href="/admin/system-settings" class="underline">System Settings</Link>
              and any per-agent overrides in <code class="px-1 py-0.5 bg-stone-100 rounded text-xs">agent_commission_rates</code>.
            </p>
            <p>
              This dry run does not write rows. To make permanent changes, save updated rates from the
              <Link href="/admin/system-settings/update" class="underline">Update Settings</Link> page.
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Loader2, Play, Info } from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import CardContent from '../Design/Components/CardContent.vue'
import Badge from '../Design/Components/Badge.vue'
import Button from '../Design/Components/Button.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AdminLayout })

const form = reactive({
  sale_amount: 1000,
  earning_agent_id: null,
  source_agent_id: null,
})

const rows = ref([])
const ranOnce = ref(false)
const loading = ref(false)
const error = ref('')

const canRun = computed(() =>
  Number(form.sale_amount) > 0 && form.earning_agent_id && form.source_agent_id
)

const total = computed(() =>
  rows.value.reduce((sum, r) => sum + Number(r.amount || 0), 0)
)

const runPreview = async () => {
  error.value = ''
  loading.value = true
  try {
    const params = new URLSearchParams({
      sale_amount: form.sale_amount ?? 0,
      earning_agent_id: form.earning_agent_id ?? '',
      source_agent_id: form.source_agent_id ?? '',
    })
    const res = await fetch(`/admin/commission-rate-preview/run?${params}`, {
      headers: { Accept: 'application/json' },
    })
    if (!res.ok) {
      throw new Error(`Preview failed (${res.status})`)
    }
    const data = await res.json()
    rows.value = data.rows || []
    ranOnce.value = true
  } catch (e) {
    error.value = e.message || 'Preview unavailable.'
    rows.value = []
  } finally {
    loading.value = false
  }
}
</script>
