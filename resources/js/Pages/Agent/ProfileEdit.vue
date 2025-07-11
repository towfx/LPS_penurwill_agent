<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  agent: {
    type: Object,
    default: null
  }
})

const form = ref({
  profile_type: props.agent?.profile_type || 'individual',
  individual_name: props.agent?.individual_name || '',
  individual_phone: props.agent?.individual_phone || '',
  individual_address: props.agent?.individual_address || '',
  company_representative_name: props.agent?.company_representative_name || '',
  company_name: props.agent?.company_name || '',
  company_registration_number: props.agent?.company_registration_number || '',
  company_address: props.agent?.company_address || '',
  company_phone: props.agent?.company_phone || '',
  status: props.agent?.status || 'active',
})

const isIndividual = computed(() => form.value.profile_type === 'individual')
const isCompany = computed(() => form.value.profile_type === 'company')

const isSaving = ref(false)
const errors = ref({})

const saveProfile = async () => {
  isSaving.value = true
  errors.value = {}
  try {
    await router.put('/agent/profile/edit', form.value, {
      onSuccess: () => router.visit('/agent/profile'),
      onError: (e) => { errors.value = e },
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Agent</span> / <span class="text-stone-900 font-medium">Edit Profile</span>
    </nav>
    <h1 class="text-2xl font-bold text-forest-dark mb-4">Edit Agent Profile</h1>

    <form @submit.prevent="saveProfile" class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto space-y-6">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Agent Type</label>
        <div class="flex space-x-4">
          <label class="flex items-center">
            <input type="radio" value="individual" v-model="form.profile_type" class="mr-2" /> Individual
          </label>
          <label class="flex items-center">
            <input type="radio" value="company" v-model="form.profile_type" class="mr-2" /> Company
          </label>
        </div>
      </div>

      <div v-if="isIndividual" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
          <input v-model="form.individual_name" type="text" class="w-full px-3 py-2 border rounded" />
          <p v-if="errors.individual_name" class="text-accent-red text-sm mt-1">{{ errors.individual_name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
          <input v-model="form.individual_phone" type="text" class="w-full px-3 py-2 border rounded" />
          <p v-if="errors.individual_phone" class="text-accent-red text-sm mt-1">{{ errors.individual_phone }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
          <textarea v-model="form.individual_address" class="w-full px-3 py-2 border rounded"></textarea>
          <p v-if="errors.individual_address" class="text-accent-red text-sm mt-1">{{ errors.individual_address }}</p>
        </div>
      </div>

      <div v-if="isCompany" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
          <input v-model="form.company_name" type="text" class="w-full px-3 py-2 border rounded" />
          <p v-if="errors.company_name" class="text-accent-red text-sm mt-1">{{ errors.company_name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Representative</label>
          <input v-model="form.company_representative_name" type="text" class="w-full px-3 py-2 border rounded" />
          <p v-if="errors.company_representative_name" class="text-accent-red text-sm mt-1">{{ errors.company_representative_name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Registration Number</label>
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
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
        <select v-model="form.status" class="w-full px-3 py-2 border rounded">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
          <option value="banned">Banned</option>
        </select>
      </div>

      <div class="flex justify-end">
        <button type="submit" :disabled="isSaving" class="bg-gold hover:bg-amber-700 text-white px-6 py-2 rounded font-medium transition-colors">
          <span v-if="isSaving">Saving...</span>
          <span v-else>Save</span>
        </button>
      </div>
    </form>
  </div>
</template>
