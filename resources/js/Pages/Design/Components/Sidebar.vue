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
        <h1 class="text-xl font-bold" style="color: #bc9c5f">AdminPanel</h1>
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
  DollarSign
} from 'lucide-vue-next'
import Badge from './Badge.vue'
import { computed } from 'vue'

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

const defaultAdminMenus = [
  { icon: Home, label: 'Dashboard', href: '/admin/dashboard' },
  { icon: UsersIcon, label: 'Agents', href: '/admin/agents/list' },
  { icon: DollarSign, label: 'Commissions', href: '/admin/commissions/list' },
  { icon: Settings, label: 'System Settings', href: '/admin/system-settings' },
]

const menuItems = computed(() => {
  if (props.menus && Array.isArray(props.menus)) {
    return props.menus
  }
  if (props.adminMenu) {
    return defaultAdminMenus
  }
  // fallback to old navItems if needed
  return [
    { icon: Home, label: 'Dashboard', href: '#' },
    { icon: Users, label: 'Users', badge: '23', href: '#' },
    { icon: BarChart3, label: 'Analytics', href: '#' },
    { icon: ShoppingCart, label: 'Orders', badge: '12', href: '#' },
    { icon: FileText, label: 'Reports', href: '#' },
    { icon: Calendar, label: 'Calendar', href: '#' },
    { icon: Settings, label: 'Settings', href: '#' },
  ]
})
</script>
