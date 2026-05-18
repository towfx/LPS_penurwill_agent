<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import { CheckCircle, XCircle, Clock, CreditCard, Banknote, Loader2 } from 'lucide-vue-next'
import { useRoleNames } from '../../composables/useRoleNames.js'
import { formatCurrency } from '../../lib/utils.js'

const { roleNames } = useRoleNames()

defineOptions({ layout: AgentLayout })

const props = defineProps({
  agent: { type: Object, default: null },
  status: { type: String, default: 'pending' }, // 'success' | 'cancelled' | 'pending' | 'submitted'
  package: { type: String, default: 'agent' },
  packages: { type: Array, default: () => [] },
  companyBank: { type: Object, default: null },
})

const localStatus = ref(props.status)

const isSuccess = computed(() => localStatus.value === 'success')
const isCancelled = computed(() => localStatus.value === 'cancelled')
const isSubmitted = computed(() => localStatus.value === 'submitted')
const isPending = computed(() => localStatus.value === 'pending')

const selectedPackage = computed(() => props.packages.find(p => p.slug === props.package) || null)
const feeAmount = computed(() => selectedPackage.value?.price ?? 0)
const packageLabel = computed(() => {
  const pkg = selectedPackage.value
  if (!pkg) return ''
  return roleNames.value[pkg.role_name_key] || pkg.slug
})

const payment = ref({ method: 'stripe', receiptFile: null, reference: '' })
const paymentErrors = ref({})
const submitting = ref(false)

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

async function payWithStripe() {
  submitting.value = true
  paymentErrors.value = {}
  try {
    const res = await fetch('/agent/payment/initiate-stripe', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new FormData(),
    })
    const json = await res.json().catch(() => ({}))
    if (json.url) {
      window.location.href = json.url
    } else {
      paymentErrors.value.general = 'Stripe is not configured. Please use manual bank transfer.'
    }
  } catch {
    paymentErrors.value.general = 'Unable to start Stripe checkout. Please try again.'
  } finally {
    submitting.value = false
  }
}

async function submitManualPayment() {
  paymentErrors.value = {}
  if (!payment.value.receiptFile) {
    paymentErrors.value.receipt_file = 'Please upload your transfer receipt'
    return
  }
  submitting.value = true
  try {
    const body = new FormData()
    body.append('receipt_file', payment.value.receiptFile)
    if (payment.value.reference) body.append('reference', payment.value.reference)
    const res = await fetch('/agent/payment/complete', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body,
    })
    if (res.ok) {
      localStatus.value = 'submitted'
    } else {
      const json = await res.json().catch(() => ({}))
      paymentErrors.value.receipt_file =
        json.message || (json.errors?.receipt_file?.[0]) || 'Failed to submit. Please try again.'
    }
  } catch {
    paymentErrors.value.receipt_file = 'Unable to submit. Please check your connection.'
  } finally {
    submitting.value = false
  }
}

function goToDashboard() {
  router.visit('/agent/dashboard')
}

function retryPayment() {
  localStatus.value = 'pending'
}

function onReceiptChange(e) {
  payment.value.receiptFile = e.target.files[0] || null
  if (payment.value.receiptFile) paymentErrors.value.receipt_file = ''
}
</script>

