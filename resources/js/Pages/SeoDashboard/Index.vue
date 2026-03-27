<template>
  <div>
    <Head title="SEO Dashboard" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-search" style="color:#f59e0b;" /> SEO Dashboard</h1>
        <p class="page-subtitle">Theo dõi keyword rankings, audit issues, và hiệu suất SEO</p>
      </div>
      <button class="btn-add" @click="showAddKw = true"><i class="pi pi-plus" /> Thêm Keyword</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-total"><i class="pi pi-key" /></div><div class="stat-body"><span class="stat-val">{{ stats.total }}</span><span class="stat-lbl">Keywords</span></div></div>
      <div class="stat-card"><div class="stat-icon si-top10"><i class="pi pi-star" /></div><div class="stat-body"><span class="stat-val">{{ stats.top10 }}</span><span class="stat-lbl">Top 10</span></div></div>
      <div class="stat-card"><div class="stat-icon si-top30"><i class="pi pi-chart-bar" /></div><div class="stat-body"><span class="stat-val">{{ stats.top30 }}</span><span class="stat-lbl">Top 30</span></div></div>
      <div class="stat-card"><div class="stat-icon si-up"><i class="pi pi-arrow-up" /></div><div class="stat-body"><span class="stat-val">{{ stats.improved }}</span><span class="stat-lbl">Cải thiện</span></div></div>
      <div class="stat-card"><div class="stat-icon si-crit"><i class="pi pi-exclamation-triangle" /></div><div class="stat-body"><span class="stat-val">{{ stats.audit_critical }}</span><span class="stat-lbl">Critical Issues</span></div></div>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <button class="tab" :class="{ active: tab === 'keywords' }" @click="tab = 'keywords'"><i class="pi pi-key" /> Keywords</button>
      <button class="tab" :class="{ active: tab === 'audit' }" @click="tab = 'audit'"><i class="pi pi-shield" /> Site Audit <span v-if="stats.audit_critical" class="tab-badge-red">{{ stats.audit_critical }}</span></button>
      <button class="tab" :class="{ active: tab === 'distribution' }" @click="tab = 'distribution'"><i class="pi pi-chart-pie" /> Rank Distribution</button>
    </div>

    <!-- Tab: Keywords -->
    <div v-show="tab === 'keywords'" class="tab-content">
      <div class="filter-bar">
        <div class="search-wrap"><i class="pi pi-search" /><input v-model="search" type="text" placeholder="Tìm keyword..." @input="doSearch" /></div>
        <select v-model="filterStatus" class="filter-select" @change="doSearch">
          <option value="">Tất cả</option>
          <option value="tracking">Tracking</option>
          <option value="achieved">Achieved</option>
          <option value="paused">Paused</option>
        </select>
      </div>

      <div v-if="keywords.data?.length" class="kw-table-wrap">
        <table class="kw-table">
          <thead>
            <tr><th>Keyword</th><th>URL</th><th>Rank</th><th>Thay đổi</th><th>Best</th><th>Volume</th><th>Độ khó</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-for="kw in keywords.data" :key="kw.id">
              <td class="kw-cell"><span class="kw-text">{{ kw.keyword }}</span></td>
              <td class="url-cell">{{ kw.url || '—' }}</td>
              <td class="rank-cell"><span class="rank-badge" :class="rankClass(kw.current_rank)">{{ kw.current_rank || '—' }}</span></td>
              <td class="change-cell">
                <span v-if="kw.rank_change > 0" class="ch-up"><i class="pi pi-arrow-up" /> +{{ kw.rank_change }}</span>
                <span v-else-if="kw.rank_change < 0" class="ch-down"><i class="pi pi-arrow-down" /> {{ kw.rank_change }}</span>
                <span v-else class="ch-stable">—</span>
              </td>
              <td class="best-cell">{{ kw.best_rank || '—' }}</td>
              <td class="vol-cell">{{ kw.search_volume ? kw.search_volume.toLocaleString() : '—' }}</td>
              <td><span class="diff-badge" :class="'df-' + kw.difficulty">{{ kw.difficulty || '—' }}</span></td>
              <td><span class="st-badge" :class="'st-' + kw.status">{{ kw.status }}</span></td>
              <td><button class="act-del" @click="deleteKw(kw.id)"><i class="pi pi-trash" /></button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-key" /></div>
        <h3>Chưa có keyword</h3>
        <p>Thêm keyword để bắt đầu theo dõi ranking</p>
      </div>
    </div>

    <!-- Tab: Audit -->
    <div v-show="tab === 'audit'" class="tab-content">
      <div v-if="auditIssues?.length" class="audit-list">
        <div v-for="issue in auditIssues" :key="issue.id" class="audit-row">
          <span class="audit-sev" :class="'sev-' + issue.severity">{{ issue.severity }}</span>
          <div class="audit-body">
            <div class="audit-type">{{ issueTypes[issue.issue_type]?.icon }} {{ issueTypes[issue.issue_type]?.label || issue.issue_type }}</div>
            <div class="audit-url">{{ issue.page_url }}</div>
            <div v-if="issue.description" class="audit-desc">{{ issue.description }}</div>
            <div v-if="issue.recommendation" class="audit-rec"><i class="pi pi-lightbulb" /> {{ issue.recommendation }}</div>
          </div>
          <button v-if="issue.status === 'open'" class="fix-btn" @click="fixIssue(issue.id)"><i class="pi pi-check" /> Fix</button>
          <span v-else class="fixed-badge">Fixed</span>
        </div>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-shield" /></div>
        <h3>Không có vấn đề SEO</h3>
        <p>Website của bạn đang tốt!</p>
      </div>
    </div>

    <!-- Tab: Distribution -->
    <div v-show="tab === 'distribution'" class="tab-content">
      <div class="dist-grid">
        <div class="dist-card dc-gold"><span class="dc-num">{{ rankDistribution.top3 }}</span><span class="dc-lbl">Top 3</span><span class="dc-range">#1 – #3</span></div>
        <div class="dist-card dc-silver"><span class="dc-num">{{ rankDistribution.top10 }}</span><span class="dc-lbl">Top 10</span><span class="dc-range">#1 – #10</span></div>
        <div class="dist-card dc-bronze"><span class="dc-num">{{ rankDistribution.top30 }}</span><span class="dc-lbl">Top 30</span><span class="dc-range">#1 – #30</span></div>
        <div class="dist-card dc-basic"><span class="dc-num">{{ rankDistribution.top50 }}</span><span class="dc-lbl">Top 50</span><span class="dc-range">#1 – #50</span></div>
        <div class="dist-card dc-beyond"><span class="dc-num">{{ rankDistribution.beyond }}</span><span class="dc-lbl">Ngoài 50</span><span class="dc-range">#50+</span></div>
      </div>
    </div>

    <!-- Add Keyword Modal -->
    <div v-if="showAddKw" class="modal-overlay" @click.self="showAddKw = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Thêm Keyword SEO</h3><button class="modal-close" @click="showAddKw = false"><i class="pi pi-times" /></button></div>
        <div class="fm-group"><label>Keyword <span class="req">*</span></label><input v-model="newKw.keyword" type="text" class="fm-input" placeholder="thiết kế website" /></div>
        <div class="fm-group"><label>Target URL</label><input v-model="newKw.url" type="url" class="fm-input" placeholder="https://..." /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Rank hiện tại</label><input v-model.number="newKw.current_rank" type="number" min="1" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Search Volume</label><input v-model.number="newKw.search_volume" type="number" class="fm-input" /></div>
        </div>
        <div class="fm-group"><label>Độ khó</label>
          <div class="diff-options">
            <button v-for="d in ['easy','medium','hard']" :key="d" class="diff-opt" :class="{ active: newKw.difficulty === d }" @click="newKw.difficulty = d">{{ d }}</button>
          </div>
        </div>
        <button class="btn-save" :disabled="!newKw.keyword || saving" @click="saveKw"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> Thêm</button>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { keywords: Object, stats: Object, auditIssues: Array, issueTypes: Object, rankDistribution: Object, filters: Object },
  data() {
    return {
      tab: 'keywords', search: this.filters?.search || '', filterStatus: this.filters?.status || '',
      searchTimeout: null, showAddKw: false, saving: false,
      newKw: { keyword: '', url: '', current_rank: null, search_volume: null, difficulty: 'medium' },
    }
  },
  methods: {
    rankClass(rank) { if (!rank) return 'rk-none'; return rank <= 3 ? 'rk-gold' : rank <= 10 ? 'rk-green' : rank <= 30 ? 'rk-yellow' : 'rk-red' },
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/seo-dashboard', { search: this.search || undefined, status: this.filterStatus || undefined }, { preserveState: true, replace: true })
      }, 400)
    },
    saveKw() {
      this.saving = true
      router.post('/seo-dashboard/keywords', this.newKw, {
        onSuccess: () => { this.newKw = { keyword: '', url: '', current_rank: null, search_volume: null, difficulty: 'medium' }; this.showAddKw = false },
        onFinish: () => { this.saving = false },
      })
    },
    deleteKw(id) { if (confirm('Xóa keyword?')) router.delete(`/seo-dashboard/keywords/${id}`) },
    fixIssue(id) { router.post(`/seo-dashboard/issues/${id}/fix`) },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }

.stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-total { background: #fef3c7; color: #f59e0b; }
.si-top10 { background: #ecfdf5; color: #10b981; }
.si-top30 { background: #eef2ff; color: #6366f1; }
.si-up { background: #d1fae5; color: #059669; }
.si-crit { background: #fef2f2; color: #ef4444; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; }

.tabs-bar { display: flex; gap: 0; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 0.75rem; }
.tab { padding: 0.5rem 0.9rem; border: none; background: transparent; font-size: 0.72rem; font-weight: 700; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 0.25rem; font-family: inherit; }
.tab.active { color: #f59e0b; border-bottom-color: #f59e0b; }
.tab-badge-red { padding: 0.1rem 0.35rem; border-radius: 9px; background: #ef4444; color: white; font-size: 0.5rem; font-weight: 800; }

.filter-bar { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 280px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-select { padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; font-size: 0.72rem; color: #475569; font-family: inherit; outline: none; }

/* KW Table */
.kw-table-wrap { overflow-x: auto; }
.kw-table { width: 100%; border-collapse: collapse; font-size: 0.72rem; }
.kw-table th { padding: 0.5rem 0.6rem; background: #f8fafc; text-align: left; font-weight: 700; color: #475569; font-size: 0.62rem; text-transform: uppercase; border-bottom: 1.5px solid #e2e8f0; }
.kw-table td { padding: 0.5rem 0.6rem; border-bottom: 1px solid #f1f5f9; }
.kw-text { font-weight: 700; color: #0f172a; }
.url-cell { font-size: 0.6rem; color: #94a3b8; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rank-badge { padding: 0.15rem 0.45rem; border-radius: 6px; font-weight: 800; font-size: 0.72rem; }
.rk-gold { background: #fef3c7; color: #b45309; }
.rk-green { background: #ecfdf5; color: #059669; }
.rk-yellow { background: #fff7ed; color: #ea580c; }
.rk-red { background: #fef2f2; color: #dc2626; }
.rk-none { background: #f1f5f9; color: #94a3b8; }
.ch-up { color: #10b981; font-weight: 700; display: flex; align-items: center; gap: 0.15rem; }
.ch-down { color: #ef4444; font-weight: 700; display: flex; align-items: center; gap: 0.15rem; }
.ch-stable { color: #cbd5e1; }
.ch-up i, .ch-down i { font-size: 0.5rem; }
.diff-badge { padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.58rem; font-weight: 700; text-transform: capitalize; }
.df-easy { background: #ecfdf5; color: #10b981; }
.df-medium { background: #fef3c7; color: #f59e0b; }
.df-hard { background: #fef2f2; color: #ef4444; }
.st-badge { padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.55rem; font-weight: 700; text-transform: capitalize; }
.st-tracking { background: #eef2ff; color: #6366f1; }
.st-achieved { background: #ecfdf5; color: #10b981; }
.st-paused { background: #f1f5f9; color: #94a3b8; }
.act-del { width: 24px; height: 24px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.55rem; }
.act-del:hover { color: #ef4444; }

/* Audit */
.audit-list { display: flex; flex-direction: column; gap: 0.4rem; }
.audit-row { display: flex; gap: 0.5rem; padding: 0.65rem; background: white; border-radius: 10px; border: 1.5px solid #f1f5f9; align-items: flex-start; }
.audit-sev { padding: 0.12rem 0.4rem; border-radius: 5px; font-size: 0.55rem; font-weight: 800; text-transform: uppercase; flex-shrink: 0; }
.sev-critical { background: #fef2f2; color: #ef4444; }
.sev-warning { background: #fef3c7; color: #f59e0b; }
.sev-info { background: #eef2ff; color: #6366f1; }
.audit-body { flex: 1; }
.audit-type { font-size: 0.75rem; font-weight: 700; color: #0f172a; }
.audit-url { font-size: 0.6rem; color: #94a3b8; }
.audit-desc { font-size: 0.65rem; color: #64748b; margin-top: 0.15rem; }
.audit-rec { font-size: 0.6rem; color: #10b981; margin-top: 0.1rem; display: flex; align-items: center; gap: 0.2rem; }
.audit-rec i { font-size: 0.5rem; }
.fix-btn { padding: 0.25rem 0.55rem; border-radius: 6px; background: #ecfdf5; border: 1px solid #10b981; color: #10b981; font-size: 0.6rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; flex-shrink: 0; font-family: inherit; }
.fixed-badge { padding: 0.15rem 0.4rem; border-radius: 5px; background: #f1f5f9; color: #94a3b8; font-size: 0.55rem; font-weight: 700; }

/* Distribution */
.dist-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.65rem; }
.dist-card { text-align: center; padding: 1.5rem 1rem; border-radius: 14px; border: 1.5px solid #f1f5f9; background: white; }
.dc-num { font-size: 2rem; font-weight: 900; display: block; }
.dc-lbl { font-size: 0.78rem; font-weight: 700; color: #475569; display: block; margin-top: 0.2rem; }
.dc-range { font-size: 0.58rem; color: #94a3b8; }
.dc-gold .dc-num { color: #f59e0b; }
.dc-silver .dc-num { color: #10b981; }
.dc-bronze .dc-num { color: #6366f1; }
.dc-basic .dc-num { color: #64748b; }
.dc-beyond .dc-num { color: #cbd5e1; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 16px; padding: 1.2rem; width: 95%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.modal-head h3 { font-size: 0.95rem; font-weight: 800; margin: 0; }
.modal-close { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.diff-options { display: flex; gap: 0.3rem; }
.diff-opt { padding: 0.3rem 0.7rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.68rem; font-weight: 600; cursor: pointer; text-transform: capitalize; font-family: inherit; }
.diff-opt.active { border-color: #f59e0b; background: #fef3c7; color: #b45309; }
.btn-save { width: 100%; padding: 0.5rem; border-radius: 9px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 0.3rem; margin-top: 0.5rem; }

.empty-state { text-align: center; padding: 2.5rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #fef3c7; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #f59e0b; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0; }

@media (max-width: 768px) { .stats-row, .dist-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
