<template>
  <div>
    <Head title="Loyalty & CSKH" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-heart" style="color: #f59e0b; margin-right: 0.5rem;" />Loyalty & CSKH</h1>
        <p class="page-subtitle">{{ analytics.total_customers }} khách hàng · Chăm sóc, giữ chân & phát triển</p>
      </div>
      <Link href="/customers/create"><Button :label="t('common.create_customer')" icon="pi pi-plus" /></Link>
    </div>

    <!-- KPI Strip -->
    <div class="kpi-strip">
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--blue"><i class="pi pi-users" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.total_customers }}</span>
          <span class="kpi-lbl">{{ t('common.total_customers_label') }}</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--green"><i class="pi pi-heart" /></div>
        <div class="kpi-data">
          <div class="kpi-row">
            <span class="kpi-num">{{ analytics.avg_health_score }}</span>
            <span class="health-mini-badge" :class="healthClass(analytics.avg_health_score)">{{ healthLabel(analytics.avg_health_score) }}</span>
          </div>
          <span class="kpi-lbl">{{ t('common.avg_health') }}</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--orange"><i class="pi pi-dollar" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ formatCurrency(analytics.total_mrr) }}</span>
          <span class="kpi-lbl">MRR</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--red"><i class="pi pi-exclamation-triangle" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.churn_rate }}%</span>
          <span class="kpi-lbl">{{ t('common.churn_rate') }}</span>
        </div>
      </div>
      <div class="kpi-chip">
        <div class="kpi-icon kpi-icon--purple"><i class="pi pi-sync" /></div>
        <div class="kpi-data">
          <span class="kpi-num">{{ analytics.renewing_soon }}</span>
          <span class="kpi-lbl">{{ t('common.renewing_soon') }}</span>
        </div>
      </div>
    </div>

    <!-- Lifecycle Distribution -->
    <div class="lifecycle-bar">
      <div
        v-for="(label, key) in lifecycleStatuses" :key="key"
        class="lifecycle-segment" :class="`lifecycle-${key}`"
        :style="{ flex: analytics.status_counts[key] || 0 }"
        :title="`${label}: ${analytics.status_counts[key] || 0}`"
        @click="setFilter('lifecycle_status', key)"
      >
        <span v-if="analytics.status_counts[key]">{{ label }}: {{ analytics.status_counts[key] }}</span>
      </div>
    </div>

    <!-- Churn Risks -->
    <div v-if="churnRisks.length > 0" class="churn-alert">
      <div class="churn-alert-header">
        <div class="churn-icon"><i class="pi pi-exclamation-triangle" /></div>
        <div>
          <h4>{{ t('common.churn_risk_detected') }}</h4>
          <p>{{ churnRisks.length }} khách hàng có nguy cơ rời bỏ</p>
        </div>
      </div>
      <div class="churn-list">
        <div v-for="risk in churnRisks" :key="risk.id" class="churn-item">
          <div class="health-dot" :class="healthClass(risk.health_score)">{{ risk.health_score }}</div>
          <Link :href="`/customers/${risk.id}/edit`" class="churn-name">{{ risk.name }}</Link>
          <span class="risk-badge" :class="`risk-${risk.risk_level}`">{{ risk.risk_level }}</span>
          <span class="churn-factors">{{ risk.risk_factors.join(' · ') }}</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <InputText v-model="form.search" :placeholder="t('common.search_customers')" class="w-full" @input="handleSearch" />
      </div>
      <Select v-model="form.lifecycle_status" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('common.all_statuses')" class="filter-select" @change="handleFilter" />
      <Select v-model="form.assigned_to" :options="assignedOptions" optionLabel="label" optionValue="value" :placeholder="t('common.all_users')" class="filter-select" @change="handleFilter" />
      <Button icon="pi pi-refresh" severity="secondary" text size="small" @click="reset" v-tooltip="'Reset'" />
    </div>

    <!-- Table -->
    <div class="data-card">
      <DataTable :value="customers.data" :paginator="false" responsiveLayout="scroll" class="p-datatable-sm" :rowHover="true">
        <template #empty>
          <div class="empty-state">
            <div class="empty-icon"><i class="pi pi-users" /></div>
            <h3>{{ t('common.no_customers') }}</h3>
            <p>Thêm khách hàng đầu tiên để bắt đầu theo dõi</p>
            <Link href="/customers/create"><Button label="Tạo khách hàng" icon="pi pi-plus" size="small" /></Link>
          </div>
        </template>

        <Column field="name" :header="t('common.name')" sortable>
          <template #body="{ data }">
            <Link :href="`/customers/${data.id}/edit`" class="customer-link">
              <div class="customer-avatar" :class="healthClass(data.health_score)">{{ data.health_score }}</div>
              <div class="customer-info">
                <span class="customer-name">{{ data.name }}</span>
                <span v-if="data.organization" class="customer-org">{{ data.organization.name }}</span>
              </div>
            </Link>
          </template>
        </Column>

        <Column :header="t('common.lifecycle')">
          <template #body="{ data }">
            <span class="status-badge" :class="`status-${data.lifecycle_status}`">
              <i class="status-dot" />{{ lifecycleStatuses[data.lifecycle_status] || data.lifecycle_status }}
            </span>
          </template>
        </Column>

        <Column :header="t('common.health')">
          <template #body="{ data }">
            <div class="health-cell">
              <div class="health-bar-bg"><div class="health-bar" :class="healthClass(data.health_score)" :style="{ width: data.health_score + '%' }" /></div>
              <span class="health-value" :class="healthClass(data.health_score)">{{ data.health_score }}</span>
            </div>
          </template>
        </Column>

        <Column header="MRR">
          <template #body="{ data }">
            <span v-if="data.mrr" class="mrr-value">{{ formatCurrency(data.mrr) }}</span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column :header="t('common.renewal')">
          <template #body="{ data }">
            <div v-if="data.contract_end" class="renewal-cell">
              <span class="renewal-date" :class="{ 'renewal-soon': isRenewingSoon(data.contract_end) }">
                <i class="pi pi-calendar" />{{ data.contract_end }}
              </span>
              <span v-if="data.renewal_status" class="renewal-badge" :class="`renewal-${data.renewal_status}`">{{ data.renewal_status }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column :header="t('common.assigned_to')">
          <template #body="{ data }">
            <div v-if="data.assigned_user" class="assigned-cell">
              <div class="mini-avatar">{{ initials(data.assigned_user.name) }}</div>
              <span>{{ data.assigned_user.name }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column style="width: 50px">
          <template #body="{ data }">
            <Link :href="`/customers/${data.id}/edit`"><Button icon="pi pi-chevron-right" text rounded size="small" /></Link>
          </template>
        </Column>
      </DataTable>

      <div v-if="customers.total > 0" class="pagination-wrapper">
        <span class="pagination-info">{{ t('common.showing') }} {{ customers.from }}–{{ customers.to }} {{ t('common.of') }} {{ customers.total }}</span>
        <Paginator :first="(customers.current_page - 1) * customers.per_page" :rows="customers.per_page" :totalRecords="customers.total" @page="onPageChange" template="PrevPageLink PageLinks NextPageLink" />
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link, DataTable, Column, Button, InputText, Select, Paginator },
  layout: Layout,
  props: { customers: Object, analytics: Object, churnRisks: Array, filters: Object, lifecycleStatuses: Object, salesUsers: Array },
  setup() { const { t } = useTranslation(); return { t } },
  data() {
    return {
      form: { search: this.filters.search, lifecycle_status: this.filters.lifecycle_status, assigned_to: this.filters.assigned_to },
    }
  },
  computed: {
    statusOptions() {
      return [{ label: this.t('common.all_statuses'), value: null }, ...Object.entries(this.lifecycleStatuses).map(([value, label]) => ({ label, value }))]
    },
    assignedOptions() {
      return [{ label: this.t('common.all_users'), value: null }, ...this.salesUsers.map(u => ({ label: u.name, value: u.id }))]
    },
  },
  methods: {
    handleSearch: throttle(function () { this.$inertia.get('/customers', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { this.$inertia.get('/customers', pickBy(this.form), { preserveState: true }) },
    setFilter(key, value) { this.form[key] = value; this.handleFilter() },
    reset() { this.form = mapValues(this.form, () => null); this.$inertia.get('/customers', {}, { preserveState: true }) },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    healthClass(s) { if (s >= 80) return 'health-great'; if (s >= 60) return 'health-good'; if (s >= 40) return 'health-fair'; return 'health-poor' },
    healthLabel(s) { if (s >= 80) return 'Tốt'; if (s >= 60) return 'Khá'; if (s >= 40) return 'TB'; return 'Yếu' },
    initials(n) { return n ? n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '?' },
    isRenewingSoon(d) { const diff = new Date(d) - new Date(); return diff > 0 && diff < 30 * 86400000 },
    onPageChange(e) {
      const url = new URL(window.location.href)
      url.searchParams.set('page', e.page + 1)
      router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* KPI Strip */
.kpi-strip { display: flex; gap: 0.5rem; margin-bottom: 0.85rem; overflow-x: auto; }
.kpi-chip {
  display: flex; align-items: center; gap: 0.55rem; padding: 0.6rem 0.85rem;
  background: white; border-radius: 12px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); flex-shrink: 0; transition: all 0.2s;
}
.kpi-chip:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-1px); }
.kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
.kpi-icon--blue { background: #dbeafe; color: #2563eb; }
.kpi-icon--green { background: #d1fae5; color: #059669; }
.kpi-icon--orange { background: #fef3c7; color: #d97706; }
.kpi-icon--red { background: #fee2e2; color: #dc2626; }
.kpi-icon--purple { background: #ede9fe; color: #7c3aed; }
.kpi-data { display: flex; flex-direction: column; }
.kpi-row { display: flex; align-items: center; gap: 0.35rem; }
.kpi-num { font-size: 0.95rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.kpi-lbl { font-size: 0.62rem; color: #94a3b8; }
.health-mini-badge { font-size: 0.55rem; font-weight: 700; padding: 0.05rem 0.3rem; border-radius: 4px; color: white; }

/* Lifecycle */
.lifecycle-bar { display: flex; height: 26px; border-radius: 8px; overflow: hidden; margin-bottom: 0.85rem; gap: 2px; }
.lifecycle-segment { display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 600; color: white; min-width: 0; transition: flex 0.3s; cursor: pointer; }
.lifecycle-segment:hover { opacity: 0.85; }
.lifecycle-onboarding { background: #6366f1; }
.lifecycle-active { background: #10b981; }
.lifecycle-at_risk { background: #f59e0b; }
.lifecycle-churned { background: #ef4444; }
.lifecycle-reactivated { background: #8b5cf6; }

/* Health */
.health-great { background: #10b981; color: #059669; }
.health-good { background: #3b82f6; color: #2563eb; }
.health-fair { background: #f59e0b; color: #d97706; }
.health-poor { background: #ef4444; color: #dc2626; }

/* Churn */
.churn-alert { background: #fef2f2; border: 1px solid #fecaca; border-radius: 14px; padding: 1rem; margin-bottom: 0.85rem; }
.churn-alert-header { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.65rem; }
.churn-icon { width: 36px; height: 36px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
.churn-alert-header h4 { font-size: 0.85rem; font-weight: 600; color: #dc2626; margin: 0; }
.churn-alert-header p { font-size: 0.72rem; color: #f87171; margin: 0; }
.churn-list { display: flex; flex-direction: column; gap: 0.35rem; }
.churn-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.78rem; padding: 0.4rem 0.55rem; background: white; border-radius: 8px; }
.health-dot { width: 28px; height: 28px; border-radius: 50%; color: white; font-size: 0.6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.churn-name { font-weight: 600; color: #1e293b; text-decoration: none; }
.churn-name:hover { color: #dc2626; }
.risk-badge { font-size: 0.58rem; font-weight: 700; padding: 0.08rem 0.3rem; border-radius: 4px; text-transform: uppercase; }
.risk-high { background: #fee2e2; color: #dc2626; }
.risk-medium { background: #fef3c7; color: #d97706; }
.risk-low { background: #dbeafe; color: #2563eb; }
.churn-factors { font-size: 0.68rem; color: #94a3b8; flex: 1; text-align: right; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; margin-bottom: 0.85rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.35rem; flex: 1; min-width: 200px; }
.search-box i { color: #94a3b8; font-size: 0.82rem; }
.filter-select { min-width: 145px; }

/* Table */
.data-card { background: white; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; overflow: hidden; }
.customer-link { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: inherit; }
.customer-avatar { width: 34px; height: 34px; border-radius: 50%; color: white; font-size: 0.68rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.customer-info { display: flex; flex-direction: column; }
.customer-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; transition: color 0.15s; }
.customer-link:hover .customer-name { color: #6366f1; }
.customer-org { font-size: 0.68rem; color: #94a3b8; }

/* Status */
.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.5rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-onboarding { background: #eef2ff; color: #6366f1; } .status-onboarding .status-dot { background: #6366f1; }
.status-active { background: #d1fae5; color: #059669; } .status-active .status-dot { background: #059669; }
.status-at_risk { background: #fef3c7; color: #d97706; } .status-at_risk .status-dot { background: #d97706; }
.status-churned { background: #fee2e2; color: #dc2626; } .status-churned .status-dot { background: #dc2626; }
.status-reactivated { background: #ede9fe; color: #7c3aed; } .status-reactivated .status-dot { background: #7c3aed; }

/* Health Bar */
.health-cell { display: flex; align-items: center; gap: 0.5rem; }
.health-bar-bg { width: 60px; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.health-bar { height: 100%; border-radius: 3px; transition: width 0.4s; }
.health-value { font-size: 0.78rem; font-weight: 700; }

/* MRR */
.mrr-value { font-size: 0.82rem; font-weight: 600; color: #059669; }

/* Renewal */
.renewal-cell { display: flex; flex-direction: column; gap: 0.15rem; }
.renewal-date { font-size: 0.78rem; color: #64748b; display: flex; align-items: center; gap: 0.25rem; }
.renewal-date i { font-size: 0.62rem; }
.renewal-soon { color: #d97706; font-weight: 600; }
.renewal-badge { font-size: 0.58rem; font-weight: 600; padding: 0.08rem 0.3rem; border-radius: 4px; text-transform: capitalize; }
.renewal-upcoming { background: #fef3c7; color: #d97706; }
.renewal-in_progress { background: #dbeafe; color: #2563eb; }
.renewal-renewed { background: #d1fae5; color: #059669; }
.renewal-lost { background: #fee2e2; color: #dc2626; }

/* Assigned */
.assigned-cell { display: flex; align-items: center; gap: 0.35rem; font-size: 0.82rem; color: #334155; }
.mini-avatar { width: 22px; height: 22px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 3rem; text-align: center; }
.empty-icon { width: 56px; height: 56px; border-radius: 16px; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin-bottom: 0.25rem; }
.empty-icon i { font-size: 1.5rem; color: #6366f1; }
.empty-state h3 { font-size: 1rem; font-weight: 600; color: #475569; margin: 0; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0; }

/* Pagination */
.pagination-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }
.text-muted { color: #cbd5e1; font-size: 0.82rem; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .kpi-strip { flex-wrap: nowrap; }
  .filter-bar { flex-direction: column; }
  .search-box { width: 100%; }
}
</style>
