<template>
  <div class="min-h-screen bg-cream font-sans">
    <!-- Google Fonts Import -->
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
          <div class="flex items-center justify-center space-x-4">
            <div
              v-for="(step, index) in steps"
              :key="index"
              class="flex items-center"
            >
              <div
                class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors"
                :class="getStepClasses(index)"
              >
                <span
                  class="text-sm font-medium"
                  :class="getStepTextClasses(index)"
                >
                  {{ index + 1 }}
                </span>
              </div>
              <span
                class="ml-2 text-sm font-medium hidden sm:block"
                :class="getStepLabelClasses(index)"
              >
                {{ step.label }}
              </span>
              <div
                v-if="index < steps.length - 1"
                class="w-16 h-0.5 mx-4 transition-colors"
                :class="getStepLineClasses(index)"
              ></div>
            </div>
          </div>
        </div>

        <!-- Registration Form -->
        <Card class="p-8">
          <!-- Step 1: Agent Info -->
          <div v-if="currentStep === 0" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Agent Information</h2>
              <p class="text-gray-600">Tell us about yourself or your company</p>
            </div>

            <!-- Agent Type Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3">Agent Type *</label>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label
                  class="relative cursor-pointer"
                  :class="form.profile_type === 'individual' ? 'ring-2 ring-gold' : 'ring-1 ring-gray-300'"
                >
                  <input
                    v-model="form.profile_type"
                    type="radio"
                    value="individual"
                    class="sr-only"
                  />
                  <div class="p-4 border rounded-lg transition-all hover:bg-accent-green/5">
                    <div class="flex items-center">
                      <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center">
                        <div
                          v-if="form.profile_type === 'individual'"
                          class="w-3 h-3 bg-gold rounded-full"
                        ></div>
                      </div>
                      <div>
                        <div class="font-medium text-forest-dark">Individual</div>
                        <div class="text-sm text-gray-500">Personal agent account</div>
                      </div>
                    </div>
                  </div>
                </label>

                <label
                  class="relative cursor-pointer"
                  :class="form.profile_type === 'company' ? 'ring-2 ring-gold' : 'ring-1 ring-gray-300'"
                >
                  <input
                    v-model="form.profile_type"
                    type="radio"
                    value="company"
                    class="sr-only"
                  />
                  <div class="p-4 border rounded-lg transition-all hover:bg-accent-green/5">
                    <div class="flex items-center">
                      <div class="w-5 h-5 border-2 rounded-full mr-3 flex items-center justify-center">
                        <div
                          v-if="form.profile_type === 'company'"
                          class="w-3 h-3 bg-gold rounded-full"
                        ></div>
                      </div>
                      <div>
                        <div class="font-medium text-forest-dark">Company</div>
                        <div class="text-sm text-gray-500">Business agent account</div>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
              <p v-if="errors.profile_type" class="text-accent-red text-sm mt-1">{{ errors.profile_type }}</p>
            </div>

            <!-- Individual Fields -->
            <div v-if="form.profile_type === 'individual'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Individual Name *</label>
                <input
                  v-model="form.individual_name"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter your full name"
                />
                <p v-if="errors.individual_name" class="text-accent-red text-sm mt-1">{{ errors.individual_name }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                <input
                  v-model="form.individual_phone"
                  type="tel"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter your phone number"
                />
                <p v-if="errors.individual_phone" class="text-accent-red text-sm mt-1">{{ errors.individual_phone }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                <textarea
                  v-model="form.individual_address"
                  rows="3"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter your address"
                ></textarea>
                <p v-if="errors.individual_address" class="text-accent-red text-sm mt-1">{{ errors.individual_address }}</p>
              </div>
            </div>

            <!-- Company Fields -->
            <div v-if="form.profile_type === 'company'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Representative Name *</label>
                <input
                  v-model="form.company_representative_name"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter representative's full name"
                />
                <p v-if="errors.company_representative_name" class="text-accent-red text-sm mt-1">{{ errors.company_representative_name }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                <input
                  v-model="form.company_name"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter company name"
                />
                <p v-if="errors.company_name" class="text-accent-red text-sm mt-1">{{ errors.company_name }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Registration Number *</label>
                <input
                  v-model="form.company_registration_number"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter registration number"
                />
                <p v-if="errors.company_registration_number" class="text-accent-red text-sm mt-1">{{ errors.company_registration_number }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Address *</label>
                <textarea
                  v-model="form.company_address"
                  rows="3"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter company address"
                ></textarea>
                <p v-if="errors.company_address" class="text-accent-red text-sm mt-1">{{ errors.company_address }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Phone *</label>
                <input
                  v-model="form.company_phone"
                  type="tel"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                  placeholder="Enter company phone number"
                />
                <p v-if="errors.company_phone" class="text-accent-red text-sm mt-1">{{ errors.company_phone }}</p>
              </div>
            </div>

            <!-- Next Button -->
            <div class="flex justify-end pt-6">
              <Button
                type="button"
                @click="nextStep"
                :disabled="!canProceedToNext"
                class="bg-gold hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition-colors"
              >
                Next
              </Button>
            </div>
          </div>

          <!-- Step 2: Login Info -->
          <div v-if="currentStep === 1" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Login Information</h2>
              <p class="text-gray-600">Set up your account credentials</p>
            </div>

            <!-- Email (pre-filled) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
              <input
                v-model="form.email"
                type="email"
                required
                readonly
                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
              />
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Password *
                <span class="text-accent-red">(Minimum 12 characters with numbers and special characters)</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Create a strong password"
                @input="validatePassword"
              />
              <div class="mt-2 space-y-1">
                <div class="flex items-center text-sm">
                  <div
                    class="w-2 h-2 rounded-full mr-2"
                    :class="passwordValidation.length ? 'bg-accent-green' : 'bg-gray-300'"
                  ></div>
                  <span :class="passwordValidation.length ? 'text-accent-green' : 'text-gray-500'">
                    At least 12 characters
                  </span>
                </div>
                <div class="flex items-center text-sm">
                  <div
                    class="w-2 h-2 rounded-full mr-2"
                    :class="passwordValidation.numbers ? 'bg-accent-green' : 'bg-gray-300'"
                  ></div>
                  <span :class="passwordValidation.numbers ? 'text-accent-green' : 'text-gray-500'">
                    Contains numbers (0-9)
                  </span>
                </div>
                <div class="flex items-center text-sm">
                  <div
                    class="w-2 h-2 rounded-full mr-2"
                    :class="passwordValidation.special ? 'bg-accent-green' : 'bg-gray-300'"
                  ></div>
                  <span :class="passwordValidation.special ? 'text-accent-green' : 'text-gray-500'">
                    Contains special characters (!@#$%^&*()_+-=[]{}|;:,.<>?)
                  </span>
                </div>
              </div>
              <p v-if="errors.password" class="text-accent-red text-sm mt-1">{{ errors.password }}</p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Confirm your password"
                @input="validatePassword"
              />
              <p v-if="errors.password_confirmation" class="text-accent-red text-sm mt-1">{{ errors.password_confirmation }}</p>
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start space-x-3">
              <input
                v-model="form.terms"
                type="checkbox"
                required
                class="mt-1 h-4 w-4 text-gold focus:ring-gold border-gray-300 rounded"
              />
              <label class="text-sm text-gray-600">
                I agree to the
                <a href="/terms-of-service" class="text-gold hover:text-amber-700 font-medium">Terms of Service</a>
                and
                <a href="/privacy-policy" class="text-gold hover:text-amber-700 font-medium">Privacy Policy</a>
              </label>
            </div>

            <!-- Navigation Buttons -->
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
                type="button"
                @click="submitApplication"
                :disabled="!canSubmit"
                class="bg-gold hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition-colors"
            >
              <span v-if="isLoading" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                  Submitting...
              </span>
                <span v-else>Submit Agent Application</span>
            </Button>
            </div>
          </div>

          <!-- Step 3: Confirmation -->
          <div v-if="currentStep === 2" class="text-center space-y-6">
            <div class="mb-6">
              <div class="w-16 h-16 bg-accent-green rounded-full flex items-center justify-center mx-auto mb-4">
                <CheckCircle class="w-8 h-8 text-white" />
              </div>
              <h2 class="text-2xl font-bold text-forest-dark mb-2">Application Submitted!</h2>
              <p class="text-gray-600">Your agent application has been successfully submitted</p>
            </div>

            <!-- Agent Profile Summary -->
            <div class="bg-gray-50 rounded-lg p-6 text-left">
              <h3 class="text-lg font-semibold text-forest-dark mb-4">Agent Profile</h3>

              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Agent Type:</span>
                  <span class="font-medium text-forest-dark">
                    {{ form.profile_type === 'individual' ? 'Individual' : 'Company' }}
                  </span>
                </div>

                <div v-if="form.profile_type === 'individual'">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Name:</span>
                    <span class="font-medium text-forest-dark">{{ form.individual_name }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Phone:</span>
                    <span class="font-medium text-forest-dark">{{ form.individual_phone }}</span>
                  </div>
                </div>

                <div v-else>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Company:</span>
                    <span class="font-medium text-forest-dark">{{ form.company_name }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Representative:</span>
                    <span class="font-medium text-forest-dark">{{ form.company_representative_name }}</span>
                  </div>
                </div>

                <div class="flex justify-between">
                  <span class="text-gray-600">Login Email:</span>
                  <span class="font-medium text-forest-dark">{{ form.email }}</span>
                </div>
              </div>
            </div>

            <!-- Login Button -->
            <div class="pt-6">
              <Button
                @click="goToLogin"
                class="bg-gold hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition-colors"
              >
                Login Now
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle } from 'lucide-vue-next'

// Components
import Card from './Design/Components/Card.vue'
import Button from './Design/Components/Button.vue'

// Props
const props = defineProps({
  email: {
    type: String,
    default: ''
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Reactive data
const currentStep = ref(0)
const isLoading = ref(false)

const steps = [
  { label: 'Agent Info', key: 'agent-info' },
  { label: 'Login Info', key: 'login-info' },
  { label: 'Confirmation', key: 'confirmation' }
]

const form = ref({
  email: '',
  profile_type: 'individual',
  individual_name: '',
  individual_phone: '',
  individual_address: '',
  company_representative_name: '',
  company_name: '',
  company_registration_number: '',
  company_address: '',
  company_phone: '',
  password: '',
  password_confirmation: '',
  terms: false
})

const passwordValidation = ref({
  length: false,
  numbers: false,
  special: false
})

// Computed properties
const canProceedToNext = computed(() => {
  if (currentStep.value === 0) {
    if (form.value.profile_type === 'individual') {
      return form.value.individual_name && form.value.individual_phone && form.value.individual_address
    } else {
      return form.value.company_representative_name && form.value.company_name &&
             form.value.company_registration_number && form.value.company_address && form.value.company_phone
    }
  }
  return true
})

const canSubmit = computed(() => {
  return form.value.password &&
         form.value.password_confirmation &&
         form.value.terms &&
         passwordValidation.value.length &&
         passwordValidation.value.numbers &&
         passwordValidation.value.special &&
         form.value.password === form.value.password_confirmation
})

// Methods
const validatePassword = () => {
  const password = form.value.password
  passwordValidation.value = {
    length: password.length >= 12,
    numbers: /\d/.test(password),
    special: /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password)
  }
}

const nextStep = () => {
  if (canProceedToNext.value) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const submitApplication = async () => {
  if (!canSubmit.value) return

  isLoading.value = true

  try {
    await router.post('/register-as-agent', form.value, {
      onSuccess: () => {
        currentStep.value = 2
      },
      onError: (errors) => {
        console.error('Registration errors:', errors)
      }
    })
  } catch (error) {
    console.error('Registration error:', error)
  } finally {
    isLoading.value = false
  }
}

const goToLogin = () => {
  router.visit(`/login?email=${encodeURIComponent(form.value.email)}`)
}

const getStepClasses = (index) => {
  if (index < currentStep.value) {
    return 'border-gold bg-gold text-white'
  } else if (index === currentStep.value) {
    return 'border-gold bg-white text-gold'
  } else {
    return 'border-gray-300 bg-white text-gray-400'
  }
}

const getStepTextClasses = (index) => {
  if (index < currentStep.value) {
    return 'text-white'
  } else if (index === currentStep.value) {
    return 'text-gold'
  } else {
    return 'text-gray-400'
  }
}

const getStepLabelClasses = (index) => {
  if (index < currentStep.value) {
    return 'text-gold'
  } else if (index === currentStep.value) {
    return 'text-forest-dark'
  } else {
    return 'text-gray-400'
  }
}

const getStepLineClasses = (index) => {
  if (index < currentStep.value) {
    return 'bg-gold'
  } else {
    return 'bg-gray-300'
  }
}

// Lifecycle
onMounted(() => {
  // Set email from props or URL params
  const urlParams = new URLSearchParams(window.location.search)
  form.value.email = props.email || urlParams.get('email') || ''
})
</script>

<style scoped>
/* Custom styles for Geist font */
@import url('https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&display=swap');

.font-sans {
  font-family: 'Geist', sans-serif !important;
}
</style>
