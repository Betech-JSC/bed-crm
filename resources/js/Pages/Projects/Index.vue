<template>
  <div>
    <Head :title="t('common.projects')" />

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ t('common.projects') }}</h1>
        <p class="page-subtitle">{{ analytics.total_projects }} {{ t('common.projects') }}</p>
      </div>
      <Link href="/projects/create"><Button :label="t('common.create_project')" icon="pi pi-plus" /></Link>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon bg-blue"><i class="pi pi-folder" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ analytics.active_count }}</span>
          <span class="kpi-label">{{ t('common.active_projects') }}</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon bg-red"><i class="pi pi-exclamation-triangle" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ analytics.delayed_count }}</span>
          <span class="kpi-label">{{ t('common.delayed') }}</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon bg-green"><i class="pi pi-chart-line" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ formatCurrency(analytics.total_profit) }}</span>
          <span class="kpi-label">{{ t('common.total_profit') }}</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon bg-purple"><i class="pi pi-percentage" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ analytics.avg_margin }}%</span>
          <span class="kpi-label">{{ t('common.avg_margin') }}</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon bg-amber"><i class="pi pi-users" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ analytics.utilization }}%</span>
          <span class="kpi-label">{{ t('common.utilization') }}</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <span class="p-input-icon-left" style="flex:1">
        <i class="pi pi-search" />
        <InputText v-model="form.search" :placeholder="t('common.search_projects')" class="w-full" @input="handleSearch" />
      </span>
      <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('common.all_statuses')" class="filter-select" @change="applyFilter" />
      <Button icon="pi pi-refresh" severity="secondary" text @click="reset" />
    </div>

    <!-- Project Cards Grid -->
    <div class="projects-grid">
      <div v-for="p in projects.data" :key="p.id" class="project-card" :class="{ 'card-overdue': p.is_overdue }">
        <div class="card-top">
          <span class="status-chip" :class="`chip-${p.status}`">{{ statuses[p.status] }}</span>
          <span v-if="p.priority === 'urgent'" class="priority-dot dot-urgent" title="Urgent"></span>
          <span v-else-if="p.priority === 'high'" class="priority-dot dot-high" title="High"></span>
        </div>

        <Link :href="`/projects/${p.id}/edit`" class="card-link">
          <h3 class="card-title">{{ p.name }}</h3>
        </Link>

        <div v-if="p.customer" class="card-customer"><i class="pi pi-building" /> {{ p.customer.name }}</div>

        <!-- Progress -->
        <div class="progress-section">
          <div class="progress-header">
            <span>{{ p.tasks_done }}/{{ p.tasks_count }} tasks</span>
            <span class="progress-pct">{{ p.progress }}%</span>
          </div>
          <div class="progress-track"><div class="progress-fill" :class="progressClass(p)" :style="{ width: p.progress + '%' }"></div></div>
        </div>

        <!-- Timeline -->
        <div class="card-timeline">
          <span v-if="p.due_date" class="timeline-date" :class="{ 'date-overdue': p.is_overdue }">
            <i class="pi pi-calendar" />
            {{ p.due_date }}
            <span v-if="p.days_remaining !== null && !p.is_overdue" class="days-badge">{{ p.days_remaining }}d</span>
            <span v-if="p.is_overdue" class="overdue-badge">Overdue</span>
          </span>
        </div>

        <!-- Financials -->
        <div class="card-financials">
          <div class="fin-row">
            <span class="fin-label">Revenue</span>
            <span class="fin-revenue">{{ formatCurrency(p.revenue) }}</span>
          </div>
          <div class="fin-row">
            <span class="fin-label">{{ t('common.profit') }}</span>
            <span class="fin-profit" :class="p.profit >= 0 ? 'positive' : 'negative'">
              {{ formatCurrency(p.profit) }}
              <small v-if="p.margin">({{ p.margin }}%)</small>
            </span>
          </div>
        </div>

        <!-- Footer -->
        <div class="card-footer">
          <div v-if="p.manager" class="card-manager"><div class="mini-avatar">{{ initials(p.manager.name) }}</div>{{ p.manager.name }}</div>
          <span class="card-resources"><i class="pi pi-users" /> {{ p.resources_count }}</span>
        </div>
      </div>
    </div>

    <div v-if="projects.data.length === 0" class="empty-state"><i class="pi pi-folder-open" /><span>{{ t('common.no_projects') }}</span></div>

    <div v-if="projects.total > 0" class="pagination-wrapper">
      <span class="pagination-info">{{ projects.from }}–{{ projects.to }} / {{ projects.total }}</span>
      <Paginator :first="(projects.current_page - 1) * projects.per_page" :rows="projects.per_page" :totalRecords="projects.total" @page="onPage" template="PrevPageLink PageLinks NextPageLink" />
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, Button, InputText, Select, Paginator },
  layout: Layout,
  props: { projects: Object, analytics: Object, filters: Object, statuses: Object, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return { form: { search: this.filters.search, status: this.filters.status } }
  },
  computed: {
    statusOptions() {
      return [{ label: this.t('common.all_statuses'), value: null }, ...Object.entries(this.statuses).map(([v, l]) => ({ label: l, value: v }))]
    },
  },
  methods: {
    handleSearch: throttle(function () { this.$inertia.get('/projects', pickBy(this.form), { preserveState: true }) }, 300),
    applyFilter() { this.$inertia.get('/projects', pickBy(this.form), { preserveState: true }) },
    reset() { this.form = { search: null, status: null }; this.$inertia.get('/projects') },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    initials(n) { return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?' },
    progressClass(p) { if (p.is_overdue) return 'fill-red'; if (p.progress >= 80) return 'fill-green'; if (p.progress >= 40) return 'fill-blue'; return 'fill-gray' },
    onPage(e) { router.visit(`/projects?page=${e.page + 1}`, { preserveState: true }) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; }

.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.kpi-card { background: white; border-radius: 12px; padding: 0.85rem; display: flex; align-items: center; gap: 0.65rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
.kpi-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.9rem; }
.bg-blue { background: #3b82f6; } .bg-red { background: #ef4444; } .bg-green { background: #10b981; } .bg-purple { background: #8b5cf6; } .bg-amber { background: #f59e0b; }
.kpi-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; display: block; }
.kpi-label { font-size: 0.68rem; color: #94a3b8; }

.filter-bar { display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.filter-select { min-width: 145px; }

.projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 0.85rem; margin-bottom: 1rem; }

.project-card { background: white; border-radius: 14px; padding: 1.1rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 0.55rem; transition: all 0.2s; }
.project-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); transform: translateY(-2px); }
.card-overdue { border-left: 3px solid #ef4444; }

.card-top { display: flex; align-items: center; gap: 0.4rem; }
.status-chip { font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.chip-planning { background: #f1f5f9; color: #64748b; }
.chip-in_progress { background: #dbeafe; color: #2563eb; }
.chip-on_hold { background: #fef3c7; color: #d97706; }
.chip-delayed { background: #fee2e2; color: #dc2626; }
.chip-completed { background: #d1fae5; color: #059669; }
.chip-cancelled { background: #f1f5f9; color: #94a3b8; }

.priority-dot { width: 8px; height: 8px; border-radius: 50%; }
.dot-urgent { background: #dc2626; } .dot-high { background: #f59e0b; }

.card-link { text-decoration: none; } .card-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; }
.card-link:hover .card-title { color: #6366f1; }
.card-customer { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.3rem; }
.card-customer i { font-size: 0.65rem; }

.progress-section { margin-top: 0.15rem; }
.progress-header { display: flex; justify-content: space-between; font-size: 0.68rem; color: #94a3b8; margin-bottom: 0.2rem; }
.progress-pct { font-weight: 700; color: #334155; }
.progress-track { height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 3px; transition: width 0.4s; }
.fill-green { background: #10b981; } .fill-blue { background: #3b82f6; } .fill-gray { background: #94a3b8; } .fill-red { background: #ef4444; }

.card-timeline { min-height: 1.2rem; }
.timeline-date { font-size: 0.72rem; color: #64748b; display: flex; align-items: center; gap: 0.3rem; }
.timeline-date i { font-size: 0.62rem; }
.date-overdue { color: #dc2626; font-weight: 600; }
.days-badge { font-size: 0.6rem; font-weight: 700; background: #eef2ff; color: #6366f1; padding: 0.05rem 0.3rem; border-radius: 4px; }
.overdue-badge { font-size: 0.6rem; font-weight: 700; background: #fee2e2; color: #dc2626; padding: 0.05rem 0.3rem; border-radius: 4px; }

.card-financials { background: #f8fafc; border-radius: 8px; padding: 0.5rem 0.65rem; }
.fin-row { display: flex; justify-content: space-between; font-size: 0.75rem; }
.fin-label { color: #94a3b8; }
.fin-revenue { font-weight: 600; color: #1e293b; }
.fin-profit { font-weight: 700; }
.fin-profit small { font-weight: 500; opacity: 0.8; }
.positive { color: #059669; } .negative { color: #dc2626; }

.card-footer { display: flex; align-items: center; justify-content: space-between; }
.card-manager { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: #64748b; }
.mini-avatar { width: 20px; height: 20px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.card-resources { font-size: 0.7rem; color: #94a3b8; display: flex; align-items: center; gap: 0.25rem; }

.empty-state { text-align: center; padding: 3rem; color: #94a3b8; } .empty-state i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
.pagination-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }
</style>
