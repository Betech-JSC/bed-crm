<template>
  <div>
    <Head title="Quản lý Dự án" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-folder" style="color: #6366f1; margin-right: 0.5rem;" />Quản lý Dự án</h1>
        <p class="page-subtitle">{{ analytics.total_projects }} dự án · Tiến độ, nguồn lực & lợi nhuận</p>
      </div>
      <Link href="/projects/create"><Button :label="t('common.create_project')" icon="pi pi-plus" /></Link>
    </div>

    <!-- KPI Strip -->
    <div class="kpi-strip">
      <div class="kpi-chip" @click="setFilter('status', 'in_progress')">
        <div class="kpi-dot kpi-dot--blue" />
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.active_count }}</span>
          <span class="kpi-lbl">Đang thực hiện</span>
        </div>
      </div>
      <div class="kpi-chip" @click="setFilter('status', 'delayed')">
        <div class="kpi-dot kpi-dot--red" />
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.delayed_count }}</span>
          <span class="kpi-lbl">{{ t('common.delayed') }}</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-dot kpi-dot--green" />
        <div class="kpi-data">
          <span class="kpi-num">{{ formatCurrency(analytics.total_profit) }}</span>
          <span class="kpi-lbl">{{ t('common.total_profit') }}</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-dot kpi-dot--purple" />
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.avg_margin }}%</span>
          <span class="kpi-lbl">{{ t('common.avg_margin') }}</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-dot kpi-dot--amber" />
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.utilization }}%</span>
          <span class="kpi-lbl">{{ t('common.utilization') }}</span>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <InputText v-model="form.search" :placeholder="t('common.search_projects')" class="w-full" @input="handleSearch" />
      </div>
      <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('common.all_statuses')" class="filter-select" @change="applyFilter" />
      <Button icon="pi pi-refresh" severity="secondary" text @click="reset" v-tooltip="'Reset'" />
    </div>

    <!-- Project Cards -->
    <div class="projects-grid">
      <div v-for="p in projects.data" :key="p.id" class="project-card" :class="{ 'card-overdue': p.is_overdue }">
        <!-- Top -->
        <div class="card-top">
          <span class="status-chip" :class="`chip-${p.status}`">{{ statuses[p.status] }}</span>
          <span v-if="p.priority === 'urgent'" class="priority-pill pill-urgent"><i class="pi pi-bolt" /> Gấp</span>
          <span v-else-if="p.priority === 'high'" class="priority-pill pill-high"><i class="pi pi-arrow-up" /> Cao</span>
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
          <div class="progress-track"><div class="progress-fill" :class="progressClass(p)" :style="{ width: p.progress + '%' }" /></div>
        </div>

        <!-- Timeline -->
        <div v-if="p.due_date" class="card-timeline">
          <span class="timeline-date" :class="{ 'date-overdue': p.is_overdue }">
            <i class="pi pi-calendar" /> {{ p.due_date }}
          </span>
          <span v-if="p.days_remaining !== null && !p.is_overdue" class="days-badge">{{ p.days_remaining }}d</span>
          <span v-if="p.is_overdue" class="overdue-badge"><i class="pi pi-exclamation-triangle" /> Quá hạn</span>
        </div>

        <!-- Financials -->
        <div class="card-financials">
          <div class="fin-row">
            <span class="fin-label">Doanh thu</span>
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
          <div v-if="p.manager" class="card-manager">
            <div class="mini-avatar">{{ initials(p.manager.name) }}</div>
            <span>{{ p.manager.name }}</span>
          </div>
          <span class="card-resources"><i class="pi pi-users" /> {{ p.resources_count }}</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="projects.data.length === 0" class="empty-state">
      <div class="empty-icon"><i class="pi pi-folder-open" /></div>
      <h3>{{ t('common.no_projects') }}</h3>
      <p>Tạo dự án đầu tiên để bắt đầu theo dõi tiến độ và lợi nhuận</p>
      <Link href="/projects/create"><Button label="Tạo dự án" icon="pi pi-plus" size="small" /></Link>
    </div>

    <!-- Pagination -->
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
    setFilter(key, value) { this.form[key] = value; this.applyFilter() },
    reset() { this.form = { search: null, status: null }; this.$inertia.get('/projects') },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    initials(n) { return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?' },
    progressClass(p) { if (p.is_overdue) return 'fill-red'; if (p.progress >= 80) return 'fill-green'; if (p.progress >= 40) return 'fill-blue'; return 'fill-gray' },
    onPage(e) { router.visit(`/projects?page=${e.page + 1}`, { preserveState: true }) },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* KPI Strip */
.kpi-strip { display: flex; gap: 0.5rem; margin-bottom: 1rem; overflow-x: auto; }
.kpi-chip {
  display: flex; align-items: center; gap: 0.55rem;
  padding: 0.6rem 0.85rem; background: white; border-radius: 12px;
  border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.kpi-chip:hover { border-color: #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-1px); }
.kpi-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.kpi-dot--blue { background: #3b82f6; } .kpi-dot--red { background: #ef4444; }
.kpi-dot--green { background: #10b981; } .kpi-dot--purple { background: #8b5cf6; }
.kpi-dot--amber { background: #f59e0b; }
.kpi-data { display: flex; flex-direction: column; }
.kpi-num { font-size: 0.95rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.kpi-lbl { font-size: 0.62rem; color: #94a3b8; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.search-box { display: flex; align-items: center; gap: 0.35rem; flex: 1; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.filter-select { min-width: 145px; }

/* Grid */
.projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 0.85rem; margin-bottom: 1rem; }

/* Card */
.project-card {
  background: white; border-radius: 14px; padding: 1.1rem; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.5rem;
  transition: all 0.2s;
}
.project-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); border-color: #e2e8f0; }
.card-overdue { border-left: 3px solid #ef4444; }

.card-top { display: flex; align-items: center; gap: 0.35rem; }
.status-chip { font-size: 0.6rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.chip-planning { background: #f1f5f9; color: #64748b; }
.chip-in_progress { background: #dbeafe; color: #2563eb; }
.chip-on_hold { background: #fef3c7; color: #d97706; }
.chip-delayed { background: #fee2e2; color: #dc2626; }
.chip-completed { background: #d1fae5; color: #059669; }
.chip-cancelled { background: #f1f5f9; color: #94a3b8; }

.priority-pill { display: flex; align-items: center; gap: 0.2rem; font-size: 0.58rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px; }
.priority-pill i { font-size: 0.5rem; }
.pill-urgent { background: #dc2626; color: white; }
.pill-high { background: #fef3c7; color: #d97706; }

.card-link { text-decoration: none; }
.card-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; transition: color 0.15s; }
.card-link:hover .card-title { color: #6366f1; }
.card-customer { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.3rem; }
.card-customer i { font-size: 0.62rem; }

/* Progress */
.progress-section { margin-top: 0.1rem; }
.progress-header { display: flex; justify-content: space-between; font-size: 0.65rem; color: #94a3b8; margin-bottom: 0.2rem; }
.progress-pct { font-weight: 700; color: #334155; }
.progress-track { height: 5px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; }
.fill-green { background: linear-gradient(90deg, #10b981, #34d399); }
.fill-blue { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.fill-gray { background: #94a3b8; }
.fill-red { background: linear-gradient(90deg, #ef4444, #f87171); }

/* Timeline */
.card-timeline { display: flex; align-items: center; gap: 0.35rem; }
.timeline-date { font-size: 0.72rem; color: #64748b; display: flex; align-items: center; gap: 0.25rem; }
.timeline-date i { font-size: 0.6rem; }
.date-overdue { color: #dc2626; font-weight: 600; }
.days-badge { font-size: 0.58rem; font-weight: 700; background: #eef2ff; color: #6366f1; padding: 0.05rem 0.3rem; border-radius: 4px; }
.overdue-badge { font-size: 0.58rem; font-weight: 700; background: #fee2e2; color: #dc2626; padding: 0.05rem 0.3rem; border-radius: 4px; display: flex; align-items: center; gap: 0.2rem; }
.overdue-badge i { font-size: 0.5rem; }

/* Financials */
.card-financials { background: #f8fafc; border-radius: 8px; padding: 0.45rem 0.6rem; }
.fin-row { display: flex; justify-content: space-between; font-size: 0.72rem; }
.fin-row + .fin-row { margin-top: 0.15rem; }
.fin-label { color: #94a3b8; }
.fin-revenue { font-weight: 600; color: #1e293b; }
.fin-profit { font-weight: 700; }
.fin-profit small { font-weight: 500; opacity: 0.75; }
.positive { color: #059669; } .negative { color: #dc2626; }

/* Footer */
.card-footer { display: flex; align-items: center; justify-content: space-between; padding-top: 0.35rem; border-top: 1px solid #f8fafc; }
.card-manager { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: #64748b; }
.mini-avatar { width: 22px; height: 22px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.card-resources { font-size: 0.68rem; color: #94a3b8; display: flex; align-items: center; gap: 0.25rem; }
.card-resources i { font-size: 0.6rem; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 3rem; background: white; border-radius: 14px; border: 1px solid #f1f5f9; text-align: center; }
.empty-icon { width: 56px; height: 56px; border-radius: 16px; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin-bottom: 0.25rem; }
.empty-icon i { font-size: 1.5rem; color: #6366f1; }
.empty-state h3 { font-size: 1rem; font-weight: 600; color: #475569; margin: 0; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0; max-width: 320px; }

/* Pagination */
.pagination-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .kpi-strip { flex-wrap: nowrap; }
  .projects-grid { grid-template-columns: 1fr; }
  .filter-bar { flex-wrap: wrap; }
}
</style>
