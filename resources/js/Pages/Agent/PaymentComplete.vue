<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import { CheckCircle, XCircle, Clock, CreditCard } from 'lucide-vue-next'
import { useRoleNames } from '../../composables/useRoleNames.js'

const { roleNames } = useRoleNames()

defineOptions({ layout: AgentLayout })

const props = defineProps({
  agent: { type: Object, default: null },
  status: { type: String, default: 'pending' }, // 'success' | 'cancelled' | 'pending'
})

const isSuccess = computed(() => props.status === 'success')
const isCancelled = computed(() => props.status === 'cancelled')
const isPending = computed(() => props.status === 'pending')

function goToDashboard() {
  router.visit('/agent/dashboard')
}

function retryPayment() {
  router.visit('/agent/payment/complete')
}
</script>

<template>
  <div>
    <PageHeader
      title="Payment"
      :breadcrumbs="[{ label: roleNames.agent, href: '/agent/dashboard' }, { label: 'Complete Payment' }]"
    />

    <div class="max-w-lg mx-auto">
      <!-- Success State -->
      <div v-if="isSuccess" class="bg-white rounded-xl shadow-sm border border-stone-200 p-8 text-center">
        <div class="w-16 h-16 bg-accent-green/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <CheckCircle class="w-8 h-8 text-accent-green" />
        </div>
        <h2 class="text-2xl font-bold text-forest-dark mb-2">Payment Successful!</h2>
        <p class="text-stone-600 mb-6">
          Your registration fee has been received. Your account is now pending admin approval.
          You will be notified once approved.
        </p>
        <Button variant="default" @click="goToDashboard">Go to Dashboard</Button>
      </div>

      <!-- Cancelled State -->
      <div v-else-if="isCancelled" class="bg-white rounded-xl shadow-sm border border-stone-200 p-8 text-center">
        <div class="w-16 h-16 bg-accent-red/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <XCircle class="w-8 h-8 text-accent-red" />
        </div>
        <h2 class="text-2xl font-bold text-forest-dark mb-2">Payment Cancelled</h2>
        <p class="text-stone-600 mb-6">
          Your payment was not completed. You can try again below or pay manually via bank transfer.
        </p>
        <div class="flex flex-col gap-3">
          <Button variant="default" @click="retryPayment">
            <CreditCard class="w-4 h-4 mr-2" /> Try Again
          </Button>
          <Button variant="outline" @click="goToDashboard">Back to Dashboard</Button>
        </div>
      </div>

      <!-- Pending / Default State -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-stone-200 p-8">
        <div class="text-center mb-6">
          <div class="w-16 h-16 bg-gold/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <Clock class="w-8 h-8 text-gold" />
          </div>
          <h2 class="text-2xl font-bold text-forest-dark mb-2">Complete Your Registration Fee</h2>
          <p class="text-stone-600">
            Your account requires a registration fee payment to be fully activated.
            Pay via Stripe (card) or arrange a manual bank transfer.
          </p>
        </div>

        <div class="border border-stone-200 rounded-lg p-5 mb-5 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-stone-500">Agent:</span>
            <span class="font-medium text-forest-dark">
              {{ agent?.individual_name || agent?.company_name || '—' }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-stone-500">Status:</span>
            <span class="font-medium text-amber-600 capitalize">
              {{ agent?.fee_payment_status || 'pending' }}
            </span>
          </div>
        </div>

        <div class="space-y-3">
          <Button variant="default" class="w-full" @click="retryPayment">
            <CreditCard class="w-4 h-4 mr-2" /> Pay by Card (Stripe)
          </Button>
          <p class="text-xs text-stone-500 text-center">
            Or contact your administrator to arrange manual payment.
          </p>
          <Button variant="ghost" class="w-full" @click="goToDashboard">
            Back to Dashboard
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
