<script setup>
import { router, usePage } from '@inertiajs/vue3'
import ProfileLayout from '../Design/ProfileLayout.vue'

defineOptions({ layout: ProfileLayout })

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const page = usePage()

const goToEdit = () => {
  router.visit('/profile/edit')
}
</script>

<template>
  <div class="min-h-screen bg-cream">
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-forest-dark">Profile</h1>
          <p class="text-gray-600 mt-2">Your account information</p>
        </div>

        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow p-6 space-y-6">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold text-forest-dark">Account Information</h2>
            <button
              @click="goToEdit"
              class="px-4 py-2 bg-gold hover:bg-amber-700 text-white rounded-lg font-medium transition-colors"
            >
              Edit Profile
            </button>
                </div>

          <div class="space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
              <span class="text-gray-600">Name:</span>
              <span class="font-medium text-forest-dark">{{ user.name }}</span>
                </div>

            <div class="flex justify-between items-center py-3 border-b border-gray-100">
              <span class="text-gray-600">Email:</span>
              <span class="font-medium text-forest-dark">{{ user.email }}</span>
                </div>

            <div class="flex justify-between items-center py-3 border-b border-gray-100">
              <span class="text-gray-600">Member Since:</span>
              <span class="font-medium text-forest-dark">{{ new Date(user.created_at).toLocaleDateString() }}</span>
            </div>
          </div>
        </div>

        <!-- Success Message -->
        <div v-if="$page.props.flash.success" class="mt-6 bg-accent-green/10 border border-accent-green/20 rounded-lg p-4">
          <p class="text-accent-green font-medium">{{ $page.props.flash.success }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
