<template>
  <div class="min-h-screen bg-cream font-sans">
    <!-- Google Fonts Import -->
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <!-- Back to Get Started -->
        <div class="mb-8">
          <a href="/get-started" class="inline-flex items-center text-gold hover:text-amber-700 font-medium">
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

        <!-- Registration Form -->
        <Card class="p-8">
          <form @submit.prevent="handleRegister" class="space-y-6">
            <!-- Email (pre-filled) -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Email Address
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                readonly
                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
              />
            </div>

            <!-- First Name -->
            <div>
              <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                First Name
              </label>
              <input
                id="first_name"
                v-model="form.first_name"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Enter your first name"
              />
            </div>

            <!-- Last Name -->
            <div>
              <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                Last Name
              </label>
              <input
                id="last_name"
                v-model="form.last_name"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Enter your last name"
              />
            </div>



            <!-- Password -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Password
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Create a strong password"
              />
            </div>

            <!-- Confirm Password -->
            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                Confirm Password
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Confirm your password"
              />
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start space-x-3">
              <input
                id="terms"
                v-model="form.terms"
                type="checkbox"
                required
                class="mt-1 h-4 w-4 text-gold focus:ring-gold border-gray-300 rounded"
              />
              <label for="terms" class="text-sm text-gray-600">
                I agree to the
                <a href="/terms-of-service" class="text-gold hover:text-amber-700 font-medium">Terms of Service</a>
                and
                <a href="/privacy-policy" class="text-gold hover:text-amber-700 font-medium">Privacy Policy</a>
              </label>
            </div>

            <!-- Submit Button -->
            <Button
              type="submit"
              variant="secondary"
              size="lg"
              class="w-full"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Creating Account...
              </span>
              <span v-else>Create Agent Account</span>
            </Button>
          </form>

          <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
              Already have an account?
              <a href="/login" class="text-gold hover:text-amber-700 font-medium">Sign in</a>
            </p>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'

// Components
import Card from './Design/Components/Card.vue'
import Button from './Design/Components/Button.vue'

// Props
const props = defineProps({
  email: {
    type: String,
    default: ''
  }
})

// Reactive data
const form = ref({
  email: '',
  first_name: '',
  last_name: '',
  password: '',
  password_confirmation: '',
  terms: false
})

const isLoading = ref(false)

// Methods
const handleRegister = async () => {
  if (!form.value.terms) {
    alert('Please accept the terms and conditions')
    return
  }

  if (form.value.password !== form.value.password_confirmation) {
    alert('Passwords do not match')
    return
  }

  isLoading.value = true

  try {
    // Submit registration form
    await router.post('/register-as-agent', form.value)
  } catch (error) {
    console.error('Registration error:', error)
  } finally {
    isLoading.value = false
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
