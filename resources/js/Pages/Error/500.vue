<template>
  <div class="min-h-screen bg-cream flex items-center justify-center">
    <div class="max-w-md mx-auto text-center px-4">
      <div class="mb-8">
        <div class="w-24 h-24 bg-accent-red/10 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-12 h-12 text-accent-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <p class="text-6xl font-bold text-forest-dark mb-2">500</p>
        <h1 class="text-2xl font-bold text-forest-dark mb-4">Server Error</h1>
        <p class="text-gray-600 mb-8">
          Something went wrong on our end. Please try again later or contact support if the problem persists.
        </p>
      </div>
      <div class="space-y-3">
        <button
          @click="goBack"
          class="w-full px-6 py-3 bg-gold hover:bg-amber-700 text-white rounded-lg font-medium transition-colors"
        >
          Go Back
        </button>
        <button
          @click="goHome"
          class="w-full px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
        >
          Go to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const goBack = () => window.history.back()
const goHome = () => router.visit('/dashboard')
</script>
