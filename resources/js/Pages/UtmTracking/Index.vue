<template>
  <div>
    <Head title="UTM Tracking — Attribution" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-chart-line" style="color:#6366f1;" /> UTM Tracking & Attribution</h1>
        <p class="page-subtitle">Theo dõi nguồn traffic, tạo UTM links, và phân tích hiệu quả kênh</p>
      </div>
      <button class="btn-add" @click="showBuilder = true"><i class="pi pi-plus" /> Tạo UTM Link</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-leads"><i class="pi pi-users" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_leads }}</span><span class="stat-lbl">Tổng Leads</span></div></div>
      <div class="stat-card"><div class="stat-icon si-month"><i class="pi pi-calendar" /></div><div class="stat-body"><span class="stat-val">{{ stats.this_month }}</span><span class="stat-lbl">Tháng này</span></div></div>
      <div class="stat-card"><div class="stat-icon si-links"><i class="pi pi-link" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_utm_links }}</span><span class="stat-lbl">UTM Links</span></div></div>
      <div class="stat-card"><div class="stat-icon si-subs"><i class="pi pi-inbox" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_submissions }}</span><span class="stat-lbl">Form Submissions</span></div></div>
      <div class="stat-card"><div class="stat-icon si-top"><i class="pi pi-star" /></div><div class="stat-body"><span class="stat-val">{{ stats.top_source }}</span><span class="stat-lbl">Top Source</span></div></div>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <button class="tab" :class="{ active: tab === 'dashboard' }" @click="tab = 'dashboard'"><i class="pi pi-chart-bar" /> Attribution</button>
      <button class="tab" :class="{ active: tab === 'links' }" @click="tab = 'links'"><i class="pi pi-link" /> UTM Links <span v-if="links.total" class="tab-badge">{{ links.total }}</span></button>
      <button class="tab" :class="{ active: tab === 'builder' }" @click="tab = 'builder'"><i class="pi pi-wrench" /> UTM Builder</button>
    </div>

    <!-- Tab: Attribution Dashboard -->
    <div v-show="tab === 'dashboard'" class="tab-content">
      <div class="charts-grid">
        <!-- Leads by Source -->
        <div class="chart-card">
          <h3 class="chart-title"><i class="pi pi-chart-pie" /> Leads theo Nguồn</h3>
          <div v-if="attribution.by_source?.length" class="bar-chart">
            <div v-for="item in attribution.by_source" :key="item.source" class="bar-row">
              <span class="bar-label">{{ item.source }}</span>
              <div class="bar-track"><div class="bar-fill bf-source" :style="{ width: barWidth(item.total, attribution.by_source) }" /></div>
              <span class="bar-val">{{ item.total }}</span>
            </div>
          </div>
          <div v-else class="no-data">Chưa có dữ liệu</div>
        </div>

        <!-- Leads by UTM Source -->
        <div class="chart-card">
          <h3 class="chart-title"><i class="pi pi-tag" /> Leads theo UTM Source</h3>
          <div v-if="attribution.by_utm_source?.length" class="bar-chart">
            <div v-for="item in attribution.by_utm_source" :key="item.source" class="bar-row">
              <span class="bar-label">{{ item.source }}</span>
              <div class="bar-track"><div class="bar-fill bf-utm" :style="{ width: barWidth(item.total, attribution.by_utm_source) }" /></div>
              <span class="bar-val">{{ item.total }}</span>
            </div>
          </div>
          <div v-else class="no-data">Chưa có UTM data</div>
        </div>

        <!-- Leads by Medium -->
        <div class="chart-card">
          <h3 class="chart-title"><i class="pi pi-sliders-h" /> Leads theo Medium</h3>
          <div v-if="attribution.by_utm_medium?.length" class="bar-chart">
            <div v-for="item in attribution.by_utm_medium" :key="item.medium" class="bar-row">
              <span class="bar-label">{{ item.medium }}</span>
              <div class="bar-track"><div class="bar-fill bf-medium" :style="{ width: barWidth(item.total, attribution.by_utm_medium) }" /></div>
              <span class="bar-val">{{ item.total }}</span>
            </div>
          </div>
          <div v-else class="no-data">Chưa có data</div>
        </div>

        <!-- Leads by Campaign -->
        <div class="chart-card">
          <h3 class="chart-title"><i class="pi pi-megaphone" /> Top Campaigns</h3>
          <div v-if="attribution.by_campaign?.length" class="bar-chart">
            <div v-for="item in attribution.by_campaign" :key="item.campaign" class="bar-row">
              <span class="bar-label">{{ item.campaign }}</span>
              <div class="bar-track"><div class="bar-fill bf-campaign" :style="{ width: barWidth(item.total, attribution.by_campaign) }" /></div>
              <span class="bar-val">{{ item.total }}</span>
            </div>
          </div>
          <div v-else class="no-data">Chưa có campaign data</div>
        </div>
      </div>

      <!-- Leads Trend -->
      <div class="chart-card" style="margin-top: 0.65rem;">
        <h3 class="chart-title"><i class="pi pi-chart-line" /> Leads 30 ngày gần nhất</h3>
        <div v-if="attribution.trend?.length" class="trend-chart">
          <div class="trend-bars">
            <div v-for="day in attribution.trend" :key="day.date" class="trend-col" :title="`${day.date}: ${day.total} leads`">
              <div class="trend-bar" :style="{ height: trendHeight(day.total) }" />
              <span class="trend-lbl">{{ day.date?.slice(8) }}</span>
            </div>
          </div>
        </div>
        <div v-else class="no-data">Chưa có dữ liệu trend</div>
      </div>
    </div>

    <!-- Tab: UTM Links -->
    <div v-show="tab === 'links'" class="tab-content">
      <div class="filter-bar">
        <div class="search-wrap"><i class="pi pi-search" /><input v-model="search" type="text" placeholder="Tìm UTM link..." @input="doSearch" /></div>
      </div>

      <div v-if="links.data?.length" class="links-table-wrap">
        <table class="links-table">
          <thead>
            <tr>
              <th>Tên</th>
              <th>Source</th>
              <th>Medium</th>
              <th>Campaign</th>
              <th>Clicks</th>
              <th>Leads</th>
              <th>CR%</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="link in links.data" :key="link.id">
              <td>
                <div class="link-name">{{ link.name }}</div>
                <div class="link-url">{{ link.base_url }}</div>
              </td>
              <td><span class="utm-chip uc-source">{{ link.utm_source }}</span></td>
              <td><span class="utm-chip uc-medium">{{ link.utm_medium }}</span></td>
              <td><span class="utm-chip uc-campaign">{{ link.utm_campaign }}</span></td>
              <td class="num-cell">{{ link.clicks_count }}</td>
              <td class="num-cell">{{ link.leads_count }}</td>
              <td class="num-cell"><span :class="link.conversion_rate > 5 ? 'cr-good' : 'cr-low'">{{ link.conversion_rate }}%</span></td>
              <td>
                <div class="tbl-actions">
                  <button class="act-btn" @click="copyUrl(link.full_url)" title="Copy URL"><i class="pi pi-copy" /></button>
                  <button class="act-btn act-del" @click="deleteLink(link.id)"><i class="pi pi-trash" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-link" /></div>
        <h3>Chưa có UTM link</h3>
        <p>Sử dụng UTM Builder để tạo link tracking</p>
        <button class="btn-add" @click="tab = 'builder'"><i class="pi pi-plus" /> Tạo UTM Link</button>
      </div>
    </div>

    <!-- Tab: UTM Builder -->
    <div v-show="tab === 'builder'" class="tab-content">
      <div class="builder-layout">
        <div class="builder-main">
          <div class="section-card">
            <h3 class="sec-title"><i class="pi pi-wrench" /> Tạo UTM Link</h3>
            <div class="fm-group">
              <label>Tên link <span class="req">*</span></label>
              <input v-model="newLink.name" type="text" class="fm-input" placeholder="VD: Facebook Spring Campaign" />
            </div>
            <div class="fm-group">
              <label>URL đích <span class="req">*</span></label>
              <input v-model="newLink.base_url" type="url" class="fm-input" placeholder="https://betech.vn/landing-page" />
            </div>
            <div class="fm-row">
              <div class="fm-group flex-1">
                <label>Source <span class="req">*</span></label>
                <div class="preset-input">
                  <input v-model="newLink.utm_source" type="text" class="fm-input" placeholder="google" />
                  <div class="preset-chips">
                    <button v-for="s in sourcePresets.slice(0, 6)" :key="s" class="preset-chip" :class="{ active: newLink.utm_source === s }" @click="newLink.utm_source = s">{{ s }}</button>
                  </div>
                </div>
              </div>
              <div class="fm-group flex-1">
                <label>Medium <span class="req">*</span></label>
                <div class="preset-input">
                  <input v-model="newLink.utm_medium" type="text" class="fm-input" placeholder="cpc" />
                  <div class="preset-chips">
                    <button v-for="m in mediumPresets.slice(0, 6)" :key="m" class="preset-chip" :class="{ active: newLink.utm_medium === m }" @click="newLink.utm_medium = m">{{ m }}</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="fm-group">
              <label>Campaign <span class="req">*</span></label>
              <input v-model="newLink.utm_campaign" type="text" class="fm-input" placeholder="spring_2026_promo" />
            </div>
            <div class="fm-row">
              <div class="fm-group flex-1">
                <label>Term <span class="opt">(tùy chọn)</span></label>
                <input v-model="newLink.utm_term" type="text" class="fm-input" placeholder="thiết kế website" />
              </div>
              <div class="fm-group flex-1">
                <label>Content <span class="opt">(tùy chọn)</span></label>
                <input v-model="newLink.utm_content" type="text" class="fm-input" placeholder="banner_v1" />
              </div>
            </div>
            <button class="btn-generate" :disabled="!canGenerate || saving" @click="saveLink">
              <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-bolt'" /> Tạo & Lưu UTM Link
            </button>
          </div>
        </div>

        <!-- Preview -->
        <div class="builder-sidebar">
          <div class="preview-card">
            <h3 class="preview-title">Preview URL</h3>
            <div class="url-preview" v-if="generatedUrl">
              <div class="url-base">{{ newLink.base_url || 'https://...' }}</div>
              <div class="url-params">
                <span v-if="newLink.utm_source" class="url-param"><b>utm_source</b>={{ newLink.utm_source }}</span>
                <span v-if="newLink.utm_medium" class="url-param"><b>utm_medium</b>={{ newLink.utm_medium }}</span>
                <span v-if="newLink.utm_campaign" class="url-param"><b>utm_campaign</b>={{ newLink.utm_campaign }}</span>
                <span v-if="newLink.utm_term" class="url-param"><b>utm_term</b>={{ newLink.utm_term }}</span>
                <span v-if="newLink.utm_content" class="url-param"><b>utm_content</b>={{ newLink.utm_content }}</span>
              </div>
            </div>
            <div class="full-url-box" v-if="generatedUrl">
              <code>{{ generatedUrl }}</code>
              <button class="copy-sm" @click="copyUrl(generatedUrl)"><i class="pi pi-copy" /></button>
            </div>
            <div v-else class="no-data" style="padding: 1rem;">Nhập URL và UTM params để xem preview</div>
          </div>

          <div class="preview-card">
            <h3 class="preview-title">UTM Params giải thích</h3>
            <div class="param-explain">
              <div class="pe-row"><span class="pe-key">Source</span><span class="pe-desc">Nguồn traffic (google, facebook, email...)</span></div>
              <div class="pe-row"><span class="pe-key">Medium</span><span class="pe-desc">Kênh (cpc, organic, social, email...)</span></div>
              <div class="pe-row"><span class="pe-key">Campaign</span><span class="pe-desc">Tên chiến dịch cụ thể</span></div>
              <div class="pe-row"><span class="pe-key">Term</span><span class="pe-desc">Từ khóa tìm kiếm (tùy chọn)</span></div>
              <div class="pe-row"><span class="pe-key">Content</span><span class="pe-desc">Phân biệt quảng cáo (tùy chọn)</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Builder modal overlay (for quick add from links tab) -->
    <div v-if="showBuilder" class="modal-overlay" @click.self="showBuilder = false">
      <div class="modal-box">
        <div class="modal-head">
          <h3>Tạo nhanh UTM Link</h3>
          <button class="modal-close" @click="showBuilder = false"><i class="pi pi-times" /></button>
        </div>
        <div class="fm-group"><label>Tên</label><input v-model="newLink.name" type="text" class="fm-input" /></div>
        <div class="fm-group"><label>URL</label><input v-model="newLink.base_url" type="url" class="fm-input" /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Source</label><input v-model="newLink.utm_source" type="text" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Medium</label><input v-model="newLink.utm_medium" type="text" class="fm-input" /></div>
        </div>
        <div class="fm-group"><label>Campaign</label><input v-model="newLink.utm_campaign" type="text" class="fm-input" /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Term</label><input v-model="newLink.utm_term" type="text" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Content</label><input v-model="newLink.utm_content" type="text" class="fm-input" /></div>
        </div>
        <div v-if="generatedUrl" class="full-url-box modal-url"><code>{{ generatedUrl }}</code></div>
        <button class="btn-generate" :disabled="!canGenerate || saving" @click="saveLink" style="width:100%;margin-top:0.5rem;">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> Lưu
        </button>
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
  props: {
    links: Object, stats: Object, attribution: Object,
    sourcePresets: Array, mediumPresets: Array,
    distinctSources: Array, distinctMediums: Array, distinctCampaigns: Array,
    filters: Object,
  },
  data() {
    return {
      tab: 'dashboard',
      search: this.filters?.search || '',
      searchTimeout: null,
      showBuilder: false,
      saving: false,
      newLink: { name: '', base_url: '', utm_source: '', utm_medium: '', utm_campaign: '', utm_term: '', utm_content: '' },
    }
  },
  computed: {
    canGenerate() {
      return this.newLink.name && this.newLink.base_url && this.newLink.utm_source && this.newLink.utm_medium && this.newLink.utm_campaign
    },
    generatedUrl() {
      if (!this.newLink.base_url) return ''
      const params = new URLSearchParams()
      if (this.newLink.utm_source) params.set('utm_source', this.newLink.utm_source)
      if (this.newLink.utm_medium) params.set('utm_medium', this.newLink.utm_medium)
      if (this.newLink.utm_campaign) params.set('utm_campaign', this.newLink.utm_campaign)
      if (this.newLink.utm_term) params.set('utm_term', this.newLink.utm_term)
      if (this.newLink.utm_content) params.set('utm_content', this.newLink.utm_content)
      const sep = this.newLink.base_url.includes('?') ? '&' : '?'
      return this.newLink.base_url + sep + params.toString()
    },
  },
  methods: {
    barWidth(val, arr) {
      const max = Math.max(...arr.map(a => a.total || 0))
      return max > 0 ? ((val / max) * 100) + '%' : '0%'
    },
    trendHeight(val) {
      const max = Math.max(...this.attribution.trend.map(d => d.total))
      return max > 0 ? Math.max(((val / max) * 80), 4) + 'px' : '4px'
    },
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/utm-tracking', { search: this.search || undefined }, { preserveState: true, replace: true })
      }, 400)
    },
    copyUrl(url) {
      navigator.clipboard.writeText(url)
      alert('Đã copy URL!')
    },
    saveLink() {
      this.saving = true
      router.post('/utm-tracking', this.newLink, {
        onSuccess: () => {
          this.newLink = { name: '', base_url: '', utm_source: '', utm_medium: '', utm_campaign: '', utm_term: '', utm_content: '' }
          this.showBuilder = false
          this.tab = 'links'
        },
        onFinish: () => { this.saving = false },
      })
    },
    deleteLink(id) {
      if (!confirm('Xóa UTM link này?')) return
      router.delete(`/utm-tracking/${id}`)
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-title i { font-size: 1.2rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; transition: all 0.15s; text-decoration: none; }
.btn-add:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-leads { background: #eef2ff; color: #6366f1; }
.si-month { background: #ecfdf5; color: #10b981; }
.si-links { background: #fef3c7; color: #f59e0b; }
.si-subs { background: #fce7f3; color: #ec4899; }
.si-top { background: #f1f5f9; color: #64748b; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; font-weight: 500; }

/* Tabs */
.tabs-bar { display: flex; gap: 0; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 0.75rem; }
.tab { padding: 0.5rem 0.9rem; border: none; background: transparent; font-size: 0.72rem; font-weight: 700; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 0.25rem; font-family: inherit; transition: all 0.12s; }
.tab.active { color: #6366f1; border-bottom-color: #6366f1; }
.tab:hover { color: #6366f1; }
.tab-badge { padding: 0.1rem 0.35rem; border-radius: 9px; background: #6366f1; color: white; font-size: 0.5rem; font-weight: 800; }

/* Charts grid */
.charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; }
.chart-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; }
.chart-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.25rem; }
.chart-title i { font-size: 0.65rem; color: #6366f1; }

/* Bar chart */
.bar-chart { display: flex; flex-direction: column; gap: 0.3rem; }
.bar-row { display: flex; align-items: center; gap: 0.4rem; }
.bar-label { font-size: 0.65rem; font-weight: 600; color: #475569; min-width: 70px; text-align: right; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; }
.bar-track { flex: 1; height: 18px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 5px; transition: width 0.6s ease; min-width: 4px; }
.bf-source { background: linear-gradient(90deg, #6366f1, #818cf8); }
.bf-utm { background: linear-gradient(90deg, #10b981, #34d399); }
.bf-medium { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.bf-campaign { background: linear-gradient(90deg, #ec4899, #f472b6); }
.bar-val { font-size: 0.65rem; font-weight: 800; color: #0f172a; min-width: 24px; }

/* Trend chart */
.trend-chart { padding: 0.3rem 0; }
.trend-bars { display: flex; align-items: flex-end; gap: 2px; height: 90px; }
.trend-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
.trend-bar { width: 100%; background: linear-gradient(180deg, #6366f1, #818cf8); border-radius: 3px 3px 0 0; transition: height 0.5s ease; min-height: 2px; }
.trend-lbl { font-size: 0.42rem; color: #94a3b8; margin-top: 0.1rem; }
.no-data { color: #cbd5e1; font-size: 0.72rem; text-align: center; padding: 1.5rem; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.75rem; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 300px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }

/* Links table */
.links-table-wrap { overflow-x: auto; }
.links-table { width: 100%; border-collapse: collapse; font-size: 0.72rem; }
.links-table th { padding: 0.5rem 0.65rem; background: #f8fafc; text-align: left; font-weight: 700; color: #475569; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1.5px solid #e2e8f0; }
.links-table td { padding: 0.5rem 0.65rem; border-bottom: 1px solid #f1f5f9; }
.link-name { font-weight: 700; color: #0f172a; }
.link-url { font-size: 0.58rem; color: #94a3b8; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.utm-chip { padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.58rem; font-weight: 700; }
.uc-source { background: #eef2ff; color: #6366f1; }
.uc-medium { background: #ecfdf5; color: #10b981; }
.uc-campaign { background: #fef3c7; color: #f59e0b; }
.num-cell { text-align: center; font-weight: 700; }
.cr-good { color: #10b981; }
.cr-low { color: #94a3b8; }
.tbl-actions { display: flex; gap: 0.2rem; }
.act-btn { width: 26px; height: 26px; border-radius: 6px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; font-size: 0.55rem; }
.act-btn:hover { border-color: #6366f1; color: #6366f1; }
.act-del:hover { border-color: #ef4444; color: #ef4444; }

/* Builder */
.builder-layout { display: grid; grid-template-columns: 1fr 340px; gap: 0.75rem; align-items: start; }
.section-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 1rem; }
.sec-title { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0 0 0.6rem; display: flex; align-items: center; gap: 0.3rem; }
.sec-title i { font-size: 0.72rem; color: #6366f1; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.opt { color: #94a3b8; font-weight: 400; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-input:focus { border-color: #6366f1; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.preset-chips { display: flex; flex-wrap: wrap; gap: 0.2rem; margin-top: 0.25rem; }
.preset-chip { padding: 0.15rem 0.4rem; border-radius: 5px; border: 1px solid #e2e8f0; background: white; font-size: 0.58rem; font-weight: 600; color: #64748b; cursor: pointer; font-family: inherit; transition: all 0.1s; }
.preset-chip:hover { border-color: #6366f1; color: #6366f1; }
.preset-chip.active { background: #6366f1; color: white; border-color: #6366f1; }
.btn-generate { display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.55rem 1.2rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.82rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; margin-top: 0.5rem; }
.btn-generate:disabled { opacity: 0.5; cursor: not-allowed; }

/* Preview sidebar */
.preview-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; margin-bottom: 0.65rem; }
.preview-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; }
.url-preview { padding: 0.6rem; background: #f8fafc; border-radius: 8px; }
.url-base { font-size: 0.72rem; font-weight: 700; color: #0f172a; word-break: break-all; margin-bottom: 0.3rem; }
.url-params { display: flex; flex-direction: column; gap: 0.1rem; }
.url-param { font-size: 0.62rem; color: #6366f1; }
.url-param b { color: #475569; }
.full-url-box { display: flex; align-items: center; gap: 0.3rem; padding: 0.5rem; background: #1e293b; border-radius: 8px; margin-top: 0.4rem; }
.full-url-box code { font-size: 0.58rem; color: #10b981; flex: 1; word-break: break-all; font-family: 'JetBrains Mono', monospace; }
.copy-sm { width: 24px; height: 24px; border: none; background: rgba(16,185,129,0.15); border-radius: 5px; color: #10b981; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.55rem; flex-shrink: 0; }

/* Param explain */
.param-explain { display: flex; flex-direction: column; gap: 0.3rem; }
.pe-row { display: flex; gap: 0.35rem; align-items: flex-start; }
.pe-key { font-size: 0.62rem; font-weight: 700; color: #6366f1; min-width: 55px; }
.pe-desc { font-size: 0.6rem; color: #64748b; }

/* Empty */
.empty-state { text-align: center; padding: 2.5rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #6366f1; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0 0 0.75rem; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 16px; padding: 1.2rem; width: 95%; max-width: 460px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
.modal-head h3 { font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0; }
.modal-close { width: 28px; height: 28px; border: none; background: #f1f5f9; border-radius: 7px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #94a3b8; }
.modal-url { margin-top: 0.5rem; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .charts-grid { grid-template-columns: 1fr; }
  .builder-layout { grid-template-columns: 1fr; }
}
</style>
