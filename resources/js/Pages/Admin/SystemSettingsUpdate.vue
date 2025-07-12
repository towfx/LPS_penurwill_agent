<template>
  <div class="space-y-6">
    <!-- Breadcrumbs -->
    <nav class="flex items-center space-x-2 text-sm text-gray-600">
      <Link href="/admin/dashboard" class="hover:text-forest-dark transition-colors">
        Dashboard
      </Link>
      <span class="text-gray-400">/</span>
      <Link href="/admin/system-settings" class="hover:text-forest-dark transition-colors">
        System Settings
      </Link>
      <span class="text-gray-400">/</span>
      <span class="text-forest-dark font-medium">Update Settings</span>
    </nav>

    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-forest-dark">Update System Settings</h1>
      <p class="text-gray-600 mt-2">
        Modify global system configuration values. Changes will affect new agents and referrals.
      </p>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitForm" class="space-y-6">
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Commission Default Rate -->
        <Card class="bg-white shadow-sm border border-gray-200">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Percent class="w-5 h-5 text-gold" />
              <span>Commission Default Rate</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">
                Commission Rate (%)
              </label>
              <input
                id="commission_rate"
                v-model="form.commission_default_rate"
                type="number"
                step="0.01"
                min="0"
                max="100"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold focus:border-gold transition-colors"
                :class="{ 'border-red-500': errors.commission_default_rate }"
              />
              <p v-if="errors.commission_default_rate" class="text-red-500 text-sm mt-1">
                {{ errors.commission_default_rate }}
              </p>
            </div>
            <p class="text-sm text-gray-600">
              Default commission rate applied to new agents when no specific rate is set.
              This percentage determines how much commission agents earn from sales.
            </p>
          </CardContent>
        </Card>

        <!-- Referral Code Prefix -->
        <Card class="bg-white shadow-sm border border-gray-200">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Hash class="w-5 h-5 text-accent-blue" />
              <span>Referral Code Prefix</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label for="referral_prefix" class="block text-sm font-medium text-gray-700 mb-2">
                Prefix
              </label>
              <input
                id="referral_prefix"
                v-model="form.referral_code_prefix"
                type="text"
                maxlength="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-blue focus:border-accent-blue transition-colors"
                :class="{ 'border-red-500': errors.referral_code_prefix }"
              />
              <p v-if="errors.referral_code_prefix" class="text-red-500 text-sm mt-1">
                {{ errors.referral_code_prefix }}
              </p>
            </div>
            <p class="text-sm text-gray-600">
              Prefix used for generating unique referral codes for agents.
              This helps identify and track referrals across the system.
            </p>
          </CardContent>
        </Card>

        <!-- Global Referral Usage Limit -->
        <Card class="bg-white shadow-sm border border-gray-200">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <Users class="w-5 h-5 text-accent-green" />
              <span>Global Referral Usage Limit</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">
                Usage Limit
              </label>
              <input
                id="usage_limit"
                v-model="form.global_referral_usage_limit"
                type="number"
                min="1"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-green focus:border-accent-green transition-colors"
                :class="{ 'border-red-500': errors.global_referral_usage_limit }"
              />
              <p v-if="errors.global_referral_usage_limit" class="text-red-500 text-sm mt-1">
                {{ errors.global_referral_usage_limit }}
              </p>
            </div>
            <p class="text-sm text-gray-600">
              Maximum number of times a referral code can be used globally.
              This prevents abuse and controls the referral system usage.
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Warning Card -->
      <Card class="bg-gradient-to-r from-accent-orange/10 to-accent-red/10 border border-accent-orange/20">
        <CardContent class="pt-6">
          <div class="flex items-start space-x-3">
            <AlertTriangle class="w-5 h-5 text-accent-orange mt-0.5 flex-shrink-0" />
            <div>
              <h3 class="font-semibold text-forest-dark mb-2">Important Notice</h3>
              <p class="text-sm text-gray-700 leading-relaxed">
                Changes to these settings will only affect new agents and referrals created after the update.
                Existing data and current agent configurations will remain unchanged.
                Please review the changes carefully before saving.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
        <Link
          href="/admin/system-settings"
          class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="isSubmitting"
          class="inline-flex items-center px-6 py-2 bg-gold text-forest-dark font-medium rounded-lg hover:bg-gold/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <Save v-if="!isSubmitting" class="w-4 h-4 mr-2" />
          <Loader2 v-else class="w-4 h-4 mr-2 animate-spin" />
          {{ isSubmitting ? 'Updating...' : 'Update Settings' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  Percent,
  Hash,
  Users,
  AlertTriangle,
  Save,
  Loader2
} from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import CardContent from '../Design/Components/CardContent.vue'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({
  layout: AdminLayout
})

const props = defineProps({
  settings: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const isSubmitting = ref(false)

const form = reactive({
  commission_default_rate: props.settings.commission_default_rate,
  referral_code_prefix: props.settings.referral_code_prefix,
  global_referral_usage_limit: props.settings.global_referral_usage_limit,
})

const submitForm = () => {
  isSubmitting.value = true

  router.put('/admin/system-settings/update', form, {
    onSuccess: () => {
      isSubmitting.value = false
    },
    onError: () => {
      isSubmitting.value = false
    }
  })
}
</script>
