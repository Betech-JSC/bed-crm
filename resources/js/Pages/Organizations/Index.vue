<template>
  <div>
    <Head :title="t('common.organizations')" />

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-building" style="color: #f59e0b; margin-right: 0.5rem;" />
          {{ t('common.organizations') }}
        </h1>
        <p class="page-subtitle">{{ organizations.total || organizations.data?.length || 0 }} {{ t('common.results') }}</p>
      </div>
      <Link href="/organizations/create">
        <Button :label="t('common.create_organization')" icon="pi pi-plus" />
      </Link>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-chip stat-chip--total">
        <i class="pi pi-building" />
        <span class="stat-chip-value">{{ organizations.total || 0 }}</span>
        <span class="stat-chip-label">Tổng</span>
      </div>
      <div class="stat-chip stat-chip--contacts">
        <i class="pi pi-users" />
        <span class="stat-chip-value">{{ totalContacts }}</span>
        <span class="stat-chip-label">Liên hệ</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrapper">
        <i class="pi pi-search search-icon" />
        <InputText v-model="form.search" :placeholder="t('common.search_organizations')" class="search-input" @input="handleSearch" />
      </div>
      <Select
        v-model="form.trashed"
        :options="trashedOptions"
        optionLabel="label"
        optionValue="value"
        class="filter-select"
        @change="handleFilter"
      />
      <Button :label="t('common.reset_filters')" icon="pi pi-refresh" severity="secondary" text size="small" @click="reset" />
    </div>

    <!-- Organizations Grid View (default) / Table View -->
    <div class="view-toggle">
      <button class="view-btn" :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'">
        <i class="pi pi-th-large" />
      </button>
      <button class="view-btn" :class="{ active: viewMode === 'table' }" @click="viewMode = 'table'">
        <i class="pi pi-list" />
      </button>
    </div>

    <!-- Grid View -->
    <div v-if="viewMode === 'grid'" class="org-grid">
      <div
        v-for="org in organizations.data"
        :key="org.id"
        class="org-card"
        :class="{ 'org-card--deleted': org.deleted_at }"
        @click="goToOrg(org)"
      >
        <div class="org-card-header">
          <div class="org-card-avatar" :style="{ background: getAvatarGradient(org.name) }">
            {{ initials(org.name) }}
          </div>
          <div class="org-card-badge" v-if="org.deleted_at">
            <i class="pi pi-trash" /> Đã xóa
          </div>
        </div>
        <div class="org-card-body">
          <h3 class="org-card-name">{{ org.name }}</h3>
          <p class="org-card-email" v-if="org.email">
            <i class="pi pi-envelope" /> {{ org.email }}
          </p>
          <div class="org-card-meta">
            <span v-if="org.phone" class="meta-item">
              <i class="pi pi-phone" /> {{ org.phone }}
            </span>
            <span v-if="org.city" class="meta-item">
              <i class="pi pi-map-marker" /> {{ org.city }}{{ org.country ? `, ${org.country}` : '' }}
            </span>
          </div>
        </div>
        <div class="org-card-footer">
          <div class="org-contacts-badge" v-if="org.contacts_count">
            <i class="pi pi-users" />
            <span>{{ org.contacts_count }} liên hệ</span>
          </div>
          <div class="org-contacts-badge org-contacts-badge--empty" v-else>
            <span>Chưa có liên hệ</span>
          </div>
          <Link :href="`/organizations/${org.id}/edit`" class="org-card-arrow" @click.stop>
            <i class="pi pi-chevron-right" />
          </Link>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <div v-else class="data-card">
      <DataTable
        :value="organizations.data"
        :paginator="false"
        :rows="15"
        responsiveLayout="scroll"
        class="p-datatable-sm"
        :rowHover="true"
      >
        <template #empty>
          <div class="empty-state">
            <div class="empty-illustration">
              <i class="pi pi-building" />
            </div>
            <h3>{{ t('common.no_organizations') }}</h3>
            <p>Tạo tổ chức đầu tiên để bắt đầu quản lý</p>
            <Link href="/organizations/create">
              <Button label="Tạo tổ chức" icon="pi pi-plus" size="small" />
            </Link>
          </div>
        </template>

        <Column field="name" :header="t('common.name')" sortable>
          <template #body="{ data }">
            <Link :href="`/organizations/${data.id}/edit`" class="org-name-link">
              <div class="org-avatar" :style="{ background: getAvatarGradient(data.name) }">
                {{ initials(data.name) }}
              </div>
              <div class="org-name-info">
                <span class="org-name-text">{{ data.name }}</span>
                <span v-if="data.email" class="org-email-sub">{{ data.email }}</span>
              </div>
              <i v-if="data.deleted_at" class="pi pi-trash deleted-icon" />
            </Link>
          </template>
        </Column>

        <Column field="phone" :header="t('common.phone')">
          <template #body="{ data }">
            <span v-if="data.phone" class="info-cell">
              <i class="pi pi-phone" />{{ data.phone }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="city" :header="t('common.city')">
          <template #body="{ data }">
            <span v-if="data.city" class="info-cell">
              <i class="pi pi-map-marker" />{{ data.city }}
            </span>
            <span v-else class="text-muted">—</span>
          </template>
        </Column>

        <Column field="country" :header="t('common.country')">
          <template #body="{ data }">
            <span class="country-text">{{ data.country || '—' }}</span>
          </template>
        </Column>

        <Column :header="t('common.contacts')">
          <template #body="{ data }">
            <span v-if="data.contacts_count" class="contacts-count">
              <i class="pi pi-users" />{{ data.contacts_count }}
            </span>
            <span v-else class="text-muted">0</span>
          </template>
        </Column>

        <Column style="width: 60px">
          <template #body="{ data }">
            <Link :href="`/organizations/${data.id}/edit`">
              <Button icon="pi pi-chevron-right" text rounded size="small" />
            </Link>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Pagination -->
    <div v-if="organizations.links && organizations.links.length > 3" class="pagination-wrapper">
      <Paginator
        v-if="organizations.total"
        :first="(organizations.current_page - 1) * organizations.per_page"
        :rows="organizations.per_page"
        :totalRecords="organizations.total"
        @page="onPageChange"
        template="PrevPageLink PageLinks NextPageLink"
      />
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
    organizations: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      viewMode: 'grid',
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
      trashedOptions: [
        { label: this.t('common.active_only'), value: null },
        { label: this.t('common.with_trashed'), value: 'with' },
        { label: this.t('common.only_trashed'), value: 'only' },
      ],
    }
  },
  computed: {
    totalContacts() {
      return (this.organizations.data || []).reduce((sum, org) => sum + (org.contacts_count || 0), 0)
    },
  },
  methods: {
    handleSearch: throttle(function () {
      this.$inertia.get('/organizations', pickBy(this.form), { preserveState: true })
    }, 300),
    handleFilter() {
      this.$inertia.get('/organizations', pickBy(this.form), { preserveState: true })
    },
    reset() {
      this.form = mapValues(this.form, () => null)
      this.$inertia.get('/organizations', {}, { preserveState: true })
    },
    onPageChange(event) {
      const page = event.page + 1
      const currentUrl = new URL(window.location.href)
      currentUrl.searchParams.set('page', page)
      router.visit(currentUrl.pathname + currentUrl.search, { preserveState: true, preserveScroll: true })
    },
    goToOrg(org) {
      router.visit(`/organizations/${org.id}/edit`)
    },
    initials(name) {
      if (!name) return '?'
      return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
    },
    getAvatarGradient(name) {
      const gradients = [
        'linear-gradient(135deg, #6366f1, #8b5cf6)',
        'linear-gradient(135deg, #f59e0b, #d97706)',
        'linear-gradient(135deg, #10b981, #059669)',
        'linear-gradient(135deg, #ef4444, #dc2626)',
        'linear-gradient(135deg, #3b82f6, #2563eb)',
        'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'linear-gradient(135deg, #ec4899, #db2777)',
        'linear-gradient(135deg, #06b6d4, #0891b2)',
      ]
      const idx = (name || '').charCodeAt(0) % gradients.length
      return gradients[idx]
    },
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }

/* ===== Stats Row ===== */
.stats-row {
  display: flex; gap: 0.65rem; margin-bottom: 1rem;
}
.stat-chip {
  display: flex; align-items: center; gap: 0.45rem;
  padding: 0.45rem 0.85rem; border-radius: 10px;
  font-size: 0.78rem; font-weight: 500;
}
.stat-chip i { font-size: 0.72rem; }
.stat-chip-value { font-weight: 700; font-size: 0.88rem; }
.stat-chip-label { font-weight: 400; }

.stat-chip--total { background: #eef2ff; color: #6366f1; }
.stat-chip--contacts { background: #ecfdf5; color: #10b981; }

/* ===== Filter Bar ===== */
.filter-bar {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.85rem 1rem; background: white; border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 1rem;
}
.search-wrapper { position: relative; flex: 1; min-width: 220px; }
.search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.85rem; }
.search-input { width: 100%; padding-left: 2.2rem !important; font-size: 0.82rem !important; border-radius: 8px !important; }
.filter-select { min-width: 150px; }

/* ===== View Toggle ===== */
.view-toggle {
  display: flex; gap: 0.25rem; margin-bottom: 1rem;
  background: white; border-radius: 10px; padding: 0.2rem;
  border: 1px solid #f1f5f9; width: fit-content;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.view-btn {
  width: 34px; height: 34px; border-radius: 8px; border: none;
  background: transparent; color: #94a3b8; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; transition: all 0.2s;
}
.view-btn.active { background: #6366f1; color: white; box-shadow: 0 2px 6px rgba(99,102,241,0.3); }
.view-btn:hover:not(.active) { background: #f1f5f9; color: #475569; }

/* ===== Grid View ===== */
.org-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 0.85rem;
}

.org-card {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}
.org-card:hover {
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
  border-color: #e2e8f0;
  transform: translateY(-2px);
}
.org-card--deleted { opacity: 0.55; }

.org-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1rem 0;
}
.org-card-avatar {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.82rem; font-weight: 700; color: white;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.org-card-badge {
  font-size: 0.62rem; font-weight: 600; color: #ef4444;
  background: #fef2f2; padding: 0.2rem 0.5rem; border-radius: 6px;
  display: flex; align-items: center; gap: 0.2rem;
}
.org-card-badge i { font-size: 0.55rem; }

.org-card-body { padding: 0.75rem 1rem; }
.org-card-name { font-size: 0.92rem; font-weight: 600; color: #1e293b; margin: 0 0 0.3rem; }
.org-card:hover .org-card-name { color: #6366f1; }
.org-card-email { font-size: 0.72rem; color: #94a3b8; margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.3rem; }
.org-card-email i { font-size: 0.62rem; }

.org-card-meta { display: flex; flex-direction: column; gap: 0.25rem; }
.meta-item {
  font-size: 0.72rem; color: #64748b;
  display: flex; align-items: center; gap: 0.3rem;
}
.meta-item i { font-size: 0.6rem; color: #94a3b8; }

.org-card-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.65rem 1rem; border-top: 1px solid #f8fafc;
  background: #fafbfc;
}
.org-contacts-badge {
  font-size: 0.7rem; font-weight: 600; color: #6366f1;
  display: flex; align-items: center; gap: 0.3rem;
}
.org-contacts-badge i { font-size: 0.6rem; }
.org-contacts-badge--empty { color: #cbd5e1; font-weight: 400; }

.org-card-arrow {
  width: 28px; height: 28px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: #94a3b8; transition: all 0.2s; text-decoration: none;
}
.org-card:hover .org-card-arrow { background: #eef2ff; color: #6366f1; }

/* ===== Table View ===== */
.data-card {
  background: white; border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; overflow: hidden;
}
.org-name-link {
  display: flex; align-items: center; gap: 0.65rem;
  text-decoration: none; color: inherit;
}
.org-avatar {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.65rem; font-weight: 700; color: white;
  text-transform: uppercase; letter-spacing: 0.5px; flex-shrink: 0;
}
.org-name-info { display: flex; flex-direction: column; }
.org-name-text { font-size: 0.85rem; font-weight: 600; color: #1e293b; transition: color 0.2s; }
.org-name-link:hover .org-name-text { color: #6366f1; }
.org-email-sub { font-size: 0.72rem; color: #94a3b8; }
.deleted-icon { font-size: 0.65rem; color: #cbd5e1; margin-left: auto; }

.info-cell {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.82rem; color: #475569;
}
.info-cell i { font-size: 0.68rem; color: #94a3b8; }
.country-text { font-size: 0.82rem; color: #475569; }

.contacts-count {
  display: inline-flex; align-items: center; gap: 0.35rem;
  font-size: 0.72rem; font-weight: 600; color: #6366f1;
  background: #eef2ff; padding: 0.2rem 0.5rem; border-radius: 6px;
}
.contacts-count i { font-size: 0.6rem; }

/* ===== Pagination ===== */
.pagination-wrapper {
  display: flex; align-items: center; justify-content: center;
  padding: 0.85rem; margin-top: 1rem;
  background: white; border-radius: 12px; border: 1px solid #f1f5f9;
}

/* ===== Empty State ===== */
.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 0.65rem;
  padding: 3.5rem; color: #94a3b8;
}
.empty-illustration {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
}
.empty-illustration i { font-size: 1.8rem; color: #6366f1; }
.empty-state h3 { font-size: 1rem; font-weight: 600; color: #1e293b; margin: 0; }
.empty-state p { font-size: 0.82rem; margin: 0; }
.text-muted { color: #cbd5e1; font-size: 0.82rem; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .org-grid { grid-template-columns: 1fr; }
  .filter-bar { flex-wrap: wrap; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
  .stats-row { flex-wrap: wrap; }
}
</style>
