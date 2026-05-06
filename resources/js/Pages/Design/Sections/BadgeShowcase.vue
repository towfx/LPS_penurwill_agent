<template>
  <Showcase
    title="Badges & Status"
    description="Generic badges for counts/labels and status badges for entity state. Status classes (status-pending/paid/cancelled) are in app.css."
  >
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Badge variants</p>
      <div class="flex flex-wrap gap-2">
        <Badge variant="default">Default</Badge>
        <Badge variant="secondary">Secondary</Badge>
        <Badge variant="success">Success</Badge>
        <Badge variant="warning">Warning</Badge>
        <Badge variant="destructive">Destructive</Badge>
        <Badge variant="outline">Outline</Badge>
      </div>
    </div>

    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Status badges</p>
      <div class="flex flex-wrap gap-2">
        <StatusBadge status="pending" label="Pending" />
        <StatusBadge status="approved" label="Approved" />
        <StatusBadge status="processing" label="Processing" />
        <StatusBadge status="paid" label="Paid" />
        <StatusBadge status="active" label="Active" />
        <StatusBadge status="inactive" label="Inactive" />
        <StatusBadge status="suspended" label="Suspended" />
        <StatusBadge status="rejected" label="Rejected" />
        <StatusBadge status="expired" label="Expired" />
        <StatusBadge status="cancelled" label="Cancelled" />
      </div>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import Badge from '../Components/Badge.vue'
import StatusBadge from '../Components/StatusBadge.vue'
</script>
