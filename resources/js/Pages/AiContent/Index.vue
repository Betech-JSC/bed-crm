<template>
  <div>
    <Head title="AI Content Generator" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-sparkles" style="color:#8b5cf6;" /> AI Content Generator</h1>
        <p class="page-subtitle">Tạo nội dung SEO, social, email bằng AI thông minh</p>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-gen"><i class="pi pi-sparkles" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_generated }}</span><span class="stat-lbl">Nội dung đã tạo</span></div></div>
      <div class="stat-card"><div class="stat-icon si-tpl"><i class="pi pi-file" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_templates }}</span><span class="stat-lbl">Templates</span></div></div>
      <div class="stat-card"><div class="stat-icon si-pub"><i class="pi pi-check-circle" /></div><div class="stat-body"><span class="stat-val">{{ stats.published }}</span><span class="stat-lbl">Đã publish</span></div></div>
      <div class="stat-card"><div class="stat-icon si-top"><i class="pi pi-crown" /></div><div class="stat-body"><span class="stat-val stat-purple">{{ stats.most_used }}</span><span class="stat-lbl">Template hot</span></div></div>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <button class="tab" :class="{ active: tab === 'generate' }" @click="tab = 'generate'"><i class="pi pi-sparkles" /> Tạo nội dung</button>
      <button class="tab" :class="{ active: tab === 'templates' }" @click="tab = 'templates'"><i class="pi pi-file" /> Templates</button>
      <button class="tab" :class="{ active: tab === 'history' }" @click="tab = 'history'"><i class="pi pi-history" /> Lịch sử</button>
    </div>

    <!-- Generate Tab -->
    <div v-show="tab === 'generate'" class="gen-panel">
      <div class="gen-form">
        <h3 class="gen-title"><i class="pi pi-bolt" /> Tạo nội dung mới</h3>
        <div class="fm-group"><label>Tiêu đề <span class="req">*</span></label><input v-model="genForm.title" type="text" class="fm-input" placeholder="VD: Thiết kế website chuẩn SEO 2026" /></div>
        <div class="fm-group"><label>Loại nội dung</label>
          <div class="type-grid">
            <button v-for="(cat, key) in categories" :key="key" class="type-btn" :class="{ active: genForm.content_type === key }" @click="genForm.content_type = key" :style="{ '--tc': cat.color }">
              <i :class="cat.icon" /> {{ cat.label }}
            </button>
          </div>
        </div>
        <div class="fm-group"><label>Template</label>
          <select v-model="genForm.template_id" class="fm-input">
            <option :value="null">— Không dùng template —</option>
            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
        </div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Keyword chính</label><input v-model="genForm.input_data.keyword" type="text" class="fm-input" placeholder="SEO keyword" /></div>
          <div class="fm-group flex-1"><label>Tone</label>
            <select v-model="genForm.input_data.tone" class="fm-input"><option value="professional">Chuyên nghiệp</option><option value="friendly">Thân thiện</option><option value="casual">Thoải mái</option><option value="formal">Trang trọng</option><option value="creative">Sáng tạo</option></select>
          </div>
        </div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Độ dài</label>
            <select v-model="genForm.input_data.length" class="fm-input"><option value="short">Ngắn (~200 từ)</option><option value="medium">Trung bình (~500 từ)</option><option value="long">Dài (~1000+ từ)</option></select>
          </div>
          <div class="fm-group flex-1"><label>Ngôn ngữ</label>
            <select v-model="genForm.input_data.language" class="fm-input"><option value="vi">Tiếng Việt</option><option value="en">English</option></select>
          </div>
        </div>
        <div class="fm-group"><label>Chủ đề / Mô tả thêm</label><textarea v-model="genForm.input_data.topic" rows="2" class="fm-input" placeholder="Mô tả thêm yêu cầu..." /></div>
        <button class="btn-generate" :disabled="!genForm.title || saving" @click="generate">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-sparkles'" /> {{ saving ? 'Đang tạo...' : 'Tạo nội dung AI' }}
        </button>
      </div>
    </div>

    <!-- Templates Tab -->
    <div v-show="tab === 'templates'">
      <button class="btn-add-sm" @click="showTplModal = true"><i class="pi pi-plus" /> Tạo Template</button>
      <div class="tpl-grid">
        <div v-for="t in templates" :key="t.id" class="tpl-card">
          <div class="tc-cat" :style="{ background: categories[t.category]?.color + '15', color: categories[t.category]?.color }">
            <i :class="categories[t.category]?.icon" /> {{ categories[t.category]?.label }}
          </div>
          <h4 class="tc-name">{{ t.name }}</h4>
          <p v-if="t.description" class="tc-desc">{{ t.description }}</p>
          <div class="tc-footer">
            <span class="tc-uses"><i class="pi pi-play" /> {{ t.usage_count }} lần dùng</span>
            <span v-if="t.is_system" class="tc-system">System</span>
          </div>
        </div>
      </div>
    </div>

    <!-- History Tab -->
    <div v-show="tab === 'history'">
      <div v-if="history.data?.length" class="hist-list">
        <div v-for="h in history.data" :key="h.id" class="hist-card">
          <div class="hc-header">
            <h4 class="hc-title">{{ h.title }}</h4>
            <span class="hc-type" :style="{ color: categories[h.content_type]?.color }">{{ categories[h.content_type]?.label }}</span>
          </div>
          <div class="hc-content" :class="{ expanded: expandedId === h.id }">{{ h.generated_content }}</div>
          <button class="hc-expand" @click="expandedId = expandedId === h.id ? null : h.id">{{ expandedId === h.id ? 'Thu gọn' : 'Xem thêm' }}</button>
          <div v-if="h.seo_suggestions" class="hc-seo">
            <span class="hc-seo-tag"><i class="pi pi-search" /> SEO: {{ h.seo_suggestions.readability_score || 0 }}%</span>
            <span class="hc-seo-tag"><i class="pi pi-file-edit" /> {{ h.seo_suggestions.word_count_suggestion }}</span>
          </div>
          <div class="hc-footer">
            <span class="hc-date">{{ h.created_at }}</span>
            <span class="hc-status" :class="'hs-' + h.status">{{ h.status }}</span>
            <button class="hc-copy" @click="copyContent(h.generated_content)"><i class="pi pi-copy" /></button>
            <button class="hc-del" @click="deleteContent(h.id)"><i class="pi pi-trash" /></button>
          </div>
        </div>
      </div>
      <div v-else class="empty-state"><div class="empty-icon"><i class="pi pi-sparkles" /></div><h3>Chưa tạo nội dung nào</h3></div>
    </div>

    <!-- Template Modal -->
    <div v-if="showTplModal" class="modal-overlay" @click.self="showTplModal = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Tạo AI Template</h3><button class="modal-close" @click="showTplModal = false"><i class="pi pi-times" /></button></div>
        <div class="fm-group"><label>Tên <span class="req">*</span></label><input v-model="tplForm.name" type="text" class="fm-input" /></div>
        <div class="fm-group"><label>Danh mục</label><select v-model="tplForm.category" class="fm-input"><option v-for="(c, k) in categories" :key="k" :value="k">{{ c.label }}</option></select></div>
        <div class="fm-group"><label>Mô tả</label><textarea v-model="tplForm.description" rows="2" class="fm-input" /></div>
        <div class="fm-group"><label>System Prompt <span class="req">*</span></label><textarea v-model="tplForm.system_prompt" rows="3" class="fm-input" placeholder="Bạn là chuyên gia viết nội dung SEO..." /></div>
        <div class="fm-group"><label>User Prompt Template <span class="req">*</span></label><textarea v-model="tplForm.user_prompt_template" rows="3" class="fm-input" placeholder="Viết bài blog về {keyword} với tone {tone}..." /></div>
        <button class="btn-save" :disabled="!tplForm.name || !tplForm.system_prompt" @click="saveTpl"><i class="pi pi-save" /> Tạo</button>
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
  props: { templates: Array, history: Object, stats: Object, categories: Object, currentTab: String },
  data() {
    return {
      tab: this.currentTab || 'generate', saving: false, showTplModal: false, expandedId: null,
      genForm: { title: '', content_type: 'blog_seo', template_id: null, input_data: { keyword: '', tone: 'professional', length: 'medium', language: 'vi', topic: '' } },
      tplForm: { name: '', category: 'blog_seo', description: '', system_prompt: '', user_prompt_template: '' },
    }
  },
  methods: {
    generate() {
      this.saving = true
      router.post('/ai-content/generate', this.genForm, {
        onSuccess: () => { this.tab = 'history'; this.genForm.title = ''; this.genForm.input_data = { keyword: '', tone: 'professional', length: 'medium', language: 'vi', topic: '' } },
        onFinish: () => { this.saving = false },
      })
    },
    saveTpl() {
      router.post('/ai-content/templates', this.tplForm, {
        onSuccess: () => { this.tplForm = { name: '', category: 'blog_seo', description: '', system_prompt: '', user_prompt_template: '' }; this.showTplModal = false },
      })
    },
    deleteContent(id) { if (confirm('Xóa nội dung?')) router.delete(`/ai-content/${id}`) },
    copyContent(text) { navigator.clipboard.writeText(text); alert('Đã copy!') },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem}.page-title{font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem}.page-subtitle{font-size:.78rem;color:#94a3b8;margin:.1rem 0 0}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.55rem;margin-bottom:1rem}.stat-card{display:flex;align-items:center;gap:.5rem;padding:.6rem .8rem;border-radius:12px;background:#fff;border:1.5px solid #f1f5f9}.stat-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.78rem;flex-shrink:0}.si-gen{background:#ede9fe;color:#8b5cf6}.si-tpl{background:#fef3c7;color:#f59e0b}.si-pub{background:#ecfdf5;color:#10b981}.si-top{background:#fce7f3;color:#ec4899}.stat-val{font-size:1.05rem;font-weight:800;color:#0f172a;display:block}.stat-purple{color:#8b5cf6;font-size:.72rem}.stat-lbl{font-size:.6rem;color:#94a3b8}
.tabs-bar{display:flex;gap:0;border-bottom:1.5px solid #f1f5f9;margin-bottom:.75rem}.tab{padding:.5rem .9rem;border:none;background:0 0;font-size:.72rem;font-weight:700;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;display:flex;align-items:center;gap:.25rem;font-family:inherit}.tab.active{color:#8b5cf6;border-bottom-color:#8b5cf6}
.gen-panel{max-width:600px}.gen-form{background:#fff;border-radius:14px;border:1.5px solid #f1f5f9;padding:1rem}.gen-title{font-size:.88rem;font-weight:800;margin:0 0 .75rem;display:flex;align-items:center;gap:.3rem;color:#8b5cf6}
.type-grid{display:flex;gap:.3rem;flex-wrap:wrap}.type-btn{padding:.35rem .6rem;border-radius:7px;border:1.5px solid #e2e8f0;background:#fff;font-size:.65rem;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:.2rem}.type-btn.active{border-color:var(--tc);background:color-mix(in srgb,var(--tc) 10%,white);color:var(--tc)}.type-btn i{font-size:.6rem}
.fm-group{margin-bottom:.55rem}.fm-group label{display:block;font-size:.68rem;font-weight:600;color:#475569;margin-bottom:.15rem}.req{color:#ef4444}.fm-input{width:100%;padding:.42rem .65rem;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.78rem;color:#1e293b;outline:none;font-family:inherit;box-sizing:border-box}.fm-row{display:flex;gap:.5rem}.flex-1{flex:1}
.btn-generate{width:100%;padding:.65rem;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;font-size:.82rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.5rem}.btn-generate:disabled{opacity:.5}
.btn-add-sm{display:inline-flex;align-items:center;gap:.2rem;padding:.3rem .6rem;border-radius:7px;background:#f1f5f9;border:none;color:#64748b;font-size:.65rem;font-weight:600;cursor:pointer;margin-bottom:.5rem;font-family:inherit}
.tpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.5rem}.tpl-card{background:#fff;border-radius:12px;border:1.5px solid #f1f5f9;padding:.7rem;transition:all .15s}.tpl-card:hover{border-color:#8b5cf6;box-shadow:0 4px 12px rgba(139,92,246,.08)}.tc-cat{padding:.15rem .4rem;border-radius:5px;font-size:.55rem;font-weight:700;display:inline-flex;align-items:center;gap:.15rem;margin-bottom:.3rem}.tc-name{font-size:.78rem;font-weight:700;margin:0 0 .15rem;color:#0f172a}.tc-desc{font-size:.6rem;color:#94a3b8;margin:0 0 .3rem;line-height:1.4}.tc-footer{display:flex;justify-content:space-between;align-items:center;font-size:.55rem;color:#94a3b8}.tc-uses{display:flex;align-items:center;gap:.15rem}.tc-system{background:#eef2ff;color:#6366f1;padding:.1rem .3rem;border-radius:4px;font-weight:700}
.hist-list{display:flex;flex-direction:column;gap:.4rem}.hist-card{background:#fff;border-radius:12px;border:1.5px solid #f1f5f9;padding:.75rem}.hc-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem}.hc-title{font-size:.78rem;font-weight:700;margin:0;color:#0f172a}.hc-type{font-size:.55rem;font-weight:700}.hc-content{font-size:.7rem;color:#475569;line-height:1.6;max-height:80px;overflow:hidden;white-space:pre-wrap}.hc-content.expanded{max-height:none}.hc-expand{border:none;background:0 0;color:#8b5cf6;font-size:.6rem;font-weight:600;cursor:pointer;padding:.15rem 0;font-family:inherit}
.hc-seo{display:flex;gap:.3rem;margin:.3rem 0}.hc-seo-tag{padding:.15rem .35rem;border-radius:5px;background:#ede9fe;color:#8b5cf6;font-size:.5rem;font-weight:600;display:flex;align-items:center;gap:.15rem}
.hc-footer{display:flex;align-items:center;gap:.3rem;margin-top:.3rem}.hc-date{font-size:.55rem;color:#94a3b8;flex:1}.hc-status{font-size:.5rem;font-weight:700;text-transform:capitalize;padding:.1rem .3rem;border-radius:4px}.hs-draft{background:#fef3c7;color:#f59e0b}.hs-edited{background:#e0f2fe;color:#0ea5e9}.hs-published{background:#ecfdf5;color:#10b981}.hc-copy,.hc-del{width:22px;height:22px;border:none;border-radius:5px;background:#f1f5f9;color:#94a3b8;cursor:pointer;font-size:.55rem;display:flex;align-items:center;justify-content:center}.hc-del:hover{background:#fef2f2;color:#ef4444}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999}.modal-box{background:#fff;border-radius:16px;padding:1.2rem;width:95%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.15);max-height:90vh;overflow-y:auto}.modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem}.modal-head h3{font-size:.95rem;font-weight:800;margin:0}.modal-close{width:28px;height:28px;border:none;background:#f1f5f9;border-radius:7px;cursor:pointer;color:#94a3b8;display:flex;align-items:center;justify-content:center}
.btn-save{width:100%;padding:.5rem;border-radius:9px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;font-size:.78rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.5rem}.btn-save:disabled{opacity:.5}
.empty-state{text-align:center;padding:2.5rem 1rem}.empty-icon{width:48px;height:48px;border-radius:14px;background:#ede9fe;display:flex;align-items:center;justify-content:center;margin:0 auto .6rem}.empty-icon i{font-size:1.1rem;color:#8b5cf6}.empty-state h3{font-size:.95rem;color:#1e293b;margin:0}
@media(max-width:768px){.stats-row{grid-template-columns:repeat(2,1fr)}}
</style>
