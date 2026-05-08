<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../Design/AdminLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import Badge from '../Design/Components/Badge.vue'
import EmptyState from '../Design/Components/EmptyState.vue'
import Pagination from '../Design/Components/Pagination.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import { Download, Filter, RefreshCw } from 'lucide-vue-next'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  logs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')
const actorId = ref(props.filters.actor_id || '')
const action = ref(props.filters.action || '')
const targetType = ref(props.filters.target_type || '')

function applyFilter() {
  router.get('/admin/activity-log', {
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    actor_id: actorId.value || undefined,
    action: action.value || undefined,
    target_type: targetType.value || undefined,
  }, { preserveState: false })
}

function resetFilter() {
  startDate.value = ''
  endDate.value = ''
  actorId.value = ''
  action.value = ''
  targetType.value = ''
  router.get('/admin/activity-log', {}, { preserveState: false })
}

function exportCsv() {
  const params = new URLSearchParams()
  if (startDate.value) params.set('start_date', startDate.value)
  if (endDate.value) params.set('end_date', endDate.value)
  if (actorId.value) params.set('actor_id', actorId.value)
  if (action.value) params.set('action', action.value)
  if (targetType.value) params.set('target_type', targetType.value)
  window.location.href = `/admin/activity-log/export?${params.toString()}`
}

function changePage(page) {
  router.get('/admin/activity-log', {
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    actor_id: actorId.value || undefined,
    action: action.value || undefined,
    target_type: targetType.value || undefined,
    page,
  }, { preserveScroll: true })
}

function formatDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('en-MY', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function actionVariant(act) {
  const map = {
    create: 'success', delete: 'destructive', update: 'warning',
    approve: 'success', reject: 'destructive', cancel: 'destructive',
  }
  return map[act] || 'secondary'
}

function targetLabel(type) {
  if (!type) return '—'
  return type.split('\\').pop()
}
</script>

<template>
  <div>
    <PageHeader
      title="Activity Log"
      description="Audit trail of all system actions."
      :breadcrumbs="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Activity Log' }]"
    >
      <template #actions>
        <Button variant="outline" size="sm" @click="exportCsv">
          <Download class="w-4 h-4 mr-1" /> Export CSV
        </Button>
      </template>
    </PageHeader>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-4 mb-5">
      <div class="flex flex-wrap items-end gap-3">
        <FormField label="From" class="min-w-[130px]">
          <Input type="date" v-model="startDate" />
        </FormField>
        <FormField label="To" class="min-w-[130px]">
          <Input type="date" v-model="endDate" />
        </FormField>
        <FormField label="Actor ID" class="min-w-[110px]">
          <Input type="number" v-model="actorId" placeholder="User ID" />
        </FormField>
        <FormField label="Action" class="min-w-[130px]">
          <Input v-model="action" placeholder="e.g. create" />
        </FormField>
        <FormField label="Target Model" class="min-w-[140px]">
          <Input v-model="targetType" placeholder="e.g. Agent" />
        </FormField>
        <div class="flex gap-2 pb-0.5">
          <Button variant="default" size="sm" @click="applyFilter">
            <Filter class="w-4 h-4 mr-1" /> Filter
          </Button>
          <Button variant="outline" size="sm" @click="resetFilter">
            <RefreshCw class="w-4 h-4 mr-1" /> Reset
          </Button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 overflow-hidden mb-4">
      <div v-if="!logs.data?.length" class="py-2">
        <EmptyState
          icon="Search"
          title="No activity found"
          description="No records match the current filter."
        />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-cream">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Date</th>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Actor</th>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Action</th>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Target</th>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Description</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-stone-50">
              <td class="px-4 py-3 whitespace-nowrap text-stone-600 text-xs">
                {{ formatDate(log.created_at) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="font-medium text-forest-dark text-xs">
                  {{ log.user?.name || log.user?.email || 'System' }}
                </span>
                <div v-if="log.user?.email" class="text-xs text-stone-400">{{ log.user.email }}</div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <Badge :variant="actionVariant(log.action)" class="text-xs capitalize">
                  {{ log.action }}
                </Badge>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-stone-600 text-xs">
                <span class="font-mono">{{ targetLabel(log.target_type) }}</span>
                <span v-if="log.target_id" class="text-stone-400 ml-1">#{{ log.target_id }}</span>
              </td>
              <td class="px-4 py-3 text-stone-600 max-w-xs truncate text-xs" :title="log.description">
                {{ log.description || '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="logs.last_page > 1">
      <Pagination
        :current-page="logs.current_page"
        :per-page="logs.per_page"
        :total="logs.total"
        @change="changePage"
      />
    </div>
  </div>
</template>
