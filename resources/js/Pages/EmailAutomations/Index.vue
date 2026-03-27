<template>
  <div>
    <Head title="Email Automations" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-bolt" /></div>
        <div>
          <h1 class="page-title">Email Automations</h1>
          <p class="page-subtitle">Tự động hóa quy trình email marketing</p>
        </div>
      </div>
      <Link href="/email-automations/create"><button class="btn-primary"><i class="pi pi-plus" /> Tạo Automation</button></Link>
    </div>

    <!-- KPI -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #eef2ff; color: #6366f1;"><i class="pi pi-bolt" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.total || 0 }}</span><span class="kpi-label">Tổng automation</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #dcfce7; color: #16a34a;"><i class="pi pi-play" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.active || 0 }}</span><span class="kpi-label">Đang hoạt động</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #f5f3ff; color: #8b5cf6;"><i class="pi pi-users" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.total_processed || 0 }}</span><span class="kpi-label">Đã xử lý</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #fef3c7; color: #f59e0b;"><i class="pi pi-send" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.total_emails || 0 }}</span><span class="kpi-label">Emails gửi</span></div>
      </div>
    </div>

    <!-- Status Tabs -->
    <div class="funnel-row">
      <button v-for="s in statusTabs" :key="s.key" class="funnel-btn" :class="{ active: form.status === s.key }" @click="filterByStatus(s.key)">
        <span class="funnel-dot" :style="{ background: s.color }" />
        <span class="funnel-label">{{ s.label }}</span>
        <span class="funnel-count">{{ stats.by_status?.[s.key] || 0 }}</span>
      </button>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" placeholder="Tìm automation..." class="search-input" @input="handleSearch" />
      </div>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> Reset</button>
    </div>

    <!-- Table -->
    <div class="data-card">
      <div v-if="!automations.data || automations.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-bolt" /></div>
        <h3>Chưa có automation nào</h3>
        <p>Tạo automation để tự động hóa quy trình email</p>
        <Link href="/email-automations/create"><button class="btn-primary sm"><i class="pi pi-plus" /> Tạo Automation</button></Link>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Tên</th>
            <th>Trigger</th>
            <th>Status</th>
            <th>Steps</th>
            <th>Đã xử lý</th>
            <th>Emails</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in automations.data" :key="a.id" class="table-row">
            <td>
              <Link :href="`/email-automations/${a.id}`" class="name-link">
                <div class="auto-avatar" :class="a.status === 'active' ? 'auto-active' : ''"><i class="pi pi-bolt" /></div>
                <span class="auto-name">{{ a.name }}</span>
              </Link>
            </td>
            <td><span class="trigger-badge">{{ triggerLabel(a.trigger_type) }}</span></td>
            <td>
              <span class="status-badge" :class="`st-${a.status}`">
                <span class="status-dot" /> {{ statusLabel(a.status) }}
              </span>
            </td>
            <td><span class="text-sub">{{ a.steps_count || 0 }}</span></td>
            <td><span class="text-sub font-semibold">{{ a.contacts_processed || 0 }}</span></td>
            <td><span class="text-sub font-semibold">{{ a.emails_sent || 0 }}</span></td>
            <td><Link :href="`/email-automations/${a.id}`" class="row-arrow"><i class="pi pi-chevron-right" /></Link></td>
          </tr>
        </tbody>
      </table>

      <div v-if="automations.total > 0" class="pagination">
        <span class="page-info">{{ automations.from }}–{{ automations.to }} / {{ automations.total }}</span>
        <div class="page-btns">
          <button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === automations.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button>
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

