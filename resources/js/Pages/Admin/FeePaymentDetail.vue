<template>
  <div class="space-y-6">
    <PageHeader
      title="Fee Payment Details"
      :breadcrumbs="[
        { label: 'Admin', href: '/admin/dashboard' },
        { label: 'Fee Payments', href: '/admin/fee-payments' },
        { label: 'View Payment' }
      ]"
    >
      <template #actions>
        <Button variant="outline" @click="goBack">
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back to List
        </Button>
        <template v-if="payment.status === 'pending'">
          <Button variant="default" @click="showConfirmModal = true" :disabled="processing" class="bg-forest hover:bg-forest-dark">
            <Check class="w-4 h-4 mr-2" />
            Confirm Payment
          </Button>
          <Button variant="destructive" @click="showVoidModal = true" :disabled="processing">
            <XCircle class="w-4 h-4 mr-2" />
            Void Payment
          </Button>
        </template>
      </template>
    </PageHeader>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Payment info -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardContent class="pt-6">
          <h3 class="text-lg font-semibold text-forest-dark mb-4">Payment Summary</h3>
          <div class="space-y-3">
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Payment ID</span>
              <span class="font-mono font-medium">#{{ payment.id }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Fee Type</span>
              <Badge :variant="payment.fee_type === 'entry' ? 'success' : 'secondary'">{{ payment.fee_type }}</Badge>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Amount</span>
              <span class="font-bold text-lg text-forest-dark">{{ formatCurrency('RM', payment.amount) }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Method</span>
              <Badge :variant="methodVariant(payment.payment_method)">{{ payment.payment_method }}</Badge>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Status</span>
              <Badge :variant="statusVariant(payment.status)">{{ payment.status }}</Badge>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Reference</span>
              <span class="font-mono text-xs text-gray-600">{{ payment.payment_reference || '—' }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Paid At</span>
              <span class="text-gray-700">{{ formatDate(payment.paid_at) }}</span>
            </div>
             <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Recorded By</span>
              <span class="text-gray-700">{{ payment.recorded_by?.name || '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Created At</span>
              <span class="text-gray-700">{{ formatDate(payment.created_at) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Agent info -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardContent class="pt-6">
          <h3 class="text-lg font-semibold text-forest-dark mb-4">Agent Details</h3>
          <div v-if="payment.agent" class="space-y-3">
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Agent Name</span>
              <Link :href="`/admin/agents/${payment.agent.id}/view`" class="text-gold hover:text-amber-700 font-medium">
                {{ payment.agent.name || `#${payment.agent.id}` }}
              </Link>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Role during payment</span>
              <span class="text-gray-700">{{ roleLabel(payment.role) }}</span>
            </div>
             <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Current Agent Status</span>
              <Badge :variant="agentStatusVariant(payment.agent.status)">{{ payment.agent.status }}</Badge>
            </div>
             <div class="flex justify-between border-b border-gray-100 pb-2">
              <span class="text-gray-500">Membership Expiry</span>
              <span class="text-gray-700 font-medium">{{ payment.agent.expires_at || '—' }}</span>
            </div>
            <div class="pt-2">
              <Button variant="outline" size="sm" @click="router.visit(`/admin/agents/${payment.agent.id}/view`)">
                View Full Agent Profile
              </Button>
            </div>
          </div>
          <div v-else class="text-gray-500">Agent record not found.</div>
        </CardContent>
      </Card>

      <!-- Receipt -->
      <Card v-if="payment.receipt_file" class="bg-white shadow-sm border border-gray-200 md:col-span-2">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-forest-dark">Payment Receipt</h3>
            <a
              :href="`/admin/agents/${payment.agent_id}/file/receipt_file`"
              target="_blank"
              class="text-gold hover:text-amber-700 font-medium inline-flex items-center text-sm"
            >
              <ExternalLink class="w-4 h-4 mr-1" />
              Full Screen
            </a>
          </div>
          
          <div class="aspect-[1/1.4] md:aspect-auto md:h-[600px] w-full bg-stone-50 rounded-lg overflow-hidden border border-gray-100">
            <iframe
              v-if="payment.receipt_file.toLowerCase().endsWith('.pdf')"
              :src="`/admin/agents/${payment.agent_id}/file/receipt_file`"
              class="w-full h-full"
            ></iframe>
            <div v-else class="w-full h-full flex items-center justify-center p-4">
              <img
                :src="`/admin/agents/${payment.agent_id}/file/receipt_file`"
                class="max-w-full max-h-full object-contain shadow-md rounded"
                alt="Receipt"
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Confirm Modal -->
    <Modal :show="showConfirmModal" max-width="md" @close="showConfirmModal = false">
      <div class="p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
            <Check class="w-6 h-6" />
          </div>
          <h3 class="text-lg font-semibold text-gray-900">Confirm Payment?</h3>
        </div>
        <p class="text-gray-600 mb-6">
          Are you sure you want to confirm this payment? This will activate the agent's membership and update their expiry dates.
        </p>
        <div class="flex justify-end gap-3">
          <Button variant="outline" @click="showConfirmModal = false">Cancel</Button>
          <Button variant="default" @click="handleConfirm" :disabled="processing" class="bg-green-600 hover:bg-green-700">
            {{ processing ? 'Processing...' : 'Confirm' }}
          </Button>
        </div>
      </div>
    </Modal>

    <!-- Void Modal -->
    <Modal :show="showVoidModal" max-width="md" @close="showVoidModal = false">
      <div class="p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
            <AlertTriangle class="w-6 h-6" />
          </div>
          <h3 class="text-lg font-semibold text-gray-900">Void Payment?</h3>
        </div>
        <p class="text-gray-600 mb-6">
          This will mark the payment as invalid. This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
          <Button variant="outline" @click="showVoidModal = false">Cancel</Button>
          <Button variant="destructive" @click="handleVoid" :disabled="processing">
            {{ processing ? 'Processing...' : 'Void Payment' }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { ArrowLeft, Check, XCircle, ExternalLink, AlertTriangle } from 'lucide-vue-next'
import AdminLayout from '../Design/AdminLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import Card from '../Design/Components/Card.vue'
import CardContent from '../Design/Components/CardContent.vue'
import Badge from '../Design/Components/Badge.vue'
import Modal from '../../Components/Modal.vue'
import { formatCurrency } from '../../lib/utils.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  payment: { type: Object, required: true },
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

const formatDate = (s) => {
  if (!s) return '—'
  return new Date(s).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const methodVariant = (m) => {
  switch (m) {
    case 'stripe': return 'success'
    case 'bank_transfer': return 'default'
    case 'waived': return 'warning'
    default: return 'secondary'
  }
}

const statusVariant = (s) => {
  switch (s) {
    case 'confirmed': return 'success'
    case 'pending': return 'warning'
    case 'void': return 'danger'
    default: return 'secondary'
  }
}

const agentStatusVariant = (s) => {
  switch (s) {
    case 'active': return 'success'
    case 'pending': return 'warning'
    case 'rejected': return 'danger'
    case 'suspended': return 'warning'
    default: return 'secondary'
  }
}

const processing = ref(false)
const showConfirmModal = ref(false)
const showVoidModal = ref(false)

const goBack = () => {
  router.visit('/admin/fee-payments')
}

const handleConfirm = () => {
  processing.value = true
  router.post(`/admin/fee-payments/${props.payment.id}/confirm`, {}, {
    onSuccess: () => { showConfirmModal.value = false },
    onFinish: () => { processing.value = false }
  })
}

const handleVoid = () => {
  processing.value = true
  router.post(`/admin/fee-payments/${props.payment.id}/void`, {}, {
    onSuccess: () => { showVoidModal.value = false },
    onFinish: () => { processing.value = false }
  })
}
</script>
