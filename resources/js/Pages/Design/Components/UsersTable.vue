<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Team Members</CardTitle>
        <div class="flex items-center space-x-2">
          <Button variant="outline" size="sm">
            <Filter size="16" class="mr-2" />
            Filter
          </Button>
          <Button variant="outline" size="sm">
            <Download size="16" class="mr-2" />
            Export
          </Button>
          <Button size="sm">
            <Plus size="16" class="mr-2" />
            Add User
          </Button>
        </div>
      </div>
    </CardHeader>

    <CardContent class="p-0">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-cream">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">User</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Last Active</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Join Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 bg-gold">
                    <span class="text-white text-sm font-medium">
                      {{ user.name.charAt(0) }}
                    </span>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="getRoleVariant(user.role)">
                  {{ user.role }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="getStatusVariant(user.status)">
                  {{ user.status }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.lastActive }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.joinDate }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="flex items-center space-x-2">
                  <Button variant="ghost" size="sm">
                    <Eye size="16" />
                  </Button>
                  <Button variant="ghost" size="sm">
                    <MoreHorizontal size="16" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import {
  Filter,
  Download,
  Plus,
  Eye,
  MoreHorizontal
} from 'lucide-vue-next'
import Card from './Card.vue'
import CardHeader from './CardHeader.vue'
import CardContent from './CardContent.vue'
import CardTitle from './CardTitle.vue'
import Button from './Button.vue'
import Badge from './Badge.vue'

const users = [
  { id: 1, name: 'Alice Johnson', email: 'alice@example.com', role: 'Admin', status: 'Active', joinDate: '2024-01-15', lastActive: '2 min ago' },
  { id: 2, name: 'Bob Smith', email: 'bob@example.com', role: 'User', status: 'Active', joinDate: '2024-01-14', lastActive: '1 hour ago' },
  { id: 3, name: 'Carol Davis', email: 'carol@example.com', role: 'Manager', status: 'Away', joinDate: '2024-01-13', lastActive: '2 days ago' },
  { id: 4, name: 'David Wilson', email: 'david@example.com', role: 'User', status: 'Inactive', joinDate: '2024-01-12', lastActive: '1 week ago' },
  { id: 5, name: 'Eva Brown', email: 'eva@example.com', role: 'Admin', status: 'Active', joinDate: '2024-01-11', lastActive: '5 min ago' },
]

const getStatusVariant = (status) => {
  switch(status) {
    case 'Active': return 'success'
    case 'Away': return 'warning'
    case 'Inactive': return 'destructive'
    default: return 'default'
  }
}

const getRoleVariant = (role) => {
  switch(role) {
    case 'Admin': return 'secondary'
    case 'Manager': return 'outline'
    case 'User': return 'default'
    default: return 'default'
  }
}
</script>