export default {
  components: { Head, Link },
  layout: Layout,
  props: { automations: Object, stats: Object, filters: Object },
  data() {
    return {
      form: { search: this.filters?.search || null, status: this.filters?.status || null },
      statusTabs: [
        { key: 'draft', label: 'Nháp', color: '#94a3b8' },
        { key: 'active', label: 'Hoạt động', color: '#16a34a' },
        { key: 'paused', label: 'Tạm dừng', color: '#f59e0b' },
        { key: 'completed', label: 'Hoàn tất', color: '#3b82f6' },
      ],
    }
  },
  computed: {
    pageNumbers() {
      const total = this.automations.last_page, cur = this.automations.current_page, pages = []
      if (total <= 7) { for (let i = 1; i <= total; i++) pages.push(i) } else {
        pages.push(1); if (cur > 3) pages.push('...')
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
        if (cur < total - 2) pages.push('...'); pages.push(total)
      }
      return pages
    },
  },
  methods: {
    handleSearch: throttle(function () { router.get('/email-automations', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { router.get('/email-automations', pickBy(this.form), { preserveState: true }) },
    filterByStatus(status) { this.form.status = this.form.status === status ? null : status; this.handleFilter() },
    reset() { this.form = mapValues(this.form, () => null); router.get('/email-automations', {}, { preserveState: true }) },
    triggerLabel(t) { return { lead_created: 'Lead Created', contact_created: 'Contact Created', deal_won: 'Deal Won', tag_added: 'Tag Added', segment_entered: 'Segment Enter', form_submitted: 'Form Submit', page_visited: 'Page Visit' }[t] || t },
    statusLabel(s) { return { draft: 'Nháp', active: 'Hoạt động', paused: 'Tạm dừng', completed: 'Hoàn tất' }[s] || s },
    goToPage(pg) {
      const url = new URL(window.location.href); url.searchParams.set('page', pg)
      router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.85rem; }
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(245,158,11,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(245,158,11,0.3); }
.btn-primary.sm { font-size: 0.78rem; padding: 0.45rem 0.85rem; }
.btn-text { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.7rem; border-radius: 8px; border: none; background: transparent; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; }
.btn-text:hover { background: #f1f5f9; }

.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
.kpi-card { display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 0.85rem; background: white; border-radius: 14px; border: 1.5px solid #e2e8f0; transition: all 0.2s; }
.kpi-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.kpi-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0; }
.kpi-body { display: flex; flex-direction: column; }
.kpi-value { font-size: 1.15rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
.kpi-label { font-size: 0.62rem; color: #94a3b8; margin-top: 0.1rem; }

.funnel-row { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.funnel-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.85rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.78rem; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s; }
.funnel-btn:hover { border-color: #cbd5e1; }
.funnel-btn.active { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
.funnel-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.funnel-label { white-space: nowrap; }
.funnel-count { background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 6px; font-size: 0.68rem; font-weight: 700; color: #64748b; min-width: 20px; text-align: center; }
.funnel-btn.active .funnel-count { background: #fef3c7; color: #92400e; }

.filter-bar { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 1rem; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #f8fafc; flex: 1; min-width: 200px; }
.search-box:focus-within { border-color: #f59e0b; background: white; }
.search-box i { color: #94a3b8; font-size: 0.78rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }

.data-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.65rem 1rem; font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; text-align: left; background: #fafbfc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 0.65rem 1rem; font-size: 0.82rem; color: #334155; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
.table-row { transition: background 0.15s; }
.table-row:hover { background: #fffdf7; }

.name-link { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: inherit; }
.name-link:hover .auto-name { color: #d97706; }
.auto-avatar { width: 34px; height: 34px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0; }
.auto-avatar.auto-active { background: #dcfce7; color: #16a34a; animation: pulse-active 2s infinite; }
@keyframes pulse-active { 0%, 100% { box-shadow: 0 0 0 0 rgba(22,163,106,0.3); } 50% { box-shadow: 0 0 0 6px rgba(22,163,106,0); } }
.auto-name { font-weight: 600; color: #1e293b; transition: color 0.15s; }

.trigger-badge { font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 6px; background: #f1f5f9; color: #475569; }

.status-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.55rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; }
.st-draft { background: #f1f5f9; color: #64748b; } .st-draft .status-dot { background: #94a3b8; }
.st-active { background: #dcfce7; color: #16a34a; } .st-active .status-dot { background: #16a34a; }
.st-paused { background: #fef3c7; color: #d97706; } .st-paused .status-dot { background: #d97706; }
.st-completed { background: #dbeafe; color: #2563eb; } .st-completed .status-dot { background: #2563eb; }

.text-sub { font-size: 0.78rem; color: #64748b; }
.font-semibold { font-weight: 600; color: #334155; }
.row-arrow { color: #cbd5e1; text-decoration: none; font-size: 0.72rem; transition: color 0.15s; }
.row-arrow:hover { color: #d97706; }

.pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 0.72rem; color: #94a3b8; }
.page-btns { display: flex; gap: 0.2rem; }
.page-btn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.page-btn:hover:not(.active):not(.dots) { border-color: #f59e0b; color: #d97706; }
.page-btn.active { background: #f59e0b; color: white; border-color: #f59e0b; }
.page-btn.dots { border: none; cursor: default; }

.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

@media (max-width: 768px) {
  .kpi-row { grid-template-columns: repeat(2, 1fr); }
  .data-table { display: block; overflow-x: auto; }
}
</style>
