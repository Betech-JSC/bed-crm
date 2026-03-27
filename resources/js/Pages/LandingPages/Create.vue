<template>
  <div>
    <Head title="Tạo Landing Page" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-plus" style="color:#8b5cf6;" /> Tạo Landing Page</h1>
        <p class="page-subtitle">Chọn blocks, tuỳ chỉnh nội dung, và publish</p>
      </div>
      <Link href="/landing-pages" class="btn-back"><i class="pi pi-arrow-left" /> Quay lại</Link>
    </div>

    <div class="builder-layout">
      <!-- Left: Block editor -->
      <div class="builder-main">
        <div class="section-card">
          <h3 class="sec-title"><i class="pi pi-info-circle" /> Thông tin</h3>
          <div class="fm-group"><label>Tiêu đề <span class="req">*</span></label><input v-model="form.title" type="text" class="fm-input" placeholder="Landing page title" /></div>
          <div class="fm-group"><label>Mô tả</label><textarea v-model="form.description" rows="2" class="fm-input" placeholder="Mô tả ngắn..." /></div>
          <div class="fm-row">
            <div class="fm-group flex-1"><label>Meta Title</label><input v-model="form.meta_title" type="text" class="fm-input" /></div>
            <div class="fm-group flex-1">
              <label>Liên kết Web Form</label>
              <select v-model="form.web_form_id" class="fm-input">
                <option :value="null">Không liên kết</option>
                <option v-for="f in webForms" :key="f.id" :value="f.id">{{ f.name }}</option>
              </select>
            </div>
          </div>
          <div class="fm-group"><label>Meta Description</label><textarea v-model="form.meta_description" rows="2" class="fm-input" /></div>
        </div>

        <!-- Blocks -->
        <div class="section-card">
          <div class="sec-head">
            <h3 class="sec-title"><i class="pi pi-th-large" /> Blocks</h3>
          </div>

          <div v-for="(block, i) in form.blocks" :key="i" class="block-row">
            <div class="block-handle"><i class="pi pi-bars" /></div>
            <div class="block-body">
              <div class="block-head">
                <span class="block-type"><i :class="blockTypes[block.type]?.icon" /> {{ blockTypes[block.type]?.label }}</span>
                <button class="block-del" @click="form.blocks.splice(i, 1)"><i class="pi pi-trash" /></button>
              </div>

              <!-- Hero -->
              <div v-if="block.type === 'hero'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="Heading" />
                <input v-model="block.data.subheading" type="text" class="fm-input mb-2" placeholder="Subheading" />
                <div class="fm-row">
                  <input v-model="block.data.cta_text" type="text" class="fm-input flex-1" placeholder="CTA text" />
                  <input v-model="block.data.bg_color" type="color" class="color-input" />
                </div>
              </div>

              <!-- Features -->
              <div v-if="block.type === 'features'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="Section heading" />
                <div v-for="(item, fi) in block.data.items" :key="fi" class="feat-row">
                  <input v-model="item.icon" type="text" class="fm-input" style="width:40px;" />
                  <input v-model="item.title" type="text" class="fm-input flex-1" placeholder="Title" />
                  <input v-model="item.desc" type="text" class="fm-input flex-1" placeholder="Describe" />
                  <button class="opt-del" @click="block.data.items.splice(fi, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ title: '', desc: '', icon: '✅' })"><i class="pi pi-plus" /> Feature</button>
              </div>

              <!-- Testimonials -->
              <div v-if="block.type === 'testimonials'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="Section heading" />
                <div v-for="(item, ti) in block.data.items" :key="ti" class="feat-row">
                  <input v-model="item.name" type="text" class="fm-input" placeholder="Name" />
                  <input v-model="item.role" type="text" class="fm-input" placeholder="Role" />
                  <input v-model="item.text" type="text" class="fm-input flex-1" placeholder="Quote" />
                  <button class="opt-del" @click="block.data.items.splice(ti, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ name: '', role: '', text: '' })"><i class="pi pi-plus" /> Testimonial</button>
              </div>

              <!-- CTA -->
              <div v-if="block.type === 'cta'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="CTA heading" />
                <div class="fm-row">
                  <input v-model="block.data.subheading" type="text" class="fm-input flex-1" placeholder="Subheading" />
                  <input v-model="block.data.button_text" type="text" class="fm-input" style="width:120px;" placeholder="Button text" />
                  <input v-model="block.data.bg_color" type="color" class="color-input" />
                </div>
              </div>

              <!-- FAQ -->
              <div v-if="block.type === 'faq'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="FAQ heading" />
                <div v-for="(item, qi) in block.data.items" :key="qi" class="feat-row">
                  <input v-model="item.q" type="text" class="fm-input flex-1" placeholder="Câu hỏi" />
                  <input v-model="item.a" type="text" class="fm-input flex-1" placeholder="Trả lời" />
                  <button class="opt-del" @click="block.data.items.splice(qi, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ q: '', a: '' })"><i class="pi pi-plus" /> FAQ</button>
              </div>

              <!-- Stats -->
              <div v-if="block.type === 'stats'">
                <div v-for="(item, si) in block.data.items" :key="si" class="feat-row">
                  <input v-model="item.number" type="text" class="fm-input" style="width:80px;" placeholder="500+" />
                  <input v-model="item.label" type="text" class="fm-input flex-1" placeholder="Label" />
                  <button class="opt-del" @click="block.data.items.splice(si, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ number: '', label: '' })"><i class="pi pi-plus" /> Stat</button>
              </div>

              <!-- Form -->
              <div v-if="block.type === 'form'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="Form heading" />
                <p class="hint">Form sẽ tự động sử dụng Web Form đã liên kết ở trên</p>
              </div>

              <!-- Text -->
              <div v-if="block.type === 'text'">
                <textarea v-model="block.data.content" rows="3" class="fm-input" placeholder="Viết nội dung..." />
              </div>
            </div>
          </div>

          <!-- Add block palette -->
          <div class="add-block-palette">
            <p class="abp-title">Thêm block:</p>
            <div class="abp-grid">
              <button v-for="(info, key) in blockTypes" :key="key" class="abp-btn" @click="addBlock(key)">
                <i :class="info.icon" />
                <span>{{ info.label }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Style + Preview -->
      <div class="builder-sidebar">
        <div class="preview-card">
          <h3 class="preview-title">Giao diện</h3>
          <div class="fm-group"><label>Màu chính</label><input v-model="form.style_settings.primary_color" type="color" class="color-input-lg" /></div>
          <div class="fm-group"><label>Font</label>
            <select v-model="form.style_settings.font" class="fm-input">
              <option value="Inter">Inter</option>
              <option value="Roboto">Roboto</option>
              <option value="Be Vietnam Pro">Be Vietnam Pro</option>
            </select>
          </div>
          <div class="fm-group"><label>Bo góc</label><input v-model.number="form.style_settings.border_radius" type="range" min="0" max="24" class="range-input" /></div>
        </div>

        <div class="preview-card">
          <h3 class="preview-title">Tổng quan</h3>
          <div class="overview-items">
            <div class="ov-item"><span class="ov-lbl">Blocks:</span><span class="ov-val">{{ form.blocks.length }}</span></div>
            <div class="ov-item"><span class="ov-lbl">Status:</span><span class="ov-val">{{ form.status }}</span></div>
            <div class="ov-item" v-if="form.web_form_id"><span class="ov-lbl">Form:</span><span class="ov-val ov-linked">Linked</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
      <Link href="/landing-pages" class="btn-cancel">Hủy</Link>
      <div style="display:flex;gap:0.5rem;">
        <button class="btn-draft" @click="save('draft')" :disabled="!form.title || saving"><i class="pi pi-save" /> Lưu Draft</button>
        <button class="btn-publish" @click="save('published')" :disabled="!form.title || !form.blocks.length || saving">
          <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-globe'" /> Publish
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { blockTypes: Object, webForms: Array },
  data() {
    return {
      saving: false,
      form: {
        title: '', description: '', meta_title: '', meta_description: '',
        status: 'draft', web_form_id: null,
        blocks: [],
        style_settings: { primary_color: '#8b5cf6', font: 'Inter', border_radius: 12 },
      },
    }
  },
  methods: {
    addBlock(type) {
      const defaults = JSON.parse(JSON.stringify(this.blockTypes[type]?.defaults || {}))
      this.form.blocks.push({ type, data: defaults })
    },
    save(status) {
      this.saving = true
      this.form.status = status
      router.post('/landing-pages', this.form, { onFinish: () => { this.saving = false } })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.page-subtitle { font-size: 0.75rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-back { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; text-decoration: none; }
.builder-layout { display: grid; grid-template-columns: 1fr 280px; gap: 0.75rem; align-items: start; }
.section-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 1rem; margin-bottom: 0.65rem; }
.sec-title { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0 0 0.6rem; display: flex; align-items: center; gap: 0.3rem; }
.sec-title i { font-size: 0.72rem; color: #8b5cf6; }
.sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem; }
.sec-head .sec-title { margin: 0; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-input:focus { border-color: #8b5cf6; }
.fm-row { display: flex; gap: 0.5rem; align-items: flex-end; }
.flex-1 { flex: 1; }
.mb-2 { margin-bottom: 0.35rem; }

/* Blocks */
.block-row { display: flex; gap: 0.35rem; padding: 0.6rem; background: #fafbfc; border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 0.4rem; }
.block-handle { color: #cbd5e1; font-size: 0.6rem; padding-top: 0.3rem; cursor: grab; }
.block-body { flex: 1; }
.block-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; }
.block-type { font-size: 0.68rem; font-weight: 700; color: #8b5cf6; display: flex; align-items: center; gap: 0.2rem; }
.block-type i { font-size: 0.6rem; }
.block-del { width: 22px; height: 22px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.55rem; }
.block-del:hover { color: #ef4444; }
.feat-row { display: flex; gap: 0.25rem; margin-bottom: 0.2rem; align-items: center; }
.opt-del { width: 20px; height: 20px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.5rem; }
.opt-add { padding: 0.2rem 0.4rem; border: 1px dashed #e2e8f0; border-radius: 5px; background: transparent; color: #94a3b8; font-size: 0.58rem; cursor: pointer; font-family: inherit; margin-top: 0.2rem; }
.hint { font-size: 0.62rem; color: #94a3b8; margin: 0; }
.color-input { width: 32px; height: 32px; border: none; cursor: pointer; border-radius: 6px; flex-shrink: 0; }

/* Add block palette */
.add-block-palette { padding: 0.6rem; border: 1.5px dashed #e2e8f0; border-radius: 10px; margin-top: 0.5rem; }
.abp-title { font-size: 0.65rem; font-weight: 600; color: #94a3b8; margin: 0 0 0.3rem; }
.abp-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.3rem; }
.abp-btn { display: flex; flex-direction: column; align-items: center; padding: 0.4rem; border-radius: 7px; border: 1px solid #e2e8f0; background: white; cursor: pointer; transition: all 0.1s; font-family: inherit; }
.abp-btn:hover { border-color: #8b5cf6; background: #ede9fe; }
.abp-btn i { font-size: 0.78rem; color: #64748b; margin-bottom: 0.1rem; }
.abp-btn span { font-size: 0.5rem; color: #475569; font-weight: 600; }

/* Sidebar */
.preview-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; margin-bottom: 0.65rem; }
.preview-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; }
.color-input-lg { width: 100%; height: 32px; border: none; cursor: pointer; border-radius: 6px; }
.range-input { width: 100%; accent-color: #8b5cf6; }
.overview-items { display: flex; flex-direction: column; gap: 0.25rem; }
.ov-item { display: flex; justify-content: space-between; font-size: 0.68rem; }
.ov-lbl { color: #94a3b8; }
.ov-val { font-weight: 700; color: #0f172a; }
.ov-linked { color: #10b981; }

/* Footer */
.form-footer { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; margin-top: 0.5rem; }
.btn-cancel { padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; text-decoration: none; }
.btn-draft { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-size: 0.72rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-publish { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 1rem; border-radius: 8px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-size: 0.72rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }
.btn-publish:disabled, .btn-draft:disabled { opacity: 0.5; cursor: not-allowed; }

@media (max-width: 900px) { .builder-layout { grid-template-columns: 1fr; } }
</style>
