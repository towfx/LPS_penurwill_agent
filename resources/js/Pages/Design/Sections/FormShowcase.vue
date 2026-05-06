<template>
  <Showcase
    title="Form Elements"
    description="All form primitives wrap label + control + hint/error in <FormField>. Use v-model on inputs as standard."
  >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <FormField label="Full name" required hint="As shown on official ID.">
        <Input v-model="name" placeholder="Jane Doe" />
      </FormField>

      <FormField label="Email" error="Please enter a valid email address.">
        <Input v-model="email" type="email" placeholder="you@example.com" invalid />
      </FormField>

      <FormField label="Disabled input">
        <Input model-value="Read only" disabled />
      </FormField>

      <FormField label="Country" required>
        <Select
          v-model="country"
          placeholder="Select country"
          :options="[
            { value: 'my', label: 'Malaysia' },
            { value: 'sg', label: 'Singapore' },
            { value: 'id', label: 'Indonesia' },
          ]"
        />
      </FormField>

      <FormField label="Notes" class="md:col-span-2" hint="Optional — visible to admins only.">
        <Textarea v-model="notes" placeholder="Add any context for the reviewer…" :rows="3" />
      </FormField>

      <FormField label="Bank statement (PDF)" class="md:col-span-2">
        <FileInput v-model="file" accept=".pdf,image/*" />
      </FormField>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2 border-t border-stone-100">
      <FormField label="Marketing preferences">
        <div class="space-y-2">
          <Checkbox v-model="prefs.email" label="Email updates" />
          <Checkbox v-model="prefs.sms" label="SMS notifications" />
          <Checkbox :model-value="false" label="Disabled option" disabled />
        </div>
      </FormField>

      <FormField label="Payout method" required>
        <div class="space-y-2">
          <Radio v-model="payout" name="payout" value="bank" label="Bank transfer" />
          <Radio v-model="payout" name="payout" value="ewallet" label="E-wallet" />
          <Radio v-model="payout" name="payout" value="cheque" label="Cheque" disabled />
        </div>
      </FormField>
    </div>
  </Showcase>
</template>

<script setup>
import { reactive, ref } from 'vue'
import Showcase from '../Components/Showcase.vue'
import FormField from '../Components/FormField.vue'
import Input from '../Components/Input.vue'
import Textarea from '../Components/Textarea.vue'
import Select from '../Components/Select.vue'
import Checkbox from '../Components/Checkbox.vue'
import Radio from '../Components/Radio.vue'
import FileInput from '../Components/FileInput.vue'

const name = ref('')
const email = ref('not-an-email')
const country = ref('my')
const notes = ref('')
const file = ref(null)
const prefs = reactive({ email: true, sms: false })
const payout = ref('bank')
</script>
