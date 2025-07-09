<template>
  <div class="space-y-4">
    <Alert variant="success">
      <CheckCircle class="h-4 w-4 mr-2 inline" />
      <strong>Success!</strong> Your backup completed successfully.
    </Alert>

    <Alert variant="warning">
      <AlertCircle class="h-4 w-4 mr-2 inline" />
      <strong>Warning:</strong> Server storage is 85% full.
    </Alert>

    <Alert variant="destructive">
      <XCircle class="h-4 w-4 mr-2 inline" />
      <strong>Error:</strong> Failed to sync with external API.
    </Alert>
  </div>
</template>

<script setup>
import {
  CheckCircle,
  AlertCircle,
  XCircle
} from 'lucide-vue-next'
import Alert from './Alert.vue'
</script>
