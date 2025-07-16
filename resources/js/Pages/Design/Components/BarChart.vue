<template>
  <div>
    <Bar :data="chartData" :options="chartOptions" :height="height" />
  </div>
</template>

<script setup>
import { Bar } from 'vue-chartjs'
import {
  Chart,
  BarElement,
  CategoryScale,
  LinearScale,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { computed } from 'vue'

Chart.register(BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend)

const props = defineProps({
  labels: Array,
  data: Array,
  label: String,
  color: {
    type: String,
    default: '#5d775f' // Forest Light
  },
  height: {
    type: Number,
    default: 300
  }
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      label: props.label,
      data: props.data,
      backgroundColor: props.color,
      borderRadius: 6,
      maxBarThickness: 18
    }
  ]
}))

const chartOptions = {
  responsive: true,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: true }
  },
  scales: {
    x: {
      grid: { color: '#eae1d0' },
      ticks: { color: '#162d25' }
    },
    y: {
      grid: { color: '#eae1d0' },
      ticks: { color: '#162d25' }
    }
  }
}
</script>
