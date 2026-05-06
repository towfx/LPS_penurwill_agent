<template>
  <div class="space-y-6">
    <nav class="flex items-center space-x-2 text-sm text-gray-600">
      <Link href="/admin/dashboard" class="hover:text-forest-dark transition-colors">Dashboard</Link>
      <span class="text-gray-400">/</span>
      <span class="text-forest-dark font-medium">Fee Payments</span>
    </nav>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-forest-dark">Fee Payments</h1>
        <p class="text-gray-600 mt-2">
          Full history of entry &amp; renewal fee events recorded against agents.
        </p>
      </div>
      <button
        @click="showAddModal = true"
        class="inline-flex items-center px-4 py-2 bg-gold text-forest-dark font-medium rounded-lg hover:bg-gold/90 transition-colors"
      >
        <Plus class="w-4 h-4 mr-2" />
        Record Fee Payment
      </button>
    </div>

    <!-- Filters -->
    <Card class="bg-white shadow-sm border border-gray-200">
      <CardContent class="pt-6">
        <div class="grid gap-4 md:grid-cols-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Search Agent</label>
            <input
              v-model="filters.search"
              @input="applyFilters"
              type="text"
              placeholder="Name or ID"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Fee Type</label>
            <select
              v-model="filters.fee_type"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold text-sm"
            >
              <option value="">All</option>
              <option value="entry">Entry</option>
              <option value="renewal">Renewal</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
            <select
              v-model="filters.role"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold text-sm"
            >
              <option value="">All</option>
              <option value="agent">{{ roleNames.agent }}</option>
              <option value="agent_leader">{{ roleNames.leader }}</option>
              <option value="business_partner">{{ roleNames.business_partner }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Payment Method</label>
            <select
              v-model="filters.payment_method"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold text-sm"
            >
              <option value="">All</option>
              <option value="stripe">Stripe</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="manual">Manual</option>
              <option value="waived">Waived</option>
            </select>
          </div>
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
            <button
              :disabled="pagination.current_page === 1"
              @click="goToPage(pagination.current_page - 1)"
              class="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50"
            >
              Previous
            </button>
            <span class="text-sm">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button
              :disabled="pagination.current_page === pagination.last_page"
              @click="goToPage(pagination.current_page + 1)"
              class="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50"
            >
              Next
            </button>
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
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Agent ID *</label>
            <input
              v-model="newFee.agent_id"
              type="number"
              min="1"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Fee Type *</label>
              <select v-model="newFee.fee_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="entry">Entry</option>
                <option value="renewal">Renewal</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
              <select v-model="newFee.role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="agent">{{ roleNames.agent }}</option>
                <option value="agent_leader">{{ roleNames.leader }}</option>
                <option value="business_partner">{{ roleNames.business_partner }}</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount (RM) *</label>
              <input
                v-model="newFee.amount"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
              <select v-model="newFee.payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="manual">Manual</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="stripe">Stripe</option>
                <option value="waived">Waived</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
            <input
              v-model="newFee.payment_reference"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              placeholder="Optional reference (e.g. Stripe session id)"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Paid At</label>
            <input
              v-model="newFee.paid_at"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
            />
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="showAddModal = false"
              class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 text-sm bg-gold text-forest-dark font-medium rounded-lg hover:bg-gold/90 disabled:opacity-50"
            >
              {{ submitting ? 'Saving...' : 'Save' }}
            </button>
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
