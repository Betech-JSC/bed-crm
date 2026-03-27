<template>
  <div>
    <Head title="Tạo Video Ads" />

    <div class="page-header">
      <div class="header-left">
        <Link href="/video-ads"><button class="btn-back"><i class="pi pi-arrow-left" /></button></Link>
        <div>
          <h1 class="page-title">Tạo Video Ads mới</h1>
          <p class="page-subtitle">Chọn template, nhập thông tin sản phẩm — AI sẽ viết kịch bản</p>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit" class="create-flow">
      <!-- Step 1: Video Type & Template -->
      <div class="form-section">
        <h2 class="section-title"><span class="step-num">1</span> Loại video</h2>
        <div class="type-grid">
          <button
            v-for="(info, key) in videoTypes" :key="key" type="button"
            :class="['type-card', { active: form.video_type === key }]"
            @click="form.video_type = key"
          >
            <span class="type-icon">{{ info.icon }}</span>
            <strong>{{ info.label }}</strong>
          </button>
        </div>
      </div>

      <!-- Step 2: Template Selection -->
      <div class="form-section">
        <h2 class="section-title"><span class="step-num">2</span> Template kịch bản</h2>
        <div class="tpl-grid">
          <button
            v-for="tpl in filteredTemplates" :key="tpl.id" type="button"
            :class="['tpl-card', { active: selectedTemplate?.id === tpl.id }]"
            @click="selectTemplate(tpl)"
          >
            <div class="tpl-top">
              <strong>{{ tpl.name }}</strong>
              <span class="tpl-dur">{{ tpl.duration_seconds }}s</span>
            </div>
            <p class="tpl-desc">{{ tpl.description }}</p>
            <div class="tpl-scenes">
              <span v-for="(s, i) in tpl.scene_structure" :key="i" class="scene-dot" :title="s.label">{{ i + 1 }}</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Step 3: Platform & Format -->
      <div class="form-section">
        <h2 class="section-title"><span class="step-num">3</span> Nền tảng đăng</h2>
        <div class="platform-grid">
          <button
            v-for="(info, key) in platforms" :key="key" type="button"
            :class="['plat-card', { active: form.target_platforms.includes(key) }]"
            @click="togglePlatform(key)"
          >
            <i :class="info.icon" :style="{ color: info.color }" />
            <span>{{ info.label }}</span>
          </button>
        </div>
        <div class="ratio-row">
          <label>Tỷ lệ khung hình:</label>
          <div class="ratio-options">
            <button
              v-for="(info, key) in aspectRatios" :key="key" type="button"
              :class="['ratio-btn', { active: form.aspect_ratio === key }]"
              @click="form.aspect_ratio = key"
            >
              <span class="ratio-preview" :class="'r-' + key.replace(':', 'x')"></span>
              {{ info.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Step 4: Product Info -->
      <div class="form-section">
        <h2 class="section-title"><span class="step-num">4</span> Thông tin sản phẩm</h2>
        <div class="form-grid">
          <div class="form-group full">
            <label>Tiêu đề dự án <span class="req">*</span></label>
            <input v-model="form.title" type="text" class="form-control" placeholder="VD: Video quảng cáo kem dưỡng da" />
            <span v-if="form.errors.title" class="error">{{ form.errors.title }}</span>
          </div>
          <div class="form-group">
            <label>Tên sản phẩm</label>
            <input v-model="form.product_name" type="text" class="form-control" placeholder="VD: Kem dưỡng da XYZ" />
          </div>
          <div class="form-group">
            <label>Giá sản phẩm</label>
            <input v-model.number="form.product_price" type="number" class="form-control" placeholder="299000" />
          </div>
          <div class="form-group full">
            <label>Điểm nổi bật (mỗi dòng 1 điểm)</label>
            <textarea v-model="form.product_highlights" class="form-control" rows="3" placeholder="Dưỡng ẩm 24h&#10;Chiết xuất thiên nhiên&#10;Phù hợp mọi loại da"></textarea>
          </div>
          <div class="form-group">
            <label>Link sản phẩm</label>
            <input v-model="form.product_url" type="url" class="form-control" placeholder="https://..." />
          </div>
          <div class="form-group">
            <label>Mã khuyến mãi</label>
            <input v-model="form.promo_code" type="text" class="form-control" placeholder="SALE20" />
          </div>
          <div class="form-group">
            <label>Text CTA</label>
            <input v-model="form.cta_text" type="text" class="form-control" placeholder="Mua ngay" />
          </div>
          <div class="form-group">
            <label>Link CTA</label>
            <input v-model="form.cta_url" type="url" class="form-control" placeholder="https://shop.com/..." />
          </div>
          <div class="form-group full">
            <label>Mô tả thêm cho AI</label>
            <textarea v-model="form.description" class="form-control" rows="2" placeholder="Bạn muốn video như thế nào? Phong cách gì?"></textarea>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="form-actions">
        <Link href="/video-ads"><button type="button" class="btn-cancel">Huỷ</button></Link>
        <button type="submit" class="btn-submit" :disabled="form.processing">
          <i class="pi pi-sparkles" /> Tạo dự án & Viết kịch bản AI
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    templates: Array,
    videoTypes: Object,
    platforms: Object,
    aspectRatios: Object,
  },
  data() {
    return { selectedTemplate: null }
  },
  setup() {
    const form = useForm({
      title: '',
      description: '',
      video_type: 'product',
      target_platforms: ['tiktok'],
      aspect_ratio: '9:16',
      duration_seconds: 30,
      product_name: '',
      product_highlights: '',
      product_price: null,
      product_url: '',
      promo_code: '',
      cta_text: 'Mua ngay',
      cta_url: '',
      ai_scenes: [],
    })
    return { form }
  },
  computed: {
    filteredTemplates() {
      return this.templates.filter(t => t.category === this.form.video_type || t.category === 'promo')
    },
  },
  methods: {
    selectTemplate(tpl) {
      this.selectedTemplate = tpl
      this.form.duration_seconds = tpl.duration_seconds
      this.form.aspect_ratio = tpl.aspect_ratio
      this.form.ai_scenes = JSON.parse(JSON.stringify(tpl.scene_structure))
    },
    togglePlatform(key) {
      const idx = this.form.target_platforms.indexOf(key)
      if (idx >= 0) { if (this.form.target_platforms.length > 1) this.form.target_platforms.splice(idx, 1) }
      else { this.form.target_platforms.push(key) }
    },
    submit() {
      this.form.post('/video-ads')
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; margin-bottom: 1.25rem; }
.header-left { display: flex; align-items: center; gap: 0.75rem; }
.btn-back { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.btn-back:hover { border-color: #f43f5e; color: #f43f5e; }
.page-title { font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }

.create-flow { max-width: 820px; }
.form-section { background: white; border-radius: 14px; padding: 1.25rem; margin-bottom: 0.85rem; border: 1.5px solid #f1f5f9; }
.section-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
.step-num { width: 24px; height: 24px; border-radius: 7px; background: linear-gradient(135deg, #f43f5e, #e11d48); color: white; font-size: 0.68rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }

/* Type Cards */
.type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
.type-card { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; padding: 0.75rem 0.5rem; border-radius: 10px; border: 2px solid #f1f5f9; background: white; cursor: pointer; transition: all 0.2s; }
.type-card:hover { border-color: #fecdd3; }
.type-card.active { border-color: #f43f5e; background: #fff1f2; box-shadow: 0 2px 8px rgba(244,63,94,0.15); }
.type-icon { font-size: 1.5rem; }
.type-card strong { font-size: 0.72rem; color: #475569; }
.type-card.active strong { color: #e11d48; }

/* Template Cards */
.tpl-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
.tpl-card { text-align: left; padding: 0.75rem; border-radius: 10px; border: 2px solid #f1f5f9; background: white; cursor: pointer; transition: all 0.2s; }
.tpl-card:hover { border-color: #fecdd3; }
.tpl-card.active { border-color: #f43f5e; background: #fff1f2; }
.tpl-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; }
.tpl-top strong { font-size: 0.8rem; color: #1e293b; }
.tpl-dur { font-size: 0.6rem; font-weight: 700; background: #f1f5f9; color: #64748b; padding: 0.1rem 0.35rem; border-radius: 5px; }
.tpl-desc { font-size: 0.65rem; color: #94a3b8; margin: 0 0 0.35rem; }
.tpl-scenes { display: flex; gap: 0.15rem; }
.scene-dot { width: 18px; height: 18px; border-radius: 4px; background: #f1f5f9; color: #64748b; font-size: 0.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.tpl-card.active .scene-dot { background: #fecdd3; color: #e11d48; }

/* Platform */
.platform-grid { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.75rem; }
.plat-card { display: flex; align-items: center; gap: 0.35rem; padding: 0.45rem 0.75rem; border-radius: 10px; border: 2px solid #f1f5f9; background: white; cursor: pointer; font-size: 0.78rem; font-weight: 600; color: #475569; transition: all 0.15s; }
.plat-card:hover { border-color: #e2e8f0; }
.plat-card.active { border-color: #f43f5e; background: #fff1f2; }
.plat-card i { font-size: 0.85rem; }

.ratio-row { display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap; }
.ratio-row label { font-size: 0.72rem; font-weight: 600; color: #475569; }
.ratio-options { display: flex; gap: 0.35rem; }
.ratio-btn { display: flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.65rem; border-radius: 8px; border: 1.5px solid #f1f5f9; background: white; cursor: pointer; font-size: 0.68rem; color: #64748b; transition: all 0.15s; }
.ratio-btn.active { border-color: #f43f5e; background: #fff1f2; color: #e11d48; font-weight: 700; }
.ratio-preview { display: inline-block; border: 1.5px solid currentColor; border-radius: 2px; }
.r-9x16 { width: 8px; height: 14px; } .r-16x9 { width: 14px; height: 8px; } .r-1x1 { width: 10px; height: 10px; } .r-4x5 { width: 10px; height: 12px; }

/* Form fields */
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.65rem; }
.form-group { margin-bottom: 0; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { display: block; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem; }
.req { color: #f43f5e; }
.form-control { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; color: #1e293b; background: white; outline: none; font-family: inherit; transition: border-color 0.15s; }
.form-control:focus { border-color: #f43f5e; box-shadow: 0 0 0 3px rgba(244,63,94,0.08); }
textarea.form-control { resize: vertical; min-height: 50px; }
.error { font-size: 0.65rem; color: #f43f5e; margin-top: 0.15rem; display: block; }

/* Actions */
.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.5rem 0 1rem; }
.btn-cancel { padding: 0.5rem 1rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.82rem; font-weight: 600; cursor: pointer; }
.btn-submit { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.25rem; border-radius: 10px; background: linear-gradient(135deg, #f43f5e, #e11d48); color: white; font-size: 0.82rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(244,63,94,0.3); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

@media (max-width: 640px) { .type-grid { grid-template-columns: repeat(2, 1fr); } .tpl-grid { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>
