# Chart.js Integration Guide

## Installation

Run the following command to install Chart.js and vue-chartjs:

```bash
npm install chart.js vue-chartjs --save
```

## Chart Components Created

### 1. LineChart.vue
- Used for trend analysis (e.g., pipeline value over time)
- Supports area fill, custom colors, and tooltips

### 2. BarChart.vue
- Used for comparing values (e.g., leads created, deals by stage)
- Supports multiple datasets and dual Y-axis

### 3. DoughnutChart.vue
- Used for distribution visualization (e.g., leads by status)
- Shows percentages and supports custom colors

## Dashboard Charts

The dashboard now includes 4 interactive charts:

1. **Pipeline Value Trend (7 Days)** - Line chart showing pipeline value over the last week
2. **Leads Created Over Time (30 Days)** - Bar chart showing daily lead creation
3. **Leads by Status** - Doughnut chart showing distribution of leads by status
4. **Deals by Stage** - Bar chart with dual Y-axis showing deal count and value by stage

## Usage Example

```vue
<template>
  <Card>
    <template #title>My Chart</template>
    <template #content>
      <LineChart :data="chartData" :options="chartOptions" />
    </template>
  </Card>
</template>

<script>
import LineChart from '@/Shared/Charts/LineChart.vue'

export default {
  components: { LineChart },
  data() {
    return {
      chartData: {
        labels: ['Jan', 'Feb', 'Mar'],
        datasets: [{
          label: 'Sales',
          data: [10, 20, 30],
          borderColor: '#ef6820',
          backgroundColor: 'rgba(239, 104, 32, 0.1)',
        }],
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
      },
    }
  },
}
</script>
```

## Customization

All chart components accept:
- `data`: Chart.js data object
- `options`: Chart.js options object

Charts are fully responsive and maintain aspect ratio.

