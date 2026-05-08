<template>
  <div class="min-h-screen bg-cream flex items-center justify-center">
    <div class="max-w-md mx-auto text-center px-4">
      <div class="mb-8">
        <div class="w-24 h-24 bg-accent-blue/10 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-12 h-12 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-6xl font-bold text-forest-dark mb-2">404</p>
        <h1 class="text-2xl font-bold text-forest-dark mb-4">Page Not Found</h1>
        <p class="text-gray-600 mb-8">
          The page you're looking for doesn't exist or has been moved.
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
