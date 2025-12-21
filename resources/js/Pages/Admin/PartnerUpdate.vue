<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Partners</span> / <span class="text-stone-900 font-medium">Edit Partner</span>
    </nav>
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold text-forest-dark">Edit Business Partner</h1>
      <button @click="goBack" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-medium transition-colors">
        Back to List
      </button>
    </div>

    <form @submit.prevent="savePartner" class="space-y-6" enctype="multipart/form-data">
      <!-- Partner Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">Partner Information</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
            <input v-model="form.company_name" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_name" class="text-accent-red text-sm mt-1">{{ errors.company_name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Registration Number</label>
            <input v-model="form.company_registration_number" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_registration_number" class="text-accent-red text-sm mt-1">{{ errors.company_registration_number }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
            <textarea v-model="form.company_address" class="w-full px-3 py-2 border rounded"></textarea>
            <p v-if="errors.company_address" class="text-accent-red text-sm mt-1">{{ errors.company_address }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Phone</label>
            <input v-model="form.company_phone" type="text" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_phone" class="text-accent-red text-sm mt-1">{{ errors.company_phone }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Email</label>
            <input v-model="form.company_email" type="email" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.company_email" class="text-accent-red text-sm mt-1">{{ errors.company_email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Partner Code</label>
            <input v-model="form.code" type="text" class="w-full px-3 py-2 border rounded font-mono" />
            <p v-if="errors.code" class="text-accent-red text-sm mt-1">{{ errors.code }}</p>
          </div>


          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select v-model="form.status" class="w-full px-3 py-2 border rounded">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Company Profile File</label>
            <div v-if="partner.company_profile_file" class="mb-2">
              <span class="text-sm text-gray-600">Current file: </span>
              <a :href="`/storage/${partner.company_profile_file}`" target="_blank" class="text-gold hover:text-amber-700 text-sm">
                View Current File
              </a>
            </div>
            <input
              @change="handleFileChange"
              type="file"
              accept=".jpg,.jpeg,.png,.pdf"
              class="w-full px-3 py-2 border rounded"
            />
            <p class="text-sm text-gray-500 mt-1">Accepted formats: JPG, JPEG, PNG, PDF (Max 10MB)</p>
            <p v-if="errors.company_profile_file" class="text-accent-red text-sm mt-1">{{ errors.company_profile_file }}</p>
          </div>
        </div>
      </div>

      <!-- User Account Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-forest-dark mb-4">User Account</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">User Email</label>
            <input v-model="form.user_email" type="email" class="w-full px-3 py-2 border rounded bg-gray-50" readonly />
            <p class="text-sm text-gray-500 mt-1">Email cannot be changed</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">New Password (leave blank to keep current)</label>
            <input v-model="form.user_password" type="password" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.user_password" class="text-accent-red text-sm mt-1">{{ errors.user_password }}</p>
          </div>

          <div v-if="form.user_password">
            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
            <input v-model="form.user_password_confirmation" type="password" class="w-full px-3 py-2 border rounded" />
            <p v-if="errors.user_password_confirmation" class="text-accent-red text-sm mt-1">{{ errors.user_password_confirmation }}</p>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex justify-end">
        <button type="submit" :disabled="isSaving" class="bg-gold hover:bg-amber-700 text-white px-6 py-2 rounded font-medium transition-colors">
          <span v-if="isSaving">Saving...</span>
          <span v-else>Save Changes</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  },
  partner: {
    type: Object,
    default: null
  },
  partners: {
    type: Array,
    default: () => []
  }
})

const form = reactive({
  company_name: props.partner?.company_name || '',
  company_registration_number: props.partner?.company_registration_number || '',
  company_address: props.partner?.company_address || '',
  company_phone: props.partner?.company_phone || '',
  company_email: props.partner?.company_email || '',
  code: props.partner?.code || '',
  status: props.partner?.status || 'active',
  company_profile_file: null,
  user_email: props.partner?.user_email || '',
  user_password: '',
  user_password_confirmation: ''
})

const isSaving = ref(false)
const errors = ref({})

const handleFileChange = (event) => {
  form.company_profile_file = event.target.files[0]
}

const savePartner = async () => {
  isSaving.value = true
  errors.value = {}

  const formData = new FormData()
  Object.keys(form).forEach(key => {
    if (key === 'company_profile_file' && form[key]) {
      formData.append(key, form[key])
    } else if (form[key] !== null && form[key] !== '') {
      formData.append(key, form[key])
    }
  })

  // Add method spoofing for PUT
  formData.append('_method', 'PUT')

  try {
    await router.post(`/admin/partners/${props.id}/update`, formData, {
      onError: (e) => {
        errors.value = e
      },
    })
  } finally {
    isSaving.value = false
  }
}

const goBack = () => {
  router.visit('/admin/partners/list')
}
</script>

