<template>
  <div>
    <Head :title="`Video: ${project.title}`" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <Link href="/video-ads"><button class="btn-back"><i class="pi pi-arrow-left" /></button></Link>
        <div>
          <h1 class="page-title">{{ project.title }}</h1>
          <div class="header-meta">
            <span class="status-badge" :class="project.status"><i :class="project.status_info.icon" /> {{ project.status_info.label }}</span>
            <span v-for="p in project.platform_labels" :key="p" class="plat-tag">{{ p }}</span>
            <span class="meta-time">{{ project.updated_at }}</span>
          </div>
        </div>
      </div>
      <div class="header-right">
        <select v-model="form.status" @change="updateField('status')" class="status-select">
          <option v-for="(info, key) in statuses" :key="key" :value="key">{{ info.label }}</option>
        </select>
      </div>
    </div>

    <!-- Main Layout: 2 columns -->
    <div class="edit-layout">
      <!-- Left: Script & Storyboard -->
      <div class="main-col">
        <!-- AI Script Generator -->
        <div class="section-card ai-section">
          <div class="section-header">
            <h2><i class="pi pi-sparkles" /> AI Script Generator</h2>
            <button class="btn-ai" @click="generateScript" :disabled="generating">
              <i :class="generating ? 'pi pi-spin pi-spinner' : 'pi pi-bolt'" />
              {{ generating ? 'Đang tạo...' : (project.ai_script ? 'Tạo lại kịch bản' : 'Tạo kịch bản AI') }}
            </button>
          </div>

          <!-- Script Result -->
          <div v-if="form.ai_script" class="script-block">
            <label>Kịch bản</label>
            <textarea v-model="form.ai_script" class="form-control script-textarea" rows="5" @blur="updateField('ai_script')" />
          </div>

          <div v-if="form.ai_voiceover_text" class="script-block">
            <label><i class="pi pi-microphone" /> Voiceover / Thuyết minh</label>
            <textarea v-model="form.ai_voiceover_text" class="form-control" rows="3" @blur="updateField('ai_voiceover_text')" />
          </div>

          <div v-if="form.ai_music_suggestion" class="script-block">
            <label><i class="pi pi-volume-up" /> Gợi ý nhạc nền</label>
            <div class="music-suggestion">{{ form.ai_music_suggestion }}</div>
          </div>
        </div>

        <!-- Storyboard -->
        <div class="section-card">
          <div class="section-header">
            <h2><i class="pi pi-images" /> Storyboard ({{ (form.ai_scenes || []).length }} cảnh)</h2>
            <button v-if="!form.ai_scenes?.length" class="btn-sm" @click="generateScript"><i class="pi pi-sparkles" /> Tạo từ AI</button>
          </div>

          <div v-if="form.ai_scenes?.length" class="storyboard">
            <div v-for="(scene, idx) in form.ai_scenes" :key="idx" class="scene-card">
              <div class="scene-num">{{ scene.scene || idx + 1 }}</div>
              <div class="scene-body">
                <div class="scene-top">
                  <strong>{{ scene.label }}</strong>
                  <span class="scene-dur">{{ scene.duration }}s</span>
                </div>
                <div v-if="scene.visual" class="scene-visual">
                  <i class="pi pi-eye" /> {{ scene.visual }}
                </div>
                <div v-if="scene.text_overlay" class="scene-overlay">
                  <i class="pi pi-align-left" /> {{ scene.text_overlay }}
                </div>
                <div v-if="scene.voiceover" class="scene-voice">
                  <i class="pi pi-microphone" /> {{ scene.voiceover }}
                </div>
                <div v-if="scene.transition" class="scene-trans">
                  <span class="trans-chip">{{ scene.transition }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="empty-storyboard">
            <i class="pi pi-film" />
            <p>Nhấn "Tạo kịch bản AI" để AI tạo storyboard tự động</p>
          </div>
        </div>

        <!-- Caption & Hashtags -->
        <div class="section-card">
          <div class="section-header">
            <h2><i class="pi pi-hashtag" /> Caption & Hashtags</h2>
            <button class="btn-sm" @click="generateCaption" :disabled="generatingCaption">
              <i :class="generatingCaption ? 'pi pi-spin pi-spinner' : 'pi pi-sparkles'" />
              {{ generatingCaption ? 'Đang tạo...' : 'Tạo caption AI' }}
            </button>
          </div>

          <div v-if="form.ai_caption" class="script-block">
            <label>Caption</label>
            <textarea v-model="form.ai_caption" class="form-control" rows="4" @blur="updateField('ai_caption')" />
          </div>

          <div v-if="form.ai_hashtags?.length" class="hashtags-row">
            <span v-for="tag in form.ai_hashtags" :key="tag" class="hashtag">{{ tag }}</span>
          </div>
        </div>
      </div>

      <!-- Right: Product Info & Settings -->
      <div class="side-col">
        <!-- Product Info -->
        <div class="section-card compact">
          <h3 class="side-title"><i class="pi pi-tag" /> Sản phẩm</h3>
          <div class="side-field">
            <label>Tên sản phẩm</label>
            <input v-model="form.product_name" type="text" class="form-control" @blur="updateField('product_name')" />
          </div>
          <div class="side-field">
            <label>Giá</label>
            <input v-model.number="form.product_price" type="number" class="form-control" @blur="updateField('product_price')" />
          </div>
          <div class="side-field">
            <label>Điểm nổi bật</label>
            <textarea v-model="form.product_highlights" class="form-control" rows="3" @blur="updateField('product_highlights')" />
          </div>
          <div class="side-field">
            <label>Link sản phẩm</label>
            <input v-model="form.product_url" type="url" class="form-control" @blur="updateField('product_url')" />
          </div>
          <div class="side-field">
            <label>Mã giảm giá</label>
            <input v-model="form.promo_code" type="text" class="form-control" @blur="updateField('promo_code')" />
          </div>
        </div>

        <!-- CTA -->
        <div class="section-card compact">
          <h3 class="side-title"><i class="pi pi-external-link" /> CTA</h3>
          <div class="side-field">
            <label>Text CTA</label>
            <input v-model="form.cta_text" type="text" class="form-control" @blur="updateField('cta_text')" />
          </div>
          <div class="side-field">
            <label>Link CTA</label>
            <input v-model="form.cta_url" type="url" class="form-control" @blur="updateField('cta_url')" />
          </div>
        </div>

        <!-- Video Settings -->
        <div class="section-card compact">
          <h3 class="side-title"><i class="pi pi-cog" /> Cài đặt video</h3>
          <div class="side-field">
            <label>Loại video</label>
            <select v-model="form.video_type" class="form-control" @change="updateField('video_type')">
              <option v-for="(info, key) in videoTypes" :key="key" :value="key">{{ info.icon }} {{ info.label }}</option>
            </select>
          </div>
          <div class="side-field">
            <label>Tỷ lệ</label>
            <select v-model="form.aspect_ratio" class="form-control" @change="updateField('aspect_ratio')">
              <option v-for="(info, key) in aspectRatios" :key="key" :value="key">{{ info.label }}</option>
            </select>
          </div>
          <div class="side-field">
            <label>Thời lượng (giây)</label>
            <input v-model.number="form.duration_seconds" type="number" class="form-control" @blur="updateField('duration_seconds')" />
          </div>
          <div class="side-field">
            <label>Nền tảng</label>
            <div class="plat-checks">
              <label v-for="(info, key) in platforms" :key="key" class="plat-check">
                <input type="checkbox" :value="key" v-model="form.target_platforms" @change="updateField('target_platforms')" />
                <span>{{ info.label }}</span>
              </label>
            </div>
          </div>
          <div class="side-field">
            <label>Màu thương hiệu</label>
            <div class="color-pick">
              <input type="color" v-model="form.brand_color" @change="updateField('brand_color')" />
              <span>{{ form.brand_color || 'Chưa chọn' }}</span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-card compact actions-card">
          <button class="action-btn full" @click="duplicateProject"><i class="pi pi-copy" /> Tạo bản sao</button>
          <button class="action-btn full danger" @click="deleteProject"><i class="pi pi-trash" /> Xóa dự án</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import axios from 'axios'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    project: Object,
    statuses: Object,
    videoTypes: Object,
    platforms: Object,
    aspectRatios: Object,
  },
  data() {
    return {
      generating: false,
      generatingCaption: false,
      form: {
        status: this.project.status,
        title: this.project.title,
        video_type: this.project.video_type,
        target_platforms: this.project.target_platforms || [],
        aspect_ratio: this.project.aspect_ratio,
        duration_seconds: this.project.duration_seconds,
        ai_script: this.project.ai_script,
        ai_scenes: this.project.ai_scenes || [],
        ai_voiceover_text: this.project.ai_voiceover_text,
        ai_music_suggestion: this.project.ai_music_suggestion,
        ai_hashtags: this.project.ai_hashtags || [],
        ai_caption: this.project.ai_caption,
        product_name: this.project.product_name,
        product_highlights: this.project.product_highlights,
        product_price: this.project.product_price,
        product_url: this.project.product_url,
        promo_code: this.project.promo_code,
        cta_text: this.project.cta_text,
        cta_url: this.project.cta_url,
        brand_color: this.project.brand_color || '#f43f5e',
      },
    }
  },
  methods: {
    updateField(field) {
      const data = {}
      data[field] = this.form[field]
      router.put(`/video-ads/${this.project.id}`, data, { preserveState: true, preserveScroll: true })
    },
    async generateScript() {
      this.generating = true
      try {
        const { data } = await axios.post(`/video-ads/${this.project.id}/generate-script`)
        this.form.ai_script = data.script
        this.form.ai_scenes = data.scenes || []
        this.form.ai_voiceover_text = data.voiceover
        this.form.ai_music_suggestion = data.music_suggestion
        this.form.ai_hashtags = data.hashtags || []
        this.form.ai_caption = data.caption
      } catch (e) { console.error(e) }
      this.generating = false
    },
    async generateCaption() {
      this.generatingCaption = true
      try {
        const { data } = await axios.post(`/video-ads/${this.project.id}/generate-caption`)
        this.form.ai_caption = data.caption
        this.form.ai_hashtags = data.hashtags || []
      } catch (e) { console.error(e) }
      this.generatingCaption = false
    },
    duplicateProject() {
      router.post(`/video-ads/${this.project.id}/duplicate`)
    },
    deleteProject() {
      if (!confirm('Xóa dự án video này?')) return
      router.delete(`/video-ads/${this.project.id}`)
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
.header-left { display: flex; align-items: center; gap: 0.75rem; }
.header-right { display: flex; gap: 0.5rem; }
.btn-back { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.btn-back:hover { border-color: #f43f5e; color: #f43f5e; }
.page-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0; }
.header-meta { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.2rem; flex-wrap: wrap; }
.status-badge { font-size: 0.6rem; font-weight: 700; padding: 0.12rem 0.45rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.2rem; }
.status-badge i { font-size: 0.5rem; }
.status-badge.draft { background: #f8fafc; color: #64748b; }
.status-badge.scripting { background: #eff6ff; color: #3b82f6; }
.status-badge.producing { background: #fffbeb; color: #f59e0b; }
.status-badge.review { background: #faf5ff; color: #8b5cf6; }
.status-badge.published { background: #ecfdf5; color: #10b981; }
.plat-tag { font-size: 0.55rem; font-weight: 600; padding: 0.08rem 0.35rem; border-radius: 5px; background: #f1f5f9; color: #64748b; }
.meta-time { font-size: 0.6rem; color: #94a3b8; }
.status-select { padding: 0.4rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.78rem; color: #475569; background: white; outline: none; cursor: pointer; }

/* Layout */
.edit-layout { display: grid; grid-template-columns: 1fr 320px; gap: 0.85rem; align-items: start; }
.section-card { background: white; border-radius: 14px; padding: 1.1rem; border: 1.5px solid #f1f5f9; margin-bottom: 0.85rem; }
.section-card.compact { padding: 0.85rem; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
.section-header h2 { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.35rem; }
.section-header h2 i { color: #f43f5e; font-size: 0.85rem; }

.side-title { font-size: 0.82rem; font-weight: 700; color: #1e293b; margin: 0 0 0.65rem; display: flex; align-items: center; gap: 0.3rem; }
.side-title i { color: #f43f5e; font-size: 0.78rem; }

/* AI Section */
.ai-section { border-color: #fecdd3; background: linear-gradient(135deg, #fff1f2 0%, white 30%); }
.btn-ai { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.45rem 0.85rem; border-radius: 8px; background: linear-gradient(135deg, #f43f5e, #e11d48); color: white; font-size: 0.75rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s; }
.btn-ai:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(244,63,94,0.25); }
.btn-ai:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.btn-sm { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.3rem 0.65rem; border-radius: 7px; border: 1.5px solid #fecdd3; background: white; color: #f43f5e; font-size: 0.68rem; font-weight: 600; cursor: pointer; }
.btn-sm:hover { background: #fff1f2; }

.script-block { margin-bottom: 0.65rem; }
.script-block label { display: flex; align-items: center; gap: 0.25rem; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem; }
.script-block label i { font-size: 0.62rem; color: #f43f5e; }
.script-textarea { font-size: 0.78rem; line-height: 1.6; }
.music-suggestion { font-size: 0.75rem; color: #64748b; background: #fafbfc; padding: 0.55rem; border-radius: 8px; border: 1px solid #f1f5f9; line-height: 1.5; }

/* Form */
.form-control { width: 100%; padding: 0.45rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.78rem; color: #1e293b; background: white; outline: none; font-family: inherit; transition: border-color 0.15s; }
.form-control:focus { border-color: #f43f5e; }
textarea.form-control { resize: vertical; min-height: 40px; }
.side-field { margin-bottom: 0.5rem; }
.side-field label { display: block; font-size: 0.65rem; font-weight: 600; color: #64748b; margin-bottom: 0.2rem; }

/* Storyboard */
.storyboard { display: flex; flex-direction: column; gap: 0.5rem; }
.scene-card { display: flex; gap: 0.5rem; padding: 0.65rem; border-radius: 10px; background: #fafbfc; border: 1px solid #f1f5f9; transition: all 0.15s; }
.scene-card:hover { border-color: #e2e8f0; }
.scene-num { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, #f43f5e, #e11d48); color: white; font-size: 0.68rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.scene-body { flex: 1; min-width: 0; }
.scene-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; }
.scene-top strong { font-size: 0.78rem; color: #1e293b; }
.scene-dur { font-size: 0.55rem; font-weight: 700; background: #fef2f2; color: #f43f5e; padding: 0.08rem 0.3rem; border-radius: 4px; }
.scene-visual, .scene-overlay, .scene-voice { font-size: 0.68rem; color: #64748b; margin-bottom: 0.15rem; display: flex; align-items: flex-start; gap: 0.25rem; line-height: 1.4; }
.scene-visual i, .scene-overlay i, .scene-voice i { font-size: 0.6rem; color: #94a3b8; margin-top: 0.1rem; flex-shrink: 0; }
.scene-trans { margin-top: 0.2rem; }
.trans-chip { font-size: 0.52rem; font-weight: 600; padding: 0.06rem 0.3rem; border-radius: 4px; background: #f1f5f9; color: #94a3b8; text-transform: uppercase; }

.empty-storyboard { text-align: center; padding: 2rem; color: #cbd5e1; }
.empty-storyboard i { font-size: 1.5rem; margin-bottom: 0.5rem; display: block; }
.empty-storyboard p { font-size: 0.72rem; }

/* Hashtags */
.hashtags-row { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.hashtag { font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 6px; background: #fef2f2; color: #f43f5e; }

/* Platform checks */
.plat-checks { display: flex; flex-direction: column; gap: 0.3rem; }
.plat-check { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: #475569; cursor: pointer; }
.plat-check input { accent-color: #f43f5e; }

/* Color */
.color-pick { display: flex; align-items: center; gap: 0.4rem; }
.color-pick input[type="color"] { width: 30px; height: 30px; border: 1.5px solid #e2e8f0; border-radius: 6px; cursor: pointer; padding: 1px; }
.color-pick span { font-size: 0.72rem; color: #64748b; }

/* Actions */
.actions-card { display: flex; flex-direction: column; gap: 0.35rem; }
.action-btn.full { width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.45rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-size: 0.72rem; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.action-btn.full:hover { border-color: #f43f5e; color: #f43f5e; }
.action-btn.full.danger:hover { background: #fef2f2; }

@media (max-width: 900px) { .edit-layout { grid-template-columns: 1fr; } }
</style>
