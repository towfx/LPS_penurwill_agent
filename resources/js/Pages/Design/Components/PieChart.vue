<template>
  <div>
    <Doughnut :data="chartData" :options="chartOptions" :height="height" />
  </div>
</template>

<script setup>
import { Doughnut } from 'vue-chartjs'
import {
  Chart,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'
import { computed } from 'vue'

Chart.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  labels: Array,
  data: Array,
  height: {
    type: Number,
    default: 300
  }
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      data: props.data,
      backgroundColor: [
        '#e07b39', // Accent Orange
        '#7a9b7d', // Accent Green
        '#d4423f', // Accent Red
        '#4a6b73', // Accent Blue
        '#8a9ba8', // Accent Gray
        '#bc9c5f', // Gold
      ],
      borderWidth: 2,
      borderColor: '#eae1d0', // Cream
      hoverBorderWidth: 3,
      hoverBorderColor: '#162d25', // Forest Dark
    }
  ]
}))

const chartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: '#162d25',
        padding: 20,
        usePointStyle: true,
        pointStyle: 'circle'
      }
    },
    tooltip: {
      enabled: true,
      backgroundColor: '#162d25',
      titleColor: '#eae1d0',
      bodyColor: '#eae1d0',
      borderColor: '#bc9c5f',
      borderWidth: 1
    }
  }
}
</script>
