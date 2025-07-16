<template>
  <div>
    <Line :data="chartData" :options="chartOptions" :height="height" />
  </div>
</template>

<script setup>
import { Line } from 'vue-chartjs'
import {
  Chart,
  LineElement,
  PointElement,
  LinearScale,
  Title,
  CategoryScale,
  Tooltip,
  Filler,
  Legend
} from 'chart.js'
import { computed } from 'vue'

Chart.register(LineElement, PointElement, LinearScale, Title, CategoryScale, Tooltip, Filler, Legend)

const props = defineProps({
  labels: Array,
  data: Array,
  label: String,
  color: {
    type: String,
    default: '#bc9c5f' // Gold
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
      fill: true,
      backgroundColor: 'rgba(236, 220, 180, 0.3)', // Cream fill
      borderColor: props.color,
      tension: 0.4,
      pointRadius: 3,
      pointBackgroundColor: props.color,
      borderWidth: 3
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
