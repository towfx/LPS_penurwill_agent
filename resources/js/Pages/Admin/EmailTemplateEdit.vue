<template>
  <div class="space-y-6">
    <PageHeader
      :title="registry.title || template.ref"
      description="Edit the content for this email template."
      :breadcrumbs="[{ label: 'Dashboard', href: '/admin/dashboard' }, { label: 'Email Templates', href: '/admin/email-templates' }, { label: 'Edit' }]"
    />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left side: Form -->
      <div class="space-y-6">
        <form @submit.prevent="submit" class="space-y-6">
          <Card class="bg-white shadow-sm border border-gray-200">
            <CardHeader>
              <CardTitle>Subject Line</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <Input v-model="form.title" class="w-full" />
                <p class="text-xs text-gray-500">
                  Available variables: {{ registry.required_vars.map(v => `[${v}]`).join(', ') }}
                </p>
              </div>
            </CardContent>
          </Card>

          <Card class="bg-white shadow-sm border border-gray-200">
            <CardHeader>
              <CardTitle>Content Blocks</CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
              <div v-for="(spec, varName) in registry.messages" :key="varName" class="space-y-2">
                <label class="block font-medium text-gray-700">
                  {{ spec.label }} <code class="text-xs ml-2 bg-gray-100 px-1 rounded">messages.{{ varName }}</code>
                </label>
                
                <div v-if="spec.type === 'quill'" class="border border-gray-300 rounded overflow-hidden">
                  <QuillEditor
                    v-model:content="form.messages[varName]"
                    contentType="html"
                    theme="snow"
                    toolbar="minimal"
                    style="min-height: 150px"
                  />
                </div>
                
                <textarea
                  v-else
                  v-model="form.messages[varName]"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-gold focus:ring focus:ring-gold focus:ring-opacity-50"
                  rows="3"
                ></textarea>

                <p class="text-xs text-gray-500">
                  Available variables: {{ registry.required_vars.map(v => `[${v}]`).join(', ') }}
                </p>
              </div>
            </CardContent>
          </Card>

          <div class="flex justify-between items-center">
            <Button type="button" variant="outline" @click="fetchPreview">
              <Eye class="w-4 h-4 mr-2" />
              Refresh Preview
            </Button>
            
            <div class="flex space-x-2">
              <Button type="button" variant="outline" @click="() => router.visit('/admin/email-templates')">Cancel</Button>
              <Button type="submit" variant="default" :disabled="form.processing">
                Save Changes
              </Button>
            </div>
          </div>
        </form>
      </div>

      <!-- Right side: Preview -->
      <div class="space-y-6">
        <Card class="bg-white shadow-sm border border-gray-200 sticky top-6">
          <CardHeader>
            <div class="flex justify-between items-center">
              <CardTitle>Live Preview</CardTitle>
              <Badge v-if="previewData?.missing_vars?.length" variant="destructive">
                Missing Variables
              </Badge>
            </div>
          </CardHeader>
          <CardContent>
            <div v-if="previewData?.missing_vars?.length" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
              <div class="flex">
                <AlertTriangle class="h-5 w-5 text-red-500" />
                <div class="ml-3">
                  <p class="text-sm text-red-700">
                    <strong>Warning:</strong> The following variables are used in your content but not provided by the system:
                  </p>
                  <ul class="list-disc pl-5 mt-1 text-sm text-red-700">
                    <li v-for="v in previewData.missing_vars" :key="v">[{{ v }}]</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="border border-gray-200 rounded-md bg-gray-50 p-4 h-[600px] overflow-y-auto">
              <div v-if="loadingPreview" class="flex justify-center items-center h-full">
                <Loader2 class="w-8 h-8 animate-spin text-gray-400" />
              </div>
              <div v-else-if="previewData?.html" v-html="previewData.html" class="bg-white shadow-sm p-4 min-h-full"></div>
              <div v-else class="flex justify-center items-center h-full text-gray-500">
                Click 'Refresh Preview' to load
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { Edit, Eye, AlertTriangle, Loader2 } from 'lucide-vue-next'
import Card from '../Design/Components/Card.vue'
import CardHeader from '../Design/Components/CardHeader.vue'
import CardTitle from '../Design/Components/CardTitle.vue'
import CardContent from '../Design/Components/CardContent.vue'
import Button from '../Design/Components/Button.vue'
import PageHeader from '../Design/Components/PageHeader.vue'
import Input from '../Design/Components/Input.vue'
import Badge from '../Design/Components/Badge.vue'
import AdminLayout from '../Design/AdminLayout.vue'
import axios from 'axios'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  template: { type: Object, required: true },
  registry: { type: Object, required: true },
})

const form = useForm({
  title: props.template.title,
  messages: props.template.messages || {},
})

// Initialize missing messages with default from registry
Object.keys(props.registry.messages).forEach(key => {
  if (form.messages[key] === undefined) {
    form.messages[key] = props.registry.messages[key].default || ''
  }
})

const submit = () => {
  form.put(`/admin/email-templates/${props.template.id}/update`, {
    onSuccess: () => {
      fetchPreview()
    }
  })
}

const previewData = ref(null)
const loadingPreview = ref(false)

const fetchPreview = async () => {
  loadingPreview.value = true
  try {
    const response = await axios.post(`/admin/email-templates/${props.template.id}/preview`, {
      title: form.title,
      messages: form.messages
    })
    previewData.value = response.data
  } catch (error) {
    console.error("Failed to fetch preview:", error)
  } finally {
    loadingPreview.value = false
  }
}

onMounted(() => {
  fetchPreview()
})
</script>
