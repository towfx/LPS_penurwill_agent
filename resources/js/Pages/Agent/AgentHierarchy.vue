<template>
  <div>
    <PageHeader
      title="My Downline Hierarchy"
      :breadcrumbs="[{ label: 'Dashboard', href: '/agent/dashboard' }, { label: 'Hierarchy' }]"
    >
      <template #actions>
        <div class="flex items-center space-x-2">
          <Button variant="outline" size="sm" @click="vocApi?.zoomReset">
            <RotateCcw size="14" class="mr-1" /> Reset
          </Button>
          <Button variant="outline" size="sm" @click="vocApi?.expandAll">
            Expand All
          </Button>
          <Button variant="outline" size="sm" @click="vocApi?.collapseAll">
            Collapse All
          </Button>
        </div>
      </template>
    </PageHeader>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 overflow-hidden min-h-[75vh] flex flex-col">
      <!-- Toolbar -->
      <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <button @click="vocApi?.zoomIn" class="p-2 hover:bg-slate-100 rounded-lg transition-colors border border-slate-200" title="Zoom In">
            <PlusIcon size="18" />
          </button>
          <button @click="vocApi?.zoomOut" class="p-2 hover:bg-slate-100 rounded-lg transition-colors border border-slate-200" title="Zoom Out">
            <MinusIcon size="18" />
          </button>
          <button @click="vocApi?.minimap.toggle" class="p-2 hover:bg-slate-100 rounded-lg transition-colors border border-slate-200 flex items-center space-x-2">
            <MapIcon size="18" />
            <span class="text-sm font-medium">Minimap</span>
          </button>
        </div>
      </div>

      <!-- Chart Area -->
      <div class="flex-1 relative border border-slate-100 rounded-lg overflow-hidden bg-slate-50">
        <Vue3OrgChart
          minimap
          @on-ready="initVue3OrgChart"
          :data="hierarchyData"
          style="--vue3-org-chart-line-color: #162d25"
        >
          <template #node="{item, children, open, toggleChildren}">
            <div class="flex flex-col items-center">
              <div class="org-node" :class="{'is-active': open}">
                <div class="org-node-header">{{ item.title }}</div>
                <div class="org-node-body">
                  <div v-if="item.imageUrl" class="org-node-avatar">
                    <img :src="item.imageUrl" alt="avatar">
                  </div>
                  <div v-else class="org-node-avatar-placeholder">
                    <User size="20" />
                  </div>
                  <div class="org-node-info">
                    <div class="org-node-name" :title="item.name">{{ item.name }}</div>
                    <div class="org-node-id text-[10px] text-slate-400">ID: {{ item.id }}</div>
                  </div>
                </div>
              </div>

              <div v-if="children.length" class="mt-2 h-0 relative flex justify-center">
                <button
                  @click.stop="toggleChildren"
                  class="org-toggle-btn absolute top-0 -translate-y-1/2"
                >
                  {{ open ? '−' : '+' }}
                </button>
              </div>
            </div>
          </template>

          <template #no-data>
            <div class="flex flex-col items-center justify-center h-full text-slate-400">
              <NetworkIcon size="48" class="mb-2 opacity-20" />
              <p>No downline data available</p>
            </div>
          </template>
        </Vue3OrgChart>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AgentLayout from '../Design/AgentLayout.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Button from '../Design/Components/Button.vue'
import { Vue3OrgChart } from 'vue3-org-chart'
import 'vue3-org-chart/dist/style.css'
import {
  Plus as PlusIcon,
  Minus as MinusIcon,
  RotateCcw,
  Map as MapIcon,
  User,
  Network as NetworkIcon
} from 'lucide-vue-next'

defineOptions({ layout: AgentLayout })

defineProps({
  hierarchyData: {
    type: Array,
    required: true
  }
})

const vocApi = ref(null)

const initVue3OrgChart = ({api}) => {
  vocApi.value = api
}
</script>

<style scoped>
.org-node {
  width: 180px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
  transition: all 0.2s ease;
}

.org-node:hover {
  border-color: #bc9c5f;
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  transform: translateY(-2px);
}

.org-node.is-active {
  border-color: #162d25;
}

.org-node-header {
  background-color: #162d25;
  color: #bc9c5f;
  font-size: 10px;
  font-weight: 700;
  padding: 6px 10px;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.org-node-body {
  padding: 12px 10px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.org-node-avatar, .org-node-avatar-placeholder {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background-color: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px #e2e8f0;
}

.org-node-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.org-node-avatar-placeholder {
  color: #94a3b8;
}

.org-node-info {
  flex-grow: 1;
  min-width: 0;
}

.org-node-name {
  color: #1e293b;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.org-toggle-btn {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: bold;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
}

.org-toggle-btn:hover {
  background: #162d25;
  color: #bc9c5f;
  border-color: #162d25;
}

:deep(.vue3-org-chart-line) {
  background-color: #162d25 !important;
}

::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
