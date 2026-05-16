<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>{{ tableTitle }}</CardTitle>
        <div class="flex items-center space-x-2">
          <select
            v-if="!hasTypeFilter"
            v-model="roleFilter"
            @change="resetAndFetch"
            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent text-sm"
          >
            <option value="">All Roles</option>
            <option value="agent">{{ roleNames.agent }}</option>
            <option value="agent_leader">{{ roleNames.agent_leader }}</option>
            <option value="business_partner">{{ roleNames.business_partner }}</option>
          </select>
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search agents..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              @input="handleSearch"
            />
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
          </div>
          <Button variant="outline" size="sm" @click="exportData">
            <Download size="16" class="mr-2" />
            Export
          </Button>
        </div>
      </div>
    </CardHeader>

    <CardContent class="p-0">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('id')"
              >
                <div class="flex items-center">
                  ID
                  <ChevronUp v-if="sortByField === 'id' && sortOrder === 'asc'" class="ml-1 h-4 w-4" />
                  <ChevronDown v-if="sortByField === 'id' && sortOrder === 'desc'" class="ml-1 h-4 w-4" />
                </div>
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('profile_type')"
              >
                <div class="flex items-center">
                  Agent Type
                  <ChevronUp v-if="sortByField === 'profile_type' && sortOrder === 'asc'" class="ml-1 h-4 w-4" />
                  <ChevronDown v-if="sortByField === 'profile_type' && sortOrder === 'desc'" class="ml-1 h-4 w-4" />
                </div>
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('name')"
              >
                <div class="flex items-center">
                  Name
                  <ChevronUp v-if="sortByField === 'name' && sortOrder === 'asc'" class="ml-1 h-4 w-4" />
                  <ChevronDown v-if="sortByField === 'name' && sortOrder === 'desc'" class="ml-1 h-4 w-4" />
                </div>
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('agent_role')"
              >
                <div class="flex items-center">
                  Role
                  <ChevronUp v-if="sortByField === 'agent_role' && sortOrder === 'asc'" class="ml-1 h-4 w-4" />
                  <ChevronDown v-if="sortByField === 'agent_role' && sortOrder === 'desc'" class="ml-1 h-4 w-4" />
                </div>
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('created_at')"
              >
                <div class="flex items-center">
                  Reg Date
                  <ChevronUp v-if="sortByField === 'created_at' && sortOrder === 'asc'" class="ml-1 h-4 w-4" />
                  <ChevronDown v-if="sortByField === 'created_at' && sortOrder === 'desc'" class="ml-1 h-4 w-4" />
                </div>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Expiry</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="agent in agents"
              :key="agent.id"
              class="hover:bg-gray-50 transition-colors cursor-pointer"
              @click="viewAgent(agent.id)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ agent.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="getAgentTypeVariant(agent.agent_type)">
                  {{ agent.agent_type }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-gold">
                    <span class="text-white text-sm font-medium">
                      {{ agent.name.charAt(0) }}
                    </span>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ agent.name }}</div>
                    <div v-if="agent.representative" class="text-sm text-gray-500">
                      Rep: {{ agent.representative }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <Badge :variant="getRoleVariant(agent.agent_role)">
                  {{ roleLabel(agent.agent_role) }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ agent.reg_date }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="getStatusVariant(agent.status)">
                  {{ agent.status }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <Badge :variant="getExpiryVariant(agent)">
                  {{ getExpiryLabel(agent) }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="flex items-center space-x-2">
                  <Button variant="ghost" size="sm" @click.stop="viewAgent(agent.id)">
                    <Eye size="16" />
                  </Button>
                  <Button variant="ghost" size="sm" @click.stop="editAgent(agent.id)">
                    <Edit size="16" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination" class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
          </div>
          <div class="flex items-center space-x-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="pagination.current_page === 1"
              @click="changePage(pagination.current_page - 1)"
            >
              Previous
            </Button>
            <span class="text-sm text-gray-700">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            <Button
              variant="outline"
              size="sm"
              :disabled="pagination.current_page === pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
            >
              Next
            </Button>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  Search,
  Download,
  Eye,
  Edit,
  ChevronUp,
  ChevronDown
} from 'lucide-vue-next'
import Card from './Card.vue'
import CardHeader from './CardHeader.vue'
import CardContent from './CardContent.vue'
import CardTitle from './CardTitle.vue'
import Button from './Button.vue'
import Badge from './Badge.vue'

// Props
const props = defineProps({
  initialAgents: {
    type: Array,
    default: () => []
  },
  initialPagination: {
    type: Object,
    default: () => null
  }
})

// Reactive data
const page = usePage()
const agents = ref(props.initialAgents || [])
const pagination = ref(props.initialPagination || null)
const searchQuery = ref('')
const urlParams = new URLSearchParams(window.location.search)
const hasTypeFilter = computed(() => !!(urlParams.get('type') || urlParams.get('role')))
const tableTitle = computed(() => {
  const typeParam = urlParams.get('type') || urlParams.get('role')
  if (typeParam === 'business_partner') return `${roleNames.value.business_partner}s`
  if (typeParam === 'agent_leader') return `${roleNames.value.agent_leader}s`
  if (typeParam === 'agent') return `${roleNames.value.agent}s`
  return `${roleNames.value.agent}s`
})
const roleFilter = ref(urlParams.get('type') || urlParams.get('role') || '')
const sortByField = ref('id')
const sortOrder = ref('desc')
const isLoading = ref(false)
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))

