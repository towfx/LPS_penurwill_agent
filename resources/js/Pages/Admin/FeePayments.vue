<template>
  <div class="space-y-6">
    <PageHeader
      title="Fee Payments"
      description="Full history of entry &amp; renewal fee events recorded against agents."
      :breadcrumbs="[{ label: 'Dashboard', href: '/admin/dashboard' }, { label: 'Fee Payments' }]"
    >
      <template #actions>
        <Button @click="showAddModal = true">
          <Plus class="w-4 h-4 mr-2" />
          Record Fee Payment
        </Button>
      </template>
    </PageHeader>

    <!-- Filters -->
    <Card class="bg-white shadow-sm border border-gray-200">
      <CardContent class="pt-6">
        <div class="grid gap-4 md:grid-cols-4">
          <FormField label="Search Agent">
            <Input v-model="filters.search" @input="applyFilters" type="text" placeholder="Name or ID" />
          </FormField>
          <FormField label="Fee Type">
            <Select
              v-model="filters.fee_type"
              @change="applyFilters"
              :options="[{ value: '', label: 'All' }, { value: 'entry', label: 'Entry' }, { value: 'renewal', label: 'Renewal' }]"
            />
          </FormField>
          <FormField label="Role">
            <Select
              v-model="filters.role"
              @change="applyFilters"
              :options="[
                { value: '', label: 'All' },
                { value: 'agent', label: roleNames.agent },
                { value: 'agent_leader', label: roleNames.leader },
                { value: 'business_partner', label: roleNames.business_partner },
              ]"
            />
          </FormField>
          <FormField label="Payment Method">
            <Select
              v-model="filters.payment_method"
              @change="applyFilters"
              :options="[
                { value: '', label: 'All' },
                { value: 'stripe', label: 'Stripe' },
                { value: 'bank_transfer', label: 'Bank Transfer' },
                { value: 'manual', label: 'Manual' },
                { value: 'waived', label: 'Waived' },
              ]"
            />
          </FormField>
        </div>
      </CardContent>
    </Card>

    <!-- Summary -->
    <div class="grid gap-4 md:grid-cols-3">
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardContent class="pt-6">
          <p class="text-sm text-gray-500">Total Recorded</p>
          <p class="text-2xl font-bold text-forest-dark">{{ summary.count || 0 }}</p>
        </CardContent>
      </Card>
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardContent class="pt-6">
          <p class="text-sm text-gray-500">Total Amount</p>
          <p class="text-2xl font-bold text-forest-dark">{{ formatCurrency('RM', summary.total_amount || 0) }}</p>
        </CardContent>
      </Card>
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardContent class="pt-6">
          <p class="text-sm text-gray-500">Renewal Count</p>
          <p class="text-2xl font-bold text-forest-dark">{{ summary.renewal_count || 0 }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Table -->
    <Card class="bg-white shadow-sm border border-gray-200">
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-cream">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Date</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Agent</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Role</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Fee Type</th>
                <th class="px-4 py-3 text-right font-medium text-gray-700">Amount</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Method</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Reference</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Recorded By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-if="payments.length === 0">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No fee payments recorded.</td>
              </tr>
              <tr v-for="p in payments" :key="p.id" class="hover:bg-stone-50">
                <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(p.paid_at || p.created_at) }}</td>
                <td class="px-4 py-3">
                  <a v-if="p.agent" :href="`/admin/agents/${p.agent.id}/view`" class="text-gold hover:text-amber-700">
                    {{ p.agent.name || `#${p.agent.id}` }}
                  </a>
                  <span v-else class="text-gray-500">—</span>
                </td>
                <td class="px-4 py-3">{{ roleLabel(p.role) }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="p.fee_type === 'entry' ? 'success' : 'secondary'">
                    {{ p.fee_type }}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-right font-medium">{{ formatCurrency('RM', p.amount) }}</td>
                <td class="px-4 py-3">
                  <Badge :variant="methodVariant(p.payment_method)">{{ p.payment_method }}</Badge>
                </td>
                <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ p.payment_reference || '—' }}</td>
                <td class="px-4 py-3">{{ p.recorded_by?.name || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }}
          </div>
          <div class="flex items-center space-x-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="pagination.current_page === 1"
              @click="goToPage(pagination.current_page - 1)"
            >Previous</Button>
            <span class="text-sm">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <Button
              variant="outline"
              size="sm"
              :disabled="pagination.current_page === pagination.last_page"
              @click="goToPage(pagination.current_page + 1)"
            >Next</Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Record Fee Modal -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showAddModal = false"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Record Fee Payment</h3>
        <form @submit.prevent="submitFee" class="space-y-4">
          <FormField label="Agent ID" required>
            <Input v-model="newFee.agent_id" type="number" />
          </FormField>
          <div class="grid grid-cols-2 gap-3">
            <FormField label="Fee Type" required>
              <Select
                v-model="newFee.fee_type"
                :options="[{ value: 'entry', label: 'Entry' }, { value: 'renewal', label: 'Renewal' }]"
              />
            </FormField>
            <FormField label="Role" required>
              <Select
                v-model="newFee.role"
                :options="[
                  { value: 'agent', label: roleNames.agent },
                  { value: 'agent_leader', label: roleNames.leader },
                  { value: 'business_partner', label: roleNames.business_partner },
                ]"
              />
            </FormField>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <FormField label="Amount (RM)" required>
              <Input v-model="newFee.amount" type="number" />
            </FormField>
            <FormField label="Payment Method" required>
              <Select
                v-model="newFee.payment_method"
                :options="[
                  { value: 'manual', label: 'Manual' },
                  { value: 'bank_transfer', label: 'Bank Transfer' },
                  { value: 'stripe', label: 'Stripe' },
                  { value: 'waived', label: 'Waived' },
                ]"
              />
            </FormField>
          </div>
          <FormField label="Reference">
            <Input v-model="newFee.payment_reference" type="text" placeholder="Optional reference (e.g. Stripe session id)" />
          </FormField>
          <FormField label="Paid At">
            <Input v-model="newFee.paid_at" type="date" />
          </FormField>
          <div class="flex justify-end gap-3 pt-2">
            <Button type="button" variant="outline" @click="showAddModal = false">Cancel</Button>
            <Button type="submit" :disabled="submitting">
              {{ submitting ? 'Saving...' : 'Save' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardContent from '../Design/Components/CardContent.vue'
import Badge from '../Design/Components/Badge.vue'
import AdminLayout from '../Design/AdminLayout.vue'
import { formatCurrency } from '../../lib/utils.js'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import Select from '../Design/Components/Select.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  payments: { type: Array, default: () => [] },
  pagination: { type: Object, default: null },
  summary: { type: Object, default: () => ({ count: 0, total_amount: 0, renewal_count: 0 }) },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))

