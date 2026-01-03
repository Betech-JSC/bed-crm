<template>
  <div>
    <Head title="Dashboard" />
    <div class="mb-6">
      <h1 class="text-3xl font-bold">Dashboard</h1>
      <p class="mt-2 text-gray-600">Welcome back! Here's an overview of your CRM.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">Total Leads</p>
              <p class="text-2xl font-bold">{{ stats.totalLeads || 0 }}</p>
            </div>
            <i class="pi pi-users text-3xl text-blue-500" />
          </div>
          <div class="mt-4">
            <Link href="/leads" class="text-sm text-primary-600 hover:text-primary-800">
              View all →
            </Link>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">Open Deals</p>
              <p class="text-2xl font-bold text-indigo-600">{{ stats.openDeals || 0 }}</p>
            </div>
            <i class="pi pi-briefcase text-3xl text-indigo-500" />
          </div>
          <div class="mt-4">
            <Link href="/deals" class="text-sm text-primary-600 hover:text-primary-800">
              View pipeline →
            </Link>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">Won Deals</p>
              <p class="text-2xl font-bold text-green-600">{{ stats.wonDeals || 0 }}</p>
            </div>
            <i class="pi pi-check-circle text-3xl text-green-500" />
          </div>
          <div class="mt-4">
            <Link href="/deals" class="text-sm text-primary-600 hover:text-primary-800">
              View deals →
            </Link>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">Pipeline Value</p>
              <p class="text-2xl font-bold text-primary-600">{{ formatCurrency(stats.totalPipelineValue || 0) }}</p>
            </div>
            <i class="pi pi-dollar text-3xl text-primary-500" />
          </div>
          <div class="mt-4">
            <Link href="/deals" class="text-sm text-primary-600 hover:text-primary-800">
              View pipeline →
            </Link>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">Tasks Overdue</p>
              <p class="text-2xl font-bold" :class="stats.tasksOverdue > 0 ? 'text-red-600' : 'text-gray-600'">
                {{ stats.tasksOverdue || 0 }}
              </p>
            </div>
            <i class="pi pi-exclamation-triangle text-3xl" :class="stats.tasksOverdue > 0 ? 'text-red-500' : 'text-gray-400'" />
          </div>
          <div class="mt-4">
            <span v-if="stats.tasksOverdue > 0" class="text-sm text-red-600 font-medium">
              Action required
            </span>
            <span v-else class="text-sm text-gray-400">
              All up to date
            </span>
          </div>
        </template>
      </Card>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <Card>
        <template #title>Pipeline Value Trend (7 Days)</template>
        <template #content>
          <LineChart
            :data="pipelineTrendData"
            :options="pipelineTrendOptions"
          />
        </template>
      </Card>

      <Card>
        <template #title>Leads Created Over Time (30 Days)</template>
        <template #content>
          <BarChart
            :data="leadsOverTimeData"
            :options="leadsOverTimeOptions"
          />
        </template>
      </Card>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <Card>
        <template #title>Leads by Status (Last 30 Days)</template>
        <template #content>
          <DoughnutChart
            :data="leadsByStatusData"
            :options="leadsByStatusOptions"
          />
        </template>
      </Card>

      <Card>
        <template #title>Deals by Stage</template>
        <template #content>
          <BarChart
            :data="dealsByStageData"
            :options="dealsByStageOptions"
          />
        </template>
      </Card>
    </div>

    <!-- Lead Quality Metrics -->
    <Card class="mb-6">
      <template #title>Lead Quality Metrics</template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-600">{{ stats.highQualityLeads || 0 }}</p>
            <p class="text-sm text-gray-600 mt-1">High Quality (80+)</p>
          </div>
          <div class="text-center p-4 bg-yellow-50 rounded-lg">
            <p class="text-2xl font-bold text-yellow-600">{{ stats.mediumQualityLeads || 0 }}</p>
            <p class="text-sm text-gray-600 mt-1">Medium (60-79)</p>
          </div>
          <div class="text-center p-4 bg-orange-50 rounded-lg">
            <p class="text-2xl font-bold text-orange-600">{{ stats.lowQualityLeads || 0 }}</p>
            <p class="text-sm text-gray-600 mt-1">Low (40-59)</p>
          </div>
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <p class="text-2xl font-bold text-blue-600">{{ stats.averageLeadScore || 0 }}</p>
            <p class="text-sm text-gray-600 mt-1">Avg Score</p>
          </div>
          <div class="text-center p-4 bg-purple-50 rounded-lg">
            <p class="text-2xl font-bold text-purple-600">{{ stats.icpMatchRate || 0 }}%</p>
            <p class="text-sm text-gray-600 mt-1">ICP Match Rate</p>
          </div>
        </div>
      </template>
    </Card>

    <!-- Quick Actions & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card>
        <template #title>Quick Actions</template>
        <template #content>
          <div class="grid grid-cols-2 gap-2">
            <Link href="/leads/create">
              <Button label="New Lead" icon="pi pi-plus" size="small" class="w-full justify-start" />
            </Link>
            <Link href="/icps/create">
              <Button label="ICP Profile" icon="pi pi-user-edit" severity="secondary" size="small" class="w-full justify-start" />
            </Link>
            <Link href="/contacts/create">
              <Button label="New Contact" icon="pi pi-user-plus" severity="secondary" size="small" class="w-full justify-start" />
            </Link>
            <Link href="/organizations/create">
              <Button label="New Org" icon="pi pi-building" severity="secondary" size="small" class="w-full justify-start" />
            </Link>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Recent Activity</template>
        <template #content>
          <div v-if="recentLeads && recentLeads.length > 0" class="space-y-3">
            <div v-for="lead in recentLeads" :key="lead.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div>
                <Link :href="`/leads/${lead.id}/edit`" class="font-medium text-primary-600 hover:text-primary-800">
                  {{ lead.name }}
                </Link>
                <p class="text-xs text-gray-500 mt-1">{{ lead.company || 'No company' }}</p>
              </div>
              <Tag :value="statuses[lead.status] || lead.status" :severity="getStatusSeverity(lead.status)" />
            </div>
          </div>
          <div v-else class="py-8 text-center text-gray-400 text-sm">No recent activity</div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import LineChart from '@/Shared/Charts/LineChart.vue'
