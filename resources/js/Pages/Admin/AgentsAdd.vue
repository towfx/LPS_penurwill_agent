<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Agents</span> / <span class="text-stone-900 font-medium">Add</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-forest-dark">Add Agent</h1>
      <Button variant="outline" @click="goBack">
        <ArrowLeft size="16" class="mr-2" />
        Back to List
      </Button>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Agent Information</CardTitle>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Agent Type Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Agent Type *</label>
            <div class="flex space-x-4">
              <label class="flex items-center">
                <input
                  v-model="form.profile_type"
                  type="radio"
                  value="individual"
                  class="mr-2"
                />
                Individual
              </label>
              <label class="flex items-center">
                <input
                  v-model="form.profile_type"
                  type="radio"
                  value="company"
                  class="mr-2"
                />
                Company
              </label>
            </div>
            <p v-if="errors.profile_type" class="text-red-500 text-sm mt-1">{{ errors.profile_type }}</p>
          </div>

          <!-- Individual Fields -->
          <div v-if="form.profile_type === 'individual'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Individual Name *</label>
              <input
                v-model="form.individual_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter individual name"
              />
              <p v-if="errors.individual_name" class="text-red-500 text-sm mt-1">{{ errors.individual_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
              <input
                v-model="form.individual_phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter phone number"
              />
              <p v-if="errors.individual_phone" class="text-red-500 text-sm mt-1">{{ errors.individual_phone }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
              <textarea
                v-model="form.individual_address"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter address"
              ></textarea>
              <p v-if="errors.individual_address" class="text-red-500 text-sm mt-1">{{ errors.individual_address }}</p>
            </div>
          </div>

          <!-- Company Fields -->
          <div v-if="form.profile_type === 'company'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Representative Name *</label>
              <input
                v-model="form.company_representative_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter representative name"
              />
              <p v-if="errors.company_representative_name" class="text-red-500 text-sm mt-1">{{ errors.company_representative_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
              <input
                v-model="form.company_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company name"
              />
              <p v-if="errors.company_name" class="text-red-500 text-sm mt-1">{{ errors.company_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Registration Number *</label>
              <input
                v-model="form.company_registration_number"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter registration number"
              />
              <p v-if="errors.company_registration_number" class="text-red-500 text-sm mt-1">{{ errors.company_registration_number }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Address *</label>
              <textarea
                v-model="form.company_address"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company address"
              ></textarea>
              <p v-if="errors.company_address" class="text-red-500 text-sm mt-1">{{ errors.company_address }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Phone *</label>
              <input
                v-model="form.company_phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company phone"
              />
              <p v-if="errors.company_phone" class="text-red-500 text-sm mt-1">{{ errors.company_phone }}</p>
            </div>
          </div>

          <!-- User Account Information -->
          <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">User Account Information</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                <input
                  v-model="form.user_email"
                  type="email"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                  placeholder="Enter email address"
                />
                <p v-if="errors.user_email" class="text-red-500 text-sm mt-1">{{ errors.user_email }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                <input
                  v-model="form.user_password"
                  type="password"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                  placeholder="Enter password"
                />
                <p v-if="errors.user_password" class="text-red-500 text-sm mt-1">{{ errors.user_password }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                <input
                  v-model="form.user_password_confirmation"
                  type="password"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                  placeholder="Confirm password"
                />
                <p v-if="errors.user_password_confirmation" class="text-red-500 text-sm mt-1">{{ errors.user_password_confirmation }}</p>
              </div>
            </div>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
              <option value="banned">Banned</option>
            </select>
            <p v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</p>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end space-x-4 pt-6 border-t">
            <Button type="button" variant="outline" @click="goBack">
              Cancel
            </Button>
            <Button type="submit" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Creating...
              </span>
              <span v-else>Create Agent</span>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import AdminLayout from '../Design/AdminLayout.vue'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardContent from '../Design/Components/CardContent.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import Button from '../Design/Components/Button.vue'

defineOptions({ layout: AdminLayout })

// Props
const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Reactive data
const isSubmitting = ref(false)

const form = reactive({
  profile_type: 'individual',
  individual_name: '',
  individual_phone: '',
  individual_address: '',
  company_representative_name: '',
  company_name: '',
  company_registration_number: '',
  company_address: '',
  company_phone: '',
  user_email: '',
  user_password: '',
  user_password_confirmation: '',
  status: 'active'
})

// Methods
const submitForm = async () => {
  isSubmitting.value = true

  try {
    await router.post('/admin/agents/store', form, {
      onSuccess: () => {
        router.visit('/admin/agents/list')
      },
      onError: (errors) => {
        console.error('Form errors:', errors)
      }
    })
  } catch (error) {
    console.error('Submission error:', error)
  } finally {
    isSubmitting.value = false
  }
}

const goBack = () => {
  router.visit('/admin/agents/list')
}
</script>
