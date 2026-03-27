<template>
  <div class="showcase-page">
    <Head title="Showcase Finder" />

    <!-- ═══ HERO ═══ -->
    <div class="hero">
      <div class="hero-bg">
        <div v-for="n in 10" :key="n" class="hero-orb" :style="orbStyle(n)" />
      </div>
      <div class="hero-content">
        <div class="hero-badge"><i class="pi pi-globe" /> AI SHOWCASE RESEARCH</div>
        <h1>Website <span class="grad-text">Showcase</span> Finder</h1>
        <p>Phân tích website hoặc tìm tham khảo theo ngành — AI trả kết quả formatted sẵn gửi khách</p>
      </div>
    </div>

    <!-- ═══ 2 ACTION CARDS ═══ -->
    <div class="action-grid">
      <!-- Analyze URL -->
      <div :class="['action-card', activePanel === 'analyze' && 'active']" @click="activePanel = 'analyze'">
        <div class="action-icon analyze-icon"><i class="pi pi-search" /></div>
        <h3>Phân tích URL</h3>
        <p>Paste URL website → AI phân tích design, UX, tech stack</p>
      </div>
      <!-- Discover -->
      <div :class="['action-card', activePanel === 'discover' && 'active']" @click="activePanel = 'discover'">
        <div class="action-icon discover-icon"><i class="pi pi-compass" /></div>
        <h3>Tìm theo Ngành</h3>
        <p>Chọn ngành → AI suggest 3 website tham khảo tốt nhất</p>
      </div>
    </div>

    <!-- ═══ ANALYZE PANEL ═══ -->
    <div v-if="activePanel === 'analyze'" class="panel glass-card">
      <h3><i class="pi pi-search" style="color:#6366f1" /> Phân tích Website</h3>
      <form @submit.prevent="analyzeUrl" class="input-row">
        <input v-model="analyzeForm.url" class="brand-input flex-1" placeholder="https://stripe.com" required />
        <button type="submit" class="btn-ai" :disabled="analyzing">
          <i :class="analyzing ? 'pi pi-spin pi-spinner' : 'pi pi-bolt'" /> {{ analyzing ? 'Đang phân tích...' : 'Analyze' }}
        </button>
      </form>
    </div>

    <!-- ═══ DISCOVER PANEL ═══ -->
    <div v-if="activePanel === 'discover'" class="panel glass-card">
      <h3><i class="pi pi-compass" style="color:#f59e0b" /> Tìm Website theo Ngành</h3>
      <form @submit.prevent="discoverByIndustry" class="discover-form">
        <div class="input-row">
          <select v-model="discoverForm.industry" class="brand-input" required>
            <option value="">-- Chọn ngành --</option>
            <option v-for="(label, key) in industries" :key="key" :value="key">{{ label }}</option>
          </select>
          <input v-model="discoverForm.style_preference" class="brand-input flex-1" placeholder="Phong cách (VD: minimalist, bold, luxury)..." />
          <button type="submit" class="btn-ai discover-btn" :disabled="discovering">
            <i :class="discovering ? 'pi pi-spin pi-spinner' : 'pi pi-sparkles'" /> {{ discovering ? 'Đang tìm...' : 'Discover' }}
          </button>
        </div>
      </form>
    </div>

    <!-- ═══ AI RESULTS ═══ -->
    <div v-if="results.length" class="results-section">
      <div class="results-header">
        <h2><i class="pi pi-star-fill" style="color:#f59e0b" /> Kết quả ({{ results.length }})</h2>
        <div class="results-actions">
          <button class="btn-outline" @click="copyAllForClient"><i class="pi pi-copy" /> Copy gửi khách</button>
          <button class="btn-primary" @click="showSaveDialog = true"><i class="pi pi-bookmark" /> Lưu Collection</button>
        </div>
      </div>

      <div class="website-cards">
        <div v-for="(site, idx) in results" :key="idx" class="website-card">
          <!-- Header -->
          <div class="wc-header">
            <div class="wc-score" :style="{ '--score-color': scoreColor(site.design_score) }">
              <svg viewBox="0 0 40 40">
                <circle cx="20" cy="20" r="17" fill="none" stroke="rgba(0,0,0,.06)" stroke-width="3" />
                <circle cx="20" cy="20" r="17" fill="none" :stroke="scoreColor(site.design_score)" stroke-width="3"
                  :stroke-dasharray="scoreDash(site.design_score)" stroke-linecap="round" transform="rotate(-90 20 20)" />
              </svg>
              <span>{{ site.design_score || '?' }}</span>
            </div>
            <div class="wc-title-area">
              <h4>{{ site.title }}</h4>
              <a :href="site.url" target="_blank" class="wc-url"><i class="pi pi-external-link" /> {{ site.url }}</a>
              <span class="wc-industry">{{ site.industry }}</span>
            </div>
          </div>

          <!-- Summary -->
          <p class="wc-summary">{{ site.summary }}</p>

          <!-- Highlights -->
          <div v-if="site.highlights" class="wc-highlights">
            <div v-for="(val, key) in site.highlights" :key="key" class="highlight-chip">
              <i :class="highlightIcon(key)" /> <strong>{{ highlightLabel(key) }}:</strong> {{ val }}
            </div>
          </div>

          <!-- Tech Stack -->
          <div v-if="site.tech_stack?.length" class="wc-tech">
            <span v-for="t in site.tech_stack" :key="t" class="tech-tag">{{ t }}</span>
          </div>

          <!-- Strengths -->
          <div v-if="site.strengths?.length" class="wc-strengths">
            <span class="strength-label"><i class="pi pi-check-circle" /> Điểm mạnh:</span>
            <ul><li v-for="s in site.strengths" :key="s">{{ s }}</li></ul>
          </div>

          <!-- Why Reference (discover mode) -->
          <div v-if="site.why_reference" class="wc-why">
            <i class="pi pi-info-circle" /> {{ site.why_reference }}
          </div>

          <!-- Client Summary -->
          <div v-if="site.client_summary" class="wc-client">
            <div class="client-label"><i class="pi pi-send" /> Gửi khách:</div>
            <div class="client-text">{{ site.client_summary }}</div>
            <button class="copy-btn" @click="copyText(site.client_summary)"><i class="pi pi-copy" /> Copy</button>
          </div>
        </div>
      </div>

      <!-- Overall Recommendation (discover mode) -->
      <div v-if="overallRecommendation" class="glass-card mt-1 overall-rec">
        <h3><i class="pi pi-lightbulb" style="color:#f59e0b" /> Gợi ý tổng hợp</h3>
        <p>{{ overallRecommendation }}</p>
        <button class="copy-btn" @click="copyText(overallRecommendation)"><i class="pi pi-copy" /> Copy</button>
      </div>
    </div>

    <!-- ═══ LOADING ANIMATION ═══ -->
    <div v-if="analyzing || discovering" class="loading-panel glass-card">
      <div class="loading-orb">
        <div class="orb-ring r1" />
        <div class="orb-ring r2" />
        <div class="orb-ring r3" />
        <i class="pi pi-globe" />
      </div>
      <p>{{ analyzing ? 'Đang phân tích website...' : 'Đang tìm kiếm showcase...' }}</p>
      <span class="loading-hint">AI đang research, có thể mất 10-30 giây</span>
    </div>

    <!-- ═══ SAVED COLLECTIONS ═══ -->
    <div v-if="collections.length" class="collections-section">
      <h2><i class="pi pi-folder" style="color:#8b5cf6" /> Collections đã lưu</h2>
      <div class="collection-grid">
        <div v-for="col in collections" :key="col.id" class="collection-card" @click="loadCollection(col)">
          <div class="col-header">
            <h4>{{ col.title }}</h4>
            <button class="remove-mini" @click.stop="deleteCollection(col)"><i class="pi pi-trash" /></button>
          </div>
          <p class="col-meta">
            <span v-if="col.industry"><i class="pi pi-tag" /> {{ industries[col.industry] || col.industry }}</span>
            <span v-if="col.client_name"><i class="pi pi-user" /> {{ col.client_name }}</span>
          </p>
          <span class="col-items">{{ col.items_count }} websites · {{ col.created_at }}</span>
        </div>
      </div>
    </div>

    <!-- ═══ SAVE DIALOG ═══ -->
    <div v-if="showSaveDialog" class="overlay" @click.self="showSaveDialog = false">
      <div class="dialog glass-card">
        <h3><i class="pi pi-bookmark" style="color:#8b5cf6" /> Lưu Collection</h3>
        <div class="form-group"><label>Tên Collection</label><input v-model="saveForm.title" class="brand-input" placeholder="VD: Tham khảo khách ABC" required /></div>
        <div class="form-group"><label>Ngành</label>
          <select v-model="saveForm.industry" class="brand-input">
            <option value="">--</option>
            <option v-for="(label, key) in industries" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div class="form-group"><label>Tên khách hàng (tùy chọn)</label><input v-model="saveForm.client_name" class="brand-input" placeholder="Tên khách..." /></div>
        <div class="form-group"><label>Ghi chú</label><textarea v-model="saveForm.notes" class="brand-input" rows="2" placeholder="Ghi chú..." /></div>
        <div class="dialog-footer">
          <button class="btn-outline" @click="showSaveDialog = false">Hủy</button>
          <button class="btn-primary" @click="saveCollection"><i class="pi pi-check" /> Lưu</button>
        </div>
      </div>
    </div>

    <!-- ═══ TOAST ═══ -->
    <transition name="toast">
      <div v-if="toast" class="toast">{{ toast }}</div>
    </transition>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import axios from 'axios'

