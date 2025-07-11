<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Card from '../Design/Components/Card.vue';
import Button from '../Design/Components/Button.vue';
import Alert from '../Design/Components/Alert.vue';
import { ref, onMounted } from 'vue';

const props = defineProps({
    canResetPassword: Boolean,
    status: String,
    email: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const hasEmailParam = ref(false);
const emailFromUrl = ref('');

onMounted(() => {
    // Check if email is passed as prop (from server) or in URL query string
    if (props.email) {
        emailFromUrl.value = props.email;
        form.email = props.email;
        hasEmailParam.value = true;
    } else {
        // Check URL query parameters
        const urlParams = new URLSearchParams(window.location.search);
        const emailParam = urlParams.get('email');
        if (emailParam) {
            emailFromUrl.value = decodeURIComponent(emailParam);
            form.email = emailFromUrl.value;
            hasEmailParam.value = true;
        }
    }
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
  <Head title="Log in" />
  <div class="min-h-screen flex items-center justify-center bg-cream font-sans py-8 px-4">
    <div class="w-full max-w-md">
      <Card className="p-8 sm:p-10 flex flex-col items-center shadow-lg">
        <div class="mb-6 flex flex-col items-center">
          <Link href="/">
            <svg class="w-16 h-16 mb-2" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="24" cy="24" r="24" fill="#bc9c5f" />
              <text x="50%" y="55%" text-anchor="middle" fill="#fff" font-size="20" font-family="Geist, sans-serif" dy=".3em">PW</text>
            </svg>
          </Link>
          <h2 class="text-2xl font-bold text-forest-dark">Sign in to your account</h2>
        </div>

        <div v-if="status" class="w-full mb-4">
          <Alert variant="success">{{ status }}</Alert>
        </div>

        <form @submit.prevent="submit" class="w-full space-y-5">
          <!-- Hidden email field when email is provided via URL -->
          <input v-if="hasEmailParam" type="hidden" v-model="form.email" />

          <div>
            <label for="email" class="block text-sm font-medium text-forest-dark mb-1">Email</label>
            <div v-if="hasEmailParam" class="mt-1 block w-full rounded-lg border border-stone-300 bg-gray-100 text-stone-600 px-3 py-2 cursor-not-allowed">
              {{ emailFromUrl }}
            </div>
            <input
              v-else
              id="email"
              v-model="form.email"
              type="email"
              class="mt-1 block w-full rounded-lg border border-stone-300 bg-cream text-stone-900 focus:border-gold focus:ring-2 focus:ring-gold focus:bg-white transition placeholder-stone-400"
              required
              autofocus
              autocomplete="username"
              placeholder="you@email.com"
            />
            <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</div>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-forest-dark mb-1">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              class="mt-1 block w-full rounded-lg border border-stone-300 bg-cream text-stone-900 focus:border-gold focus:ring-2 focus:ring-gold focus:bg-white transition placeholder-stone-400"
              required
              autocomplete="current-password"
              placeholder="Your password"
            />
            <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</div>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center text-sm text-stone-700">
              <input type="checkbox" v-model="form.remember" class="rounded border-stone-300 text-gold focus:ring-gold" />
              <span class="ml-2">Remember me</span>
            </label>
            <Link v-if="canResetPassword" :href="route('password.request')" class="text-amber-700 hover:text-amber-900 text-sm font-medium underline-offset-2 hover:underline">
              Forgot your password?
            </Link>
          </div>

          <Button type="submit" className="w-full mt-2" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
            Log in
          </Button>
        </form>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.text-forest-dark {
  color: #162d25;
}
.bg-cream {
  background-color: #eae1d0;
}
.text-gold {
  color: #bc9c5f;
}
.border-gold {
  border-color: #bc9c5f;
}
.focus\:ring-gold:focus {
  --tw-ring-color: #bc9c5f;
}
.focus\:border-gold:focus {
  border-color: #bc9c5f;
}
</style>
