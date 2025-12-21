<template>
  <div class="space-y-6">
    <!-- Breadcrumbs -->
    <nav class="flex items-center space-x-2 text-sm text-gray-600">
      <Link href="/admin/dashboard" class="hover:text-forest-dark transition-colors">
        Dashboard
      </Link>
      <span class="text-gray-400">/</span>
      <span class="text-forest-dark font-medium">System Settings</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-forest-dark">System Settings</h1>
        <p class="text-gray-600 mt-2">
          Manage global system configuration and default values for the application.
        </p>
      </div>
      <Link
        href="/admin/system-settings/update"
        class="inline-flex items-center px-4 py-2 bg-gold text-forest-dark font-medium rounded-lg hover:bg-gold/90 transition-colors"
      >
        <Settings class="w-4 h-4 mr-2" />
        Update Settings
      </Link>
    </div>

    <!-- Settings Cards -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <!-- Commission Default Rate -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Percent class="w-5 h-5 text-gold" />
            <span>Commission Rate</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-3xl font-bold text-forest-dark mb-2">
            {{ settings.commission_default_rate }}%
          </div>
          <p class="text-sm text-gray-600">
            Default commission rate applied to new agents when no specific rate is set.
            This percentage determines how much commission agents earn from sales.
          </p>
        </CardContent>
      </Card>

      <!-- Partner Default Commission Rate -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Percent class="w-5 h-5 text-accent-green" />
            <span>Partner Commission Rate</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-3xl font-bold text-forest-dark mb-2">
            {{ settings.partner_default_commission_rate }}%
          </div>
          <p class="text-sm text-gray-600">
            Default commission rate applied to new partners when no specific rate is set.
            This percentage determines how much commission partners earn from sales.
          </p>
        </CardContent>
      </Card>

      <!-- Referral Code Prefix -->
      <Card class="bg-white shadow-sm border border-gray-200">
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Hash class="w-5 h-5 text-accent-blue" />
            <span>Referral Prefix</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-3xl font-bold text-forest-dark mb-2">
            {{ settings.referral_code_prefix }}
          </div>
          <p class="text-sm text-gray-600">
            Prefix used for generating unique referral codes for agents.
            This helps identify and track referrals across the system.
          </p>
        </CardContent>
      </Card>

    </div>

    <!-- Additional Info -->
    <Card class="bg-gradient-to-r from-cream to-white border border-gold/20">
      <CardContent class="pt-6">
        <div class="flex items-start space-x-3">
          <Info class="w-5 h-5 text-accent-blue mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="font-semibold text-forest-dark mb-2">About System Settings</h3>
            <p class="text-sm text-gray-700 leading-relaxed">
              These settings control the core behavior of the referral and commission system.
              Changes to these settings will affect new agents and referrals going forward.
              Existing data will not be modified when settings are updated.
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Settings, Percent, Hash, Users, Info } from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import CardContent from '../Design/Components/CardContent.vue'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({
  layout: AdminLayout
})

defineProps({
  settings: {
    type: Object,
    required: true
  }
})
</script>
