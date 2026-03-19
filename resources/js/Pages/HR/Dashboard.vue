<template>
  <div>
    <Head title="HR & Team Performance" />

    <div class="page-header">
      <div>
        <h1 class="page-title">HR & Team Performance</h1>
        <p class="page-subtitle">{{ analytics.summary.total_employees }} team members · {{ filters.period }}</p>
      </div>
      <div class="header-actions">
        <Select v-model="selectedPeriod" :options="periodOptions" optionLabel="label" optionValue="value" class="period-select" @change="changePeriod" />
        <Link href="/hr/employees"><Button label="Manage Employees" icon="pi pi-users" severity="secondary" /></Link>
        <Link href="/hr/kpi-definitions"><Button label="Manage KPIs" icon="pi pi-cog" severity="secondary" /></Link>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon icon-blue"><i class="pi pi-users" /></div>
        <div class="summary-body">
          <span class="summary-value">{{ analytics.summary.total_employees }}</span>
          <span class="summary-label">Total Employees</span>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-emerald"><i class="pi pi-chart-line" /></div>
        <div class="summary-body">
          <span class="summary-value">{{ analytics.summary.avg_kpi_achievement }}%</span>
          <span class="summary-label">Avg KPI Achievement</span>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-amber"><i class="pi pi-dollar" /></div>
        <div class="summary-body">
          <span class="summary-value">{{ formatCurrency(analytics.summary.total_revenue) }}</span>
          <span class="summary-label">Total Revenue</span>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-violet"><i class="pi pi-wallet" /></div>
        <div class="summary-body">
          <span class="summary-value">{{ formatCurrency(analytics.summary.revenue_per_employee) }}</span>
          <span class="summary-label">Revenue / Employee</span>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-rose"><i class="pi pi-clock" /></div>
        <div class="summary-body">
          <span class="summary-value">{{ analytics.summary.utilization }}%</span>
          <span class="summary-label">Utilization Rate</span>
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-icon icon-teal"><i class="pi pi-star" /></div>
        <div class="summary-body">
          <span class="summary-value">{{ analytics.summary.avg_review_score }}</span>
          <span class="summary-label">Avg Review Score</span>
        </div>
      </div>
    </div>

    <div class="dashboard-layout">
      <!-- Left Column -->
      <div class="dash-column main-column">
        <!-- Employee Performance Cards -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-users" /> Team Members</h2>
            <span class="section-count">{{ analytics.employee_cards.length }}</span>
          </div>
          <div class="emp-grid">
            <Link v-for="emp in analytics.employee_cards" :key="emp.id" :href="`/hr/employees/${emp.id}`" class="emp-card">
              <div class="emp-top">
                <div class="emp-avatar">{{ initials(emp.name) }}</div>
                <div class="emp-info">
                  <span class="emp-name">{{ emp.name }}</span>
                  <span class="emp-role">{{ emp.position || 'No position' }}</span>
                </div>
                <span class="dept-badge" :class="`dept-${emp.department}`">{{ departmentLabel(emp.department) }}</span>
              </div>
              <div class="emp-metrics">
                <div class="metric">
                  <span class="metric-label">KPI</span>
                  <div class="metric-bar">
                    <div class="metric-fill" :class="achievementColor(emp.avg_kpi_achievement)" :style="{ width: Math.min(emp.avg_kpi_achievement, 100) + '%' }"></div>
                  </div>
                  <span class="metric-value" :class="achievementColor(emp.avg_kpi_achievement)">{{ emp.avg_kpi_achievement }}%</span>
                </div>
                <div class="metric-row">
                  <span class="metric-item"><i class="pi pi-dollar" /> {{ formatCompact(emp.revenue_generated) }}</span>
                  <span v-if="emp.latest_review_rating" class="rating-chip" :class="`rating-${emp.latest_review_rating}`">{{ emp.latest_review_rating }}</span>
                </div>
              </div>
            </Link>
          </div>
          <div v-if="analytics.employee_cards.length === 0" class="empty-state">
            <i class="pi pi-user-plus" />
            <span>No employees yet. <Link href="/hr/employees" class="link-text">Add employees</Link></span>
          </div>
        </div>

        <!-- KPI Overview Table -->
        <div class="section-card" v-if="analytics.kpi_overview.length > 0">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-chart-bar" /> KPI Overview</h2>
          </div>
          <div class="kpi-table-wrap">
            <table class="kpi-table">
              <thead>
                <tr>
                  <th>KPI Name</th>
                  <th>Category</th>
                  <th>Target</th>
                  <th>Avg Value</th>
                  <th>Achievement</th>
                  <th>Tracked</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="kpi in analytics.kpi_overview" :key="kpi.id">
                  <td class="kpi-name-cell">{{ kpi.name }}</td>
                  <td><span class="cat-badge" :class="`cat-${kpi.category}`">{{ kpi.category }}</span></td>
                  <td class="num-cell">{{ formatKpiValue(kpi.target_value, kpi.unit) }}</td>
                  <td class="num-cell">{{ formatKpiValue(kpi.avg_value, kpi.unit) }}</td>
                  <td>
                    <div class="achievement-cell">
                      <div class="micro-bar"><div class="micro-fill" :class="achievementColor(kpi.avg_achievement)" :style="{ width: Math.min(kpi.avg_achievement, 100) + '%' }"></div></div>
                      <span :class="achievementColor(kpi.avg_achievement)">{{ kpi.avg_achievement }}%</span>
                    </div>
                  </td>
                  <td class="num-cell">{{ kpi.employees_tracked }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="dash-column side-column">
        <!-- Top Performers -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-trophy" /> Top Performers</h2>
          </div>
          <div class="top-list">
            <div v-for="(perf, idx) in analytics.top_performers" :key="perf.id" class="top-item">
              <span class="top-rank" :class="`rank-${idx + 1}`">{{ idx + 1 }}</span>
              <div class="top-info">
                <span class="top-name">{{ perf.name }}</span>
                <span class="top-dept">{{ perf.position || perf.department }}</span>
              </div>
              <div class="top-stats">
                <span class="top-achievement" :class="achievementColor(perf.achievement)">{{ perf.achievement }}%</span>
                <span class="top-revenue">{{ formatCompact(perf.revenue) }}</span>
              </div>
            </div>
            <div v-if="analytics.top_performers.length === 0" class="empty-mini">No data yet</div>
          </div>
        </div>

        <!-- Department Breakdown -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-sitemap" /> By Department</h2>
          </div>
          <div class="dept-list">
            <div v-for="dept in analytics.department_breakdown" :key="dept.key" class="dept-item">
              <div class="dept-header">
                <span class="dept-name">{{ dept.label }}</span>
                <span class="dept-count">{{ dept.employee_count }} people</span>
              </div>
              <div class="dept-metrics">
                <div class="dept-metric">
                  <span class="dm-label">Achievement</span>
                  <span class="dm-value" :class="achievementColor(dept.avg_achievement)">{{ dept.avg_achievement }}%</span>
                </div>
                <div class="dept-metric">
                  <span class="dm-label">Revenue</span>
                  <span class="dm-value">{{ formatCompact(dept.total_revenue) }}</span>
                </div>
              </div>
            </div>
            <div v-if="analytics.department_breakdown.length === 0" class="empty-mini">No departments</div>
          </div>
        </div>

        <!-- Revenue per Employee -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title"><i class="pi pi-chart-pie" /> Revenue / Employee</h2>
          </div>
          <div class="rev-list">
            <div v-for="rev in analytics.revenue_per_employee.slice(0, 8)" :key="rev.id" class="rev-item">
              <div class="rev-name">{{ rev.name }}</div>
              <div class="rev-bar-wrap">
                <div class="rev-bar" :style="{ width: revBarWidth(rev.total_revenue) + '%' }"></div>
              </div>
              <div class="rev-amount">{{ formatCompact(rev.total_revenue) }}</div>
            </div>
            <div v-if="analytics.revenue_per_employee.length === 0" class="empty-mini">No revenue data</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, Select },
  layout: Layout,
  props: {
    analytics: Object,
    filters: Object,
    departments: Object,
    periods: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      selectedPeriod: this.filters.period,
    }
  },
  computed: {
    periodOptions() {
      return this.periods || []
    },
    maxRevenue() {
      const revenues = (this.analytics.revenue_per_employee || []).map(r => r.total_revenue)
      return Math.max(...revenues, 1)
    },
  },
  methods: {
    changePeriod() {
      router.get('/hr', { period: this.selectedPeriod }, { preserveState: true })
    },
    formatCurrency(v) {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v || 0)
    },
    formatCompact(v) {
      if (!v) return '0₫'
      if (v >= 1e9) return (v / 1e9).toFixed(1) + 'B₫'
      if (v >= 1e6) return (v / 1e6).toFixed(1) + 'M₫'
      if (v >= 1e3) return (v / 1e3).toFixed(0) + 'K₫'
      return v.toLocaleString() + '₫'
    },
    formatKpiValue(val, unit) {
      if (unit === 'currency') return this.formatCompact(val)
      if (unit === 'percentage') return val + '%'
      if (unit === 'hours') return val + 'h'
      return val?.toLocaleString() ?? '0'
    },
    initials(n) {
      return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?'
    },
    departmentLabel(key) {
      return this.departments?.[key] || key || 'N/A'
    },
    achievementColor(val) {
      if (val >= 90) return 'ach-excellent'
      if (val >= 70) return 'ach-good'
      if (val >= 50) return 'ach-average'
      return 'ach-low'
    },
    revBarWidth(val) {
      return Math.round((val / this.maxRevenue) * 100)
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.period-select { min-width: 150px; }

/* Summary Grid */
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
.summary-card { background: white; border-radius: 14px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; transition: all 0.2s; }
.summary-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
.summary-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; flex-shrink: 0; }
.icon-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.icon-emerald { background: linear-gradient(135deg, #10b981, #059669); }
.icon-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
.icon-violet { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.icon-rose { background: linear-gradient(135deg, #f43f5e, #e11d48); }
.icon-teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.summary-body { display: flex; flex-direction: column; }
.summary-value { font-size: 1.15rem; font-weight: 700; color: #0f172a; }
.summary-label { font-size: 0.68rem; color: #94a3b8; line-height: 1.2; }

/* Layout */
.dashboard-layout { display: grid; grid-template-columns: 1fr 380px; gap: 1rem; }
@media (max-width: 1024px) { .dashboard-layout { grid-template-columns: 1fr; } }
.dash-column { display: flex; flex-direction: column; gap: 1rem; }

/* Section Card */
.section-card { background: white; border-radius: 14px; padding: 1.15rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
.section-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.section-title i { font-size: 0.85rem; color: #6366f1; }
.section-count { font-size: 0.7rem; background: #eef2ff; color: #6366f1; padding: 0.1rem 0.4rem; border-radius: 6px; font-weight: 600; }

/* Employee Cards Grid */
.emp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem; }
.emp-card { background: #fafbfc; border-radius: 12px; padding: 0.85rem; border: 1px solid #f1f5f9; transition: all 0.2s; text-decoration: none; }
.emp-card:hover { border-color: #c7d2fe; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08); transform: translateY(-1px); }
.emp-top { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.65rem; }
.emp-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.65rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.emp-info { flex: 1; min-width: 0; }
.emp-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.emp-role { font-size: 0.68rem; color: #94a3b8; }
.dept-badge { font-size: 0.58rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.dept-sales { background: #dbeafe; color: #2563eb; } .dept-marketing { background: #fce7f3; color: #db2777; }
.dept-engineering { background: #d1fae5; color: #059669; } .dept-design { background: #fef3c7; color: #d97706; }
.dept-support { background: #e0e7ff; color: #4f46e5; } .dept-management { background: #f1f5f9; color: #475569; }
.dept-hr { background: #fae8ff; color: #a855f7; } .dept-finance { background: #ccfbf1; color: #0d9488; }

/* Metrics */
.emp-metrics { display: flex; flex-direction: column; gap: 0.4rem; }
.metric { display: flex; align-items: center; gap: 0.4rem; }
.metric-label { font-size: 0.65rem; color: #94a3b8; width: 28px; flex-shrink: 0; font-weight: 600; }
.metric-bar { flex: 1; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.metric-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }
.metric-value { font-size: 0.72rem; font-weight: 700; width: 38px; text-align: right; }
.metric-row { display: flex; align-items: center; justify-content: space-between; }
.metric-item { font-size: 0.7rem; color: #64748b; display: flex; align-items: center; gap: 0.2rem; }
.metric-item i { font-size: 0.6rem; }

/* Achievement Colors */
.ach-excellent { color: #059669; } .ach-excellent .metric-fill, .ach-excellent.metric-fill { background: #10b981; }
.ach-good { color: #2563eb; } .ach-good .metric-fill, .ach-good.metric-fill { background: #3b82f6; }
.ach-average { color: #d97706; } .ach-average .metric-fill, .ach-average.metric-fill { background: #f59e0b; }
.ach-low { color: #dc2626; } .ach-low .metric-fill, .ach-low.metric-fill { background: #ef4444; }

/* Rating Chips */
.rating-chip { font-size: 0.58rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px; text-transform: capitalize; }
.rating-exceptional { background: #d1fae5; color: #059669; }
.rating-exceeds { background: #dbeafe; color: #2563eb; }
.rating-meets { background: #fef3c7; color: #d97706; }
.rating-below { background: #fee2e2; color: #dc2626; }
.rating-unsatisfactory { background: #fecaca; color: #991b1b; }

/* KPI Table */
.kpi-table-wrap { overflow-x: auto; }
.kpi-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.78rem; }
.kpi-table th { font-weight: 600; color: #64748b; text-align: left; padding: 0.5rem 0.65rem; border-bottom: 2px solid #f1f5f9; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.03em; }
.kpi-table td { padding: 0.55rem 0.65rem; border-bottom: 1px solid #f8fafc; color: #334155; }
.kpi-table tr:last-child td { border-bottom: none; }
.kpi-table tr:hover td { background: #fafbfc; }
.kpi-name-cell { font-weight: 600; color: #1e293b; }
.num-cell { text-align: right; font-variant-numeric: tabular-nums; }
.cat-badge { font-size: 0.6rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px; text-transform: capitalize; }
.cat-sales { background: #dbeafe; color: #2563eb; } .cat-support { background: #e0e7ff; color: #4f46e5; }
.cat-productivity { background: #d1fae5; color: #059669; } .cat-quality { background: #fef3c7; color: #d97706; }
.cat-custom { background: #f1f5f9; color: #475569; }
.achievement-cell { display: flex; align-items: center; gap: 0.4rem; }
.micro-bar { flex: 1; height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; min-width: 50px; }
.micro-fill { height: 100%; border-radius: 3px; }

/* Top Performers */
.top-list { display: flex; flex-direction: column; gap: 0.5rem; }
.top-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem; border-radius: 10px; transition: background 0.15s; }
.top-item:hover { background: #f8fafc; }
.top-rank { width: 24px; height: 24px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 0.68rem; font-weight: 700; flex-shrink: 0; }
.rank-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
.rank-2 { background: linear-gradient(135deg, #94a3b8, #64748b); color: white; }
.rank-3 { background: linear-gradient(135deg, #d97706, #b45309); color: white; }
.rank-4, .rank-5 { background: #f1f5f9; color: #64748b; }
.top-info { flex: 1; min-width: 0; }
.top-name { font-size: 0.78rem; font-weight: 600; color: #1e293b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-dept { font-size: 0.65rem; color: #94a3b8; }
.top-stats { text-align: right; }
.top-achievement { font-size: 0.78rem; font-weight: 700; display: block; }
.top-revenue { font-size: 0.65rem; color: #64748b; }

/* Department List */
.dept-list { display: flex; flex-direction: column; gap: 0.65rem; }
.dept-item { padding: 0.6rem; border-radius: 10px; background: #fafbfc; border: 1px solid #f1f5f9; }
.dept-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; }
.dept-name { font-size: 0.8rem; font-weight: 600; color: #1e293b; }
.dept-count { font-size: 0.65rem; color: #94a3b8; }
.dept-metrics { display: flex; gap: 1rem; }
.dept-metric { display: flex; flex-direction: column; }
.dm-label { font-size: 0.6rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.03em; }
.dm-value { font-size: 0.82rem; font-weight: 700; }

/* Revenue Chart */
.rev-list { display: flex; flex-direction: column; gap: 0.55rem; }
.rev-item { display: grid; grid-template-columns: 90px 1fr 60px; align-items: center; gap: 0.5rem; }
.rev-name { font-size: 0.72rem; color: #475569; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rev-bar-wrap { height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.rev-bar { height: 100%; border-radius: 4px; background: linear-gradient(135deg, #6366f1, #8b5cf6); transition: width 0.5s; }
.rev-amount { font-size: 0.7rem; font-weight: 600; color: #334155; text-align: right; }

/* Misc */
.empty-state { text-align: center; padding: 2.5rem; color: #94a3b8; }
.empty-state i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
.empty-mini { text-align: center; padding: 1.5rem; color: #cbd5e1; font-size: 0.78rem; }
.link-text { color: #6366f1; text-decoration: underline; }
</style>
