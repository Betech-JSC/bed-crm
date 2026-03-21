<template>
  <div>
    <Head :title="t('common.leads')" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-bolt" /></div>
        <div>
          <h1 class="page-title">{{ t('common.leads') }}</h1>
          <p class="page-subtitle">{{ isVi ? 'Quản lý khách hàng tiềm năng' : 'Manage potential customers' }}</p>
        </div>
      </div>
      <Link href="/leads/create"><button class="btn-primary"><i class="pi pi-plus" /> {{ t('common.create_lead') }}</button></Link>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div v-for="s in statCards" :key="s.label" class="stat-card" :class="{ active: form.status === s.filterKey }" @click="filterByStatus(s.filterKey)">
        <div class="stat-icon" :style="{ background: s.bg, color: s.color }"><i :class="s.icon" /></div>
        <div><span class="stat-value">{{ s.value }}</span><span class="stat-label">{{ s.label }}</span></div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" :placeholder="t('common.search_leads')" class="search-input" @input="handleSearch" />
      </div>
      <div class="filter-selects">
        <select v-model="form.status" class="filter-select" @change="handleFilter">
          <option :value="null">{{ t('common.all_statuses') }}</option>
          <option v-for="(label, val) in statuses" :key="val" :value="val">{{ label }}</option>
        </select>
        <select v-model="form.source" class="filter-select" @change="handleFilter">
          <option :value="null">{{ t('common.all_sources') }}</option>
          <option v-for="(label, val) in sources" :key="val" :value="val">{{ label }}</option>
        </select>
        <select v-model="form.assigned_to" class="filter-select" @change="handleFilter">
          <option :value="null">{{ t('common.all_users') }}</option>
          <option v-for="u in salesUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
      </div>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> {{ t('common.reset_filters') }}</button>
    </div>

    <!-- Table -->
    <div class="data-card">
      <div v-if="!leads.data || leads.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-inbox" /></div>
        <h3>{{ t('common.no_leads') }}</h3>
        <p>{{ isVi ? 'Tạo lead đầu tiên!' : 'Create your first lead' }}</p>
        <Link href="/leads/create"><button class="btn-primary sm"><i class="pi pi-plus" /> {{ t('common.create_lead') }}</button></Link>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>{{ t('common.name') }}</th>
            <th>{{ t('common.contact_info') }}</th>
            <th>{{ t('common.source') }}</th>
            <th>{{ t('common.status') }}</th>
            <th>{{ t('common.lead_score') }}</th>
            <th>{{ t('common.assigned_to') }}</th>
            <th>{{ t('common.tags') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lead in leads.data" :key="lead.id" class="table-row">
            <!-- Name -->
            <td>
              <Link :href="`/leads/${lead.id}/edit`" class="lead-link">
                <div class="lead-avatar" :style="{ background: avatarGradient(lead.name) }">{{ initials(lead.name) }}</div>
                <div class="lead-info">
                  <span class="lead-name">{{ lead.name }}</span>
                  <span v-if="lead.company" class="lead-company"><i class="pi pi-building" /> {{ lead.company }}</span>
                </div>
              </Link>
            </td>
            <!-- Contact -->
            <td>
              <div class="contact-cell">
                <span v-if="lead.email" class="contact-item"><i class="pi pi-envelope" /> {{ lead.email }}</span>
                <span v-if="lead.phone" class="contact-item"><i class="pi pi-phone" /> {{ lead.phone }}</span>
                <span v-if="!lead.email && !lead.phone" class="muted">—</span>
              </div>
            </td>
            <!-- Source -->
            <td>
              <span v-if="lead.source" class="source-badge" :class="`src-${lead.source}`">
                <i :class="sourceIcon(lead.source)" /> {{ sources[lead.source] || lead.source }}
              </span>
              <span v-else class="muted">—</span>
            </td>
            <!-- Status -->
            <td>
              <span class="status-badge" :class="`st-${lead.status}`">
                <span class="status-dot" /> {{ statuses[lead.status] || lead.status }}
              </span>
            </td>
            <!-- Score -->
            <td>
              <div v-if="lead.score != null" class="score-cell">
                <div class="score-ring" :class="scoreClass(lead.score)">{{ lead.score }}</div>
                <div class="score-bar"><div class="score-fill" :style="{ width: lead.score + '%' }" :class="scoreClass(lead.score)" /></div>
              </div>
              <span v-else class="muted">—</span>
            </td>
            <!-- Assigned -->
            <td>
              <div v-if="lead.assigned_user" class="assigned-cell">
                <div class="assigned-avatar">{{ initials(lead.assigned_user.name) }}</div>
                <span>{{ lead.assigned_user.name }}</span>
              </div>
              <span v-else class="muted">—</span>
            </td>
            <!-- Tags -->
            <td>
              <div v-if="lead.tags && lead.tags.length" class="tags-cell">
                <span v-for="tag in lead.tags.slice(0, 3)" :key="tag" class="tag-chip">{{ tag }}</span>
                <span v-if="lead.tags.length > 3" class="tag-more">+{{ lead.tags.length - 3 }}</span>
              </div>
              <span v-else class="muted">—</span>
            </td>
            <td><Link :href="`/leads/${lead.id}/edit`" class="row-arrow"><i class="pi pi-chevron-right" /></Link></td>
          </tr>
        </tbody>
      </table>

      <div v-if="leads.total > 0" class="pagination">
        <span class="page-info">{{ t('common.showing') }} {{ leads.from }}–{{ leads.to }} {{ t('common.of') }} {{ leads.total }}</span>
        <div class="page-btns">
          <button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === leads.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { filters: Object, leads: Object, statuses: Object, sources: Object, salesUsers: Array },
  setup() { const { t, locale } = useTranslation(); return { t, locale } },
  data() {
    return {
      form: { search: this.filters.search, status: this.filters.status, source: this.filters.source, assigned_to: this.filters.assigned_to, trashed: this.filters.trashed },
    }
  },
  computed: {
    isVi() { return this.locale === 'vi' },
    statusCounts() {
      const c = {}; if (this.leads.data) this.leads.data.forEach(l => { c[l.status] = (c[l.status] || 0) + 1 }); return c
    },
    statCards() {
      return [
        { label: this.isVi ? 'Tổng' : 'Total', value: this.leads.total || 0, icon: 'pi pi-users', color: '#6366f1', bg: '#eef2ff', filterKey: null },
        { label: this.isVi ? 'Mới' : 'New', value: this.statusCounts.new || 0, icon: 'pi pi-sparkles', color: '#3b82f6', bg: '#eff6ff', filterKey: 'new' },
        { label: this.isVi ? 'Đã liên hệ' : 'Contacted', value: this.statusCounts.contacted || 0, icon: 'pi pi-phone', color: '#f59e0b', bg: '#fffbeb', filterKey: 'contacted' },
        { label: this.isVi ? 'Đủ điều kiện' : 'Qualified', value: this.statusCounts.qualified || 0, icon: 'pi pi-check-circle', color: '#10b981', bg: '#ecfdf5', filterKey: 'qualified' },
      ]
    },
    pageNumbers() {
      const total = this.leads.last_page, cur = this.leads.current_page, pages = []
      if (total <= 7) { for (let i = 1; i <= total; i++) pages.push(i) }
      else {
        pages.push(1)
        if (cur > 3) pages.push('...')
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
        if (cur < total - 2) pages.push('...')
        pages.push(total)
      }
      return pages
    },
  },
  methods: {
    handleSearch: throttle(function () { this.$inertia.get('/leads', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { this.$inertia.get('/leads', pickBy(this.form), { preserveState: true }) },
    filterByStatus(status) { this.form.status = this.form.status === status ? null : status; this.handleFilter() },
    reset() { this.form = mapValues(this.form, () => null); this.$inertia.get('/leads', {}, { preserveState: true }) },
    initials(name) { if (!name) return '?'; return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) },
    scoreClass(s) { return s >= 80 ? 'sc-high' : s >= 60 ? 'sc-mid' : s >= 40 ? 'sc-low' : 'sc-cold' },
    sourceIcon(s) { return { website: 'pi pi-globe', referral: 'pi pi-share-alt', social: 'pi pi-twitter', email: 'pi pi-envelope', phone: 'pi pi-phone' }[s] || 'pi pi-circle' },
    avatarGradient(name) {
      const colors = [['#6366f1','#8b5cf6'],['#3b82f6','#06b6d4'],['#10b981','#14b8a6'],['#f59e0b','#f97316'],['#ec4899','#db2777']]
      const idx = (name || '').charCodeAt(0) % colors.length; return `linear-gradient(135deg, ${colors[idx][0]}, ${colors[idx][1]})`
    },
    goToPage(pg) {
      const url = new URL(window.location.href); url.searchParams.set('page', pg)
      router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>

<style scoped>
/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #6366f1, #4f46e5); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-primary.sm { font-size: 0.78rem; padding: 0.45rem 0.85rem; }
.btn-text { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.7rem; border-radius: 8px; border: none; background: transparent; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; }
.btn-text:hover { background: #f1f5f9; }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem 1rem; background: white; border-radius: 14px; border: 1.5px solid #e2e8f0; cursor: pointer; transition: all 0.2s; }
.stat-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.stat-card.active { border-color: #6366f1; background: #fafafe; }
.stat-icon { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.stat-value { display: block; font-size: 1.25rem; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-label { font-size: 0.65rem; color: #94a3b8; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 1rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #f8fafc; flex: 1; min-width: 200px; }
.search-box:focus-within { border-color: #6366f1; background: white; }
.search-box i { color: #94a3b8; font-size: 0.78rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.filter-selects { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.filter-select { padding: 0.4rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.78rem; color: #475569; background: white; cursor: pointer; outline: none; min-width: 130px; }
.filter-select:focus { border-color: #6366f1; }

/* Data Table */
.data-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.65rem 1rem; font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; text-align: left; background: #fafbfc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 0.65rem 1rem; font-size: 0.82rem; color: #334155; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
.table-row { transition: background 0.15s; }
.table-row:hover { background: #fafbfe; }

/* Lead cell */
.lead-link { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: inherit; }
.lead-avatar { width: 34px; height: 34px; border-radius: 10px; color: white; font-size: 0.6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: transform 0.2s; }
.lead-link:hover .lead-avatar { transform: scale(1.08); }
.lead-info { display: flex; flex-direction: column; min-width: 0; }
.lead-name { font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.15s; }
.lead-link:hover .lead-name { color: #6366f1; }
.lead-company { font-size: 0.68rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.lead-company i { font-size: 0.55rem; }

/* Contact */
.contact-cell { display: flex; flex-direction: column; gap: 0.15rem; }
.contact-item { font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 0.25rem; }
.contact-item i { font-size: 0.6rem; color: #94a3b8; }

/* Source */
.source-badge { font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.2rem; }
.source-badge i { font-size: 0.55rem; }
.src-website { background: #dbeafe; color: #2563eb; } .src-referral { background: #d1fae5; color: #059669; }
.src-social { background: #fce7f3; color: #db2777; } .src-email { background: #e0e7ff; color: #4f46e5; }
.src-phone { background: #fef3c7; color: #d97706; } .src-other { background: #f1f5f9; color: #64748b; }

/* Status */
.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.55rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.st-new { background: #eff6ff; color: #3b82f6; } .st-new .status-dot { background: #3b82f6; }
.st-contacted { background: #fef3c7; color: #d97706; } .st-contacted .status-dot { background: #d97706; }
.st-qualified { background: #d1fae5; color: #059669; } .st-qualified .status-dot { background: #059669; }
.st-won { background: #d1fae5; color: #059669; } .st-won .status-dot { background: #059669; }
.st-lost { background: #fee2e2; color: #dc2626; } .st-lost .status-dot { background: #dc2626; }

/* Score */
.score-cell { display: flex; align-items: center; gap: 0.4rem; }
.score-ring { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700; border: 2.5px solid; }
.sc-high { border-color: #10b981; color: #059669; background: #ecfdf5; }
.sc-mid { border-color: #f59e0b; color: #d97706; background: #fffbeb; }
.sc-low { border-color: #ef4444; color: #dc2626; background: #fef2f2; }
.sc-cold { border-color: #94a3b8; color: #64748b; background: #f8fafc; }
.score-bar { flex: 1; max-width: 40px; height: 3px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.score-fill { height: 100%; border-radius: 2px; }
.score-fill.sc-high { background: #10b981; } .score-fill.sc-mid { background: #f59e0b; }
.score-fill.sc-low { background: #ef4444; } .score-fill.sc-cold { background: #94a3b8; }

/* Assigned */
.assigned-cell { display: flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; color: #475569; }
.assigned-avatar { width: 24px; height: 24px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }

/* Tags */
.tags-cell { display: flex; flex-wrap: wrap; gap: 0.2rem; }
.tag-chip { font-size: 0.6rem; font-weight: 500; padding: 0.1rem 0.4rem; border-radius: 4px; background: #f1f5f9; color: #475569; }
.tag-more { font-size: 0.6rem; color: #94a3b8; font-weight: 600; }

.row-arrow { color: #cbd5e1; text-decoration: none; font-size: 0.72rem; transition: color 0.15s; }
.row-arrow:hover { color: #6366f1; }
.muted { color: #cbd5e1; font-size: 0.78rem; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 0.72rem; color: #94a3b8; }
.page-btns { display: flex; gap: 0.2rem; }
.page-btn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.page-btn:hover:not(.active):not(.dots) { border-color: #6366f1; color: #6366f1; }
.page-btn.active { background: #6366f1; color: white; border-color: #6366f1; }
.page-btn.dots { border: none; cursor: default; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .filter-bar { flex-direction: column; }
  .filter-selects { flex-direction: column; width: 100%; }
  .data-table { display: block; overflow-x: auto; }
}
</style>
