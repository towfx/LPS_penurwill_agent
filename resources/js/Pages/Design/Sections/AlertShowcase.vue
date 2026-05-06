<template>
  <Showcase
    title="Alerts & Toasts"
    description="Inline Alerts for static page messages. Toasts for transient flash notifications (Inertia $page.props.flash)."
  >
    <div class="space-y-3">
      <Alert variant="default">
        <p class="text-sm font-semibold">Information</p>
        <p class="text-sm">This is a default informational alert.</p>
      </Alert>
      <Alert variant="success">
        <p class="text-sm font-semibold">Success</p>
        <p class="text-sm">Your changes have been saved.</p>
      </Alert>
      <Alert variant="warning">
        <p class="text-sm font-semibold">Warning</p>
        <p class="text-sm">Some fields look incomplete — review before submitting.</p>
      </Alert>
      <Alert variant="destructive">
        <p class="text-sm font-semibold">Error</p>
        <p class="text-sm">Something went wrong. Please try again.</p>
      </Alert>
    </div>

    <div class="border-t border-stone-100 pt-4 space-y-2">
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-1">Toast / flash</p>
      <Toast variant="success" title="Saved" message="Agent profile updated successfully." />
      <Toast variant="error" title="Failed" message="Could not approve payout — please retry." />
      <Toast variant="warning" title="Heads up" message="Your session will expire in 5 minutes." />
      <Toast variant="info" title="FYI" message="A new payout request is awaiting your review." />
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import Alert from '../Components/Alert.vue'
import Toast from '../Components/Toast.vue'
</script>
