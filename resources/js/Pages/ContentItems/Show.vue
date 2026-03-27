<template>
  <div>
    <Head :title="contentItem.title || 'Nội Dung'" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/content-items" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-file-edit" /></div>
        <div><h1 class="page-title">{{ contentItem.title || 'Untitled' }}</h1><p class="page-subtitle">{{ contentItem.type }} · {{ contentItem.created_at }}</p></div>
      </div>
      <div class="header-actions">
        <Link :href="`/content-items/${contentItem.id}/edit`"><button class="btn-secondary"><i class="pi pi-pencil" /> Sửa</button></Link>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-purple"><i class="pi pi-sparkles" /></div><div><span class="stat-value">{{ contentItem.ai_model || '—' }}</span><span class="stat-label">AI Model</span></div></div>
      <div class="stat-card"><div class="stat-icon si-blue"><i class="pi pi-chart-line" /></div><div><span class="stat-value">{{ contentItem.usage_count }}</span><span class="stat-label">Đã sử dụng</span></div></div>
      <div class="stat-card"><div class="stat-icon" :class="statusClass"><i :class="statusIcon" /></div><div><span class="stat-value">{{ statusLabel(contentItem.status) }}</span><span class="stat-label">Trạng thái</span></div></div>
      <div class="stat-card" v-if="contentItem.template"><div class="stat-icon si-amber"><i class="pi pi-palette" /></div><div><span class="stat-value">{{ contentItem.template.name }}</span><span class="stat-label">Template</span></div></div>
    </div>

    <!-- Content Body -->
    <div class="content-card">
      <div class="card-header"><h3><i class="pi pi-file-edit" /> Nội Dung</h3></div>
      <div class="card-body">
        <div class="content-body" v-html="contentItem.content" />
      </div>
    </div>

    <!-- Tags -->
    <div v-if="contentItem.tags && contentItem.tags.length" class="tags-row">
      <span v-for="tag in contentItem.tags" :key="tag" class="tag-chip">{{ tag }}</span>
    </div>

    <!-- Platform Optimization -->
    <div class="platform-card" v-if="socialAccounts && socialAccounts.length">
      <div class="card-header"><h3><i class="pi pi-share-alt" /> Tối Ưu Cho Nền Tảng</h3></div>
      <div class="card-body">
        <p class="hint-text">Chọn nền tảng để AI tối ưu nội dung cho phù hợp</p>
        <div class="platform-row">
          <button v-for="platform in availablePlatforms" :key="platform.key" class="platform-btn" :class="{ selected: selectedPlatform === platform.key }" :style="{ '--plt-color': platform.color }" @click="selectedPlatform = platform.key">
            <i :class="platform.icon" /> {{ platform.label }}
          </button>
        </div>
        <button v-if="selectedPlatform" class="btn-optimize" @click="optimizeForPlatform" :disabled="optimizing">
          <i v-if="optimizing" class="pi pi-spin pi-spinner" /><i v-else class="pi pi-sparkles" /> Tối ưu cho {{ selectedPlatform }}
        </button>
      </div>
    </div>

    <!-- Metadata -->
    <div v-if="contentItem.ai_metadata" class="metadata-card">
      <div class="card-header"><h3><i class="pi pi-info-circle" /> AI Metadata</h3></div>
      <div class="card-body"><pre class="json-block">{{ JSON.stringify(contentItem.ai_metadata, null, 2) }}</pre></div>
    </div>
  </div>
