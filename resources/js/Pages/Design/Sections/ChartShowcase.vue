<template>
  <Showcase
    title="Charts"
    description="Reference charts from Components/. Use BarChart for categorical comparisons, LineChart for trends, PieChart for shares."
  >
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Line — monthly revenue</p>
        <LineChart />
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Pie — traffic mix</p>
        <PieChart />
      </div>
      <div class="lg:col-span-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">Bar — agents per region</p>
        <BarChart />
      </div>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import BarChart from '../Components/BarChart.vue'
import LineChart from '../Components/LineChart.vue'
import PieChart from '../Components/PieChart.vue'
</script>
