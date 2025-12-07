<template>
  <header class="bg-white shadow-sm border-b border-cream">
    <div class="flex items-center justify-between px-6 py-4">
      <div class="flex items-center space-x-4">
        <Button
          variant="ghost"
          size="icon"
          @click="$emit('toggle')"
          class="lg:hidden"
        >
          <Menu size="24" />
        </Button>

        <div class="relative" style="display: none;">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size="20" />
          <input
            type="text"
            placeholder="Search everything..."
            class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent w-64 border-cream focus:ring-gold focus:border-gold"
          />
        </div>
      </div>

      <div class="flex items-center space-x-4">
        <Button variant="ghost" size="icon" class="relative" style="display: none;">
          <Bell size="20" />
          <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
            3
          </span>
        </Button>

        <!-- <Button variant="secondary">
          <Plus size="16" class="mr-2" />
          New Project
        </Button> -->

        <!-- User Dropdown -->
        <div class="relative" @mouseenter="showDropdown = true" @mouseleave="showDropdown = false">
          <div class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 rounded-lg px-3 py-2 transition-colors" @click="showDropdown = !showDropdown">
            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gold">
              <User size="16" class="text-white" />
            </div>
            <span class="font-medium">{{ userName }}</span>
            <ChevronDown size="16" class="text-gray-500" />
          </div>
          <transition name="fade">
            <div v-if="showDropdown" class="absolute right-0 mt-2 w-48 bg-white border border-stone-200 rounded-lg shadow-lg z-50">
              <div class="px-4 py-3 border-b border-stone-100">
                <div class="flex flex-col">
                  <div class="font-medium text-stone-900">{{ userName }}</div>
                  <div class="text-xs text-stone-500">{{ userEmail }}</div>
                </div>
              </div>
              <div class="py-2 space-y-1">
                <Link :href="getDashboardLink" class="flex items-center gap-3 w-full px-4 py-2.5 text-stone-700 hover:bg-stone-50 rounded-lg transition-colors">
                  <LayoutDashboard size="20" class="flex-shrink-0 text-stone-600" />
                  <span class="font-medium">{{ getDashboardText }}</span>
                </Link>
                <Link href="/profile" class="flex items-center gap-3 w-full px-4 py-2.5 text-stone-700 hover:bg-stone-50 rounded-lg transition-colors">
                  <Settings size="20" class="flex-shrink-0 text-stone-600" />
                  <span class="font-medium">Profile Settings</span>
                </Link>
                <Link href="/logout" method="post" as="button" class="flex items-center gap-3 w-full px-4 py-2.5 text-stone-700 hover:bg-stone-50 rounded-lg transition-colors">
                  <LogOut size="20" class="flex-shrink-0 text-stone-600" />
                  <span class="font-medium">Logout</span>
                </Link>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import {
  Menu,
  Search,
  Bell,
  Plus,
  User,
  ChevronDown,
  LayoutDashboard,
  Settings,
  LogOut
} from 'lucide-vue-next'
import Button from './Button.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

defineEmits(['toggle'])

const showDropdown = ref(false)
const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const userRoles = computed(() => page.props.auth?.roles || [])
const userName = computed(() => user.value.name || 'User')
const userEmail = computed(() => user.value.email || '')

// Role checks
const isAdmin = computed(() => userRoles.value.includes('admin'))
const isAgent = computed(() => userRoles.value.includes('agent'))

// Navigation based on role
const getDashboardLink = computed(() => {
  if (isAdmin.value) return '/admin/dashboard'
  if (isAgent.value) return '/agent/dashboard'
  return '/dashboard'
})

const getDashboardText = computed(() => {
  if (isAdmin.value) return 'Admin Dashboard'
  if (isAgent.value) return 'Agent Dashboard'
  return 'Dashboard'
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.15s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
