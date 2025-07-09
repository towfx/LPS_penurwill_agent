<template>
  <Card class="hover:shadow-md transition-shadow">
    <CardContent class="p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-sm font-medium text-gray-600">{{ title }}</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ value }}</p>
        </div>
        <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-cream">
          <component :is="iconComponent" size="24" class="text-gold" />
        </div>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <p class="text-sm" :style="{ color: trendColors[trend] }">
            <TrendingUp size="16" class="inline mr-1" />
            {{ change }}
          </p>
          <span v-if="progress" class="text-xs text-gray-500">{{ progress }}%</span>
        </div>
        <Progress v-if="progress" :value="progress" />
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import {
  TrendingUp,
  Users,
  ShoppingCart,
  Activity,
  DollarSign
} from 'lucide-vue-next'
import Card from './Card.vue'
import CardContent from './CardContent.vue'
import Progress from './Progress.vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: String,
    required: true
  },
  change: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    required: true
  },
  trend: {
    type: String,
    default: 'neutral',
    validator: (value) => ['up', 'down', 'neutral'].includes(value)
  },
  progress: {
    type: Number,
    default: null
  }
})

const iconComponent = computed(() => {
  const icons = {
    DollarSign,
    Users,
    ShoppingCart,
    Activity
  }
  return icons[props.icon] || Activity
})

const trendColors = {
  up: '#7a9b7d',
  down: '#d4423f',
  neutral: '#8a9ba8'
}
</script>
