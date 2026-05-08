<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import Badge from '../Design/Components/Badge.vue'
import EmptyState from '../Design/Components/EmptyState.vue'
import Pagination from '../Design/Components/Pagination.vue'
import { Check, MailOpen } from 'lucide-vue-next'

defineOptions({ layout: AgentLayout })

const props = defineProps({
  notifications: { type: Object, required: true }, // Laravel paginated
  activeTab: { type: String, default: 'unread' },
  unreadCount: { type: Number, default: 0 },
})

const currentTab = ref(props.activeTab)

const tabs = [
  { key: 'unread', label: 'Unread' },
  { key: 'pending', label: 'Pending Action' },
  { key: 'archived', label: 'Archived' },
]

function switchTab(key) {
  currentTab.value = key
  router.get('/agent/inbox', { tab: key }, { preserveState: false })
}

function markRead(id) {
  router.post(`/agent/inbox/${id}/read`, {}, { preserveScroll: true })
}

function markAllRead() {
  router.post('/agent/inbox/read-all', {}, { preserveScroll: true })
}

function changePage(page) {
  router.get('/agent/inbox', { tab: currentTab.value, page }, { preserveScroll: true })
}

function formatDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('en-MY', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function truncate(str, n = 140) {
  if (!str) return ''
  return str.length > n ? str.slice(0, n) + '…' : str
}

const TYPE_EMOJI = {
  agent_approved: '✅', agent_rejected: '❌', fee_payment: '💳',
  commission_earned: '💰', commission_reversed: '↩️',
  payout_created: '📤', payout_paid: '✅', payout_cancelled: '🚫',
  new_team_member: '👥', appeal_received: '📋', approval_requested: '📨',
}
function typeEmoji(type) { return TYPE_EMOJI[type] || '🔔' }
</script>

<template>
  <div>
    <PageHeader
      title="Inbox"
      description="Your notifications and important messages."
      :breadcrumbs="[{ label: 'Agent', href: '/agent/dashboard' }, { label: 'Inbox' }]"
    >
      <template #actions>
        <Button
          v-if="currentTab === 'unread' && notifications.data?.length > 0"
          variant="outline"
          size="sm"
          @click="markAllRead"
        >
          <Check class="w-4 h-4 mr-1" /> Mark All Read
        </Button>
      </template>
    </PageHeader>

    <!-- Tabs -->
    <div class="flex gap-1 border-b border-stone-200 mb-6">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="px-4 py-2.5 text-sm font-medium transition-colors flex items-center gap-2"
        :class="currentTab === tab.key
          ? 'border-b-2 border-forest-dark text-forest-dark'
          : 'text-stone-500 hover:text-forest-dark'"
        @click="switchTab(tab.key)"
      >
        {{ tab.label }}
        <span
          v-if="tab.key === 'unread' && unreadCount > 0"
          class="inline-flex items-center justify-center h-5 min-w-[20px] px-1.5 rounded-full text-xs font-bold bg-accent-red text-white"
        >
          {{ unreadCount }}
        </span>
      </button>
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-xl shadow-sm border border-stone-200 overflow-hidden">
      <div v-if="!notifications.data?.length" class="py-2">
        <EmptyState
          icon="Inbox"
          title="No notifications"
          description="You have no messages in this tab."
        />
      </div>

      <ul v-else class="divide-y divide-stone-100">
        <li
          v-for="n in notifications.data"
          :key="n.id"
          class="flex items-start gap-4 px-5 py-4 hover:bg-stone-50 transition-colors"
          :class="n.status === 'unread' ? 'bg-blue-50/30' : ''"
        >
          <!-- Icon -->
          <div class="flex-shrink-0 w-10 h-10 rounded-full bg-cream flex items-center justify-center text-xl mt-0.5 select-none">
            {{ typeEmoji(n.type) }}
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2 flex-wrap">
              <p class="font-semibold text-forest-dark text-sm">{{ n.subject }}</p>
              <span class="text-xs text-stone-400 whitespace-nowrap">{{ formatDate(n.created_at) }}</span>
            </div>
            <p class="text-sm text-stone-600 mt-1">{{ truncate(n.body) }}</p>
          </div>

          <!-- Actions -->
          <div class="flex-shrink-0 flex items-center gap-2 mt-0.5">
            <Badge v-if="n.status === 'unread'" variant="secondary" class="text-xs">New</Badge>
            <Button
              v-if="n.status === 'unread'"
              variant="ghost"
              size="sm"
              @click="markRead(n.id)"
              title="Mark as read"
            >
              <MailOpen class="w-4 h-4" />
            </Button>
          </div>
        </li>
      </ul>
    </div>

    <!-- Pagination -->
    <div v-if="notifications.last_page > 1" class="mt-4">
      <Pagination
        :current-page="notifications.current_page"
        :per-page="notifications.per_page"
        :total="notifications.total"
        @change="changePage"
      />
    </div>
  </div>
</template>