import BarChart from '@/Shared/Charts/BarChart.vue'
import DoughnutChart from '@/Shared/Charts/DoughnutChart.vue'

export default {
  components: {
    Head,
    Link,
    Card,
    Button,
    Tag,
    LineChart,
    BarChart,
    DoughnutChart,
  },
  layout: Layout,
  props: {
    stats: {
      type: Object,
      default: () => ({}),
    },
    chartData: {
      type: Object,
      default: () => ({}),
    },
    recentLeads: {
      type: Array,
      default: () => [],
    },
    statuses: {
      type: Object,
      default: () => ({}),
    },
  },
  computed: {
    pipelineTrendData() {
      const labels = this.chartData.pipelineTrend?.map(item => item.date) || []
      const values = this.chartData.pipelineTrend?.map(item => item.value) || []

      return {
        labels,
        datasets: [
          {
            label: 'Pipeline Value',
            data: values,
            borderColor: '#ef6820',
            backgroundColor: 'rgba(239, 104, 32, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6,
          },
        ],
      }
    },
    pipelineTrendOptions() {
      return {
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                return `Value: ${this.formatCurrency(context.parsed.y)}`
              },
            },
          },
        },
        scales: {
          y: {
            ticks: {
              callback: (value) => {
                return this.formatCurrency(value)
              },
            },
          },
        },
      }
    },
    leadsOverTimeData() {
      const labels = this.chartData.leadsOverTime?.map(item => item.date) || []
      const counts = this.chartData.leadsOverTime?.map(item => item.count) || []

      return {
        labels,
        datasets: [
          {
            label: 'Leads Created',
            data: counts,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: '#3b82f6',
            borderWidth: 1,
          },
        ],
      }
    },
    leadsOverTimeOptions() {
      return {
        plugins: {
          legend: {
            display: false,
          },
        },
      }
    },
    leadsByStatusData() {
      const statusLabels = Object.keys(this.chartData.leadsByStatus || {})
      const statusCounts = Object.values(this.chartData.leadsByStatus || {})
      
      const colors = [
        '#3b82f6', // blue
        '#10b981', // green
        '#f59e0b', // amber
        '#ef4444', // red
        '#8b5cf6', // purple
        '#ec4899', // pink
      ]

      return {
        labels: statusLabels.map(status => this.statuses[status] || status),
        datasets: [
          {
            data: statusCounts,
            backgroundColor: colors.slice(0, statusLabels.length),
            borderWidth: 2,
            borderColor: '#fff',
          },
        ],
      }
    },
    leadsByStatusOptions() {
      return {
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
      }
    },
    dealsByStageData() {
      const stages = this.chartData.dealsByStage || []
      const labels = stages.map(item => item.stage || 'Unknown')
      const counts = stages.map(item => item.count || 0)
      const values = stages.map(item => item.value || 0)

      return {
        labels,
        datasets: [
          {
            label: 'Number of Deals',
            data: counts,
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: '#6366f1',
            borderWidth: 1,
            yAxisID: 'y',
          },
          {
            label: 'Total Value',
            data: values,
            backgroundColor: 'rgba(239, 104, 32, 0.5)',
            borderColor: '#ef6820',
            borderWidth: 1,
            yAxisID: 'y1',
          },
        ],
      }
    },
    dealsByStageOptions() {
      return {
        plugins: {
          legend: {
            display: true,
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                if (context.datasetIndex === 0) {
                  return `Deals: ${context.parsed.y}`
                } else {
                  return `Value: ${this.formatCurrency(context.parsed.y)}`
                }
              },
            },
          },
        },
        scales: {
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            beginAtZero: true,
            title: {
              display: true,
              text: 'Number of Deals',
            },
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            beginAtZero: true,
            title: {
              display: true,
              text: 'Total Value',
            },
            grid: {
              drawOnChartArea: false,
            },
            ticks: {
              callback: (value) => {
                return this.formatCurrency(value)
              },
            },
          },
        },
      }
    },
  },
  methods: {
    getStatusSeverity(status) {
      const severityMap = {
        new: 'info',
        contacted: 'warning',
        qualified: 'success',
        won: 'success',
        lost: 'danger',
      }
      return severityMap[status] || 'secondary'
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(value)
    },
  },
}
</script>
