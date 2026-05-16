<template>
  <div class="space-y-6">
    <PageHeader
      title="Pending Fee Renewals"
      description="Agents whose membership fee is unpaid and approaching or past expiry."
      :breadcrumbs="[
        { label: 'Dashboard', href: '/admin/dashboard' },
        { label: 'Fee Payments', href: '/admin/fee-payments' },
        { label: 'Pending Renewals' },
      ]"
    >
      <template #actions>
        <Link href="/admin/fee-payments">
          <Button variant="outline">
            <Receipt class="w-4 h-4 mr-2" />
            View Full History
          </Button>
        </Link>
      </template>
    </PageHeader>

    <!-- Filters -->
    <Card>
      <CardContent class="pt-6">
        <div class="grid gap-4 md:grid-cols-3">
          <FormField label="Search Agent">
            <Input v-model="filters.search" @input="applyFilters" type="text" placeholder="Name or ID" />
          </FormField>
          <FormField label="Role">
            <Select
              v-model="filters.agent_role"
              @change="applyFilters"
              :options="[
                { value: '', label: 'All' },
                { value: 'agent', label: roleNames.agent },
                { value: 'agent_leader', label: roleNames.leader },
                { value: 'business_partner', label: roleNames.business_partner },
              ]"
            />
          </FormField>
          <FormField label="Bucket">
            <Select
              v-model="filters.bucket"
              @change="applyFilters"
              :options="[
                { value: '', label: 'All Pending' },
                { value: 'expired', label: 'Expired' },
                { value: 'alert', label: 'Alert (expires today)' },
                { value: 'due', label: 'In Due Window' },
                { value: 'upcoming', label: 'Upcoming' },
              ]"
            />
          </FormField>
        </div>
      </CardContent>
    </Card>

    <!-- Summary -->
    <div class="grid gap-4 md:grid-cols-4">
      <StatsCard
        title="Expired"
        :value="String(summary.expired || 0)"
        change="Past expires_at"
        icon="AlertCircle"
        trend="down"
      />
      <StatsCard
        title="Alert Today"
        :value="String(summary.alert || 0)"
        change="Expires today"
        icon="Clock"
        trend="down"
      />
      <StatsCard
        title="In Due Window"
        :value="String(summary.due || 0)"
        change="Reminder period"
        icon="Receipt"
        trend="neutral"
      />
      <StatsCard
        title="Upcoming"
        :value="String(summary.upcoming || 0)"
        change="Not yet due"
        icon="CheckCircle"
        trend="up"
      />
    </div>

    <!-- Table -->
    <Card>
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-cream">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Agent</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Role</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Registered</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Renewal Due</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Expires</th>
                <th class="px-4 py-3 text-right font-medium text-gray-700">Days</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                <th class="px-4 py-3 text-right font-medium text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-if="agents.length === 0">
                <td colspan="8" class="p-0">
                  <EmptyState
                    title="No pending renewals"
                    description="Every agent matching your filters has paid their fee."
                    icon="Inbox"
                  />
                </td>
              </tr>
              <tr v-for="a in agents" :key="a.id" class="hover:bg-stone-50">
                <td class="px-4 py-3">
                  <a :href="`/admin/agents/${a.id}/view`" class="text-gold hover:text-amber-700 font-medium">
                    {{ a.name || `#${a.id}` }}
                  </a>
                  <div v-if="a.email" class="text-xs text-gray-500">{{ a.email }}</div>
                </td>
                <td class="px-4 py-3">{{ roleLabel(a.agent_role) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(a.registered_at) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(a.renewal_due_at) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(a.expires_at) }}</td>
                <td class="px-4 py-3 text-right font-medium" :class="daysClass(a.expires_at)">
                  {{ daysLabel(a.expires_at) }}
                </td>
                <td class="px-4 py-3">
                  <StatusBadge :status="statusForBucket(a.bucket)" :label="bucketLabel(a.bucket)" />
                </td>
                <td class="px-4 py-3 text-right">
                  <Button size="sm" @click="openRenewModal(a)">
                    <Plus class="w-3 h-3 mr-1" />
                    Record Renewal
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination && pagination.total > 0" class="px-6 py-4 border-t border-gray-200">
          <Pagination
            :current-page="pagination.current_page"
            :per-page="50"
            :total="pagination.total"
            @change="goToPage"
          />
        </div>
      </CardContent>
    </Card>

    <!-- Record Renewal Modal -->
    <Modal v-model="showRenewModal" title="Record Renewal Fee" :description="modalDescription">
      <form @submit.prevent="submitRenewal" class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <FormField label="Role" required>
            <Select
              v-model="renewFee.role"
              :options="[
                { value: 'agent', label: roleNames.agent },
                { value: 'agent_leader', label: roleNames.leader },
                { value: 'business_partner', label: roleNames.business_partner },
              ]"
            />
          </FormField>
          <FormField label="Payment Method" required>
            <Select
              v-model="renewFee.payment_method"
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
          <Input v-model="renewFee.payment_reference" type="text" placeholder="Optional reference" />
        </FormField>
        <div class="flex justify-end gap-3 pt-2">
          <Button type="button" variant="outline" @click="showRenewModal = false">Cancel</Button>
          <Button type="submit" :disabled="submitting">
            {{ submitting ? 'Saving...' : 'Confirm Renewal' }}
          </Button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Plus, Receipt } from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardContent from '../Design/Components/CardContent.vue'
