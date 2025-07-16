<template>
  <div class="space-y-4">
    <div v-for="activity in activities" :key="activity.id" class="flex items-start space-x-3">
      <!-- Activity Icon -->
      <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
           :class="getActivityIconClass(activity.action)">
        <component :is="getActivityIcon(activity.action)" size="16" class="text-white" />
      </div>

      <!-- Activity Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-forest-dark">
            {{ activity.user_name }}
          </p>
          <p class="text-xs text-stone-500">
            {{ activity.created_at }}
          </p>
        </div>
        <p class="text-sm text-stone-700 mt-1">
          {{ activity.description }}
        </p>
        <div class="flex items-center mt-1">
          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                :class="getActivityBadgeClass(activity.action)">
            {{ formatAction(activity.action) }}
          </span>
          <span v-if="activity.target_type" class="ml-2 text-xs text-stone-500">
            {{ formatTargetType(activity.target_type) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!activities.length" class="text-center py-8">
      <div class="w-12 h-12 mx-auto rounded-full bg-cream flex items-center justify-center mb-3">
        <Activity size="24" class="text-gold" />
      </div>
      <p class="text-stone-500 text-sm">No recent activity</p>
    </div>
  </div>
</template>

<script setup>
import {
  User,
  DollarSign,
  ShoppingCart,
  Settings,
  Plus,
  Edit,
  Trash2,
  Activity
} from 'lucide-vue-next'

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  }
})

function getActivityIcon(action) {
  const icons = {
    'create': Plus,
    'update': Edit,
    'delete': Trash2,
    'login': User,
    'sale': ShoppingCart,
    'commission': DollarSign,
    'payout': DollarSign,
    'settings': Settings,
    'default': Activity
  }
  return icons[action] || icons.default
}

function getActivityIconClass(action) {
  const classes = {
    'create': 'bg-accent-green',
    'update': 'bg-accent-blue',
    'delete': 'bg-accent-red',
    'login': 'bg-accent-gray',
    'sale': 'bg-gold',
    'commission': 'bg-accent-green',
    'payout': 'bg-accent-blue',
    'settings': 'bg-accent-orange',
    'default': 'bg-accent-gray'
  }
  return classes[action] || classes.default
}

function getActivityBadgeClass(action) {
  const classes = {
    'create': 'bg-accent-green/20 text-accent-green',
    'update': 'bg-accent-blue/20 text-accent-blue',
    'delete': 'bg-accent-red/20 text-accent-red',
    'login': 'bg-accent-gray/20 text-accent-gray',
    'sale': 'bg-gold/20 text-gold',
    'commission': 'bg-accent-green/20 text-accent-green',
    'payout': 'bg-accent-blue/20 text-accent-blue',
    'settings': 'bg-accent-orange/20 text-accent-orange',
    'default': 'bg-accent-gray/20 text-accent-gray'
  }
  return classes[action] || classes.default
}

function formatAction(action) {
  return action.charAt(0).toUpperCase() + action.slice(1)
}

function formatTargetType(targetType) {
  if (!targetType) return ''
  return targetType.split('\\').pop()
}
</script>
