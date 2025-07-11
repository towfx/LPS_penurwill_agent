<template>
  <div>
    <nav class="text-sm text-stone-500 mb-4">
      <span>Admin</span> / <span>Agents</span> / <span class="text-stone-900 font-medium">View</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-forest-dark">View Agent</h1>
      <div class="flex items-center space-x-2">
        <Button variant="outline" @click="editAgent">
          <Edit size="16" class="mr-2" />
          Edit Agent
        </Button>
        <Button variant="outline" @click="goBack">
          <ArrowLeft size="16" class="mr-2" />
          Back to List
        </Button>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Agent Information -->
      <Card>
        <CardHeader>
          <CardTitle>Agent Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Agent Type</label>
            <Badge :variant="getAgentTypeVariant(agent.profile_type)">
              {{ agent.profile_type === 'individual' ? 'Individual' : 'Company' }}
            </Badge>
          </div>

          <div v-if="agent.profile_type === 'individual'">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Individual Name</label>
              <p class="text-gray-900">{{ agent.individual_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
              <p class="text-gray-900">{{ agent.individual_phone }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
              <p class="text-gray-900">{{ agent.individual_address }}</p>
            </div>
          </div>

          <div v-else>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Company Representative</label>
              <p class="text-gray-900">{{ agent.company_representative_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
              <p class="text-gray-900">{{ agent.company_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Registration Number</label>
              <p class="text-gray-900">{{ agent.company_registration_number }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Company Address</label>
              <p class="text-gray-900">{{ agent.company_address }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Company Phone</label>
              <p class="text-gray-900">{{ agent.company_phone }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <Badge :variant="getStatusVariant(agent.status)">
              {{ agent.status === 'active' ? 'Active' : agent.status === 'inactive' ? 'Inactive' : agent.status === 'suspended' ? 'Suspended' : 'Banned' }}
            </Badge>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Registration Date</label>
            <p class="text-gray-900">{{ formatDate(agent.created_at) }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- User Account Information -->
      <Card>
        <CardHeader>
          <CardTitle>User Account Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <p class="text-gray-900">{{ agent.user_email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
            <p class="text-gray-900">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Verified
              </span>
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowLeft, Edit } from 'lucide-vue-next'
import AdminLayout from '../Design/AdminLayout.vue'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardContent from '../Design/Components/CardContent.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import Button from '../Design/Components/Button.vue'
import Badge from '../Design/Components/Badge.vue'

defineOptions({ layout: AdminLayout })

// Props
const props = defineProps({
  agent: {
    type: Object,
    required: true
  }
})

// Reactive data
const isLoading = ref(false)

// Methods
const editAgent = () => {
  router.visit(`/admin/agents/${props.agent.id}/update`)
}

const goBack = () => {
  router.visit('/admin/agents/list')
}

const getStatusVariant = (status) => {
  switch(status) {
    case 'active': return 'success'
    case 'inactive': return 'destructive'
    case 'suspended': return 'warning'
    case 'banned': return 'destructive'
    default: return 'default'
  }
}

const getAgentTypeVariant = (type) => {
  switch(type) {
    case 'individual': return 'outline'
    case 'company': return 'secondary'
    default: return 'default'
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