import StatsCard from '../Design/Components/StatsCard.vue'
import StatusBadge from '../Design/Components/StatusBadge.vue'
import EmptyState from '../Design/Components/EmptyState.vue'
import Pagination from '../Design/Components/Pagination.vue'
import Modal from '../Design/Components/Modal.vue'
import AdminLayout from '../Design/AdminLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import Select from '../Design/Components/Select.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  agents: { type: Array, default: () => [] },
  pagination: { type: Object, default: null },
  summary: { type: Object, default: () => ({ expired: 0, alert: 0, due: 0, upcoming: 0 }) },
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
  agent_role: props.filters?.agent_role || '',
  bucket: props.filters?.bucket || '',
})

let filterTimer = null
const applyFilters = () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    router.get('/admin/fee-payments-pending', filters, { preserveState: true, preserveScroll: true })
  }, 300)
}

const goToPage = (p) => {
  router.get('/admin/fee-payments-pending', { ...filters, page: p }, { preserveState: true, preserveScroll: true })
}

const formatDate = (s) => {
  if (!s) return '—'
  return new Date(s).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const daysFromExpiry = (expiresAt) => {
  if (!expiresAt) return null
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const exp = new Date(expiresAt)
  exp.setHours(0, 0, 0, 0)
  return Math.round((exp.getTime() - today.getTime()) / 86400000)
}

const daysLabel = (expiresAt) => {
  const d = daysFromExpiry(expiresAt)
  if (d === null) return '—'
  if (d === 0) return 'today'
  if (d < 0) return `${Math.abs(d)}d ago`
  return `in ${d}d`
}

const daysClass = (expiresAt) => {
  const d = daysFromExpiry(expiresAt)
  if (d === null) return 'text-gray-400'
  if (d < 0) return 'text-accent-red'
  if (d === 0) return 'text-accent-orange'
  if (d <= 7) return 'text-gold'
  return 'text-forest-dark'
}

const bucketLabel = (b) => ({
  expired: 'Expired',
  alert: 'Alert Today',
  due: 'In Due Window',
  upcoming: 'Upcoming',
}[b] || b)

const statusForBucket = (b) => ({
  expired: 'expired',
  alert: 'pending',
  due: 'processing',
  upcoming: 'inactive',
}[b] || 'pending')

const showRenewModal = ref(false)
const submitting = ref(false)
const renewFee = reactive({
  agent_id: '',
  agent_name: '',
  fee_type: 'renewal',
  role: 'agent',
  payment_method: 'manual',
  payment_reference: '',
})

const modalDescription = computed(() =>
  renewFee.agent_name ? `for ${renewFee.agent_name} (#${renewFee.agent_id})` : ''
)

const openRenewModal = (agent) => {
  renewFee.agent_id = agent.id
  renewFee.agent_name = agent.name || ''
  renewFee.role = agent.agent_role || 'agent'
  renewFee.payment_method = 'manual'
  renewFee.payment_reference = ''
  showRenewModal.value = true
}

const submitRenewal = () => {
  submitting.value = true
  router.post('/admin/fee-payments', {
    agent_id: renewFee.agent_id,
    fee_type: 'renewal',
    role: renewFee.role,
    payment_method: renewFee.payment_method,
    payment_reference: renewFee.payment_reference,
  }, {
    preserveScroll: true,
    onSuccess: () => { showRenewModal.value = false },
    onFinish: () => { submitting.value = false },
  })
}
</script>
