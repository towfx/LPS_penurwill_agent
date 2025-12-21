<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Partners</CardTitle>
        <div class="flex items-center space-x-2">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search partners..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent"
              @input="handleSearch"
            />
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
          </div>
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
                @click="sortBy('company_name')"
              >
                <div class="flex items-center">
                  Company Name
                  <ChevronUp v-if="sortByField === 'company_name' && sortOrder === 'asc'" class="ml-1 h-4 w-4" />
                  <ChevronDown v-if="sortByField === 'company_name' && sortOrder === 'desc'" class="ml-1 h-4 w-4" />
                </div>
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('code')"
              >
                <div class="flex items-center">
                  Code
                  <ChevronUp v-if="sortByField === 'code' && sortOrder === 'asc'" class="ml-1 h-4 w-4" />
                  <ChevronDown v-if="sortByField === 'code' && sortOrder === 'desc'" class="ml-1 h-4 w-4" />
                </div>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="partner in partners"
              :key="partner.id"
              class="hover:bg-gray-50 transition-colors cursor-pointer"
              @click="viewPartner(partner.id)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ partner.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ partner.company_name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="font-mono text-sm text-gray-900">{{ partner.code }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="getStatusVariant(partner.status)">
                  {{ partner.status }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="flex items-center space-x-2">
                  <Button variant="ghost" size="sm" @click.stop="viewPartner(partner.id)">
                    <Eye size="16" />
                  </Button>
                  <Button variant="ghost" size="sm" @click.stop="editPartner(partner.id)">
                    <Edit size="16" />
                  </Button>
                  <Button variant="ghost" size="sm" @click.stop="deletePartner(partner.id)">
                    <Trash2 size="16" />
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
import { ref, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Search,
  Eye,
  Edit,
  Trash2,
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
  initialPartners: {
    type: Array,
    default: () => []
  },
  initialPagination: {
    type: Object,
    default: () => null
  }
})

// Reactive data
const partners = ref(props.initialPartners || [])
const pagination = ref(props.initialPagination || null)
const searchQuery = ref('')
const sortByField = ref('id')
const sortOrder = ref('desc')
const isLoading = ref(false)

// Debounced search
let searchTimeout = null

// Methods
const fetchPartners = async () => {
  isLoading.value = true

  try {
    const params = new URLSearchParams({
      search: searchQuery.value,
      sort_by: sortByField.value,
      sort_order: sortOrder.value,
      page: pagination.value?.current_page || 1
    })

    router.visit(`/admin/partners/list?${params}`, {
      preserveState: true,
      preserveScroll: true,
      only: ['partners', 'pagination'],
      onSuccess: (page) => {
        partners.value = page.props.partners || []
        pagination.value = page.props.pagination || null
      }
    })
  } catch (error) {
    console.error('Error fetching partners:', error)
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (pagination.value) {
      pagination.value.current_page = 1
    }
    fetchPartners()
  }, 300)
}

const sortBy = (field) => {
  if (sortByField.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortByField.value = field
    sortOrder.value = 'asc'
  }
  fetchPartners()
}

const changePage = (page) => {
  if (pagination.value) {
    pagination.value.current_page = page
    fetchPartners()
  }
}

const viewPartner = (id) => {
  router.visit(`/admin/partners/${id}/view`)
}

const editPartner = (id) => {
  router.visit(`/admin/partners/${id}/update`)
}

const deletePartner = (id) => {
  if (confirm('Are you sure you want to delete this partner?')) {
    router.delete(`/admin/partners/${id}/delete`, {
      onSuccess: () => {
        fetchPartners()
      }
    })
  }
}

const getStatusVariant = (status) => {
  switch(status.toLowerCase()) {
    case 'active': return 'success'
    case 'inactive': return 'default'
    case 'suspended': return 'warning'
    default: return 'default'
  }
}

// Lifecycle
onMounted(() => {
  if (!partners.value.length && pagination.value) {
    fetchPartners()
  }
})

// Watch for prop changes
watch(() => props.initialPartners, (newPartners) => {
  if (newPartners) {
    partners.value = newPartners
  }
})

watch(() => props.initialPagination, (newPagination) => {
  if (newPagination) {
    pagination.value = newPagination
  }
})
</script>

