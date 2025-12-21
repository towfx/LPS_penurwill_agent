<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Business Partners</span> / <span class="text-stone-900 font-medium">Add</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-forest-dark">Add Business Partner</h1>
      <Button variant="outline" @click="goBack">
        <ArrowLeft size="16" class="mr-2" />
        Back to List
      </Button>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Business Partner Information</CardTitle>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submitForm" class="space-y-6" enctype="multipart/form-data">
          <!-- Company Information -->
          <div class="space-y-4">
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

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Email *</label>
              <input
                v-model="form.company_email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter company email"
              />
              <p v-if="errors.company_email" class="text-red-500 text-sm mt-1">{{ errors.company_email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Partner Code *</label>
              <div class="flex gap-2">
                <input
                  v-model="form.code"
                  type="text"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent font-mono"
                  placeholder="Enter unique partner code"
                />
                <button
                  type="button"
                  @click="regenerateCode"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md font-medium transition-colors whitespace-nowrap"
                  title="Generate new code"
                >
                  Regenerate
                </button>
              </div>
              <p class="text-sm text-gray-500 mt-1">Prefix: {{ referralCodePrefix }} (auto-generated)</p>
              <p v-if="errors.code" class="text-red-500 text-sm mt-1">{{ errors.code }}</p>
            </div>


            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
              <select
                v-model="form.status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
              <p v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Profile File</label>
              <input
                @change="handleFileChange"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              />
              <p class="text-sm text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG, PDF (Max 10MB)</p>
              <p v-if="errors.company_profile_file" class="text-red-500 text-sm mt-1">{{ errors.company_profile_file }}</p>
            </div>
          </div>

          <!-- User Account Information -->
          <div class="pt-6 border-t space-y-4">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">User Account</h3>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">User Email *</label>
              <input
                v-model="form.user_email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter user email"
              />
              <p v-if="errors.user_email" class="text-red-500 text-sm mt-1">{{ errors.user_email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
              <input
                v-model="form.user_password"
                type="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
                placeholder="Enter password (min 8 characters)"
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
              <span v-else>Create Partner</span>
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
  },
  partners: {
    type: Array,
    default: () => []
  },
  referralCodePrefix: {
    type: String,
    default: 'REF'
  }
})

// Reactive data
const isSubmitting = ref(false)

// Generate random code suffix (8 uppercase characters)
const generateCodeSuffix = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let result = ''
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

// Generate full partner code
const generatePartnerCode = () => {
  return props.referralCodePrefix + generateCodeSuffix()
}

const form = reactive({
  company_name: '',
  company_registration_number: '',
  company_address: '',
  company_phone: '',
  company_email: '',
  code: generatePartnerCode(),
  status: 'active',
  company_profile_file: null,
  user_email: '',
  user_password: '',
  user_password_confirmation: ''
})

// Method to regenerate partner code
const regenerateCode = () => {
  form.code = generatePartnerCode()
}

// Methods
const handleFileChange = (event) => {
  form.company_profile_file = event.target.files[0]
}

const submitForm = async () => {
  isSubmitting.value = true

  const formData = new FormData()
  Object.keys(form).forEach(key => {
    if (key === 'company_profile_file' && form[key]) {
      formData.append(key, form[key])
    } else if (form[key] !== null && form[key] !== '') {
      formData.append(key, form[key])
    }
  })

  try {
    await router.post('/admin/partners/store', formData, {
      onSuccess: () => {
        router.visit('/admin/partners/list')
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
  router.visit('/admin/partners/list')
}
</script>

