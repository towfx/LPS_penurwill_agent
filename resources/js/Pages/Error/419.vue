<template>
  <div class="min-h-screen bg-cream flex items-center justify-center">
    <div class="max-w-md mx-auto text-center px-4">
      <div class="mb-8">
        <div class="w-24 h-24 bg-accent-orange/10 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-12 h-12 text-accent-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-6xl font-bold text-forest-dark mb-2">419</p>
        <h1 class="text-2xl font-bold text-forest-dark mb-4">Page Expired</h1>
        <p class="text-gray-600 mb-8">
          Your session has expired. Please refresh the page and try again.
        </p>
      </div>
      <div class="space-y-3">
        <button
          @click="refresh"
          class="w-full px-6 py-3 bg-gold hover:bg-amber-700 text-white rounded-lg font-medium transition-colors"
        >
          Refresh Page
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

const refresh = () => window.location.reload()
const goHome = () => router.visit('/dashboard')
</script>
