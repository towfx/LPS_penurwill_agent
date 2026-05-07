<template>
  <div class="min-h-screen bg-cream font-sans">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <div class="container mx-auto px-4 py-8">
      <div class="max-w-4xl mx-auto">
        <!-- Back to Get Started -->
        <div class="mb-8">
          <a href="/get-started" class="inline-flex items-center text-gold hover:text-amber-700 font-medium transition-colors">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Get Started
          </a>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl lg:text-4xl font-bold text-forest-dark mb-4">
            Register as Agent
          </h1>
          <p class="text-lg text-gray-600">
            Complete your registration to join our network of successful agents
          </p>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
          <div class="flex items-center justify-center flex-wrap gap-y-3">
            <div
              v-for="(step, index) in steps"
              :key="index"
              class="flex items-center"
            >
              <div
                class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors"
                :class="getStepClasses(index)"
              >
                <span class="text-sm font-medium" :class="getStepTextClasses(index)">
                  {{ index + 1 }}
                </span>
              </div>
              <span class="ml-2 text-sm font-medium hidden md:block" :class="getStepLabelClasses(index)">
                {{ step.label }}
              </span>
              <div
                v-if="index < steps.length - 1"
                class="w-8 lg:w-12 h-0.5 mx-2 lg:mx-3 transition-colors"
                :class="getStepLineClasses(index)"
              ></div>
            </div>
          </div>
        </div>

        <!-- Wizard Card -->
        <Card class="p-8">
          <!-- Step 1: Referral ID Check -->
          <div v-if="currentStep === 0" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Referral ID</h2>
              <p class="text-gray-600">Do you have a Referral ID from an existing agent?</p>
            </div>

            <!-- Yes / No -->
            <div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label
                  class="relative cursor-pointer"
                  :class="referral.choice === 'yes' ? 'ring-2 ring-gold' : 'ring-1 ring-gray-300'"
                >
                  <input
                    v-model="referral.choice"
                    @change="resetReferralValidation"
                    type="radio"
                    value="yes"
                    class="sr-only"
                  />
                  <div class="p-4 border rounded-lg transition-all hover:bg-accent-green/5">
                    <div class="flex items-center">
                      <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center">
                        <div v-if="referral.choice === 'yes'" class="w-3 h-3 bg-gold rounded-full"></div>
                      </div>
                      <div>
                        <div class="font-medium text-forest-dark">Yes</div>
                        <div class="text-sm text-gray-500">I have a Referral ID</div>
                      </div>
                    </div>
                  </div>
                </label>

                <label
                  class="relative cursor-pointer"
                  :class="referral.choice === 'no' ? 'ring-2 ring-gold' : 'ring-1 ring-gray-300'"
                >
                  <input
                    v-model="referral.choice"
                    @change="resetReferralValidation"
                    type="radio"
                    value="no"
                    class="sr-only"
                  />
                  <div class="p-4 border rounded-lg transition-all hover:bg-accent-green/5">
                    <div class="flex items-center">
                      <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center">
                        <div v-if="referral.choice === 'no'" class="w-3 h-3 bg-gold rounded-full"></div>
                      </div>
                      <div>
                        <div class="font-medium text-forest-dark">No</div>
                        <div class="text-sm text-gray-500">Continue without an upline</div>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Referral Code Input -->
            <div v-if="referral.choice === 'yes'" class="space-y-3">
              <label class="block text-sm font-medium text-gray-700 mb-2">Referral ID *</label>
              <div class="flex gap-3">
                <input
                  v-model="referral.code"
                  @input="resetReferralValidation"
                  type="text"
                  class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter Referral ID"
                />
                <Button
                  type="button"
                  @click="validateReferralCode"
                  :disabled="!referral.code || referral.validating"
                  class="bg-forest-dark hover:bg-forest-light text-white px-6 py-3 rounded-lg font-medium transition-colors"
                >
                  <span v-if="referral.validating" class="flex items-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Validating
                  </span>
                  <span v-else>Validate</span>
                </Button>
              </div>

              <!-- Valid: green banner -->
              <div
                v-if="referral.status === 'valid'"
                class="p-4 rounded-lg bg-accent-green/10 border border-accent-green flex items-start gap-3"
              >
                <CheckCircle class="w-5 h-5 text-accent-green flex-shrink-0 mt-0.5" />
                <div>
                  <div class="font-medium text-forest-dark">Referral ID verified</div>
                  <div class="text-sm text-gray-600">Referring agent: <span class="font-medium">{{ referral.agentName }}</span></div>
                </div>
              </div>

              <!-- Invalid / Expired: inline error -->
              <div
                v-if="referral.status === 'invalid'"
                class="p-4 rounded-lg bg-accent-red/10 border border-accent-red flex items-start gap-3"
              >
                <AlertCircle class="w-5 h-5 text-accent-red flex-shrink-0 mt-0.5" />
                <div class="text-sm text-accent-red font-medium">{{ referral.errorMessage }}</div>
              </div>
            </div>

            <!-- No upline notice -->
            <div
              v-if="referral.choice === 'no'"
              class="p-4 rounded-lg bg-stone-100 border border-stone-200 text-sm text-gray-700"
            >
              You'll be assigned to the default Business Partner as your upline.
            </div>

            <!-- Navigation -->
            <div class="flex justify-end pt-6">
              <Button
                type="button"
                @click="nextStep"
                :disabled="!canProceedFromStep1"
                class="bg-gold hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition-colors"
              >
                Next
              </Button>
            </div>
          </div>

          <!-- Steps 2-6: Placeholders -->
          <div v-else class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">{{ steps[currentStep].label }}</h2>
              <p class="text-gray-600">This step is not yet implemented.</p>
            </div>

            <div class="p-6 rounded-lg bg-stone-100 border border-dashed border-stone-300 text-center text-gray-500">
              <Construction class="w-10 h-10 mx-auto mb-3 text-gold" />
              <div class="font-medium text-forest-dark mb-1">Coming soon</div>
              <div class="text-sm">Step {{ currentStep + 1 }} — {{ steps[currentStep].label }} will be implemented in a follow-up.</div>
            </div>

            <div class="flex justify-between pt-6">
              <Button
                type="button"
                @click="prevStep"
                variant="outline"
                class="border-gray-300 text-gray-700 hover:bg-gray-50 px-8 py-3 rounded-lg font-medium transition-colors"
              >
                Back
              </Button>
              <Button
                v-if="currentStep < steps.length - 1"
                type="button"
                @click="nextStep"
                class="bg-gold hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition-colors"
              >
                Next
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Invalid Email Dialog -->
    <DialogModal :show="showInvalidEmailDialog" @close="handleDialogClose" :closeable="false">
      <template #title>
        Invalid Email Address
      </template>
      <template #content>
        <p>The email address provided is not in a valid format. Please use a valid email address to register as an agent.</p>
      </template>
      <template #footer>
        <Button @click="handleDialogClose" class="bg-gold hover:bg-amber-700 text-white">
          OK
        </Button>
      </template>
    </DialogModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle, AlertCircle, Construction } from 'lucide-vue-next'

