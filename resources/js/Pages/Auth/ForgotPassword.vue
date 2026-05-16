<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Card from '../Design/Components/Card.vue';
import Button from '../Design/Components/Button.vue';
import Alert from '../Design/Components/Alert.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />
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
                    <h2 class="text-2xl font-bold text-forest-dark">Reset your password</h2>
                </div>

                <div v-if="status" class="w-full mb-4">
                    <Alert variant="success">{{ status }}</Alert>
                </div>

                <p class="text-sm text-stone-700 text-center mb-6">
                    Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
                </p>

                <form @submit.prevent="submit" class="w-full space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-forest-dark mb-1">Email</label>
                        <input
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

                    <Button
                        type="submit"
                        size="lg"
                        class="w-full mt-6 py-4 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 bg-accent-green hover:bg-accent-green/90 text-white"
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                    >
                        Email Password Reset Link
                    </Button>

                    <div class="text-center">
                        <Link :href="route('login')" class="text-amber-700 hover:text-amber-900 text-sm font-medium underline-offset-2 hover:underline">
                            Back to login
                        </Link>
                    </div>
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
