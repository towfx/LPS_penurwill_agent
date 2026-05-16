<template>
  <!-- Overlay for mobile -->
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
    @click="$emit('toggle')"
  />

  <!-- Sidebar -->
  <aside :class="`
    fixed left-0 top-0 h-screen w-64 text-white transform transition-transform duration-300 ease-in-out z-50
    ${isOpen ? 'translate-x-0' : '-translate-x-full'}
    lg:translate-x-0 lg:static lg:z-auto
  `" style="background-color: #162d25">
    <div class="p-6">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-xl font-bold" style="color: #bc9c5f">
          <span style="color: #eae1d0">Pen'urWill</span>
          <span style="color: #bc9c5f">{{ isAdmin ? 'Admin' : isPartner ? 'Partner' : 'Agent' }}</span>
        </h1>
        <button
          @click="$emit('toggle')"
          class="lg:hidden text-white transition-colors hover:text-amber-400"
        >
          <X size="24" />
        </button>
      </div>

      <nav class="space-y-2">
        <a
          v-for="(item, index) in menuItems"
          :key="index"
          :href="item.href"
          :class="`
            flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200
            ${item.active
              ? 'shadow-lg'
              : 'hover:bg-opacity-20 hover:bg-white'
            }
          `"
          :style="{
            backgroundColor: item.active ? '#bc9c5f' : 'transparent',
            color: item.active ? '#162d25' : '#eae1d0'
          }"
        >
          <div class="flex items-center space-x-3">
            <component :is="item.icon" size="20" />
            <span class="font-medium">{{ item.label }}</span>
          </div>
          <Badge v-if="item.badge" variant="secondary" class="ml-auto">
            {{ item.badge }}
          </Badge>
        </a>
      </nav>
    </div>
  </aside>
</template>

<script setup>
import {
  Home,
  Users,
  BarChart3,
  ShoppingCart,
  FileText,
  Calendar,
  Settings,
  X,
  Users as UsersIcon,
  DollarSign,
  User,
  Building2,
  Network,
  Receipt,
  AlertCircle,
  Briefcase,
  Award,
} from 'lucide-vue-next'
import Badge from './Badge.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useRoleNames } from '../../../composables/useRoleNames.js'

const { roleNames, roleNamesPlural } = useRoleNames()

// Role detection
const page = usePage()
const userRoles = computed(() => {
  return page.props.auth?.roles || []
})
const isAdmin = computed(() => {
  return userRoles.value.includes('admin')
})
const isAgent = computed(() => {
  return userRoles.value.includes('agent')
})
const isPartner = computed(() => {
  return userRoles.value.includes('partner')
})
const isBusinessPartner = computed(() => {
  return userRoles.value.includes('business_partner')
})
const isAgentLeader = computed(() => {
  return userRoles.value.includes('agent_leader')
})

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  menus: {
    type: Array,
    default: null
  },
  adminMenu: {
    type: Boolean,
    default: false
  }
})

defineEmits(['toggle'])

const defaultAdminMenus = computed(() => [
  { icon: Home, label: 'Dashboard', href: '/admin/dashboard' },
  { icon: Briefcase, label: roleNames.value.business_partner, href: '/admin/agents/list?type=business_partner' },
  { icon: Award, label: roleNames.value.agent_leader, href: '/admin/agents/list?type=agent_leader' },
  { icon: UsersIcon, label: roleNamesPlural.value.agent, href: '/admin/agents/list' },
  { icon: Network, label: 'Hierarchy', href: '/admin/agent/hierarchy' },
  { icon: ShoppingCart, label: 'Sales', href: '/admin/sales' },
  { icon: DollarSign, label: 'Payouts', href: '/admin/payouts' },
  { icon: Receipt, label: 'Fee Payments', href: '/admin/fee-payments' },
  { icon: AlertCircle, label: 'Pending Renewals', href: '/admin/fee-payments-pending' },
  { icon: Settings, label: 'System Settings', href: '/admin/system-settings' },
])

const defaultAgentMenus = computed(() => {
  const items = [
    { icon: BarChart3, label: 'Dashboard', href: '/agent/dashboard' },
    { icon: User, label: `${roleNames.value.agent} Profile`, href: '/agent/profile' },
    { icon: ShoppingCart, label: 'Sales & Commissions', href: '/agent/sales' },
    { icon: DollarSign, label: 'Payout', href: '/agent/payouts' },
  ]
  if (isBusinessPartner.value || isAgentLeader.value) {
    items.push({ icon: Network, label: 'Hierarchy', href: '/agent/hierarchy' })
  }
  return items
})

const defaultPartnerMenus = [
  { icon: BarChart3, label: 'Dashboard', href: '/partner/dashboard' },
]

const menuItems = computed(() => {
  // If custom menus are passed via props AND not empty, use them
  if (props.menus && Array.isArray(props.menus) && props.menus.length > 0) {
    return props.menus
  }

  // Use role-based menu detection
  if (isAdmin.value) {
    return defaultAdminMenus.value
  }
  if (isPartner.value) {
    return defaultPartnerMenus
  }
  if (isAgent.value || isBusinessPartner.value || isAgentLeader.value) {
    return defaultAgentMenus.value
  }

  // Fallback for users without specific roles
  return defaultAgentMenus.value
})
</script>
