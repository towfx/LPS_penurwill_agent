<script setup>
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Terms & Conditions" />

  <div class="min-h-screen bg-cream py-12 px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl bg-white rounded-lg shadow p-8">
      <nav class="text-sm mb-4">
        <Link href="/" class="text-forest-light hover:text-forest-dark">&larr; Home</Link>
      </nav>

      <h1 class="text-3xl font-bold text-forest-dark mb-2">Terms &amp; Conditions</h1>
      <p class="text-stone-500 mb-8">Placeholder content. Final terms will be supplied by the business owner before go-live.</p>

      <section class="prose prose-stone max-w-none space-y-4 text-stone-700">
        <h2 class="text-xl font-semibold text-forest-dark">1. Introduction</h2>
        <p>These Terms &amp; Conditions govern your use of the Penurwill Agent platform.</p>

        <h2 class="text-xl font-semibold text-forest-dark">2. Eligibility</h2>
        <p>You must meet the agent eligibility requirements set out by Penurwill to register and use the platform.</p>

        <h2 class="text-xl font-semibold text-forest-dark">3. Commissions &amp; Payouts</h2>
        <p>Commissions are calculated according to the rates configured by Penurwill at the time the qualifying sale is recorded.</p>

        <h2 class="text-xl font-semibold text-forest-dark">4. Membership &amp; Fees</h2>
        <p>Membership is subject to entry and renewal fees as published in the Penurwill agent dashboard.</p>

        <h2 class="text-xl font-semibold text-forest-dark">5. Data &amp; Privacy</h2>
        <p>Personal data is handled in accordance with applicable Malaysian data protection laws.</p>

        <h2 class="text-xl font-semibold text-forest-dark">6. Updates</h2>
        <p>Penurwill reserves the right to update these terms; changes will be communicated via the platform.</p>
      </section>
    </div>
  </div>
</template>
