<template>
  <div class="min-h-screen h-screen bg-cream font-sans">
    <div class="flex flex-col lg:flex-row h-full">
      <!-- Sidebar -->
      <Sidebar :isOpen="sidebarOpen" @toggle="toggleSidebar" :menus="agentMenus" />

      <div class="flex-1 flex flex-col lg:ml-0 min-h-0">
        <!-- Header -->
        <Header @toggle="toggleSidebar" />

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6 overflow-y-auto">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from './Components/Sidebar.vue'
import Header from './Components/Header.vue'
import { BarChart3, User, DollarSign } from 'lucide-vue-next'

const sidebarOpen = ref(false)
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const agentMenus = [
  { icon: BarChart3, label: 'Dashboard', href: '/agent/dashboard' },
  { icon: User, label: 'Agent Profile', href: '/agent/profile' },
  { icon: DollarSign, label: 'Commissions', href: '/agent/commissions' },
]
</script>
