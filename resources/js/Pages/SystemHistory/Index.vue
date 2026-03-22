<template>
  <div>
    <Head title="Lịch sử hệ thống" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <i class="pi pi-history" />
        </div>
        <div>
          <h1 class="page-title">Lịch sử hệ thống</h1>
          <p class="page-subtitle">Theo dõi mọi hoạt động trong CRM</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip today">
            <i class="pi pi-calendar" /> Hôm nay: {{ stats.today }}
          </span>
          <span class="stat-chip week">
            <i class="pi pi-chart-line" /> Tuần này: {{ stats.week }}
          </span>
          <span class="stat-chip total">
            <i class="pi pi-database" /> Tổng: {{ stats.total }}
          </span>
        </div>
        <Link href="/system-trash">
          <Button label="Thùng rác" icon="pi pi-trash" severity="secondary" outlined />
        </Link>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search search-icon" />
        <input v-model="form.search" type="text" placeholder="Tìm theo nội dung..." class="search-input" />
        <button v-if="form.search" class="search-clear" @click="form.search = null">
          <i class="pi pi-times" />
        </button>
      </div>
      <div class="filter-group">
        <Select v-model="form.module" :options="modules" optionLabel="label" optionValue="value" placeholder="Module" showClear class="filter-select" />
        <Select v-model="form.action" :options="actions" optionLabel="label" optionValue="value" placeholder="Hành động" showClear class="filter-select" />
        <Select v-model="form.user_id" :options="userOptions" optionLabel="name" optionValue="id" placeholder="Người dùng" showClear class="filter-select" />
        <button v-if="hasFilters" class="reset-btn" @click="resetFilters">
          <i class="pi pi-filter-slash" /> Xóa lọc
        </button>
      </div>
    </div>

    <!-- Timeline -->
    <div v-if="logs.data.length" class="timeline">
      <div v-for="(log, index) in logs.data" :key="log.id" class="timeline-item" :class="`action-${log.action}`">
        <div class="timeline-dot" :class="`dot-${log.action}`">
          <i :class="getActionIcon(log.action)" />
        </div>
        <div class="timeline-card">
          <div class="card-top">
            <div class="card-main">
              <div class="action-badge" :class="`badge-${log.action}`">{{ log.action_label }}</div>
              <span class="module-tag">
                <i :class="getModuleIcon(log.module)" /> {{ log.module_label }}
              </span>
            </div>
            <span class="card-time" :title="log.created_at">{{ log.created_at_human }}</span>
          </div>

          <div class="card-body">
            <span v-if="log.subject_label" class="subject-label">{{ log.subject_label }}</span>
            <span v-else class="subject-label empty">{{ log.module_label }} #{{ log.subject_id || '—' }}</span>
          </div>

          <div class="card-footer">
            <div class="user-info" v-if="log.user">
              <div class="user-avatar-sm">{{ getInitials(log.user.name) }}</div>
              <span class="user-name">{{ log.user.name }}</span>
            </div>
            <span v-else class="user-info system-user">
              <i class="pi pi-cog" /> Hệ thống
            </span>
            <span v-if="log.ip_address" class="ip-tag">
              <i class="pi pi-globe" /> {{ log.ip_address }}
            </span>
          </div>

          <!-- Changes (collapsible) -->
          <div v-if="log.changes && Object.keys(log.changes).length" class="changes-section">
            <button class="changes-toggle" @click="toggleChanges(log.id)">
              <i :class="expandedChanges.includes(log.id) ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" />
              {{ expandedChanges.includes(log.id) ? 'Ẩn' : 'Xem' }} thay đổi ({{ Object.keys(log.changes).length }})
            </button>
            <div v-if="expandedChanges.includes(log.id)" class="changes-list">
              <div v-for="(val, key) in log.changes" :key="key" class="change-row">
                <span class="change-field">{{ key }}</span>
                <span v-if="val && typeof val === 'object' && val.old !== undefined" class="change-values">
                  <span class="change-old">{{ val.old ?? '—' }}</span>
                  <i class="pi pi-arrow-right change-arrow" />
                  <span class="change-new">{{ val.new ?? '—' }}</span>
                </span>
                <span v-else class="change-values">
                  <span class="change-new">{{ val }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-history" /></div>
      <h3>Chưa có hoạt động nào</h3>
      <p>Lịch sử hệ thống sẽ được ghi lại tự động khi có thao tác.</p>
    </div>

    <!-- Pagination -->
    <div v-if="logs.last_page > 1" class="pagination">
      <Link
        v-for="link in logs.links"
        :key="link.label"
        :href="link.url"
        class="page-link"
        :class="{ active: link.active, disabled: !link.url }"
        v-html="link.label"
        preserve-state
      />
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: { Head, Link, Button, Select },
  layout: Layout,
  props: {
    logs: Object,
    filters: Object,
    stats: Object,
    users: Array,
    modules: Array,
    actions: Array,
  },
  data() {
    return {
      form: {
        search: this.filters.search || null,
        module: this.filters.module || null,
        action: this.filters.action || null,
        user_id: this.filters.user_id || null,
      },
      expandedChanges: [],
    }
  },
  computed: {
    hasFilters() {
      return this.form.search || this.form.module || this.form.action || this.form.user_id
    },
    userOptions() {
      return this.users || []
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/system-history', pickBy(this.form), { preserveState: true, preserveScroll: true })
      }, 400),
    },
  },
  methods: {
    resetFilters() {
      this.form = { search: null, module: null, action: null, user_id: null }
    },
    toggleChanges(id) {
      const idx = this.expandedChanges.indexOf(id)
      if (idx > -1) this.expandedChanges.splice(idx, 1)
      else this.expandedChanges.push(id)
    },
    getInitials(name) {
      if (!name) return '?'
      return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
    },
    getActionIcon(action) {
      const map = {
        created: 'pi pi-plus', updated: 'pi pi-pencil', deleted: 'pi pi-trash',
        restored: 'pi pi-undo', login: 'pi pi-sign-in', logout: 'pi pi-sign-out',
        exported: 'pi pi-download', imported: 'pi pi-upload',
        assigned: 'pi pi-user-plus', status_changed: 'pi pi-sync',
      }
      return map[action] || 'pi pi-circle'
    },
    getModuleIcon(module) {
      const map = {
        leads: 'pi pi-bullseye', contacts: 'pi pi-id-card', deals: 'pi pi-briefcase',
        customers: 'pi pi-heart', users: 'pi pi-users', organizations: 'pi pi-building',
        proposals: 'pi pi-file-edit', projects: 'pi pi-folder', wiki: 'pi pi-book',
        settings: 'pi pi-cog', email: 'pi pi-envelope', social: 'pi pi-share-alt',
      }
      return map[module] || 'pi pi-circle'
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;
}
.header-content { display: flex; align-items: center; gap: 0.85rem; }
.header-icon-wrapper {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
}
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.header-actions { display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap; }

.stat-chips { display: flex; gap: 0.4rem; }
.stat-chip {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.3rem 0.65rem; border-radius: 20px;
  font-size: 0.65rem; font-weight: 600;
}
.stat-chip i { font-size: 0.58rem; }
.stat-chip.today { background: #ecfdf5; color: #059669; }
.stat-chip.week { background: #eef2ff; color: #6366f1; }
.stat-chip.total { background: #f1f5f9; color: #64748b; }

/* ===== Filter Bar ===== */
.filter-bar {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.75rem 1rem; background: white;
  border: 1.5px solid #e2e8f0; border-radius: 14px;
  margin-bottom: 1.25rem; flex-wrap: wrap;
}
.search-box {
  display: flex; align-items: center; flex: 1; min-width: 200px;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  overflow: hidden; transition: border-color 0.2s;
}
.search-box:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
.search-icon { padding: 0 0.6rem; color: #94a3b8; font-size: 0.75rem; }
.search-input {
  flex: 1; border: none; outline: none; padding: 0.5rem 0.5rem 0.5rem 0;
  font-size: 0.8rem; color: #1e293b; font-family: inherit;
}
.search-input::placeholder { color: #cbd5e1; }
.search-clear { background: none; border: none; padding: 0 0.55rem; color: #94a3b8; cursor: pointer; }
.search-clear:hover { color: #ef4444; }
.filter-group { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; }
.filter-select { min-width: 120px; font-size: 0.8rem; }
.reset-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.4rem 0.7rem; border-radius: 8px; border: 1.5px solid #e2e8f0;
  background: white; font-size: 0.7rem; font-weight: 600; color: #64748b;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.reset-btn i { font-size: 0.62rem; }
.reset-btn:hover { border-color: #ef4444; color: #ef4444; }

/* ===== Timeline ===== */
.timeline { position: relative; padding-left: 2.5rem; }
.timeline::before {
  content: ''; position: absolute; left: 14px; top: 0; bottom: 0;
  width: 2px; background: linear-gradient(180deg, #e2e8f0, #f1f5f9);
}

.timeline-item {
  position: relative; margin-bottom: 0.75rem;
}
.timeline-dot {
  position: absolute; left: -2.5rem; top: 0.85rem;
  width: 28px; height: 28px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  z-index: 2; font-size: 0.65rem;
}
.dot-created { background: #ecfdf5; color: #059669; }
.dot-updated { background: #eef2ff; color: #6366f1; }
.dot-deleted { background: #fef2f2; color: #ef4444; }
.dot-restored { background: #fffbeb; color: #d97706; }
.dot-login { background: #ecfdf5; color: #059669; }
.dot-logout { background: #f1f5f9; color: #94a3b8; }
.dot-exported { background: #f0fdf4; color: #16a34a; }
.dot-imported { background: #eff6ff; color: #3b82f6; }

.timeline-card {
  background: white; border: 1.5px solid #f1f5f9; border-radius: 12px;
  padding: 0.85rem 1rem; transition: all 0.2s;
}
.timeline-card:hover { border-color: #e2e8f0; box-shadow: 0 4px 14px rgba(0,0,0,0.04); }

.card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
.card-main { display: flex; align-items: center; gap: 0.4rem; }
.action-badge {
  font-size: 0.58rem; font-weight: 700; padding: 0.12rem 0.45rem; border-radius: 5px;
  text-transform: uppercase; letter-spacing: 0.03em;
}
.badge-created { background: #ecfdf5; color: #059669; }
.badge-updated { background: #eef2ff; color: #6366f1; }
.badge-deleted { background: #fef2f2; color: #ef4444; }
.badge-restored { background: #fffbeb; color: #d97706; }
.badge-login { background: #ecfdf5; color: #059669; }
.badge-logout { background: #f1f5f9; color: #94a3b8; }
.badge-exported { background: #f0fdf4; color: #16a34a; }
.badge-imported { background: #eff6ff; color: #3b82f6; }

.module-tag {
  font-size: 0.62rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem;
}
.module-tag i { font-size: 0.55rem; }
.card-time { font-size: 0.62rem; color: #cbd5e1; }

.card-body { margin-bottom: 0.4rem; }
.subject-label { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.subject-label.empty { color: #94a3b8; font-weight: 400; }

.card-footer { display: flex; justify-content: space-between; align-items: center; }
.user-info { display: flex; align-items: center; gap: 0.35rem; }
.user-avatar-sm {
  width: 22px; height: 22px; border-radius: 6px;
  background: linear-gradient(135deg, #e0e7ff, #eef2ff); color: #6366f1;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.5rem; font-weight: 700;
}
.user-name { font-size: 0.72rem; color: #475569; font-weight: 500; }
.system-user { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.system-user i { font-size: 0.62rem; }
.ip-tag {
  font-size: 0.58rem; color: #cbd5e1; font-family: monospace;
  display: flex; align-items: center; gap: 0.2rem;
}
.ip-tag i { font-size: 0.5rem; }

/* Changes */
.changes-section { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #f1f5f9; }
.changes-toggle {
  display: flex; align-items: center; gap: 0.3rem;
  background: none; border: none; font-size: 0.65rem; font-weight: 600;
  color: #6366f1; cursor: pointer; font-family: inherit;
}
.changes-toggle i { font-size: 0.55rem; }
.changes-list { margin-top: 0.35rem; }
.change-row {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.2rem 0; font-size: 0.68rem;
}
.change-field {
  font-weight: 600; color: #475569; min-width: 100px; font-family: monospace;
  font-size: 0.62rem;
}
.change-values { display: flex; align-items: center; gap: 0.3rem; }
.change-old { color: #ef4444; text-decoration: line-through; }
.change-new { color: #059669; font-weight: 500; }
.change-arrow { font-size: 0.45rem; color: #cbd5e1; }

/* ===== Empty State ===== */
.empty-state {
  text-align: center; padding: 3rem 2rem;
  background: white; border-radius: 16px; border: 2px dashed #e2e8f0;
}
.empty-icon {
  width: 64px; height: 64px; border-radius: 16px;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem; font-size: 1.5rem; color: #6366f1;
}
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem; }
.empty-state p { font-size: 0.82rem; color: #94a3b8; margin: 0; }

/* ===== Pagination ===== */
.pagination {
  display: flex; gap: 0.25rem; justify-content: center; margin-top: 1.5rem;
}
.page-link {
  padding: 0.4rem 0.7rem; border-radius: 8px; border: 1.5px solid #e2e8f0;
  font-size: 0.72rem; color: #475569; text-decoration: none; transition: all 0.2s;
}
.page-link:hover:not(.disabled):not(.active) { border-color: #6366f1; color: #6366f1; }
.page-link.active { background: #6366f1; border-color: #6366f1; color: white; }
.page-link.disabled { opacity: 0.4; pointer-events: none; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .filter-bar { flex-direction: column; }
  .search-box { min-width: 100%; }
  .filter-group { width: 100%; }
  .stat-chips { flex-wrap: wrap; }
  .timeline { padding-left: 2rem; }
  .timeline::before { left: 10px; }
  .timeline-dot { left: -2rem; width: 22px; height: 22px; font-size: 0.55rem; }
}
</style>
