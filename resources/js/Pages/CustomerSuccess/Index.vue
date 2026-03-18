<template>
  <div>
    <Head :title="t('common.customer_success')" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ t('common.customer_success') }}</h1>
        <p class="page-subtitle">{{ analytics.total_customers }} {{ t('common.customers') }}</p>
      </div>
      <Link href="/customers/create">
        <Button :label="t('common.create_customer')" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card kpi-blue">
        <div class="kpi-icon"><i class="pi pi-users" /></div>
        <div class="kpi-info">
          <span class="kpi-value">{{ analytics.total_customers }}</span>
          <span class="kpi-label">{{ t('common.total_customers_label') }}</span>
        </div>
      </div>
      <div class="kpi-card kpi-green">
        <div class="kpi-icon"><i class="pi pi-heart" /></div>
        <div class="kpi-info">
          <span class="kpi-value">{{ analytics.avg_health_score }}</span>
          <span class="kpi-label">{{ t('common.avg_health') }}</span>
        </div>
        <div class="kpi-badge" :class="healthClass(analytics.avg_health_score)">{{ healthLabel(analytics.avg_health_score) }}</div>
      </div>
      <div class="kpi-card kpi-orange">
        <div class="kpi-icon"><i class="pi pi-dollar" /></div>
        <div class="kpi-info">
          <span class="kpi-value">{{ formatCurrency(analytics.total_mrr) }}</span>
          <span class="kpi-label">MRR</span>
        </div>
      </div>
      <div class="kpi-card kpi-red">
        <div class="kpi-icon"><i class="pi pi-exclamation-triangle" /></div>
        <div class="kpi-info">
          <span class="kpi-value">{{ analytics.churn_rate }}%</span>
          <span class="kpi-label">{{ t('common.churn_rate') }}</span>
        </div>
      </div>
      <div class="kpi-card kpi-purple">
        <div class="kpi-icon"><i class="pi pi-sync" /></div>
        <div class="kpi-info">
          <span class="kpi-value">{{ analytics.renewing_soon }}</span>
          <span class="kpi-label">{{ t('common.renewing_soon') }}</span>
        </div>
      </div>
    </div>

    <!-- Lifecycle Distribution -->
    <div class="lifecycle-bar">
      <div
        v-for="(label, key) in lifecycleStatuses"
        :key="key"
        class="lifecycle-segment"
        :class="`lifecycle-${key}`"
        :style="{ flex: analytics.status_counts[key] || 0 }"
        :title="`${label}: ${analytics.status_counts[key] || 0}`"
      >
        <span v-if="analytics.status_counts[key]">{{ label }}: {{ analytics.status_counts[key] }}</span>
      </div>
    </div>

    <!-- Churn Risks Alert -->
    <div v-if="churnRisks.length > 0" class="churn-alert">
      <div class="churn-alert-header">
        <i class="pi pi-exclamation-triangle" />
        <span>{{ t('common.churn_risk_detected') }} ({{ churnRisks.length }})</span>
      </div>
      <div class="churn-list">
        <div v-for="risk in churnRisks" :key="risk.id" class="churn-item">
          <Link :href="`/customers/${risk.id}/edit`" class="churn-name">{{ risk.name }}</Link>
          <span class="risk-badge" :class="`risk-${risk.risk_level}`">{{ risk.risk_level }}</span>
          <div class="health-mini" :class="healthClass(risk.health_score)">{{ risk.health_score }}</div>
          <span class="churn-factors">{{ risk.risk_factors.join(' · ') }}</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-search">
        <span class="p-input-icon-left w-full">
          <i class="pi pi-search" />
          <InputText v-model="form.search" :placeholder="t('common.search_customers')" class="w-full" @input="handleSearch" />
        </span>
      </div>
      <Select
        v-model="form.lifecycle_status"
        :options="statusOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('common.all_statuses')"
        class="filter-select"
        @change="handleFilter"
      />
      <Select
        v-model="form.assigned_to"
        :options="assignedOptions"
        optionLabel="label"
        optionValue="value"
        :placeholder="t('common.all_users')"
        class="filter-select"
        @change="handleFilter"
      />
      <Button :label="t('common.reset_filters')" icon="pi pi-refresh" severity="secondary" text size="small" @click="reset" />
    </div>

    <!-- Customers Table -->
    <div class="data-card">
      <DataTable :value="customers.data" :paginator="false" responsiveLayout="scroll" class="p-datatable-sm" :rowHover="true">
        <template #empty>
          <div class="empty-state"><i class="pi pi-users" /><span>{{ t('common.no_customers') }}</span></div>
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
              <div class="health-bar-bg"><div class="health-bar" :class="healthClass(data.health_score)" :style="{ width: data.health_score + '%' }"></div></div>
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
              <span v-if="data.renewal_status" class="renewal-badge" :class="`renewal-${data.renewal_status}`">
                {{ data.renewal_status }}
              </span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column :header="t('common.assigned_to')">
          <template #body="{ data }">
            <span v-if="data.assigned_user" class="assigned-cell">{{ data.assigned_user.name }}</span>
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
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        lifecycle_status: this.filters.lifecycle_status,
        assigned_to: this.filters.assigned_to,
      },
    }
  },
  computed: {
    statusOptions() {
      return [
        { label: this.t('common.all_statuses'), value: null },
        ...Object.entries(this.lifecycleStatuses).map(([value, label]) => ({ label, value })),
      ]
    },
    assignedOptions() {
      return [
        { label: this.t('common.all_users'), value: null },
        ...this.salesUsers.map(u => ({ label: u.name, value: u.id })),
      ]
    },
  },
  methods: {
    handleSearch: throttle(function () { this.$inertia.get('/customers', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { this.$inertia.get('/customers', pickBy(this.form), { preserveState: true }) },
    reset() { this.form = mapValues(this.form, () => null); this.$inertia.get('/customers', {}, { preserveState: true }) },
    formatCurrency(v) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(v) },
    healthClass(s) { if (s >= 80) return 'health-great'; if (s >= 60) return 'health-good'; if (s >= 40) return 'health-fair'; return 'health-poor' },
    healthLabel(s) { if (s >= 80) return 'Excellent'; if (s >= 60) return 'Good'; if (s >= 40) return 'Fair'; return 'Poor' },
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
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* KPI Grid */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.kpi-card { background: white; border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; position: relative; }
.kpi-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.kpi-blue .kpi-icon { background: #dbeafe; color: #2563eb; }
.kpi-green .kpi-icon { background: #d1fae5; color: #059669; }
.kpi-orange .kpi-icon { background: #fef3c7; color: #d97706; }
.kpi-red .kpi-icon { background: #fee2e2; color: #dc2626; }
.kpi-purple .kpi-icon { background: #ede9fe; color: #7c3aed; }
.kpi-value { font-size: 1.2rem; font-weight: 700; color: #0f172a; display: block; }
.kpi-label { font-size: 0.72rem; color: #94a3b8; }
.kpi-badge { position: absolute; top: 0.5rem; right: 0.5rem; font-size: 0.62rem; font-weight: 700; padding: 0.12rem 0.4rem; border-radius: 4px; }

/* Lifecycle Bar */
.lifecycle-bar { display: flex; height: 28px; border-radius: 8px; overflow: hidden; margin-bottom: 1rem; gap: 2px; }
.lifecycle-segment { display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 600; color: white; min-width: 0; transition: flex 0.3s; }
.lifecycle-onboarding { background: #6366f1; }
.lifecycle-active { background: #10b981; }
.lifecycle-at_risk { background: #f59e0b; }
.lifecycle-churned { background: #ef4444; }
.lifecycle-reactivated { background: #8b5cf6; }

/* Health Colors */
.health-great { background: #10b981; color: #059669; }
.health-good { background: #3b82f6; color: #2563eb; }
.health-fair { background: #f59e0b; color: #d97706; }
.health-poor { background: #ef4444; color: #dc2626; }

/* Churn Alert */
.churn-alert { background: #fef2f2; border: 1px solid #fee2e2; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1rem; }
.churn-alert-header { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #dc2626; margin-bottom: 0.5rem; }
.churn-list { display: flex; flex-direction: column; gap: 0.35rem; }
.churn-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.78rem; padding: 0.35rem 0.5rem; background: white; border-radius: 6px; }
.churn-name { font-weight: 600; color: #1e293b; text-decoration: none; }
.churn-name:hover { color: #dc2626; }
.risk-badge { font-size: 0.62rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px; text-transform: uppercase; }
.risk-high { background: #fee2e2; color: #dc2626; }
.risk-medium { background: #fef3c7; color: #d97706; }
.risk-low { background: #dbeafe; color: #2563eb; }
.health-mini { width: 26px; height: 26px; border-radius: 50%; color: white; font-size: 0.62rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.churn-factors { font-size: 0.7rem; color: #94a3b8; flex: 1; text-align: right; }

/* Filters */
.filter-bar { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1rem; }
.filter-search { flex: 1; min-width: 220px; }
.filter-select { min-width: 150px; }

/* Table */
.data-card { background: white; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; overflow: hidden; }
.customer-link { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: inherit; }
.customer-avatar { width: 34px; height: 34px; border-radius: 50%; color: white; font-size: 0.7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.customer-info { display: flex; flex-direction: column; }
.customer-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.customer-link:hover .customer-name { color: #6366f1; }
.customer-org { font-size: 0.7rem; color: #94a3b8; }

/* Status */
.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-onboarding { background: #eef2ff; color: #6366f1; }
.status-onboarding .status-dot { background: #6366f1; }
.status-active { background: #d1fae5; color: #059669; }
.status-active .status-dot { background: #059669; }
.status-at_risk { background: #fef3c7; color: #d97706; }
.status-at_risk .status-dot { background: #d97706; }
.status-churned { background: #fee2e2; color: #dc2626; }
.status-churned .status-dot { background: #dc2626; }
.status-reactivated { background: #ede9fe; color: #7c3aed; }
.status-reactivated .status-dot { background: #7c3aed; }

/* Health Bar */
.health-cell { display: flex; align-items: center; gap: 0.5rem; }
.health-bar-bg { width: 60px; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.health-bar { height: 100%; border-radius: 3px; transition: width 0.3s; }
.health-value { font-size: 0.78rem; font-weight: 700; }

/* MRR */
.mrr-value { font-size: 0.82rem; font-weight: 600; color: #059669; }

/* Renewal */
.renewal-cell { display: flex; flex-direction: column; gap: 0.2rem; }
.renewal-date { font-size: 0.78rem; color: #64748b; display: flex; align-items: center; gap: 0.25rem; }
.renewal-date i { font-size: 0.65rem; }
.renewal-soon { color: #d97706; font-weight: 600; }
.renewal-badge { font-size: 0.62rem; font-weight: 600; padding: 0.1rem 0.35rem; border-radius: 4px; text-transform: capitalize; }
.renewal-upcoming { background: #fef3c7; color: #d97706; }
.renewal-in_progress { background: #dbeafe; color: #2563eb; }
.renewal-renewed { background: #d1fae5; color: #059669; }
.renewal-lost { background: #fee2e2; color: #dc2626; }

/* Shared */
.assigned-cell { font-size: 0.82rem; color: #334155; }
.pagination-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.25rem; border-top: 1px solid #f1f5f9; }
.pagination-info { font-size: 0.78rem; color: #94a3b8; }
.empty-state { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 3rem; color: #94a3b8; }
.empty-state i { font-size: 2rem; }
.text-muted { color: #cbd5e1; font-size: 0.82rem; }
</style>
