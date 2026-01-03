<template>
  <div class="chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import {
  Chart,
  ArcElement,
  DoughnutController,
  Tooltip,
  Legend,
} from 'chart.js'

Chart.register(
  ArcElement,
  DoughnutController,
  Tooltip,
  Legend
)

export default {
  name: 'DoughnutChart',
  props: {
    data: {
      type: Object,
      required: true,
    },
    options: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props) {
    const chartCanvas = ref(null)
    let chartInstance = null

    const defaultOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || ''
              const value = context.parsed || 0
              const total = context.dataset.data.reduce((a, b) => a + b, 0)
              const percentage = ((value / total) * 100).toFixed(1)
              return `${label}: ${value} (${percentage}%)`
            },
          },
        },
      },
    }

    const createChart = () => {
      if (!chartCanvas.value) return

      const ctx = chartCanvas.value.getContext('2d')

      if (chartInstance) {
        chartInstance.destroy()
      }

      chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: props.data,
        options: { ...defaultOptions, ...props.options },
      })
    }

    onMounted(() => {
      createChart()
    })

    onBeforeUnmount(() => {
      if (chartInstance) {
        chartInstance.destroy()
      }
    })

    watch(
      () => props.data,
      () => {
        if (chartInstance) {
          chartInstance.data = props.data
          chartInstance.update()
        }
      },
      { deep: true }
    )

    watch(
      () => props.options,
      () => {
        if (chartInstance) {
          chartInstance.options = { ...defaultOptions, ...props.options }
          chartInstance.update()
        }
      },
      { deep: true }
    )

    return {
      chartCanvas,
    }
  },
}
</script>

<style scoped>
.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}
</style>

