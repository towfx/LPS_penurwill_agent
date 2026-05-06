<template>
  <Showcase
    title="Modals"
    description="Modal.vue is the base dialog. ConfirmationModal.vue is the destructive-action pattern (used for approve / reject / refund)."
  >
    <div class="flex flex-wrap gap-2">
      <Button variant="outline" @click="modalOpen = true">Open modal</Button>
      <Button variant="destructive" @click="confirmOpen = true">Delete agent…</Button>
    </div>

    <Modal
      v-model="modalOpen"
      title="Edit agent profile"
      description="Quick edit — full form is on the agent detail page."
    >
      <div class="space-y-4">
        <FormField label="Display name">
          <Input v-model="modalName" />
        </FormField>
        <FormField label="Status">
          <Select
            v-model="modalStatus"
            :options="['Active', 'Suspended', 'Inactive']"
          />
        </FormField>
      </div>
      <template #footer>
        <Button variant="outline" size="sm" @click="modalOpen = false">Cancel</Button>
        <Button size="sm" @click="modalOpen = false">Save changes</Button>
      </template>
    </Modal>

    <ConfirmationModal
      v-model="confirmOpen"
      title="Delete this agent?"
      body="This will revoke their login and unlink any active referrals. You cannot undo this action."
      confirm-label="Delete"
      @confirm="confirmOpen = false"
    />
  </Showcase>
</template>

<script setup>
import { ref } from 'vue'
import Showcase from '../Components/Showcase.vue'
import Button from '../Components/Button.vue'
import Modal from '../Components/Modal.vue'
import ConfirmationModal from '../Components/ConfirmationModal.vue'
import FormField from '../Components/FormField.vue'
import Input from '../Components/Input.vue'
import Select from '../Components/Select.vue'

const modalOpen = ref(false)
const confirmOpen = ref(false)
const modalName = ref('Jane Doe')
const modalStatus = ref('Active')
</script>