const roleLabel = (role) => {
  if (!role) return '—'
  return roleNames.value[role] || role
}

const getRoleVariant = (role) => {
  switch (role) {
    case 'business_partner': return 'warning'
    case 'agent_leader': return 'success'
    case 'agent': return 'default'
    default: return 'secondary'
  }
}

const getExpiryVariant = (agent) => {
  if (!agent?.expires_at) return 'secondary'
  const today = new Date()
  const exp = new Date(agent.expires_at)
  const days = Math.floor((exp - today) / (1000 * 60 * 60 * 24))
  if (days < 0) return 'destructive'
  if (days < 30) return 'warning'
  return 'success'
}

const getExpiryLabel = (agent) => {
  if (!agent?.expires_at) return '—'
  const today = new Date()
  const exp = new Date(agent.expires_at)
  const days = Math.floor((exp - today) / (1000 * 60 * 60 * 24))
  if (days < 0) return `Expired ${-days}d ago`
  if (days < 30) return `${days}d left`
  return agent.expires_at
}

// Debounced search
let searchTimeout = null

// Methods
const fetchAgents = async () => {
  isLoading.value = true

  try {
    const params = new URLSearchParams({
      search: searchQuery.value,
      role: roleFilter.value,
      sort_by: sortByField.value,
      sort_order: sortOrder.value,
      page: pagination.value?.current_page || 1
    })

    const response = await fetch(`/api/admin/agents/query?${params}`)
    const data = await response.json()

    agents.value = data.data
    pagination.value = data.pagination
  } catch (error) {
    console.error('Error fetching agents:', error)
  } finally {
    isLoading.value = false
  }
}

const resetAndFetch = () => {
  if (pagination.value) {
    pagination.value.current_page = 1
  }
  fetchAgents()
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    resetAndFetch()
  }, 300)
}

const sortBy = (field) => {
  if (sortByField.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortByField.value = field
    sortOrder.value = 'asc'
  }
  resetAndFetch()
}

const changePage = (page) => {
  if (pagination.value) {
    pagination.value.current_page = page
    fetchAgents()
  }
}

const viewAgent = (id) => {
  router.visit(`/admin/agents/${id}/view`)
}

const editAgent = (id) => {
  router.visit(`/admin/agents/${id}/update`)
}

const exportData = () => {
  // Trigger Excel download
  window.open('/admin/agents/agents.xls', '_blank')
}

const getStatusVariant = (status) => {
  switch(status) {
    case 'Active': return 'success'
    case 'Inactive': return 'destructive'
    case 'Suspended': return 'warning'
    case 'Banned': return 'destructive'
    default: return 'default'
  }
}

const getAgentTypeVariant = (type) => {
  switch(type) {
    case 'Individual': return 'outline'
    case 'Company': return 'secondary'
    default: return 'default'
  }
}

// Lifecycle
onMounted(() => {
  if (!agents.value.length) {
    fetchAgents()
  }
})

// Watch for prop changes
watch(() => props.initialAgents, (newAgents) => {
  if (newAgents) {
    agents.value = newAgents
  }
})

watch(() => props.initialPagination, (newPagination) => {
  if (newPagination) {
    pagination.value = newPagination
  }
})
</script>
