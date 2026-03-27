<template>
  <div>
    <Head title="Email Campaigns" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-megaphone" /></div>
        <div>
          <h1 class="page-title">Email Campaigns</h1>
          <p class="page-subtitle">Chiến dịch email marketing · {{ stats.total }} chiến dịch</p>
        </div>
      </div>
      <Link href="/email-campaigns/create"><button class="btn-primary"><i class="pi pi-plus" /> Tạo Campaign</button></Link>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #eef2ff; color: #6366f1;"><i class="pi pi-send" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.total_sent }}</span><span class="kpi-label">Emails Sent</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #ecfdf5; color: #10b981;"><i class="pi pi-check-circle" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.delivered }}</span><span class="kpi-label">Delivered</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #f5f3ff; color: #8b5cf6;"><i class="pi pi-eye" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.avg_open_rate }}%</span><span class="kpi-label">Avg Open Rate</span></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #fef3c7; color: #f59e0b;"><i class="pi pi-external-link" /></div>
        <div class="kpi-body"><span class="kpi-value">{{ stats.avg_click_rate }}%</span><span class="kpi-label">Avg Click Rate</span></div>
      </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="funnel-row">
      <button v-for="s in statusTabs" :key="s.key" class="funnel-btn" :class="{ active: form.status === s.key }" @click="filterByStatus(s.key)">
        <span class="funnel-dot" :style="{ background: s.color }" />
        <span class="funnel-label">{{ s.label }}</span>
        <span class="funnel-count">{{ stats.by_status?.[s.key] || 0 }}</span>
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" placeholder="Tìm campaign..." class="search-input" @input="handleSearch" />
      </div>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> Reset</button>
    </div>

    <!-- Table -->
    <div class="data-card">
      <div v-if="!campaigns.data || campaigns.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-megaphone" /></div>
        <h3>Chưa có campaign nào</h3>
        <p>Tạo campaign đầu tiên để bắt đầu gửi email</p>
        <Link href="/email-campaigns/create"><button class="btn-primary sm"><i class="pi pi-plus" /> Tạo Campaign</button></Link>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Campaign</th>
            <th>Status</th>
            <th>List</th>
            <th>Recipients</th>
            <th>Open Rate</th>
            <th>Click Rate</th>
            <th>Gửi lúc</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in campaigns.data" :key="c.id" class="table-row">
            <td>
              <Link :href="`/email-campaigns/${c.id}`" class="name-link">
                <div class="camp-avatar" :style="{ background: statusGradient(c.status) }"><i class="pi pi-megaphone" /></div>
                <div class="camp-info">
                  <span class="camp-name">{{ c.name }}</span>
                  <span v-if="c.subject" class="camp-subject">{{ c.subject }}</span>
                </div>
              </Link>
            </td>
            <td><span class="campaign-badge" :class="`cb-${c.status}`"><span class="status-dot" /> {{ statusLabel(c.status) }}</span></td>
            <td><span class="text-sub">{{ c.email_list || '—' }}</span></td>
            <td><span class="text-sub">{{ c.total_recipients || 0 }}</span></td>
            <td>
              <div class="rate-cell">
                <span class="rate-value">{{ c.open_rate ? c.open_rate.toFixed(1) : '0' }}%</span>
                <div class="rate-bar"><div class="rate-fill open" :style="{ width: (c.open_rate || 0) + '%' }" /></div>
              </div>
            </td>
            <td>
              <div class="rate-cell">
                <span class="rate-value">{{ c.click_rate ? c.click_rate.toFixed(1) : '0' }}%</span>
                <div class="rate-bar"><div class="rate-fill click" :style="{ width: (c.click_rate || 0) + '%' }" /></div>
              </div>
            </td>
            <td><span class="text-sub">{{ c.sent_at || '—' }}</span></td>
            <td><Link :href="`/email-campaigns/${c.id}`" class="row-arrow"><i class="pi pi-chevron-right" /></Link></td>
          </tr>
        </tbody>
      </table>

      <div v-if="campaigns.total > 0" class="pagination">
        <span class="page-info">Hiển thị {{ campaigns.from }}–{{ campaigns.to }} / {{ campaigns.total }}</span>
        <div class="page-btns">
          <button v-for="pg in pageNumbers" :key="pg" class="page-btn" :class="{ active: pg === campaigns.current_page, dots: pg === '...' }" :disabled="pg === '...'" @click="pg !== '...' && goToPage(pg)">{{ pg }}</button>
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
  props: { campaigns: Object, stats: Object, filters: Object },
  data() {
    return {
      form: { search: this.filters?.search || null, status: this.filters?.status || null },
      statusTabs: [
        { key: 'draft', label: 'Nháp', color: '#94a3b8' },
        { key: 'scheduled', label: 'Lên lịch', color: '#3b82f6' },
        { key: 'sending', label: 'Đang gửi', color: '#f59e0b' },
        { key: 'sent', label: 'Đã gửi', color: '#10b981' },
      ],
    }
  },
  computed: {
    pageNumbers() {
      const total = this.campaigns.last_page, cur = this.campaigns.current_page, pages = []
      if (total <= 7) { for (let i = 1; i <= total; i++) pages.push(i) } else {
        pages.push(1); if (cur > 3) pages.push('...')
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
        if (cur < total - 2) pages.push('...'); pages.push(total)
      }
      return pages
    },
  },
  methods: {
    handleSearch: throttle(function () { router.get('/email-campaigns', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { router.get('/email-campaigns', pickBy(this.form), { preserveState: true }) },
    filterByStatus(status) { this.form.status = this.form.status === status ? null : status; this.handleFilter() },
    reset() { this.form = mapValues(this.form, () => null); router.get('/email-campaigns', {}, { preserveState: true }) },
    statusLabel(s) { return { draft: 'Nháp', scheduled: 'Lên lịch', sending: 'Đang gửi', sent: 'Đã gửi', paused: 'Tạm dừng', cancelled: 'Đã hủy' }[s] || s },
    statusGradient(s) {
      const map = { draft: '#94a3b8', scheduled: '#3b82f6', sending: '#f59e0b', sent: '#10b981', paused: '#f59e0b', cancelled: '#ef4444' }
      return map[s] || '#94a3b8'
    },
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
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
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
.funnel-btn.active { border-color: #3b82f6; background: #eff6ff; color: #2563eb; }
.funnel-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.funnel-label { white-space: nowrap; }
.funnel-count { background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 6px; font-size: 0.68rem; font-weight: 700; color: #64748b; min-width: 20px; text-align: center; }
.funnel-btn.active .funnel-count { background: #bfdbfe; color: #1e40af; }

.filter-bar { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 1rem; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #f8fafc; flex: 1; min-width: 200px; }
.search-box:focus-within { border-color: #3b82f6; background: white; }
.search-box i { color: #94a3b8; font-size: 0.78rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }

.data-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.65rem 1rem; font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; text-align: left; background: #fafbfc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 0.65rem 1rem; font-size: 0.82rem; color: #334155; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
.table-row { transition: background 0.15s; }
.table-row:hover { background: #fafbfe; }

.name-link { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: inherit; }
.name-link:hover .camp-name { color: #3b82f6; }
.camp-avatar { width: 34px; height: 34px; border-radius: 10px; color: white; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.camp-info { display: flex; flex-direction: column; min-width: 0; }
.camp-name { font-weight: 600; color: #1e293b; transition: color 0.15s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.camp-subject { font-size: 0.68rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }

.campaign-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.55rem; border-radius: 20px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; }
.cb-draft { background: #f1f5f9; color: #64748b; } .cb-draft .status-dot { background: #94a3b8; }
.cb-scheduled { background: #eff6ff; color: #3b82f6; } .cb-scheduled .status-dot { background: #3b82f6; }
.cb-sending { background: #fef3c7; color: #d97706; } .cb-sending .status-dot { background: #d97706; }
.cb-sent { background: #d1fae5; color: #059669; } .cb-sent .status-dot { background: #059669; }
.cb-paused { background: #fef3c7; color: #d97706; } .cb-paused .status-dot { background: #d97706; }
.cb-cancelled { background: #fee2e2; color: #dc2626; } .cb-cancelled .status-dot { background: #dc2626; }

.rate-cell { display: flex; align-items: center; gap: 0.4rem; }
.rate-value { font-size: 0.78rem; font-weight: 600; color: #334155; min-width: 35px; }
.rate-bar { flex: 1; max-width: 50px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.rate-fill { height: 100%; border-radius: 2px; transition: width 0.3s; }
.rate-fill.open { background: #8b5cf6; }
.rate-fill.click { background: #f59e0b; }

.text-sub { font-size: 0.78rem; color: #64748b; }
.row-arrow { color: #cbd5e1; text-decoration: none; font-size: 0.72rem; transition: color 0.15s; }
.row-arrow:hover { color: #3b82f6; }

.pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 0.72rem; color: #94a3b8; }
.page-btns { display: flex; gap: 0.2rem; }
.page-btn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.page-btn:hover:not(.active):not(.dots) { border-color: #3b82f6; color: #3b82f6; }
.page-btn.active { background: #3b82f6; color: white; border-color: #3b82f6; }
.page-btn.dots { border: none; cursor: default; }

.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

@media (max-width: 768px) {
  .kpi-row { grid-template-columns: repeat(2, 1fr); }
  .data-table { display: block; overflow-x: auto; }
}
</style>
