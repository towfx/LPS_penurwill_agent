<template>
  <div class="min-h-screen bg-gradient-to-br from-cream via-white to-cream/50 font-sans flex flex-col">
    
    <!-- Main Content -->
    <div class="flex-1 container mx-auto px-4 py-12 lg:py-16">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center min-h-[70vh]">
        <!-- Left Column -->
        <div class="space-y-10">
                    <div class="space-y-6">
            <h1 class="text-6xl lg:text-8xl font-bold text-forest-dark leading-tight">
              Pen'urwill
              <span class="text-gold">Agent</span>
              System
            </h1>

            <p class="text-lg text-gray-500 leading-relaxed max-w-lg mb-8">
              Join our network of successful agents and start earning commissions today.
              Track your performance, manage your business, and grow your income with our comprehensive platform.
            </p>
          </div>

          <!-- Feature Cards -->
          <div class="space-y-6">
            <Card class="p-6 hover:shadow-lg hover:scale-[1.02] transition-all duration-300 cursor-pointer border-l-4 border-l-gold bg-white/80 backdrop-blur-sm">
              <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-14 h-14 bg-gradient-to-br from-gold to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                    <UserPlus class="w-7 h-7 text-cream" stroke-width="2" />
                  </div>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-forest-dark mb-1">Register as Agent</h3>
                  <p class="text-forest-light text-sm leading-relaxed">Join our network and start your journey as a successful agent</p>
                </div>
              </div>
            </Card>

            <Card class="p-6 hover:shadow-lg hover:scale-[1.02] transition-all duration-300 cursor-pointer border-l-4 border-l-accent-green bg-white/80 backdrop-blur-sm">
              <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-14 h-14 bg-gradient-to-br from-accent-green to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                    <DollarSign class="w-7 h-7 text-cream" stroke-width="2" />
                  </div>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-forest-dark mb-1">Track Your Commissions</h3>
                  <p class="text-forest-light text-sm leading-relaxed">Monitor your earnings and performance in real-time</p>
                </div>
              </div>
            </Card>

            <Card class="p-6 hover:shadow-lg hover:scale-[1.02] transition-all duration-300 cursor-pointer border-l-4 border-l-accent-blue bg-white/80 backdrop-blur-sm">
              <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-14 h-14 bg-gradient-to-br from-accent-blue to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <BarChart3 class="w-7 h-7 text-cream" stroke-width="2" />
                  </div>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-forest-dark mb-1">Analytics & Insights</h3>
                  <p class="text-forest-light text-sm leading-relaxed">Get detailed reports and insights to optimize your performance</p>
                </div>
              </div>
            </Card>

            <Card class="p-6 hover:shadow-lg hover:scale-[1.02] transition-all duration-300 cursor-pointer border-l-4 border-l-accent-orange bg-white/80 backdrop-blur-sm">
              <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-14 h-14 bg-gradient-to-br from-accent-orange to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                    <Users class="w-7 h-7 text-cream" stroke-width="2" />
                  </div>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-forest-dark mb-1">Build Your Network</h3>
                  <p class="text-forest-light text-sm leading-relaxed">Connect with other agents and expand your business network</p>
                </div>
              </div>
            </Card>
          </div>
        </div>

        <!-- Right Column -->
        <div class="flex justify-center lg:justify-end">
          <Card class="w-full max-w-md p-8 bg-white/90 backdrop-blur-sm shadow-xl border-0">
            <div class="text-center mb-8">
              <div class="w-16 h-16 bg-gradient-to-br from-gold to-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <span class="text-2xl font-bold text-white">→</span>
              </div>
              <h2 class="text-3xl font-bold text-forest-dark mb-3">Start Now</h2>
              <p class="text-forest-light">Enter your email to begin your journey</p>
            </div>

            <form @submit.prevent="handleContinue" class="space-y-6">
              <div>
                <label for="email" class="block text-sm font-semibold text-forest-dark mb-3">
                  Your Email Address
                </label>
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-gold/20 focus:border-gold transition-all duration-300 text-forest-dark placeholder-forest-light/60"
                  placeholder="Enter your email address"
                />
              </div>

              <Button
                type="submit"
                variant="secondary"
                size="lg"
                class="w-full py-4 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                :disabled="isLoading"
              >
                <span v-if="isLoading" class="flex items-center justify-center">
                  <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-3"></div>
                  Checking...
                </span>
                <span v-else class="flex items-center justify-center">
                  Continue
                  <ArrowRight class="w-5 h-5 ml-2" />
                </span>
              </Button>
            </form>

            <div class="mt-8 text-center">
              <p class="text-sm text-forest-light">
                Already have an account?
                <a href="/login" class="text-gold hover:text-amber-700 font-semibold transition-colors duration-300">Sign in</a>
              </p>
            </div>
          </Card>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-forest-dark py-8 border-t border-forest-light/20">
      <div class="container mx-auto px-4">
        <div class="text-center">
          <p class="text-sm text-cream">
            © 2025 Penurwill Online Will Writing System. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  UserPlus,
  DollarSign,
  BarChart3,
  Users,
  ArrowRight
} from 'lucide-vue-next'

// Components
import Card from './Design/Components/Card.vue'
import Button from './Design/Components/Button.vue'

// Reactive data
const email = ref('')
const isLoading = ref(false)

// Methods
const handleContinue = async () => {
  if (!email.value) return

  isLoading.value = true

  try {
    // Check if email exists in users table
    const response = await fetch('/api/check-email', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ email: email.value })
    })

    const data = await response.json()

    if (data.exists) {
      // Email exists, redirect to login
      router.visit('/login', {
        data: { email: email.value }
      })
    } else {
      // Email doesn't exist, redirect to register
      router.visit('/register-as-agent', {
        data: { email: email.value }
      })
    }
  } catch (error) {
    console.error('Error checking email:', error)
    // Fallback to register page
    router.visit('/register-as-agent', {
      data: { email: email.value }
    })
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Custom styles for Geist font */
@import url('https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&display=swap');

.font-sans {
  font-family: 'Geist', sans-serif !important;
}
</style>
