<template>
  <div class="min-h-screen h-screen bg-cream font-sans">
    <div class="flex flex-col lg:flex-row h-full">
      <!-- Sidebar -->
      <Sidebar :isOpen="sidebarOpen" @toggle="toggleSidebar" :adminMenu="true" />

      <div class="flex-1 flex flex-col lg:ml-0 min-h-0">
        <!-- Header -->
        <Header @toggle="toggleSidebar" />

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto flex flex-col">
          <div class="flex-1 p-4 sm:p-6">
            <slot />
          </div>
          <footer class="py-4 border-t border-stone-200 mt-auto shrink-0 bg-cream">
            <div class="container mx-auto px-4 text-center">
              <p class="text-xs text-stone-500">{{ $page.props.appFooter }}</p>
            </div>
          </footer>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from './Components/Sidebar.vue'
import Header from './Components/Header.vue'

const sidebarOpen = ref(false)
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>
