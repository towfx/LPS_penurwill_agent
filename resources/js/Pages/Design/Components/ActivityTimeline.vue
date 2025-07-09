<template>
  <Card>
    <CardHeader>
      <CardTitle>Recent Activity</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <div v-for="activity in activities" :key="activity.id" class="flex items-start space-x-3">
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center text-white"
            :style="{ backgroundColor: getActivityColor(activity.type) }"
          >
            <component :is="getActivityIcon(activity.type)" size="16" />
          </div>
          <div class="flex-1">
            <p class="text-sm text-gray-900">
              <span class="font-medium">{{ activity.user }}</span> {{ activity.action }}
            </p>
            <p class="text-xs text-gray-500">{{ activity.time }}</p>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import {
  Plus,
  Clock,
  XCircle,
  CheckCircle,
  Activity
} from 'lucide-vue-next'
import Card from './Card.vue'
import CardHeader from './CardHeader.vue'
import CardContent from './CardContent.vue'
import CardTitle from './CardTitle.vue'

const activities = [
  { id: 1, user: 'Alice Johnson', action: 'created new project', time: '2 minutes ago', type: 'create' },
  { id: 2, user: 'Bob Smith', action: 'updated user profile', time: '15 minutes ago', type: 'update' },
  { id: 3, user: 'Carol Davis', action: 'deleted old reports', time: '1 hour ago', type: 'delete' },
  { id: 4, user: 'David Wilson', action: 'completed task review', time: '2 hours ago', type: 'complete' },
]

const getActivityIcon = (type) => {
  switch(type) {
    case 'create': return Plus
    case 'update': return Clock
    case 'delete': return XCircle
    case 'complete': return CheckCircle
    default: return Activity
  }
}

const getActivityColor = (type) => {
  switch(type) {
    case 'create': return '#7a9b7d'
    case 'update': return '#4a6b73'
    case 'delete': return '#d4423f'
    case 'complete': return '#bc9c5f'
    default: return '#8a9ba8'
  }
}
</script>
