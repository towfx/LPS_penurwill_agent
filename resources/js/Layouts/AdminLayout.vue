<template>
  <div class="min-h-screen bg-white border flex flex-col">
    <div class="flex-1">
      <slot />
    </div>
    <footer class="bg-forest-dark py-4 border-t border-forest-light/20">
      <div class="container mx-auto px-4 text-center">
        <p class="text-xs text-cream/70">{{ $page.props.appFooter }}</p>
      </div>
    </footer>
  </div>
</template>
<script setup></script>
