<template>
  <div class="chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

Chart.register(
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend
)

export default {
  name: 'BarChart',
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
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        },
      },
      scales: {
        x: {
          display: true,
          grid: {
            display: false,
          },
        },
        y: {
          display: true,
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
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
        type: 'bar',
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