const roleLabel = (role) => {
  if (!role) return '—'
  const map = {
    agent: roleNames.value.agent,
    agent_leader: roleNames.value.leader,
    business_partner: roleNames.value.business_partner,
  }
  return map[role] || role
}

const filters = reactive({
  search: props.filters?.search || '',
  fee_type: props.filters?.fee_type || '',
  role: props.filters?.role || '',
  payment_method: props.filters?.payment_method || '',
})

let filterTimer = null
const applyFilters = () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    router.get('/admin/fee-payments', filters, { preserveState: true, preserveScroll: true })
  }, 300)
}

const goToPage = (p) => {
  router.get('/admin/fee-payments', { ...filters, page: p }, { preserveState: true, preserveScroll: true })
}

const formatDate = (s) => {
  if (!s) return '—'
  return new Date(s).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const methodVariant = (m) => {
  switch (m) {
    case 'stripe': return 'success'
    case 'bank_transfer': return 'default'
    case 'waived': return 'warning'
    default: return 'secondary'
  }
}

const showAddModal = ref(false)
const submitting = ref(false)
const newFee = reactive({
  agent_id: '',
  fee_type: 'entry',
  role: 'agent',
  amount: '',
  payment_method: 'manual',
  payment_reference: '',
  paid_at: new Date().toISOString().slice(0, 10),
})

const submitFee = () => {
  submitting.value = true
  router.post('/admin/fee-payments', { ...newFee }, {
    onSuccess: () => {
      showAddModal.value = false
      Object.assign(newFee, {
        agent_id: '',
        fee_type: 'entry',
        role: 'agent',
        amount: '',
        payment_method: 'manual',
        payment_reference: '',
        paid_at: new Date().toISOString().slice(0, 10),
      })
    },
    onFinish: () => { submitting.value = false },
  })
}
</script>