import Card from './Design/Components/Card.vue'
import Button from './Design/Components/Button.vue'
import DialogModal from '@/Components/DialogModal.vue'

const props = defineProps({
  email: { type: String, default: '' },
  invalidEmail: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
})

const currentStep = ref(0)
const showInvalidEmailDialog = ref(false)

const steps = [
  { label: 'Referral ID', key: 'referral' },
  { label: 'Package', key: 'package' },
  { label: 'Your Details', key: 'details' },
  { label: 'Email Verify', key: 'verify' },
  { label: 'T&C + Payment', key: 'payment' },
  { label: 'Done', key: 'done' },
]

const referral = ref({
  choice: '',
  code: '',
  validating: false,
  status: '',
  agentName: '',
  errorMessage: '',
})

const resetReferralValidation = () => {
  referral.value.status = ''
  referral.value.agentName = ''
  referral.value.errorMessage = ''
}

const validateReferralCode = async () => {
  const code = referral.value.code.trim()
  if (!code) return

  referral.value.validating = true
  resetReferralValidation()

  try {
    const response = await fetch(`/api/agents/track/code/${encodeURIComponent(code)}`, {
      headers: { Accept: 'application/json' },
    })
    const json = await response.json().catch(() => ({}))

    if (response.ok && json.success && json.data) {
      referral.value.status = 'valid'
      referral.value.agentName = json.data.agent_name || 'Unknown agent'
    } else {
      referral.value.status = 'invalid'
      referral.value.errorMessage = json.message || 'Referral ID is invalid or expired.'
    }
  } catch (e) {
    referral.value.status = 'invalid'
    referral.value.errorMessage = 'Unable to validate Referral ID. Please try again.'
  } finally {
    referral.value.validating = false
  }
}

const canProceedFromStep1 = computed(() => {
  if (referral.value.choice === 'no') return true
  if (referral.value.choice === 'yes') return referral.value.status === 'valid'
  return false
})

const nextStep = () => {
  if (currentStep.value === 0 && !canProceedFromStep1.value) return
  if (currentStep.value < steps.length - 1) currentStep.value++
}

const prevStep = () => {
  if (currentStep.value > 0) currentStep.value--
}

const handleDialogClose = () => {
  router.visit('/')
}

const getStepClasses = (index) => {
  if (index < currentStep.value) return 'border-gold bg-gold text-white'
  if (index === currentStep.value) return 'border-gold bg-white text-gold'
  return 'border-gray-300 bg-white text-gray-400'
}
const getStepTextClasses = (index) => {
  if (index < currentStep.value) return 'text-white'
  if (index === currentStep.value) return 'text-gold'
  return 'text-gray-400'
}
const getStepLabelClasses = (index) => {
  if (index < currentStep.value) return 'text-gold'
  if (index === currentStep.value) return 'text-forest-dark'
  return 'text-gray-400'
}
const getStepLineClasses = (index) => (index < currentStep.value ? 'bg-gold' : 'bg-gray-300')

onMounted(() => {
  if (props.invalidEmail) {
    showInvalidEmailDialog.value = true
  }
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&display=swap');
.font-sans { font-family: 'Geist', sans-serif !important; }
</style>
