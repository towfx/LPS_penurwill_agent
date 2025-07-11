<template>
  <div class="min-h-screen bg-cream">
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <nav class="text-sm text-stone-500 mb-4">
            <span>Profile</span> / <span class="text-stone-900 font-medium">Edit</span>
          </nav>
          <h1 class="text-2xl font-bold text-forest-dark">Profile Settings</h1>
          <p class="text-gray-600 mt-2">Update your account information and password</p>
        </div>

        <!-- Profile Form -->
        <div class="bg-white rounded-lg shadow p-6 space-y-6">
          <form @submit.prevent="updateProfile">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Enter your name"
              />
              <p v-if="errors.name" class="text-accent-red text-sm mt-1">{{ errors.name }}</p>
            </div>

            <!-- Email (read-only) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
              <input
                v-model="form.email"
                type="email"
                readonly
                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
              />
              <p class="text-sm text-gray-500 mt-1">Email address cannot be changed</p>
            </div>

            <!-- Current Password -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
              <input
                v-model="form.current_password"
                type="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Enter your current password"
              />
              <p v-if="errors.current_password" class="text-accent-red text-sm mt-1">{{ errors.current_password }}</p>
            </div>

            <!-- New Password -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                New Password
                <span class="text-accent-red">(Minimum 12 characters with numbers and special characters)</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Enter new password (leave blank to keep current)"
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

            <!-- Confirm New Password -->
            <div v-if="form.password">
              <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                placeholder="Confirm your new password"
                @input="validatePassword"
              />
              <p v-if="errors.password_confirmation" class="text-accent-red text-sm mt-1">{{ errors.password_confirmation }}</p>
              <p v-if="form.password_confirmation && form.password !== form.password_confirmation" class="text-accent-red text-sm mt-1">
                Passwords do not match
              </p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 pt-6">
              <button
                type="button"
                @click="goBack"
                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSaving || !canSave"
                class="px-6 py-3 bg-gold hover:bg-amber-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isSaving" class="flex items-center">
                  <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                  Saving...
                </span>
                <span v-else>Save Changes</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Reactive data
const isSaving = ref(false)

const form = ref({
  name: '',
  email: '',
  current_password: '',
  password: '',
  password_confirmation: ''
})

const passwordValidation = ref({
  length: false,
  numbers: false,
  special: false
})

// Computed properties
const canSave = computed(() => {
  // Always allow saving if only name is changed
  if (form.value.name !== props.user.name && !form.value.password) {
    return true
  }

  // If password is being changed, validate it
  if (form.value.password) {
    return passwordValidation.value.length &&
           passwordValidation.value.numbers &&
           passwordValidation.value.special &&
           form.value.password === form.value.password_confirmation
  }

  return true
})

// Methods
const validatePassword = () => {
  const password = form.value.password
  if (!password) {
    passwordValidation.value = {
      length: false,
      numbers: false,
      special: false
    }
    return
  }

  passwordValidation.value = {
    length: password.length >= 12,
    numbers: /\d/.test(password),
    special: /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password)
  }
}

const updateProfile = async () => {
  if (!canSave.value) return

  isSaving.value = true

  try {
    await router.put('/profile', form.value, {
      onSuccess: () => {
        // Show success message or redirect
        router.visit('/profile')
      },
      onError: (errors) => {
        console.error('Profile update errors:', errors)
      }
    })
  } catch (error) {
    console.error('Profile update error:', error)
  } finally {
    isSaving.value = false
  }
}

const goBack = () => {
  router.visit('/profile')
}

// Lifecycle
onMounted(() => {
  // Initialize form with current user data
  form.value.name = props.user.name || ''
  form.value.email = props.user.email || ''
})
</script>
