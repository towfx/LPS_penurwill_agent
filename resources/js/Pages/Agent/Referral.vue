<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import StatsCard from '../Design/Components/StatsCard.vue'
import Button from '../Design/Components/Button.vue'
import Badge from '../Design/Components/Badge.vue'
import EmptyState from '../Design/Components/EmptyState.vue'
import Pagination from '../Design/Components/Pagination.vue'
import FormField from '../Design/Components/FormField.vue'
import Input from '../Design/Components/Input.vue'
import { Copy, Check, Link2, Users, TrendingUp, Clock } from 'lucide-vue-next'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  referralCode: { type: Object, default: null },
  stats: { type: Object, default: () => ({}) },
  visits: { type: Object, required: true }, // paginated
  filters: { type: Object, default: () => ({}) },
})

const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')
const convertedFilter = ref(props.filters.converted || '')
const copied = ref(false)

const shareableUrl = computed(() => {
  if (!props.referralCode?.code) return ''
  return `${window.location.origin}/register-as-agent?ref=${props.referralCode.code}`
})

function copyCode() {
  const text = props.referralCode?.code || ''
  navigator.clipboard.writeText(text).then(() => {
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  })
}

function copyUrl() {
  navigator.clipboard.writeText(shareableUrl.value).then(() => {
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  })
}

function applyFilter() {
  router.get('/agent/referral', {
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    converted: convertedFilter.value || undefined,
  }, { preserveState: false })
}

function resetFilter() {
  startDate.value = ''
  endDate.value = ''
  convertedFilter.value = ''
  router.get('/agent/referral', {}, { preserveState: false })
}

function changePage(page) {
  router.get('/agent/referral', {
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    converted: convertedFilter.value || undefined,
    page,
  }, { preserveScroll: true })
}

function formatDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatDateTime(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('en-MY', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

const conversionRate = computed(() => {
  const r = props.stats.conversion_rate ?? 0
  return typeof r === 'number' ? r.toFixed(1) + '%' : r
})
</script>

<template>
  <div>
    <PageHeader
      title="Referral"
      description="Your referral code, link, and visitor conversion stats."
      :breadcrumbs="[{ label: 'Agent', href: '/agent/dashboard' }, { label: 'Referral' }]"
    />

    <!-- Referral Code Card -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-forest-dark mb-4 flex items-center gap-2">
        <Link2 class="text-gold" size="20" /> Your Referral Code
      </h2>

      <div v-if="referralCode" class="space-y-4">
        <!-- Code display -->
        <div class="flex items-center gap-3">
          <span class="font-mono text-2xl font-bold text-forest-dark bg-cream px-4 py-2 rounded-lg border border-stone-200 tracking-widest">
            {{ referralCode.code }}
          </span>
          <Button variant="outline" size="sm" @click="copyCode">
            <Check v-if="copied" class="w-4 h-4 text-accent-green" />
            <Copy v-else class="w-4 h-4" />
            {{ copied ? 'Copied!' : 'Copy Code' }}
          </Button>
        </div>

        <!-- Shareable URL -->
        <div class="space-y-1">
          <p class="text-xs font-medium text-stone-500 uppercase tracking-wide">Shareable Registration Link</p>
          <div class="flex items-center gap-2">
            <code class="text-sm bg-stone-50 border border-stone-200 rounded px-3 py-1.5 text-stone-700 flex-1 truncate">
              {{ shareableUrl }}
            </code>
            <Button variant="outline" size="sm" @click="copyUrl">
              <Copy class="w-4 h-4" />
            </Button>
          </div>
        </div>

        <div class="text-xs text-stone-500">
          Total used: <span class="font-semibold text-forest-dark">{{ referralCode.used_count ?? 0 }}</span> times
        </div>
      </div>

      <div v-else class="text-stone-500 text-sm">No referral code assigned yet. Contact support.</div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatsCard
        title="Total Visits"
        :value="String(stats.total_visits ?? 0)"
        icon="Users"
      />
      <StatsCard
        title="Converted"
        :value="String(stats.converted_visits ?? 0)"
        icon="TrendingUp"
      />
      <StatsCard
        title="Conversion Rate"
        :value="conversionRate"
        icon="Target"
      />
      <StatsCard
        title="Avg Days to Convert"
        :value="stats.avg_days_to_convert != null ? String(Number(stats.avg_days_to_convert).toFixed(1)) : '—'"
        icon="Clock"
      />
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 p-4 mb-4">
      <div class="flex flex-wrap items-end gap-3">
        <FormField label="From" class="min-w-[140px]">
          <Input type="date" v-model="startDate" />
        </FormField>
        <FormField label="To" class="min-w-[140px]">
          <Input type="date" v-model="endDate" />
        </FormField>
        <FormField label="Status" class="min-w-[140px]">
          <select
            v-model="convertedFilter"
            class="block w-full rounded-md border border-stone-200 bg-white px-3 py-2 text-sm text-stone-900 focus:outline-none focus:ring-2 focus:ring-forest-dark"
          >
            <option value="">All</option>
            <option value="1">Converted</option>
            <option value="0">Not Converted</option>
          </select>
        </FormField>
        <div class="flex gap-2 pb-0.5">
          <Button variant="default" size="sm" @click="applyFilter">Apply</Button>
          <Button variant="outline" size="sm" @click="resetFilter">Reset</Button>
        </div>
      </div>
    </div>

    <!-- Visits Table -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 overflow-hidden mb-4">
      <div class="px-5 py-3 border-b border-stone-200">
        <h3 class="text-base font-semibold text-forest-dark">Visits</h3>
      </div>

      <div v-if="!visits.data?.length" class="py-2">
        <EmptyState
          icon="Search"
          title="No visits found"
          description="No visits match your current filter."
        />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-cream">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Date</th>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Visitor</th>
              <th class="px-4 py-3 text-center font-medium text-stone-500 uppercase text-xs tracking-wide">Converted</th>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Linked Sale</th>
              <th class="px-4 py-3 text-left font-medium text-stone-500 uppercase text-xs tracking-wide">Time to Convert</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            <tr v-for="visit in visits.data" :key="visit.id" class="hover:bg-stone-50">
              <td class="px-4 py-3 whitespace-nowrap">{{ formatDateTime(visit.created_at) }}</td>
              <td class="px-4 py-3 text-stone-500 text-xs">
                <span class="font-mono">{{ visit.ip_address ? visit.ip_address.replace(/\d+$/, '***') : '—' }}</span>
              </td>
              <td class="px-4 py-3 text-center">
                <Badge :variant="visit.is_converted ? 'success' : 'secondary'">
                  {{ visit.is_converted ? 'Yes' : 'No' }}
                </Badge>
              </td>
              <td class="px-4 py-3 text-stone-600">
                {{ visit.sale_id ? `#${visit.sale_id}` : '—' }}
              </td>
              <td class="px-4 py-3 text-stone-600">
                {{ visit.days_to_convert != null ? visit.days_to_convert + ' day(s)' : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="visits.last_page > 1">
      <Pagination
        :current-page="visits.current_page"
        :per-page="visits.per_page"
        :total="visits.total"
        @change="changePage"
      />
    </div>
  </div>
</template>