<template>
  <div>
    <PageHeader
      title="Payment"
      :breadcrumbs="[{ label: roleNames.agent, href: '/agent/dashboard' }, { label: 'Complete Payment' }]"
    />

    <div class="max-w-2xl mx-auto">
      <!-- Success State (Stripe) -->
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

      <!-- Submitted State (Manual) -->
      <div v-else-if="isSubmitted" class="bg-white rounded-xl shadow-sm border border-stone-200 p-8 text-center">
        <div class="w-16 h-16 bg-accent-green/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <CheckCircle class="w-8 h-8 text-accent-green" />
        </div>
        <h2 class="text-2xl font-bold text-forest-dark mb-2">Receipt Submitted</h2>
        <p class="text-stone-600 mb-6">
          Your bank transfer receipt has been received. An administrator will verify your payment shortly.
          You will be notified once confirmed.
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
            Choose your payment method below.
          </p>
        </div>

        <!-- Package summary -->
        <div class="bg-cream rounded-lg p-4 border border-stone-200 mb-6">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-stone-500">Registration Package</p>
              <p class="font-semibold text-forest-dark">{{ packageLabel }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-stone-500">Entry Fee</p>
              <p class="text-2xl font-bold text-gold">{{ formatCurrency('RM', feeAmount) }}</p>
            </div>
          </div>
        </div>

        <!-- Payment method -->
        <div class="space-y-4">
          <h3 class="text-sm font-semibold text-forest-dark">How would you like to pay?</h3>

          <!-- Stripe -->
          <label class="cursor-pointer block">
            <div
              class="p-4 border-2 rounded-lg transition-all"
              :class="payment.method === 'stripe' ? 'border-gold bg-gold/5' : 'border-stone-200 hover:border-gold/30'"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                  :class="payment.method === 'stripe' ? 'border-gold' : 'border-stone-300'"
                >
                  <div v-if="payment.method === 'stripe'" class="w-3 h-3 bg-gold rounded-full"></div>
                </div>
                <input v-model="payment.method" type="radio" value="stripe" class="sr-only" />
                <div>
                  <p class="font-medium text-forest-dark">Pay via Card (Stripe)</p>
                  <p class="text-xs text-stone-500">Secure payment via Stripe Checkout</p>
                </div>
                <CreditCard class="w-5 h-5 text-gold ml-auto" />
              </div>
            </div>
          </label>

          <!-- Manual transfer -->
          <label class="cursor-pointer block">
            <div
              class="p-4 border-2 rounded-lg transition-all"
              :class="payment.method === 'manual' ? 'border-gold bg-gold/5' : 'border-stone-200 hover:border-gold/30'"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                  :class="payment.method === 'manual' ? 'border-gold' : 'border-stone-300'"
                >
                  <div v-if="payment.method === 'manual'" class="w-3 h-3 bg-gold rounded-full"></div>
                </div>
                <input v-model="payment.method" type="radio" value="manual" class="sr-only" />
                <div>
                  <p class="font-medium text-forest-dark">Manual Bank Transfer</p>
                  <p class="text-xs text-stone-500">Upload receipt after transferring</p>
                </div>
                <Banknote class="w-5 h-5 text-forest-light ml-auto" />
              </div>
            </div>
          </label>

          <!-- Manual bank details + receipt -->
          <div v-if="payment.method === 'manual'" class="ml-6 space-y-4">
            <div v-if="companyBank" class="p-4 bg-stone-50 rounded-lg border border-stone-200 text-sm space-y-1">
              <p class="font-semibold text-forest-dark mb-2">Bank Transfer Details</p>
              <p><span class="text-stone-500">Bank:</span> <span class="font-medium">{{ companyBank.bank_name }}</span></p>
              <p><span class="text-stone-500">Account Name:</span> <span class="font-medium">{{ companyBank.account_name }}</span></p>
              <p><span class="text-stone-500">Account No:</span> <span class="font-mono font-medium">{{ companyBank.account_number }}</span></p>
            </div>
            <div v-else class="p-4 bg-amber-50 rounded-lg border border-amber-200 text-sm text-amber-800">
              Bank account details are not configured. Please contact your administrator.
            </div>
            <FormField label="Upload Receipt *" :error="paymentErrors.receipt_file">
              <input
                type="file"
                @change="onReceiptChange"
                accept=".pdf,.jpg,.jpeg,.png"
                class="block w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-forest-dark file:text-white file:text-sm hover:file:bg-forest-light"
              />
              <p class="text-xs text-stone-400 mt-1">PDF/JPG/PNG, max 5MB</p>
            </FormField>
            <FormField label="Reference / Note (optional)">
              <Input v-model="payment.reference" placeholder="e.g. transfer reference number" />
            </FormField>
          </div>
        </div>

        <!-- General error -->
        <div v-if="paymentErrors.general" class="mt-4 p-3 rounded-lg bg-accent-red/10 border border-accent-red text-sm text-accent-red">
          {{ paymentErrors.general }}
        </div>

        <!-- Action buttons -->
        <div class="space-y-3 pt-6">
          <div v-if="payment.method === 'stripe'">
            <Button type="button" class="w-full" @click="payWithStripe" :disabled="submitting">
              <span v-if="submitting" class="flex items-center justify-center gap-2">
                <Loader2 class="w-4 h-4 animate-spin" /> Redirecting…
              </span>
              <span v-else class="flex items-center justify-center gap-2">
                <CreditCard class="w-4 h-4" /> Pay with Stripe →
              </span>
            </Button>
          </div>
          <div v-else-if="payment.method === 'manual'">
            <Button
              type="button"
              class="w-full"
              @click="submitManualPayment"
              :disabled="!payment.receiptFile || submitting"
            >
              <span v-if="submitting" class="flex items-center justify-center gap-2">
                <Loader2 class="w-4 h-4 animate-spin" /> Submitting…
              </span>
              <span v-else>Submit Receipt →</span>
            </Button>
          </div>
          <Button variant="ghost" class="w-full" @click="goToDashboard">
            Back to Dashboard
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
