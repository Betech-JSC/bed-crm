<template>
  <div>
    <Head :title="t('common.leads')" />

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">{{ t('common.leads') }}</h1>
        <p class="page-subtitle">{{ leads.total || 0 }} {{ t('common.results') }}</p>
      </div>
      <Link href="/leads/create">
        <Button :label="t('common.create_lead')" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-search">
        <span class="p-input-icon-left w-full">
          <i class="pi pi-search" />
          <InputText v-model="form.search" :placeholder="t('common.search_leads')" class="w-full" @input="handleSearch" />
        </span>
      </div>
      <div class="filter-selects">
        <Select
          v-model="form.status"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('common.all_statuses')"
          class="filter-select"
          @change="handleFilter"
        />
        <Select
          v-model="form.source"
          :options="sourceOptions"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('common.all_sources')"
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
      </div>
      <Button :label="t('common.reset_filters')" icon="pi pi-refresh" severity="secondary" text size="small" @click="reset" />
    </div>

    <!-- Leads Table -->
    <div class="data-card">
      <DataTable
        :value="leads.data"
        :paginator="false"
        :rows="15"
        responsiveLayout="scroll"
        class="p-datatable-sm"
        :rowHover="true"
      >
        <template #empty>
          <div class="empty-state">
            <i class="pi pi-inbox" />
            <span>{{ t('common.no_leads') }}</span>
          </div>
        </template>

        <Column field="name" :header="t('common.name')" sortable>
          <template #body="{ data }">
            <Link :href="`/leads/${data.id}/edit`" class="lead-name-link">
              <div class="lead-avatar">{{ initials(data.name) }}</div>
              <div class="lead-name-info">
                <span class="lead-name">{{ data.name }}</span>
                <span v-if="data.company" class="lead-company">{{ data.company }}</span>
              </div>
              <i v-if="data.deleted_at" class="pi pi-trash deleted-icon" />
            </Link>
          </template>
        </Column>

        <Column :header="t('common.contact_info')">
          <template #body="{ data }">
            <div class="contact-cell">
              <span v-if="data.email" class="contact-email">
                <i class="pi pi-envelope" />{{ data.email }}
              </span>
              <span v-if="data.phone" class="contact-phone">
                <i class="pi pi-phone" />{{ data.phone }}
              </span>
              <span v-if="!data.email && !data.phone" class="text-muted">—</span>
            </div>
          </template>
        </Column>

        <Column field="source" :header="t('common.source')">
          <template #body="{ data }">
            <span v-if="data.source" class="source-badge" :class="`source-${data.source}`">
              {{ sources[data.source] || data.source }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="status" :header="t('common.status')" sortable>
          <template #body="{ data }">
            <span class="status-badge" :class="`status-${data.status}`">
              <i class="status-dot" />
              {{ statuses[data.status] || data.status }}
            </span>
          </template>
        </Column>

        <Column field="score" :header="t('common.lead_score')" sortable>
          <template #body="{ data }">
            <div v-if="data.score !== null && data.score !== undefined" class="score-cell">
              <div class="score-bar-container">
                <div class="score-bar" :style="{ width: data.score + '%' }" :class="scoreClass(data.score)"></div>
              </div>
              <span class="score-value" :class="scoreClass(data.score)">{{ data.score }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column :header="t('common.assigned_to')">
          <template #body="{ data }">
            <div v-if="data.assigned_user" class="assigned-cell">
              <div class="assigned-avatar">{{ initials(data.assigned_user.name) }}</div>
              <span>{{ data.assigned_user.name }}</span>
            </div>
            <span v-else class="text-muted">{{ t('common.all_users') }}</span>
          </template>
        </Column>

        <Column :header="t('common.tags')">
          <template #body="{ data }">
            <div v-if="data.tags && data.tags.length > 0" class="tags-cell">
              <span v-for="tag in data.tags.slice(0, 3)" :key="tag" class="tag-chip">{{ tag }}</span>
              <span v-if="data.tags.length > 3" class="tag-more">+{{ data.tags.length - 3 }}</span>
            </div>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column style="width: 60px">
          <template #body="{ data }">
            <Link :href="`/leads/${data.id}/edit`">
              <Button icon="pi pi-chevron-right" text rounded size="small" />
            </Link>
          </template>
        </Column>
      </DataTable>

      <div v-if="leads.total > 0" class="pagination-wrapper">
        <span class="pagination-info">
          {{ t('common.showing') }} {{ leads.from }}–{{ leads.to }} {{ t('common.of') }} {{ leads.total }} {{ t('common.results') }}
        </span>
        <Paginator
          :first="(leads.current_page - 1) * leads.per_page"
          :rows="leads.per_page"
          :totalRecords="leads.total"
          @page="onPageChange"
          template="PrevPageLink PageLinks NextPageLink"
        />
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
  props: {
    filters: Object,
    leads: Object,
    statuses: Object,
    sources: Object,
    salesUsers: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        status: this.filters.status,
        source: this.filters.source,
        assigned_to: this.filters.assigned_to,
        trashed: this.filters.trashed,
      },
    }
  },
  computed: {
    statusOptions() {
      return [
        { label: this.t('common.all_statuses'), value: null },
        ...Object.entries(this.statuses).map(([value, label]) => ({ label, value })),
      ]
    },
    sourceOptions() {
      return [
        { label: this.t('common.all_sources'), value: null },
        ...Object.entries(this.sources).map(([value, label]) => ({ label, value })),
      ]
    },
    assignedOptions() {
      return [
        { label: this.t('common.all_users'), value: null },
        ...this.salesUsers.map(user => ({ label: user.name, value: user.id })),
      ]
    },
  },
  methods: {
    handleSearch: throttle(function () {
      this.$inertia.get('/leads', pickBy(this.form), { preserveState: true })
    }, 300),
    handleFilter() {
      this.$inertia.get('/leads', pickBy(this.form), { preserveState: true })
    },
    reset() {
      this.form = mapValues(this.form, () => null)
      this.$inertia.get('/leads', {}, { preserveState: true })
    },
    initials(name) {
      if (!name) return '?'
      return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
    },
    scoreClass(score) {
      if (score >= 80) return 'score-high'
      if (score >= 60) return 'score-mid'
      if (score >= 40) return 'score-low'
      return 'score-cold'
    },
    onPageChange(event) {
      const page = event.page + 1
      const currentUrl = new URL(window.location.href)
      currentUrl.searchParams.set('page', page)
      router.visit(currentUrl.pathname + currentUrl.search, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Filter Bar ===== */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}
.filter-search { flex: 1; min-width: 220px; }
.filter-search :deep(.p-inputtext) { border-radius: 8px; }
.filter-selects { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.filter-select { min-width: 140px; }

/* ===== Data Card ===== */
.data-card {
  background: white;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

/* ===== Lead Name Cell ===== */
.lead-name-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
  color: inherit;
}
.lead-avatar {
  width: 34px; height: 34px;
  border-radius: 8px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  font-size: 0.68rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.lead-name-info { display: flex; flex-direction: column; min-width: 0; }
.lead-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lead-name-link:hover .lead-name { color: #6366f1; }
.lead-company { font-size: 0.72rem; color: #94a3b8; }
.deleted-icon { font-size: 0.65rem; color: #cbd5e1; margin-left: auto; }

/* ===== Contact Cell ===== */
.contact-cell { display: flex; flex-direction: column; gap: 0.2rem; }
.contact-email, .contact-phone {
  font-size: 0.78rem; color: #64748b;
  display: flex; align-items: center; gap: 0.35rem;
}
.contact-email i, .contact-phone i { font-size: 0.65rem; color: #94a3b8; }

/* ===== Source Badge ===== */
.source-badge {
  font-size: 0.72rem; font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  text-transform: capitalize;
}
.source-website { background: #dbeafe; color: #2563eb; }
.source-referral { background: #d1fae5; color: #059669; }
.source-social { background: #fce7f3; color: #db2777; }
.source-email { background: #e0e7ff; color: #4f46e5; }
.source-phone { background: #fef3c7; color: #d97706; }
.source-other { background: #f1f5f9; color: #64748b; }

/* ===== Status Badge ===== */
.status-badge {
  display: inline-flex; align-items: center; gap: 0.35rem;
  font-size: 0.75rem; font-weight: 600;
  padding: 0.25rem 0.6rem;
  border-radius: 20px;
}
.status-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  display: inline-block;
}
.status-new { background: #eff6ff; color: #3b82f6; }
.status-new .status-dot { background: #3b82f6; }
.status-contacted { background: #fef3c7; color: #d97706; }
.status-contacted .status-dot { background: #d97706; }
.status-qualified { background: #d1fae5; color: #059669; }
.status-qualified .status-dot { background: #059669; }
.status-won { background: #d1fae5; color: #059669; }
.status-won .status-dot { background: #059669; }
.status-lost { background: #fee2e2; color: #dc2626; }
.status-lost .status-dot { background: #dc2626; }

/* ===== Score Cell ===== */
.score-cell { display: flex; align-items: center; gap: 0.5rem; }
.score-bar-container {
  width: 50px; height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
}
.score-bar { height: 100%; border-radius: 3px; transition: width 0.3s; }
.score-value { font-size: 0.78rem; font-weight: 700; }
.score-high { background: #10b981; color: #059669; }
.score-mid { background: #f59e0b; color: #d97706; }
.score-low { background: #ef4444; color: #dc2626; }
.score-cold { background: #94a3b8; color: #64748b; }

/* ===== Assigned Cell ===== */
.assigned-cell { display: flex; align-items: center; gap: 0.45rem; font-size: 0.82rem; color: #334155; }
.assigned-avatar {
  width: 26px; height: 26px;
  border-radius: 50%;
  background: #e0e7ff; color: #4f46e5;
  font-size: 0.6rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}

/* ===== Tags ===== */
.tags-cell { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.tag-chip {
  font-size: 0.68rem; font-weight: 500;
  padding: 0.12rem 0.45rem;
  border-radius: 4px;
  background: #f1f5f9; color: #475569;
}
.tag-more { font-size: 0.65rem; color: #94a3b8; font-weight: 600; }

/* ===== Pagination ===== */
.pagination-wrapper {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 1.25rem;
  border-top: 1px solid #f1f5f9;
}
.pagination-info { font-size: 0.78rem; color: #94a3b8; }

/* ===== Empty State ===== */
.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
  padding: 3rem; color: #94a3b8;
}
.empty-state i { font-size: 2rem; }

.text-muted { color: #cbd5e1; font-size: 0.82rem; }

@media (max-width: 768px) {
  .filter-bar { flex-direction: column; }
  .filter-selects { flex-direction: column; width: 100%; }
  .filter-select { width: 100%; }
}
</style>
