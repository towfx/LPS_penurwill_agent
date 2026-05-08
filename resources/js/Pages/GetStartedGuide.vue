<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Button from './Design/Components/Button.vue'
import { ChevronRight, ChevronLeft, CheckCircle, DollarSign, Users, BarChart3, Target, Gift } from 'lucide-vue-next'

const props = defineProps({
  agentRole: { type: String, default: 'agent' },
})

const page = usePage()
const roleNames = computed(() => ({
  agent: page.props.systemSettings?.role_name_agent || 'Agent',
  agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
  business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
}))

const roleName = computed(() => roleNames.value[props.agentRole] || 'Agent')

const slides = computed(() => [
  {
    icon: Gift,
    color: 'from-gold to-amber-600',
    title: `Welcome to Penurwill — ${roleName.value}!`,
    body: `You've successfully joined the Penurwill Agent Network. As a ${roleName.value}, you'll earn commissions on every completed sale generated through your referral link or code.`,
    highlight: 'Your account is now active and ready to use.',
  },
  {
    icon: Target,
    color: 'from-accent-green to-green-600',
    title: 'Share Your Referral Code',
    body: 'Go to the Referral page to find your unique code and shareable link. Every visitor who registers using your link is tracked automatically.',
    highlight: 'Your referral link is unique to you — share it widely!',
  },
  {
    icon: DollarSign,
    color: 'from-accent-blue to-blue-600',
    title: 'Earn Commissions',
    body: 'When a visitor you referred completes a purchase, you automatically earn a commission. Commissions appear in your Commissions page once confirmed.',
    highlight: 'Commissions are calculated based on your role and agreement rate.',
  },
  {
    icon: BarChart3,
    color: 'from-accent-orange to-orange-600',
    title: 'Track Your Performance',
    body: 'Your dashboard shows real-time stats: sales, commissions, conversion rates, and referral activity. Use these insights to grow your income.',
    highlight: 'Check your dashboard regularly to monitor progress.',
  },
  ...(props.agentRole === 'agent_leader' || props.agentRole === 'business_partner' ? [{
    icon: Users,
    color: 'from-forest-light to-forest-dark',
    title: `Manage Your Team`,
    body: `As a ${roleName.value}, you also earn override commissions on sales made by agents in your downline. Build and support your team to multiply your earnings.`,
    highlight: 'Go to your Team page to view your direct subordinates.',
  }] : []),
  {
    icon: CheckCircle,
    color: 'from-accent-green to-forest-dark',
    title: "You're All Set!",
    body: 'That\'s everything you need to get started. Your dashboard is ready. Good luck and welcome aboard!',
    highlight: null,
  },
])

const currentSlide = ref(0)
const total = computed(() => slides.value.length)
const isLast = computed(() => currentSlide.value === total.value - 1)
const isFirst = computed(() => currentSlide.value === 0)

function next() {
  if (!isLast.value) currentSlide.value++
}
function prev() {
  if (!isFirst.value) currentSlide.value--
}
function finish() {
  router.post('/get-started-guide/complete')
}
function skip() {
  router.post('/get-started-guide/complete')
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-cream via-white to-cream/50 flex flex-col items-center justify-center px-4 py-12">

    <!-- Card -->
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">

      <!-- Progress bar -->
      <div class="h-1 bg-stone-100">
        <div
          class="h-full bg-forest-dark transition-all duration-500"
          :style="{ width: `${((currentSlide + 1) / total) * 100}%` }"
        />
      </div>

      <!-- Slide content -->
      <div class="p-8">
        <!-- Icon -->
        <div class="flex justify-center mb-6">
          <div
            class="w-20 h-20 rounded-full bg-gradient-to-br flex items-center justify-center shadow-lg"
            :class="slides[currentSlide].color"
          >
            <component :is="slides[currentSlide].icon" class="w-10 h-10 text-white" />
          </div>
        </div>

        <!-- Step indicator -->
        <p class="text-xs text-stone-400 text-center mb-2 uppercase tracking-widest font-medium">
          Step {{ currentSlide + 1 }} of {{ total }}
        </p>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-forest-dark text-center mb-4 leading-tight">
          {{ slides[currentSlide].title }}
        </h1>

        <!-- Body -->
        <p class="text-stone-600 text-center leading-relaxed mb-4">
          {{ slides[currentSlide].body }}
        </p>

        <!-- Highlight callout -->
        <div v-if="slides[currentSlide].highlight" class="bg-gold/10 border border-gold/30 rounded-lg px-4 py-3 text-sm text-amber-900 text-center font-medium">
          {{ slides[currentSlide].highlight }}
        </div>
      </div>

      <!-- Dot indicators -->
      <div class="flex justify-center gap-2 pb-2">
        <button
          v-for="(_, i) in slides"
          :key="i"
          class="w-2 h-2 rounded-full transition-all"
          :class="i === currentSlide ? 'bg-forest-dark w-5' : 'bg-stone-300'"
          @click="currentSlide = i"
        />
      </div>

      <!-- Navigation -->
      <div class="px-8 pb-8 flex items-center justify-between gap-3">
        <Button
          v-if="!isFirst"
          variant="outline"
          @click="prev"
        >
          <ChevronLeft class="w-4 h-4 mr-1" /> Back
        </Button>
        <div v-else />

        <div class="flex gap-2 ml-auto">
          <Button v-if="!isLast" variant="ghost" size="sm" @click="skip">
            Skip
          </Button>
          <Button v-if="!isLast" variant="default" @click="next">
            Next <ChevronRight class="w-4 h-4 ml-1" />
          </Button>
          <Button v-if="isLast" variant="default" @click="finish">
            <CheckCircle class="w-4 h-4 mr-1" /> Go to Dashboard
          </Button>
        </div>
      </div>
    </div>

    <!-- Footer note -->
    <p class="text-xs text-stone-400 mt-6">
      You can always revisit the help center from the navigation menu.
    </p>
  </div>
</template>
