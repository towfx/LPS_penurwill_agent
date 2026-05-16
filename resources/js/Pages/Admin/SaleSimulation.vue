<template>
  <div class="space-y-6">
    <PageHeader
      title="Sale Simulation"
      description="Dry-run a sale for any active agent to preview every commission row that would be generated — no data is written."
      :breadcrumbs="[{ label: 'Dashboard', href: '/admin/dashboard' }, { label: 'Sale Simulation' }]"
    />

    <Card class="bg-white shadow-sm border border-gray-200">
      <CardHeader>
        <CardTitle>Simulation Parameters</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
          <FormField label="Agent">
            <Select
              v-model="form.agent_id"
              :options="agentOptions"
              placeholder="Select an agent..."
            />
          </FormField>
          <FormField label="Sale Amount (RM)">
            <Input
              v-model="form.sale_amount"
              type="number"
              step="0.01"
              min="0.01"
              placeholder="e.g. 1000.00"
            />
          </FormField>
        </div>

        <div class="flex items-center justify-between pt-2">
          <Button
            variant="default"
            @click="runSimulation"
            :disabled="loading || !canRun"
          >
            <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
            <Play v-else class="w-4 h-4 mr-2" />
            {{ loading ? 'Calculating...' : 'Run Simulation' }}
          </Button>
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        </div>
      </CardContent>
    </Card>

    <Card v-if="rows.length || ranOnce" class="bg-white shadow-sm border border-gray-200">
      <CardHeader>
        <CardTitle class="flex items-center justify-between">
          <span>Commission Breakdown</span>
          <span v-if="rows.length" class="text-sm font-normal text-gray-500">
            Total payout: <span class="font-semibold text-forest-dark">{{ formatCurrency('RM', total) }}</span>
          </span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="!rows.length" class="py-10 text-center text-sm text-gray-500">
          No commission rows would be generated for this configuration.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-cream">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Earner</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Type</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Rate Source</th>
                <th class="px-4 py-2 text-left font-medium text-gray-700">Calc</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Rate / Fixed</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Commission</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(r, i) in rows"
                :key="i"
                :class="r.commission_type === 'own_sales' ? 'bg-white' : 'bg-stone-50'"
              >
                <td class="px-4 py-3">
                  <div class="font-medium text-forest-dark">
                    {{ r.commission_type === 'own_sales' ? selectedAgentName : (r.earner_name || r.beneficiary_role || r.role || '—') }}
                  </div>
                  <div class="text-xs text-gray-500 capitalize">{{ r.role || r.earner_role || '—' }}</div>
                </td>
                <td class="px-4 py-3">
                  <Badge :variant="r.commission_type === 'own_sales' ? 'success' : 'secondary'">
                    {{ r.commission_type === 'own_sales' ? 'Own Sales' : 'Override' }}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  <Badge :variant="r.rate_source === 'agent_rate' ? 'default' : 'outline'" class="text-xs">
                    {{ r.rate_source === 'agent_rate' ? 'Custom Rate' : 'System Default' }}
                  </Badge>
                </td>
                <td class="px-4 py-3 capitalize text-gray-600">
                  {{ r.calc_type || r.commission_calc_type || '—' }}
                </td>
                <td class="px-4 py-3 text-right text-gray-700">
                  <span v-if="(r.calc_type || r.commission_calc_type) === 'fixed'">
                    {{ formatCurrency('RM', r.fixed_amount ?? r.commission_fixed_amount ?? 0) }}
                  </span>
                  <span v-else>
                    {{ Number(r.percentage ?? r.applied_rate ?? 0).toFixed(2) }}%
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-forest-dark">
                  {{ formatCurrency('RM', r.amount) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-200">
              <tr class="bg-cream/60">
                <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-700 text-right">Total Commission</td>
                <td class="px-4 py-3 text-right font-bold text-forest-dark">{{ formatCurrency('RM', total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </CardContent>
    </Card>

    <Card class="bg-cream/40 border border-gold/20">
      <CardContent class="pt-6">
        <div class="flex items-start space-x-3">
          <Info class="w-5 h-5 text-accent-blue mt-0.5 flex-shrink-0" />
          <div class="text-sm text-gray-700 space-y-1">
            <p class="font-semibold text-forest-dark">Dry-run only — nothing is saved</p>
            <p>
              This simulation walks the agent's management chain and applies rates from
              <Link href="/admin/system-settings" class="underline">System Settings</Link>
              and any individual overrides in <code class="px-1 py-0.5 bg-stone-100 rounded text-xs">agent_commission_rates</code>.
              No commission or sale rows are written to the database.
            </p>
            <p>
              "Custom Rate" means an individual override exists for that agent.
              "System Default" means the global role-based rate applies.
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
import Select from '../Design/Components/Select.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  agents: { type: Array, default: () => [] },
})

const agentOptions = computed(() =>
  props.agents.map(a => ({
    value: String(a.id),
    label: `${a.name} (${a.agent_role ?? 'agent'})`,
  }))
)

const selectedAgentName = computed(() => {
  const match = props.agents.find(a => String(a.id) === String(form.agent_id))
  return match ? match.name : '—'
})

const form = reactive({
  agent_id: '',
  sale_amount: 1000,
})

const rows = ref([])
const ranOnce = ref(false)
const loading = ref(false)
const error = ref('')

const canRun = computed(() =>
  form.agent_id && Number(form.sale_amount) > 0
)

const total = computed(() =>
  rows.value.reduce((sum, r) => sum + Number(r.amount || 0), 0)
)

const runSimulation = async () => {
  error.value = ''
  loading.value = true
  try {
    const params = new URLSearchParams({
      agent_id: form.agent_id,
      sale_amount: form.sale_amount ?? 0,
    })
    const res = await fetch(`/admin/sale-simulation/run?${params}`, {
      headers: { Accept: 'application/json' },
    })
    if (!res.ok) {
      const body = await res.json().catch(() => ({}))
      throw new Error(body.message || `Simulation failed (${res.status})`)
    }
    const data = await res.json()
    rows.value = data.rows || []
    ranOnce.value = true
  } catch (e) {
    error.value = e.message || 'Simulation unavailable.'
    rows.value = []
  } finally {
    loading.value = false
  }
}
</script>
