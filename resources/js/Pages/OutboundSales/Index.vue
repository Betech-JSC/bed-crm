<template>
  <div>
    <Head title="Outbound Sales" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon"><i class="pi pi-send" /></div>
        <div>
          <h1 class="page-title">Outbound Sales</h1>
          <p class="page-subtitle">Automated lead outreach campaigns · {{ stats.total }} campaigns</p>
        </div>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #eef2ff; color: #6366f1;"><i class="pi pi-bolt" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.active }}</span>
          <span class="kpi-label">Active Campaigns</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #ecfdf5; color: #10b981;"><i class="pi pi-check-circle" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.completed }}</span>
          <span class="kpi-label">Completed</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #eff6ff; color: #3b82f6;"><i class="pi pi-envelope" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.total_emails_sent }}</span>
          <span class="kpi-label">Emails Sent</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #f5f3ff; color: #8b5cf6;"><i class="pi pi-eye" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.email_open_rate }}%</span>
          <span class="kpi-label">Open Rate</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #fef3c7; color: #f59e0b;"><i class="pi pi-reply" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.reply_rate }}%</span>
          <span class="kpi-label">Reply Rate</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background: #ccfbf1; color: #14b8a6;"><i class="pi pi-chart-line" /></div>
        <div class="kpi-body">
          <span class="kpi-value">{{ stats.avg_score }}</span>
          <span class="kpi-label">Avg Score</span>
        </div>
      </div>
    </div>

    <!-- Funnel Status Tabs -->
    <div class="funnel-row">
      <button v-for="s in funnelStages" :key="s.key" class="funnel-btn" :class="{ active: form.lead_status === s.key }" @click="filterByLeadStatus(s.key)">
        <span class="funnel-dot" :style="{ background: s.color }" />
        <span class="funnel-label">{{ s.label }}</span>
        <span class="funnel-count">{{ stats.by_status[s.key] || 0 }}</span>
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="form.search" placeholder="Search leads, companies..." class="search-input" @input="handleSearch" />
      </div>
      <div class="filter-selects">
        <select v-model="form.status" class="filter-select" @change="handleFilter">
          <option :value="null">All Statuses</option>
          <option v-for="(label, val) in statuses" :key="val" :value="val">{{ label }}</option>
        </select>
      </div>
      <button class="btn-text" @click="reset"><i class="pi pi-refresh" /> Reset</button>
    </div>

    <!-- Campaign Table -->
    <div class="data-card">
      <div v-if="!campaigns.data || campaigns.data.length === 0" class="empty-state">
        <div class="empty-icon"><i class="pi pi-send" /></div>
        <h3>No campaigns yet</h3>
        <p>Outbound campaigns will appear here when new leads are created</p>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Lead</th>
            <th>Funnel Status</th>
            <th>Current Step</th>
            <th>Score</th>
            <th>Engagement</th>
            <th>Campaign Status</th>
            <th>Next Action</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in campaigns.data" :key="c.id" class="table-row" @click="showCampaign(c)">
            <!-- Lead -->
            <td>
              <div class="lead-cell">
                <div class="lead-avatar" :style="{ background: avatarGradient(c.lead?.name) }">{{ initials(c.lead?.name) }}</div>
                <div class="lead-info">
                  <span class="lead-name">{{ c.lead?.name || '—' }}</span>
                  <span v-if="c.lead?.company" class="lead-company"><i class="pi pi-building" /> {{ c.lead.company }}</span>
                </div>
              </div>
            </td>
            <!-- Funnel -->
            <td>
              <span class="funnel-badge" :class="`fl-${c.lead_status}`">
                <span class="funnel-badge-dot" /> {{ c.lead_status_label }}
              </span>
            </td>
            <!-- Step -->
            <td>
              <div class="step-cell">
                <div class="step-progress">
                  <div v-for="i in 4" :key="i" class="step-dot" :class="{ filled: i <= (c.current_step + 1), active: i === (c.current_step + 1) }" />
                </div>
                <span class="step-label">{{ c.step_name }}</span>
              </div>
            </td>
            <!-- Score -->
            <td>
              <div class="score-cell">
                <div class="score-ring" :class="scoreClass(c.lead_score)">{{ c.lead_score }}</div>
              </div>
            </td>
            <!-- Engagement -->
            <td>
              <div class="engagement-cell">
                <span class="eng-icon" :class="{ active: c.email_opened }" title="Email Opened"><i class="pi pi-eye" /></span>
                <span class="eng-icon" :class="{ active: c.link_clicked }" title="Link Clicked"><i class="pi pi-external-link" /></span>
                <span class="eng-icon" :class="{ active: c.replied }" title="Replied"><i class="pi pi-reply" /></span>
              </div>
            </td>
            <!-- Status -->
            <td>
              <span class="campaign-badge" :class="`cb-${c.status}`">{{ statuses[c.status] || c.status }}</span>
            </td>
            <!-- Next Action -->
            <td>
              <span v-if="c.next_action_at" class="next-action">
                <i class="pi pi-clock" /> {{ formatRelative(c.next_action_at) }}
              </span>
              <span v-else class="muted">—</span>
            </td>
            <td>
              <div class="action-buttons">
                <button v-if="c.status === 'active'" class="action-btn pause" @click.stop="pauseCampaign(c)" title="Pause"><i class="pi pi-pause" /></button>
                <button v-if="c.status === 'paused'" class="action-btn resume" @click.stop="resumeCampaign(c)" title="Resume"><i class="pi pi-play" /></button>
                <button v-if="c.status === 'active' && !c.replied" class="action-btn reply" @click.stop="markReplied(c)" title="Mark Replied"><i class="pi pi-reply" /></button>
                <Link :href="`/outbound-sales/${c.id}`" class="action-btn view" @click.stop title="View"><i class="pi pi-chevron-right" /></Link>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="campaigns.total > 0" class="pagination">
        <span class="page-info">Showing {{ campaigns.from }}–{{ campaigns.to }} of {{ campaigns.total }}</span>
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
  props: {
    campaigns: Object,
    stats: Object,
    filters: Object,
    statuses: Object,
  },
  data() {
    return {
      form: {
        search: this.filters?.search || null,
        status: this.filters?.status || null,
        lead_status: this.filters?.lead_status || null,
      },
      funnelStages: [
        { key: 'new', label: 'New', color: '#3b82f6' },
        { key: 'contacted', label: 'Contacted', color: '#f59e0b' },
        { key: 'engaged', label: 'Engaged', color: '#8b5cf6' },
        { key: 'qualified', label: 'Qualified', color: '#10b981' },
      ],
    }
  },
  computed: {
    pageNumbers() {
      const total = this.campaigns.last_page, cur = this.campaigns.current_page, pages = []
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
    handleSearch: throttle(function () { router.get('/outbound-sales', pickBy(this.form), { preserveState: true }) }, 300),
    handleFilter() { router.get('/outbound-sales', pickBy(this.form), { preserveState: true }) },
    filterByLeadStatus(status) {
      this.form.lead_status = this.form.lead_status === status ? null : status
      this.handleFilter()
    },
    reset() { this.form = mapValues(this.form, () => null); router.get('/outbound-sales', {}, { preserveState: true }) },
    showCampaign(c) { router.visit(`/outbound-sales/${c.id}`) },
    pauseCampaign(c) { router.post(`/outbound-sales/${c.id}/pause`, {}, { preserveScroll: true }) },
    resumeCampaign(c) { router.post(`/outbound-sales/${c.id}/resume`, {}, { preserveScroll: true }) },
    markReplied(c) {
      if (confirm('Mark this lead as replied? This will qualify the lead and complete the campaign.')) {
        router.post(`/outbound-sales/${c.id}/mark-replied`, {}, { preserveScroll: true })
      }
    },
    initials(name) { if (!name) return '?'; return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) },
    avatarGradient(name) {
      const colors = [['#6366f1','#8b5cf6'],['#3b82f6','#06b6d4'],['#10b981','#14b8a6'],['#f59e0b','#f97316'],['#ec4899','#db2777']]
      const idx = (name || '').charCodeAt(0) % colors.length
      return `linear-gradient(135deg, ${colors[idx][0]}, ${colors[idx][1]})`
    },
    scoreClass(s) { return s >= 50 ? 'sc-high' : s >= 20 ? 'sc-mid' : s >= 10 ? 'sc-low' : 'sc-cold' },
    formatRelative(dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr), now = new Date()
      const diffMs = d - now
      const diffH = Math.round(diffMs / (1000 * 60 * 60))
      if (diffH < 0) return 'Overdue'
      if (diffH < 1) return 'Soon'
      if (diffH < 24) return `${diffH}h`
      return `${Math.round(diffH / 24)}d`
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
.header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #ef6820, #e04f0f); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; box-shadow: 0 4px 14px rgba(239,104,32,0.3); }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.82rem; color: #64748b; margin: 0.1rem 0 0; }

/* KPI Cards */
.kpi-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.6rem; margin-bottom: 1rem; }
.kpi-card { display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 0.85rem; background: white; border-radius: 14px; border: 1.5px solid #e2e8f0; transition: all 0.2s; }
.kpi-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.kpi-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0; }
.kpi-body { display: flex; flex-direction: column; }
.kpi-value { font-size: 1.15rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
.kpi-label { font-size: 0.62rem; color: #94a3b8; margin-top: 0.1rem; }

/* Funnel Tabs */
.funnel-row { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.funnel-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.85rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.78rem; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s; }
.funnel-btn:hover { border-color: #cbd5e1; background: #fafbfc; }
.funnel-btn.active { border-color: #6366f1; background: #eef2ff; color: #4f46e5; }
.funnel-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.funnel-label { white-space: nowrap; }
.funnel-count { background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 6px; font-size: 0.68rem; font-weight: 700; color: #64748b; min-width: 20px; text-align: center; }
.funnel-btn.active .funnel-count { background: #c7d2fe; color: #4338ca; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.85rem; background: white; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 1rem; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #f8fafc; flex: 1; min-width: 200px; }
.search-box:focus-within { border-color: #ef6820; background: white; }
.search-box i { color: #94a3b8; font-size: 0.78rem; }
.search-input { border: none; outline: none; font-size: 0.82rem; color: #334155; background: transparent; width: 100%; }
.filter-selects { display: flex; gap: 0.4rem; }
.filter-select { padding: 0.4rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.78rem; color: #475569; background: white; cursor: pointer; outline: none; min-width: 130px; }
.filter-select:focus { border-color: #ef6820; }
.btn-text { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.7rem; border-radius: 8px; border: none; background: transparent; color: #64748b; font-size: 0.78rem; font-weight: 600; cursor: pointer; }
.btn-text:hover { background: #f1f5f9; }

/* Data Table */
.data-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 0.65rem 1rem; font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; text-align: left; background: #fafbfc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 0.65rem 1rem; font-size: 0.82rem; color: #334155; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
.table-row { transition: background 0.15s; cursor: pointer; }
.table-row:hover { background: #fafbfe; }

/* Lead cell */
.lead-cell { display: flex; align-items: center; gap: 0.6rem; }
.lead-avatar { width: 34px; height: 34px; border-radius: 10px; color: white; font-size: 0.6rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.lead-info { display: flex; flex-direction: column; min-width: 0; }
.lead-name { font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lead-company { font-size: 0.68rem; color: #94a3b8; display: flex; align-items: center; gap: 0.2rem; }
.lead-company i { font-size: 0.55rem; }

/* Funnel badge */
.funnel-badge { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.68rem; font-weight: 600; padding: 0.18rem 0.55rem; border-radius: 20px; }
.funnel-badge-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.fl-new { background: #eff6ff; color: #3b82f6; } .fl-new .funnel-badge-dot { background: #3b82f6; }
.fl-contacted { background: #fef3c7; color: #d97706; } .fl-contacted .funnel-badge-dot { background: #d97706; }
.fl-engaged { background: #f5f3ff; color: #8b5cf6; } .fl-engaged .funnel-badge-dot { background: #8b5cf6; }
.fl-qualified { background: #d1fae5; color: #059669; } .fl-qualified .funnel-badge-dot { background: #059669; }

/* Step progress */
.step-cell { display: flex; flex-direction: column; gap: 0.25rem; }
.step-progress { display: flex; gap: 0.25rem; }
.step-dot { width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0; transition: all 0.2s; }
.step-dot.filled { background: #ef6820; }
.step-dot.active { background: #ef6820; transform: scale(1.25); box-shadow: 0 0 0 3px rgba(239,104,32,0.2); }
.step-label { font-size: 0.65rem; color: #94a3b8; }

/* Score */
.score-cell { display: flex; align-items: center; }
.score-ring { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700; border: 2.5px solid; }
.sc-high { border-color: #10b981; color: #059669; background: #ecfdf5; }
.sc-mid { border-color: #f59e0b; color: #d97706; background: #fffbeb; }
.sc-low { border-color: #ef4444; color: #dc2626; background: #fef2f2; }
.sc-cold { border-color: #94a3b8; color: #64748b; background: #f8fafc; }

/* Engagement */
.engagement-cell { display: flex; gap: 0.35rem; }
.eng-icon { width: 26px; height: 26px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; background: #f1f5f9; color: #cbd5e1; transition: all 0.2s; }
.eng-icon.active { background: #ecfdf5; color: #10b981; }

/* Campaign badge */
.campaign-badge { font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 6px; text-transform: capitalize; }
.cb-active { background: #dbeafe; color: #2563eb; }
.cb-paused { background: #fef3c7; color: #d97706; }
.cb-completed { background: #d1fae5; color: #059669; }
.cb-cancelled { background: #f1f5f9; color: #64748b; }

/* Next action */
.next-action { font-size: 0.72rem; color: #64748b; display: inline-flex; align-items: center; gap: 0.25rem; }
.next-action i { font-size: 0.62rem; }

/* Actions */
.action-buttons { display: flex; gap: 0.25rem; }
.action-btn { width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.65rem; transition: all 0.15s; color: #94a3b8; text-decoration: none; }
.action-btn:hover { transform: translateY(-1px); }
.action-btn.pause:hover { border-color: #f59e0b; color: #f59e0b; background: #fffbeb; }
.action-btn.resume:hover { border-color: #10b981; color: #10b981; background: #ecfdf5; }
.action-btn.reply:hover { border-color: #8b5cf6; color: #8b5cf6; background: #f5f3ff; }
.action-btn.view:hover { border-color: #6366f1; color: #6366f1; background: #eef2ff; }

.muted { color: #cbd5e1; font-size: 0.78rem; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 1rem; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 0.72rem; color: #94a3b8; }
.page-btns { display: flex; gap: 0.2rem; }
.page-btn { width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.page-btn:hover:not(.active):not(.dots) { border-color: #ef6820; color: #ef6820; }
.page-btn.active { background: #ef6820; color: white; border-color: #ef6820; }
.page-btn.dots { border: none; cursor: default; }

/* Empty */
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 4rem 2rem; }
.empty-icon { width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, #fef5f0, #fde8dc); color: #ef6820; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem; }
.empty-state h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0; }

@media (max-width: 1024px) { .kpi-row { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
  .kpi-row { grid-template-columns: repeat(2, 1fr); }
  .funnel-row { flex-wrap: wrap; }
  .filter-bar { flex-direction: column; }
  .data-table { display: block; overflow-x: auto; }
}
</style>