export default {
  components: { Head },
  layout: Layout,
  props: { collections: Array, industries: Object },
  data() {
    return {
      activePanel: 'analyze',
      analyzing: false,
      discovering: false,
      results: [],
      overallRecommendation: '',
      toast: '',
      showSaveDialog: false,
      analyzeForm: { url: '' },
      discoverForm: { industry: '', style_preference: '' },
      saveForm: { title: '', industry: '', client_name: '', notes: '' },
    }
  },
  methods: {
    async analyzeUrl() {
      this.analyzing = true
      this.results = []
      this.overallRecommendation = ''
      try {
        const { data } = await axios.post('/showcase/analyze-url', this.analyzeForm)
        if (data.success && data.analysis) {
          this.results = [data.analysis]
          this.showToast('Phân tích hoàn tất!')
        } else {
          this.showToast(data.message || 'Lỗi phân tích')
        }
      } catch (e) {
        this.showToast(e.response?.data?.message || 'Lỗi kết nối AI')
      }
      this.analyzing = false
    },
    async discoverByIndustry() {
      this.discovering = true
      this.results = []
      this.overallRecommendation = ''
      try {
        const { data } = await axios.post('/showcase/discover', this.discoverForm)
        if (data.success && data.websites) {
          this.results = data.websites.websites || data.websites || []
          this.overallRecommendation = data.websites.overall_recommendation || ''
          this.showToast(`Tìm thấy ${this.results.length} websites!`)
        } else {
          this.showToast(data.message || 'Lỗi tìm kiếm')
        }
      } catch (e) {
        this.showToast(e.response?.data?.message || 'Lỗi kết nối AI')
      }
      this.discovering = false
    },
    async saveCollection() {
      try {
        const items = this.results.map(r => ({
          url: r.url, title: r.title, industry: r.industry,
          analysis: r, source: 'ai_discovered', is_own_project: false,
        }))
        await axios.post('/showcase/collections', { ...this.saveForm, items })
        this.showSaveDialog = false
        this.showToast('Collection đã lưu!')
        this.$inertia.reload({ only: ['collections'] })
      } catch (e) {
        this.showToast('Lỗi lưu collection')
      }
    },
    deleteCollection(col) {
      if (!confirm(`Xóa "${col.title}"?`)) return
      this.$inertia.delete(`/showcase/collections/${col.id}`, { preserveScroll: true })
    },
    loadCollection(col) {
      this.results = col.items.map(i => i.analysis || { url: i.url, title: i.title, industry: i.industry })
      this.overallRecommendation = ''
      this.showToast(`Đã load "${col.title}"`)
    },
    copyText(text) {
      navigator.clipboard.writeText(text)
      this.showToast('Đã copy!')
    },
    copyAllForClient() {
      const text = this.results.map((s, i) => `${i+1}. ${s.title}\n   ${s.url}\n   ${s.client_summary || s.summary || ''}`).join('\n\n')
      const full = `📋 Website Showcase References:\n\n${text}${this.overallRecommendation ? '\n\n💡 ' + this.overallRecommendation : ''}`
      navigator.clipboard.writeText(full)
      this.showToast('Đã copy tất cả cho khách!')
    },
    showToast(msg) { this.toast = msg; setTimeout(() => this.toast = '', 3000) },
    scoreColor(s) { if (s >= 8) return '#10b981'; if (s >= 6) return '#f59e0b'; return '#ef4444' },
    scoreDash(s) { const c = 2*Math.PI*17; return `${((s||0)/10)*c} ${c}` },
    highlightIcon(k) { return { layout:'pi pi-th-large', typography:'pi pi-align-left', color_scheme:'pi pi-palette', ux_flow:'pi pi-arrows-alt', animation:'pi pi-play', mobile_friendly:'pi pi-mobile' }[k] || 'pi pi-check' },
    highlightLabel(k) { return { layout:'Layout', typography:'Typography', color_scheme:'Colors', ux_flow:'UX Flow', animation:'Animation', mobile_friendly:'Mobile' }[k] || k },
    orbStyle(n) {
      const s = 60 + Math.random() * 100
      return { width: s+'px', height: s+'px', left: Math.random()*100+'%', top: Math.random()*100+'%', animationDelay: n*0.3+'s', animationDuration: (10+Math.random()*8)+'s' }
    },
  },
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
.showcase-page { font-family:'Inter', sans-serif }

/* ═══ HERO ═══ */
.hero { position:relative; overflow:hidden; border-radius:20px; padding:2.25rem 2.5rem 2rem; margin-bottom:1.25rem; background:linear-gradient(135deg, #0a1628 0%, #1a1050 40%, #2d1b69 100%); color:white; min-height:160px }
.hero-bg { position:absolute; inset:0; overflow:hidden }
.hero-orb { position:absolute; border-radius:50%; background:radial-gradient(circle, rgba(99,102,241,.12), transparent 70%); animation:orbFloat linear infinite }
@keyframes orbFloat { 0%,100% { transform:translateY(0) scale(1) } 50% { transform:translateY(-20px) scale(1.1) } }
.hero-content { position:relative; z-index:2 }
.hero-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.5rem; font-weight:700; letter-spacing:.1em; padding:.3rem .7rem; border-radius:16px; background:rgba(99,102,241,.18); border:1px solid rgba(99,102,241,.25); color:#a5b4fc; margin-bottom:.6rem }
.hero h1 { font-size:1.8rem; font-weight:900; margin:0 0 .3rem; letter-spacing:-.03em }
.grad-text { background:linear-gradient(135deg, #818cf8, #c084fc); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text }
.hero p { font-size:.78rem; color:#a5b4fc; margin:0; max-width:460px }

/* ═══ ACTION CARDS ═══ */
.action-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem }
.action-card { background:white; border:2px solid #e8ecf2; border-radius:16px; padding:1.5rem; cursor:pointer; transition:all .25s; text-align:center }
.action-card:hover { border-color:#c7d2fe; transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.06) }
.action-card.active { border-color:#818cf8; background:linear-gradient(135deg, #faf5ff, #eef2ff) }
.action-icon { width:52px; height:52px; border-radius:16px; display:flex; align-items:center; justify-content:center; margin:0 auto .75rem; font-size:1.3rem }
.analyze-icon { background:linear-gradient(135deg, #6366f1, #818cf8); color:white }
.discover-icon { background:linear-gradient(135deg, #f59e0b, #fbbf24); color:white }
.action-card h3 { font-size:.92rem; font-weight:800; color:#0f172a; margin:0 0 .3rem }
.action-card p { font-size:.68rem; color:#64748b; margin:0 }

/* ═══ PANELS ═══ */
.panel { margin-bottom:1rem }
.glass-card { background:white; border:1px solid #e8ecf2; border-radius:18px; padding:1.35rem; transition:all .25s }
.glass-card h3 { font-size:.88rem; font-weight:800; color:#0f172a; margin:0 0 .7rem; display:flex; align-items:center; gap:.4rem }
.mt-1 { margin-top:.85rem }
.input-row { display:flex; gap:.5rem; align-items:center }
.flex-1 { flex:1 }
.brand-input { width:100%; padding:.55rem .7rem; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.75rem; font-family:inherit; color:#1e293b; background:#fafbfc; transition:all .2s; outline:none }
.brand-input:focus { border-color:#818cf8; background:white; box-shadow:0 0 0 3px rgba(99,102,241,.08) }
.discover-form .input-row { flex-wrap:wrap }
.btn-ai { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.3rem; font-size:.75rem; font-weight:700; border:none; border-radius:10px; background:linear-gradient(135deg, #6366f1, #818cf8); color:white; cursor:pointer; font-family:inherit; transition:all .2s; box-shadow:0 2px 10px rgba(99,102,241,.3); white-space:nowrap }
.btn-ai:hover { transform:translateY(-1px) }
.btn-ai:disabled { opacity:.5; cursor:not-allowed; transform:none }
.discover-btn { background:linear-gradient(135deg, #f59e0b, #fbbf24); box-shadow:0 2px 10px rgba(245,158,11,.3) }
.btn-primary { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem 1.1rem; font-size:.72rem; font-weight:700; border:none; border-radius:10px; background:linear-gradient(135deg, #7c3aed, #a855f7); color:white; cursor:pointer; font-family:inherit; transition:all .2s }
.btn-outline { display:inline-flex; align-items:center; gap:.3rem; padding:.5rem 1rem; font-size:.72rem; font-weight:700; border:1.5px solid #e2e8f0; border-radius:10px; background:white; color:#475569; cursor:pointer; font-family:inherit; transition:all .2s }
.btn-outline:hover { border-color:#818cf8; color:#6366f1 }

/* ═══ LOADING ═══ */
.loading-panel { display:flex; flex-direction:column; align-items:center; padding:2.5rem 1rem; margin-bottom:1rem }
.loading-orb { position:relative; width:80px; height:80px; display:flex; align-items:center; justify-content:center; margin-bottom:1rem }
.loading-orb i { font-size:1.5rem; color:#6366f1; position:relative; z-index:2 }
.orb-ring { position:absolute; inset:0; border-radius:50%; border:2px solid transparent }
.r1 { border-top-color:#6366f1; animation:spin 1.2s linear infinite }
.r2 { inset:8px; border-right-color:#a855f7; animation:spin 1.8s linear infinite reverse }
.r3 { inset:16px; border-bottom-color:#f59e0b; animation:spin 2.4s linear infinite }
@keyframes spin { to { transform:rotate(360deg) } }
.loading-panel p { font-size:.82rem; font-weight:700; color:#0f172a; margin:0 }
.loading-hint { font-size:.6rem; color:#94a3b8; margin-top:.2rem }

/* ═══ RESULTS ═══ */
.results-section { margin-bottom:1.25rem }
.results-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.85rem }
.results-header h2 { font-size:1.1rem; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:.4rem }
.results-actions { display:flex; gap:.4rem }

/* ═══ WEBSITE CARD ═══ */
.website-cards { display:flex; flex-direction:column; gap:.85rem }
.website-card { background:white; border:1px solid #e8ecf2; border-radius:18px; padding:1.35rem; transition:all .25s }
.website-card:hover { border-color:#c7d2fe; box-shadow:0 4px 16px rgba(0,0,0,.04) }
.wc-header { display:flex; gap:.85rem; align-items:flex-start; margin-bottom:.65rem }
.wc-score { position:relative; width:44px; height:44px; flex-shrink:0 }
.wc-score svg { width:100%; height:100% }
.wc-score span { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:900; color:var(--score-color) }
.wc-title-area h4 { font-size:.88rem; font-weight:800; color:#0f172a; margin:0 0 .15rem }
.wc-url { font-size:.6rem; color:#6366f1; text-decoration:none; display:flex; align-items:center; gap:.2rem; transition:color .15s }
.wc-url:hover { color:#4f46e5 }
.wc-url i { font-size:.45rem }
.wc-industry { display:inline-block; font-size:.48rem; font-weight:600; color:#8b5cf6; background:#ede9fe; padding:.1rem .35rem; border-radius:5px; margin-top:.2rem }
.wc-summary { font-size:.72rem; color:#475569; line-height:1.55; margin:0 0 .65rem }

/* Highlights */
.wc-highlights { display:flex; flex-direction:column; gap:.3rem; margin-bottom:.65rem }
.highlight-chip { font-size:.62rem; color:#334155; display:flex; align-items:flex-start; gap:.25rem; padding:.3rem .5rem; background:#f8fafc; border-radius:8px }
.highlight-chip i { font-size:.52rem; color:#6366f1; margin-top:.05rem; flex-shrink:0 }
.highlight-chip strong { color:#0f172a; margin-right:.15rem }

/* Tech Stack */
.wc-tech { display:flex; flex-wrap:wrap; gap:.25rem; margin-bottom:.65rem }
.tech-tag { font-size:.5rem; font-weight:700; padding:.15rem .45rem; border-radius:6px; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0 }

/* Strengths */
.wc-strengths { margin-bottom:.65rem }
.strength-label { display:flex; align-items:center; gap:.2rem; font-size:.6rem; font-weight:700; color:#10b981; margin-bottom:.25rem }
.wc-strengths ul { margin:0; padding-left:1.2rem }
.wc-strengths li { font-size:.65rem; color:#475569; margin-bottom:.15rem }

/* Why */
.wc-why { font-size:.65rem; color:#6366f1; background:#eef2ff; border-radius:10px; padding:.5rem .7rem; margin-bottom:.65rem; display:flex; align-items:flex-start; gap:.3rem }
.wc-why i { flex-shrink:0; margin-top:.1rem }

/* Client Summary */
.wc-client { background:#fefce8; border:1px solid #fef08a; border-radius:12px; padding:.65rem .85rem; position:relative }
.client-label { font-size:.55rem; font-weight:700; color:#a16207; margin-bottom:.2rem; display:flex; align-items:center; gap:.2rem }
.client-text { font-size:.7rem; color:#713f12; line-height:1.5 }
.copy-btn { display:inline-flex; align-items:center; gap:.2rem; padding:.2rem .5rem; font-size:.52rem; font-weight:700; border:1px solid #e5e7eb; border-radius:6px; background:white; color:#475569; cursor:pointer; font-family:inherit; margin-top:.35rem; transition:all .15s }
.copy-btn:hover { border-color:#818cf8; color:#6366f1 }
.overall-rec { position:relative }
.overall-rec p { font-size:.75rem; color:#475569; line-height:1.6; margin:0 }

/* ═══ COLLECTIONS ═══ */
.collections-section { margin-top:1.5rem }
.collections-section h2 { font-size:1rem; font-weight:800; color:#0f172a; margin:0 0 .75rem; display:flex; align-items:center; gap:.4rem }
.collection-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:.65rem }
.collection-card { background:white; border:1px solid #e8ecf2; border-radius:14px; padding:1rem; cursor:pointer; transition:all .2s }
.collection-card:hover { border-color:#c7d2fe; box-shadow:0 4px 12px rgba(0,0,0,.04); transform:translateY(-1px) }
.col-header { display:flex; justify-content:space-between; align-items:flex-start }
.col-header h4 { font-size:.78rem; font-weight:800; color:#0f172a; margin:0 }
.remove-mini { width:22px; height:22px; border-radius:6px; border:none; background:#fef2f2; color:#ef4444; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:.45rem; transition:all .15s; opacity:.4 }
.remove-mini:hover { opacity:1; background:#fee2e2 }
.col-meta { font-size:.58rem; color:#64748b; margin:.2rem 0 .3rem; display:flex; gap:.6rem }
.col-meta i { font-size:.45rem }
.col-items { font-size:.52rem; color:#94a3b8; font-weight:600 }

/* ═══ DIALOG ═══ */
.overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; z-index:100 }
.dialog { width:90%; max-width:420px; padding:1.5rem }
.dialog h3 { margin-bottom:1rem }
.form-group { margin-bottom:.65rem }
.form-group label { display:block; font-size:.62rem; font-weight:600; color:#475569; margin-bottom:.2rem }
.dialog-footer { display:flex; justify-content:flex-end; gap:.4rem; margin-top:1rem }

/* ═══ TOAST ═══ */
.toast { position:fixed; bottom:2rem; right:2rem; background:#0f172a; color:white; padding:.6rem 1.2rem; border-radius:10px; font-size:.72rem; font-weight:600; z-index:200; box-shadow:0 4px 16px rgba(0,0,0,.2) }
.toast-enter-active, .toast-leave-active { transition:all .3s }
.toast-enter-from, .toast-leave-to { opacity:0; transform:translateY(10px) }

@media (max-width:768px) {
  .action-grid { grid-template-columns:1fr }
  .input-row { flex-direction:column }
  .results-header { flex-direction:column; gap:.5rem }
  .collection-grid { grid-template-columns:1fr }
}
</style>
