<template>
  <div class="space-y-6">
    <PageHeader
      title="Email Templates"
      description="Manage and customize system email notifications sent to users."
      :breadcrumbs="[{ label: 'Dashboard', href: '/admin/dashboard' }, { label: 'Email Templates' }]"
    />

    <Card class="bg-white shadow-sm border border-gray-200">
      <CardHeader>
        <CardTitle>Templates List</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-cream">
              <tr>
                <th class="px-4 py-3 font-medium text-gray-700">Template Title</th>
                <th class="px-4 py-3 font-medium text-gray-700">Ref / Slug</th>
                <th class="px-4 py-3 font-medium text-gray-700">Last Updated</th>
                <th class="px-4 py-3 text-right font-medium text-gray-700">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="template in templates" :key="template.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-forest-dark">{{ template.registry_title || template.title }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ template.ref }}</td>
                <td class="px-4 py-3 text-gray-500">
                  {{ template.updated_at ? new Date(template.updated_at).toLocaleDateString() : 'Never' }}
                </td>
                <td class="px-4 py-3 text-right">
                  <Button variant="outline" size="sm" @click="() => router.visit(`/admin/email-templates/${template.id}/edit`)">
                    <Edit class="w-4 h-4 mr-2" />
                    Edit
                  </Button>
                </td>
              </tr>
              <tr v-if="!templates.length">
                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                  No templates found. Run the seeder to populate default templates.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { Edit } from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import CardContent from '../Design/Components/CardContent.vue'
import Button from '../Design/Components/Button.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import AdminLayout from '../Design/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  templates: { type: Array, required: true },
})
</script>