</template>
<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
export default {
  components: { Head, Link }, layout: Layout,
  props: { contentItem: Object, socialAccounts: Array },
  data() { return { selectedPlatform: null, optimizing: false } },
  computed: {
    statusClass() { return { draft: 'si-gray', approved: 'si-green', published: 'si-blue', archived: 'si-amber' }[this.contentItem.status] || 'si-gray' },
    statusIcon() { return { draft: 'pi pi-pencil', approved: 'pi pi-check-circle', published: 'pi pi-globe', archived: 'pi pi-box' }[this.contentItem.status] || 'pi pi-file' },
    availablePlatforms() {
      const platforms = new Set((this.socialAccounts || []).map(a => a.platform))
      const meta = { facebook: { icon: 'pi pi-facebook', label: 'Facebook', color: '#1877F2' }, instagram: { icon: 'pi pi-instagram', label: 'Instagram', color: '#E4405F' }, linkedin: { icon: 'pi pi-linkedin', label: 'LinkedIn', color: '#0A66C2' }, twitter: { icon: 'pi pi-twitter', label: 'Twitter/X', color: '#1DA1F2' } }
      return [...platforms].filter(p => meta[p]).map(p => ({ key: p, ...meta[p] }))
    },
  },
  methods: {
    statusLabel(s) { return { draft: 'Nháp', approved: 'Đã duyệt', published: 'Đã đăng', archived: 'Lưu trữ' }[s] || s },
    optimizeForPlatform() {
      if (!this.selectedPlatform) return
      this.optimizing = true
      router.post(`/content-items/${this.contentItem.id}/optimize`, { platform: this.selectedPlatform }, {
        onFinish: () => { this.optimizing = false },
      })
    },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#ec4899;color:#ec4899;background:#fdf2f8}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#ec4899,#db2777);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{font-size:.78rem;color:#64748b;margin:0}.header-actions{display:flex;gap:.5rem}
.btn-secondary{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none}.btn-secondary:hover{border-color:#ec4899;color:#ec4899}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1rem}
.stat-card{display:flex;align-items:center;gap:.6rem;padding:.7rem .85rem;background:#fff;border-radius:14px;border:1.5px solid #e2e8f0}.stat-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}.si-purple{background:#f5f3ff;color:#8b5cf6}.si-blue{background:#eff6ff;color:#3b82f6}.si-green{background:#ecfdf5;color:#10b981}.si-amber{background:#fffbeb;color:#f59e0b}.si-gray{background:#f8fafc;color:#94a3b8}.stat-value{display:block;font-size:.88rem;font-weight:700;color:#1e293b;line-height:1.2}.stat-label{font-size:.6rem;color:#94a3b8}
.content-card,.platform-card,.metadata-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;overflow:hidden;margin-bottom:1rem}.card-header{display:flex;align-items:center;padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;background:#fafbfc}.card-header h3{font-size:.85rem;font-weight:700;color:#1e293b;margin:0;display:flex;align-items:center;gap:.35rem}.card-header h3 i{font-size:.78rem;color:#64748b}.card-body{padding:1rem}
.content-body{font-size:.85rem;line-height:1.7;color:#334155;max-height:500px;overflow-y:auto}
.tags-row{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1rem}.tag-chip{font-size:.68rem;font-weight:600;padding:.15rem .5rem;border-radius:6px;background:#fdf2f8;color:#db2777}
.hint-text{font-size:.78rem;color:#64748b;margin:0 0 .75rem}
.platform-row{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem}
.platform-btn{display:flex;align-items:center;gap:.35rem;padding:.5rem .85rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s}.platform-btn:hover{border-color:var(--plt-color);color:var(--plt-color)}.platform-btn.selected{border-color:var(--plt-color);background:color-mix(in sRGB,var(--plt-color) 8%,transparent);color:var(--plt-color);box-shadow:0 2px 8px color-mix(in sRGB,var(--plt-color) 15%,transparent)}.platform-btn i{font-size:.82rem}
.btn-optimize{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#ec4899,#db2777);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-optimize:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(236,72,153,.3)}.btn-optimize:disabled{opacity:.6;cursor:not-allowed;transform:none}
.json-block{font-size:.72rem;font-family:'Menlo','Monaco',monospace;color:#475569;background:#f8fafc;border-radius:8px;padding:.75rem;margin:0;overflow-x:auto;white-space:pre-wrap}
@media(max-width:768px){.stats-row{grid-template-columns:repeat(2,1fr)}.platform-row{flex-direction:column}}
</style>
