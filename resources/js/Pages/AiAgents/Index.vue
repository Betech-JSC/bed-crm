<template>
  <div class="ai-agents-page">
    <Head title="AI Agents" />

    <!-- ═══ HERO BANNER ═══ -->
    <div class="hero-banner">
      <div class="hero-particles">
        <div v-for="n in 20" :key="n" class="particle" :style="particleStyle(n)" />
      </div>
      <div class="hero-glow" />
      <div class="hero-content">
        <div class="hero-badge"><i class="pi pi-sparkles" /> AI AGENT PLATFORM</div>
        <h1>AI Agents <span class="gradient-text">Intelligence</span></h1>
        <p>{{ agents.length }} agents chuyên biệt — Tự động hóa mọi nghiệp vụ với AI</p>
        <div class="hero-actions">
          <button class="btn-glow" @click="openDialog()"><i class="pi pi-plus" /> Tạo Agent Mới</button>
          <button class="btn-glass" @click="$inertia.visit('/ai-data-hub')"><i class="pi pi-database" /> Data Hub</button>
        </div>
      </div>
      <!-- Stats Ring -->
      <div class="hero-stats">
        <div v-for="stat in heroStats" :key="stat.label" class="hero-stat-ring">
          <svg viewBox="0 0 60 60">
            <circle cx="30" cy="30" r="24" fill="none" :stroke="stat.trackColor" stroke-width="3" />
            <circle cx="30" cy="30" r="24" fill="none" :stroke="stat.color" stroke-width="3"
              :stroke-dasharray="stat.dashArray" stroke-dashoffset="0" stroke-linecap="round"
              transform="rotate(-90 30 30)" class="ring-progress" />
          </svg>
          <div class="ring-label">
            <span class="ring-val">{{ stat.value }}</span>
            <span class="ring-lbl">{{ stat.label }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ AGENT GRID ═══ -->
    <div v-if="agents.length" class="agent-grid">
      <div v-for="(agent, idx) in agents" :key="agent.id" class="agent-card" :class="{ inactive: !agent.is_active }"
        :style="{ '--delay': idx * 80 + 'ms', '--accent': agent.type_meta.color }">

        <!-- Glow Background -->
        <div class="card-glow" :style="{ background: `radial-gradient(ellipse at 30% 10%, ${agent.type_meta.color}12 0%, transparent 70%)` }" />

        <!-- Header -->
        <div class="card-header">
          <div class="agent-avatar-wrap">
            <div class="avatar-ring" :style="{ borderColor: agent.type_meta.color + '40' }">
              <div class="avatar-inner" :style="{ background: `linear-gradient(135deg, ${agent.type_meta.color}20, ${agent.type_meta.color}08)` }">
                <i :class="agent.type_meta.icon" :style="{ color: agent.type_meta.color }" />
              </div>
            </div>
            <span class="status-indicator" :class="agent.is_active ? 'online' : 'offline'" />
          </div>
          <div class="card-title-area">
            <h3>{{ agent.name }}</h3>
            <div class="type-chip" :style="{ background: agent.type_meta.color + '12', color: agent.type_meta.color, borderColor: agent.type_meta.color + '25' }">
              <i :class="agent.type_meta.icon" /> {{ agent.type_meta.label }}
            </div>
          </div>
          <button class="more-btn" @click.stop="toggleMenu(agent.id)">
            <i class="pi pi-ellipsis-v" />
          </button>
          <!-- Dropdown -->
          <transition name="dropdown">
            <div v-if="openMenu === agent.id" class="card-dropdown" @click.stop>
              <button @click="openDialog(agent); openMenu = null"><i class="pi pi-cog" /> Cấu hình</button>
              <button @click="toggleAgent(agent); openMenu = null"><i :class="agent.is_active ? 'pi pi-pause' : 'pi pi-play'" /> {{ agent.is_active ? 'Tạm dừng' : 'Kích hoạt' }}</button>
              <button class="danger" @click="deleteAgent(agent); openMenu = null"><i class="pi pi-trash" /> Xóa</button>
            </div>
          </transition>
        </div>

        <!-- Description -->
        <p class="card-desc">{{ agent.description || agent.type_meta.description }}</p>

        <!-- Stats -->
        <div class="card-stats">
          <div class="mini-stat">
            <div class="mini-stat-icon"><i class="pi pi-comments" /></div>
            <div>
              <span class="mini-val">{{ formatNumber(agent.total_conversations) }}</span>
              <span class="mini-lbl">Chats</span>
            </div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-icon"><i class="pi pi-envelope" /></div>
            <div>
              <span class="mini-val">{{ formatNumber(agent.total_messages) }}</span>
              <span class="mini-lbl">Messages</span>
            </div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-icon star"><i class="pi pi-star-fill" /></div>
            <div>
              <span class="mini-val">{{ agent.avg_satisfaction || '—' }}</span>
              <span class="mini-lbl">Rating</span>
            </div>
          </div>
        </div>

        <!-- Model & KB -->
        <div class="card-meta-row">
          <div v-if="agent.model_config" class="meta-chip model-chip">
            <i class="pi pi-microchip" /> {{ agent.model_config.provider }}
          </div>
          <div v-if="agent.knowledge_base_ids?.length" class="meta-chip kb-chip">
            <i class="pi pi-database" /> {{ agent.knowledge_base_ids.length }} KB
          </div>
          <div v-if="agent.tools?.length" class="meta-chip tool-chip">
            <i class="pi pi-wrench" /> {{ agent.tools.length }} Tools
          </div>
        </div>

        <!-- Chat Button -->
        <button class="chat-btn" @click="$inertia.visit(`/ai-agents/${agent.id}/chat`)"
          :style="{ background: `linear-gradient(135deg, ${agent.type_meta.color}, ${agent.type_meta.color}cc)` }">
          <i class="pi pi-comments" /> Chat với {{ agent.name }}
          <span class="btn-shimmer" />
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-hero">
      <div class="empty-orb"><i class="pi pi-sparkles" /></div>
      <h2>Tạo AI Agent đầu tiên</h2>
      <p>Bắt đầu sử dụng AI chuyên biệt cho từng nghiệp vụ — Sales, Support, Content, Analytics, HR.</p>
      <button class="btn-glow" @click="openDialog()"><i class="pi pi-plus" /> Tạo Agent</button>
    </div>

    <!-- ═══ CREATE/EDIT DIALOG ═══ -->
    <transition name="modal">
      <div v-if="dialog" class="dialog-overlay" @click.self="dialog = false">
        <div class="dialog-card">
          <div class="dialog-header">
            <div class="dialog-title-wrap">
              <div class="dialog-icon-wrap"><i class="pi pi-sparkles" /></div>
              <div>
                <h3>{{ form.id ? 'Cấu hình' : 'Tạo' }} AI Agent</h3>
                <span class="dialog-subtitle">Thiết lập nhân cách và khả năng cho AI</span>
              </div>
            </div>
            <button class="dialog-close" @click="dialog = false"><i class="pi pi-times" /></button>
          </div>
          <form @submit.prevent="submitForm" class="dialog-body">
            <div class="form-section">
              <div class="section-label"><i class="pi pi-id-card" /> Thông tin cơ bản</div>
              <div class="form-row">
                <div class="form-group flex-2"><label>Tên Agent <span class="req">*</span></label><InputText v-model="form.name" class="w-full" placeholder="VD: Sales Coach AI" /></div>
                <div class="form-group flex-1"><label>Loại</label>
                  <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
              </div>
              <div class="form-group"><label>Mô tả</label><Textarea v-model="form.description" rows="2" class="w-full" placeholder="Mô tả chức năng chính..." /></div>
            </div>

            <div class="form-section">
              <div class="section-label"><i class="pi pi-code" /> System Prompt</div>
              <div class="form-group"><Textarea v-model="form.system_prompt" rows="5" class="w-full prompt-textarea" placeholder="Bạn là AI agent chuyên về..." /></div>
            </div>

            <div class="form-section">
              <div class="section-label"><i class="pi pi-database" /> Knowledge Bases</div>
              <div class="kb-checklist">
                <label v-for="kb in knowledgeBases" :key="kb.id" class="kb-check-item">
                  <input type="checkbox" :value="kb.id" v-model="form.knowledge_base_ids" />
                  <span class="check-visual" /><span>{{ kb.name }}</span><small>{{ kb.type }}</small>
                </label>
                <p v-if="!knowledgeBases.length" class="text-muted">Chưa có Knowledge Base. <a href="/ai-data-hub">Tạo tại Data Hub →</a></p>
              </div>
            </div>

            <div class="form-section">
              <div class="section-label"><i class="pi pi-wrench" /> Tools & Capabilities</div>
              <div class="tools-grid">
                <label v-for="(meta, key) in agentTools" :key="key" class="tool-check-item" :class="{ checked: form.tools?.includes(key) }">
                  <input type="checkbox" :value="key" v-model="form.tools" />
                  <i :class="meta.icon" /><span>{{ meta.label }}</span>
                </label>
              </div>
            </div>

            <div class="form-section">
              <div class="section-label"><i class="pi pi-sliders-h" /> Model Config</div>
              <div class="form-row form-row-3">
                <div class="form-group"><label>Provider</label>
                  <Select v-model="form.model_config.provider" :options="providerOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div class="form-group"><label>Temperature</label>
                  <InputNumber v-model="form.model_config.temperature" :min="0" :max="2" :step="0.1" :maxFractionDigits="1" class="w-full" />
                </div>
                <div class="form-group"><label>Max Tokens</label>
                  <InputNumber v-model="form.model_config.max_tokens" :min="256" :max="8192" :step="256" class="w-full" />
                </div>
              </div>
            </div>

            <div class="dialog-footer">
              <button type="button" class="btn-secondary" @click="dialog = false">Hủy</button>
              <button type="submit" class="btn-primary" :disabled="form.processing">
                <i :class="form.processing ? 'pi pi-spin pi-spinner' : 'pi pi-check'" />
                {{ form.id ? 'Cập nhật' : 'Tạo Agent' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'

export default {
  components: { Head, Button, InputText, InputNumber, Textarea, Select },
  layout: Layout,
  props: { agents: Array, agentTypes: Object, agentTools: Object, knowledgeBases: Array },
  data() {
    return {
      dialog: false,
      openMenu: null,
      form: this.emptyForm(),
      providerOptions: [
        { label: 'Google Gemini', value: 'gemini' },
        { label: 'OpenAI', value: 'openai' },
        { label: 'Anthropic Claude', value: 'claude' },
        { label: 'DeepSeek', value: 'deepseek' },
        { label: 'Groq', value: 'groq' },
      ],
    }
  },
  computed: {
    typeOptions() { return Object.entries(this.agentTypes).map(([k, v]) => ({ label: v.label, value: k })) },
    heroStats() {
      const total = this.agents.length || 1
      const active = this.agents.filter(a => a.is_active).length
      const totalConv = this.agents.reduce((s, a) => s + a.total_conversations, 0)
      const avgRating = this.agents.filter(a => a.avg_satisfaction).reduce((s, a) => s + parseFloat(a.avg_satisfaction), 0) / (this.agents.filter(a => a.avg_satisfaction).length || 1)
      const circ = 2 * Math.PI * 24
      return [
        { label: 'Active', value: active + '/' + total, color: '#10b981', trackColor: '#10b98118', dashArray: `${(active/total)*circ} ${circ}` },
        { label: 'Chats', value: this.formatNumber(totalConv), color: '#8b5cf6', trackColor: '#8b5cf618', dashArray: `${Math.min(totalConv/1000, 1)*circ} ${circ}` },
        { label: 'Rating', value: avgRating.toFixed(1), color: '#f59e0b', trackColor: '#f59e0b18', dashArray: `${(avgRating/5)*circ} ${circ}` },
      ]
    },
  },
  methods: {
    emptyForm() {
      return this.$inertia.form({
        id: null, name: '', type: 'sales', description: '', system_prompt: '',
        knowledge_base_ids: [], tools: ['search_knowledge', 'query_crm'],
        model_config: { provider: 'gemini', model: 'gemini-2.5-flash', temperature: 0.7, max_tokens: 4096 },
      })
    },
    openDialog(agent = null) {
      if (agent) {
        this.form = this.$inertia.form({
          id: agent.id, name: agent.name, type: agent.type,
          description: agent.description || '', system_prompt: agent.system_prompt || '',
          knowledge_base_ids: agent.knowledge_base_ids || [],
          tools: agent.tools || [],
          model_config: agent.model_config || { provider: 'gemini', model: 'gemini-2.5-flash', temperature: 0.7, max_tokens: 4096 },
        })
      } else { this.form = this.emptyForm() }
      this.dialog = true
    },
    submitForm() {
      if (this.form.id) {
        this.form.put(`/ai-agents/${this.form.id}`, { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      } else {
        this.form.post('/ai-agents', { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      }
    },
    toggleAgent(agent) { this.$inertia.post(`/ai-agents/${agent.id}/toggle`, {}, { preserveScroll: true }) },
    deleteAgent(agent) { if (confirm(`Xóa agent "${agent.name}"?`)) this.$inertia.delete(`/ai-agents/${agent.id}`, { preserveScroll: true }) },
    toggleMenu(id) { this.openMenu = this.openMenu === id ? null : id },
    formatNumber(n) { return n >= 1000 ? (n/1000).toFixed(1) + 'k' : n },
    particleStyle(n) {
      const x = Math.random() * 100, y = Math.random() * 100, d = 3 + Math.random() * 12, dur = 8 + Math.random() * 18
      return { left: x+'%', top: y+'%', width: d+'px', height: d+'px', animationDuration: dur+'s', animationDelay: (n * 0.3)+'s' }
    },
  },
  mounted() {
    document.addEventListener('click', () => { this.openMenu = null })
  },
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.ai-agents-page { font-family: 'Inter', sans-serif; }

/* ═══ HERO ═══ */
.hero-banner { position:relative; overflow:hidden; border-radius:20px; padding:2.5rem 2.5rem 2rem; margin-bottom:1.5rem; background:linear-gradient(135deg, #0f0a1e 0%, #1a1145 30%, #2d1b69 60%, #1a1145 100%); color:white; min-height:200px }
.hero-glow { position:absolute; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle, rgba(139,92,246,.25) 0%, transparent 70%); top:-120px; right:-80px; animation:pulseGlow 6s ease-in-out infinite }
@keyframes pulseGlow { 0%,100% { opacity:.6; transform:scale(1) } 50% { opacity:1; transform:scale(1.15) } }
.hero-particles { position:absolute; inset:0; overflow:hidden }
.particle { position:absolute; border-radius:50%; background:rgba(139,92,246,.3); animation:particleFloat linear infinite }
@keyframes particleFloat { 0% { transform:translateY(0) rotate(0deg); opacity:0 } 10% { opacity:.6 } 90% { opacity:.6 } 100% { transform:translateY(-150px) rotate(360deg); opacity:0 } }
.hero-content { position:relative; z-index:2 }
.hero-badge { display:inline-flex; align-items:center; gap:.4rem; font-size:.58rem; font-weight:700; letter-spacing:.1em; padding:.35rem .75rem; border-radius:20px; background:rgba(139,92,246,.2); border:1px solid rgba(139,92,246,.3); color:#c4b5fd; margin-bottom:.75rem; backdrop-filter:blur(8px) }
.hero-badge i { font-size:.5rem }
.hero-content h1 { font-size:2rem; font-weight:900; margin:0 0 .4rem; letter-spacing:-.03em; line-height:1.1 }
.gradient-text { background:linear-gradient(135deg, #a78bfa, #c084fc, #f0abfc); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text }
.hero-content p { font-size:.85rem; color:#a5b4fc; margin:0; max-width:420px; line-height:1.5 }
.hero-actions { display:flex; gap:.6rem; margin-top:1.25rem }
.btn-glow { display:inline-flex; align-items:center; gap:.45rem; padding:.6rem 1.4rem; font-size:.78rem; font-weight:700; border:none; border-radius:12px; color:white; cursor:pointer; font-family:inherit; background:linear-gradient(135deg, #7c3aed, #a855f7); box-shadow:0 4px 20px rgba(139,92,246,.4); transition:all .25s }
.btn-glow:hover { transform:translateY(-2px); box-shadow:0 8px 30px rgba(139,92,246,.5) }
.btn-glass { display:inline-flex; align-items:center; gap:.45rem; padding:.6rem 1.4rem; font-size:.78rem; font-weight:600; border:1px solid rgba(255,255,255,.15); border-radius:12px; color:#c4b5fd; cursor:pointer; font-family:inherit; background:rgba(255,255,255,.06); backdrop-filter:blur(10px); transition:all .25s }
.btn-glass:hover { background:rgba(255,255,255,.12); border-color:rgba(255,255,255,.25); color:white }

/* Hero Stats */
.hero-stats { position:absolute; right:2.5rem; top:50%; transform:translateY(-50%); display:flex; gap:1.25rem; z-index:2 }
.hero-stat-ring { position:relative; width:80px; height:80px }
.hero-stat-ring svg { width:100%; height:100% }
.ring-progress { transition:stroke-dasharray 1.5s cubic-bezier(.4,0,.2,1) }
.ring-label { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center }
.ring-val { font-size:.82rem; font-weight:800; color:white }
.ring-lbl { font-size:.48rem; color:#a5b4fc; text-transform:uppercase; letter-spacing:.05em }

/* ═══ AGENT GRID ═══ */
.agent-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:1rem; }
.agent-card { position:relative; background:white; border-radius:20px; padding:1.5rem; border:1px solid #e8ecf2; overflow:hidden; transition:all .35s cubic-bezier(.4,0,.2,1); animation:cardEntrance .5s ease-out both; animation-delay:var(--delay) }
@keyframes cardEntrance { from { opacity:0; transform:translateY(16px) } to { opacity:1; transform:translateY(0) } }
.agent-card:hover { transform:translateY(-4px); box-shadow:0 12px 40px rgba(0,0,0,.08); border-color:color-mix(in srgb, var(--accent) 20%, #e8ecf2) }
.agent-card.inactive { opacity:.5; filter:grayscale(.3) }
.card-glow { position:absolute; inset:0; pointer-events:none; transition:opacity .4s }
.agent-card:hover .card-glow { opacity:1.5 }

/* Header */
.card-header { display:flex; align-items:flex-start; gap:.75rem; margin-bottom:.75rem; position:relative }
.agent-avatar-wrap { position:relative }
.avatar-ring { width:48px; height:48px; border:2px solid; border-radius:14px; padding:3px; flex-shrink:0 }
.avatar-inner { width:100%; height:100%; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem }
.status-indicator { position:absolute; bottom:-2px; right:-2px; width:12px; height:12px; border-radius:50%; border:2.5px solid white }
.status-indicator.online { background:#10b981; box-shadow:0 0 6px rgba(16,185,129,.5) }
.status-indicator.offline { background:#94a3b8 }
.card-title-area { flex:1; min-width:0 }
.card-title-area h3 { font-size:.92rem; font-weight:800; color:#0f172a; margin:0 0 .25rem; letter-spacing:-.01em }
.type-chip { display:inline-flex; align-items:center; gap:.25rem; font-size:.55rem; font-weight:700; padding:.15rem .5rem; border-radius:6px; border:1px solid; text-transform:uppercase; letter-spacing:.03em }
.type-chip i { font-size:.5rem }
.more-btn { position:absolute; top:0; right:0; background:none; border:none; width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s }
.more-btn:hover { background:#f1f5f9; color:#475569 }

/* Dropdown */
.card-dropdown { position:absolute; top:32px; right:0; background:white; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 12px 32px rgba(0,0,0,.12); padding:.35rem; z-index:10; min-width:150px }
.card-dropdown button { display:flex; align-items:center; gap:.5rem; width:100%; padding:.45rem .65rem; font-size:.72rem; font-weight:500; color:#334155; border:none; background:none; border-radius:8px; cursor:pointer; font-family:inherit; transition:all .15s }
.card-dropdown button:hover { background:#f1f5f9 }
.card-dropdown button.danger { color:#ef4444 }
.card-dropdown button.danger:hover { background:#fef2f2 }
.card-dropdown button i { font-size:.62rem; width:16px }
.dropdown-enter-active, .dropdown-leave-active { transition:all .2s ease }
.dropdown-enter-from, .dropdown-leave-to { opacity:0; transform:translateY(-6px) scale(.95) }

/* Description */
.card-desc { font-size:.72rem; color:#64748b; line-height:1.6; margin:0 0 .85rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }

/* Stats */
.card-stats { display:flex; gap:.5rem; margin-bottom:.85rem }
.mini-stat { display:flex; align-items:center; gap:.4rem; flex:1; padding:.45rem .55rem; background:#f8fafc; border-radius:10px; border:1px solid #f1f5f9 }
.mini-stat-icon { width:26px; height:26px; border-radius:8px; background:#ede9fe; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:.55rem; flex-shrink:0 }
.mini-stat-icon.star { background:#fef3c7; color:#f59e0b }
.mini-val { display:block; font-size:.78rem; font-weight:800; color:#0f172a; line-height:1.1 }
.mini-lbl { font-size:.45rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em }

/* Meta */
.card-meta-row { display:flex; gap:.3rem; margin-bottom:.85rem; flex-wrap:wrap }
.meta-chip { display:inline-flex; align-items:center; gap:.25rem; font-size:.52rem; font-weight:600; padding:.18rem .45rem; border-radius:6px; }
.meta-chip i { font-size:.45rem }
.model-chip { background:#f0f9ff; color:#0284c7; border:1px solid #bae6fd }
.kb-chip { background:#faf5ff; color:#7c3aed; border:1px solid #e9d5ff }
.tool-chip { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0 }

/* Chat Button */
.chat-btn { position:relative; display:flex; align-items:center; justify-content:center; gap:.5rem; width:100%; padding:.65rem; border:none; border-radius:12px; color:white; font-size:.78rem; font-weight:700; cursor:pointer; font-family:inherit; overflow:hidden; transition:all .25s; box-shadow:0 2px 8px rgba(0,0,0,.1) }
.chat-btn:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(0,0,0,.15) }
.chat-btn i { font-size:.72rem }
.btn-shimmer { position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent); transition:left .6s }
.chat-btn:hover .btn-shimmer { left:100% }

/* ═══ EMPTY ═══ */
.empty-hero { text-align:center; padding:4rem 2rem; background:linear-gradient(135deg, #faf5ff, #ede9fe, #f0f9ff); border-radius:24px; border:2px dashed #c4b5fd }
.empty-orb { width:72px; height:72px; border-radius:20px; background:linear-gradient(135deg, #7c3aed, #a855f7); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; color:white; font-size:1.5rem; box-shadow:0 8px 30px rgba(139,92,246,.3); animation:floatOrb 4s ease-in-out infinite }
@keyframes floatOrb { 0%,100% { transform:translateY(0) } 50% { transform:translateY(-8px) } }
.empty-hero h2 { font-size:1.3rem; font-weight:800; color:#1e1b4b; margin:0 0 .4rem }
.empty-hero p { font-size:.82rem; color:#64748b; margin:0 0 1.25rem; max-width:360px; margin-left:auto; margin-right:auto; line-height:1.5 }

/* ═══ DIALOG ═══ */
.dialog-overlay { position:fixed; inset:0; background:rgba(15,10,30,.6); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(8px); padding:1.5rem }
.modal-enter-active { transition:all .35s ease-out } .modal-leave-active { transition:all .2s ease-in }
.modal-enter-from { opacity:0 } .modal-leave-to { opacity:0 }
.modal-enter-from .dialog-card { transform:translateY(24px) scale(.96) } .modal-leave-to .dialog-card { transform:translateY(12px) scale(.98) }
.dialog-card { background:white; border-radius:24px; width:720px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 32px 80px rgba(0,0,0,.25); transition:transform .35s ease-out }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.75rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-title-wrap { display:flex; align-items:center; gap:.75rem }
.dialog-icon-wrap { width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg, #7c3aed, #a855f7); display:flex; align-items:center; justify-content:center; color:white; font-size:.92rem; flex-shrink:0; box-shadow:0 4px 14px rgba(139,92,246,.3) }
.dialog-header h3 { font-size:1rem; font-weight:800; color:#0f172a; margin:0 }
.dialog-subtitle { font-size:.65rem; color:#94a3b8 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.75rem; overflow-y:auto; flex:1; min-height:0 }

/* Form Sections */
.form-section { margin-bottom:1.25rem; padding-bottom:1rem; border-bottom:1px solid #f8fafc }
.form-section:last-of-type { border-bottom:none; margin-bottom:.5rem }
.section-label { font-size:.62rem; font-weight:700; color:#8b5cf6; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.6rem; display:flex; align-items:center; gap:.3rem }
.section-label i { font-size:.55rem }
.form-row { display:flex; gap:.75rem }
.form-row-3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:.75rem }
.form-group { margin-bottom:.65rem }
.flex-1 { flex:1 } .flex-2 { flex:2 }
.w-full { width:100% }
.form-group label { display:block; font-size:.68rem; font-weight:600; color:#475569; margin-bottom:.3rem }
.req { color:#ef4444 }
.prompt-textarea { font-family:'JetBrains Mono','Fira Code', monospace; font-size:.72rem; line-height:1.65; background:#f8fafc; border-radius:12px }

/* KB Checklist */
.kb-checklist { display:flex; flex-direction:column; gap:.35rem }
.kb-check-item { display:flex; align-items:center; gap:.5rem; padding:.45rem .7rem; border:1.5px solid #e2e8f0; border-radius:10px; cursor:pointer; font-size:.72rem; color:#334155; transition:all .2s }
.kb-check-item:hover { background:#faf5ff; border-color:#c4b5fd }
.kb-check-item input { display:none }
.check-visual { width:16px; height:16px; border-radius:5px; border:2px solid #cbd5e1; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all .2s }
.kb-check-item input:checked ~ .check-visual { background:#7c3aed; border-color:#7c3aed }
.kb-check-item input:checked ~ .check-visual::after { content:'✓'; color:white; font-size:.55rem; font-weight:800 }
.kb-check-item small { margin-left:auto; font-size:.5rem; color:#94a3b8; text-transform:uppercase; padding:.1rem .3rem; background:#f1f5f9; border-radius:4px }
.text-muted { font-size:.72rem; color:#94a3b8; margin:0 }
.text-muted a { color:#7c3aed; text-decoration:none; font-weight:600 }

/* Tools Grid */
.tools-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:.4rem }
.tool-check-item { display:flex; align-items:center; gap:.4rem; padding:.45rem .65rem; border:1.5px solid #e2e8f0; border-radius:10px; cursor:pointer; font-size:.68rem; font-weight:500; color:#64748b; transition:all .2s }
.tool-check-item:hover { border-color:#c4b5fd; background:#faf5ff }
.tool-check-item.checked { border-color:#8b5cf6; background:#ede9fe; color:#5b21b6 }
.tool-check-item input { display:none }
.tool-check-item i { font-size:.6rem; color:#8b5cf6 }

/* Footer */
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.75rem; border-top:1px solid #f1f5f9; flex-shrink:0; background:#fafbfc; border-radius:0 0 24px 24px }
.btn-secondary { padding:.55rem 1.2rem; font-size:.75rem; font-weight:600; border:1.5px solid #e2e8f0; border-radius:10px; background:white; color:#475569; cursor:pointer; font-family:inherit; transition:all .2s }
.btn-secondary:hover { background:#f8fafc; border-color:#cbd5e1 }
.btn-primary { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.4rem; font-size:.75rem; font-weight:700; border:none; border-radius:10px; background:linear-gradient(135deg, #7c3aed, #a855f7); color:white; cursor:pointer; font-family:inherit; transition:all .2s; box-shadow:0 2px 10px rgba(139,92,246,.3) }
.btn-primary:hover { transform:translateY(-1px); box-shadow:0 4px 16px rgba(139,92,246,.4) }
.btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none }

@media (max-width:768px) {
  .hero-banner { padding:1.5rem }
  .hero-content h1 { font-size:1.5rem }
  .hero-stats { display:none }
  .agent-grid { grid-template-columns:1fr }
  .form-row, .form-row-3 { flex-direction:column; display:flex }
  .tools-grid { grid-template-columns:1fr }
  .hero-actions { flex-direction:column }
}
</style>
