<template>
  <div>
    <Head :title="`Sửa: ${page.title}`" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-pencil" style="color:#8b5cf6;" /> {{ page.title }}</h1>
        <p class="page-subtitle">
          <span class="status-dot" :class="'sd-' + page.status" /> {{ page.status }}
          · {{ page.visits_count }} visits · {{ page.conversion_rate }}% conversion
        </p>
      </div>
      <div class="header-actions">
        <a :href="page.public_url" target="_blank" class="btn-preview"><i class="pi pi-external-link" /> Xem trang</a>
        <Link href="/landing-pages" class="btn-back"><i class="pi pi-arrow-left" /> Quay lại</Link>
      </div>
    </div>

    <div class="builder-layout">
      <div class="builder-main">
        <!-- Info -->
        <div class="section-card">
          <h3 class="sec-title"><i class="pi pi-info-circle" /> Thông tin</h3>
          <div class="fm-row">
            <div class="fm-group flex-1"><label>Tiêu đề</label><input v-model="form.title" type="text" class="fm-input" /></div>
            <div class="fm-group" style="width:120px;">
              <label>Status</label>
              <select v-model="form.status" class="fm-input">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>
          <div class="fm-group"><label>Mô tả</label><textarea v-model="form.description" rows="2" class="fm-input" /></div>
          <div class="fm-row">
            <div class="fm-group flex-1"><label>Meta Title</label><input v-model="form.meta_title" type="text" class="fm-input" /></div>
            <div class="fm-group flex-1">
              <label>Web Form</label>
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
            <h3 class="sec-title"><i class="pi pi-th-large" /> Blocks ({{ form.blocks.length }})</h3>
          </div>

          <div v-for="(block, i) in form.blocks" :key="i" class="block-row">
            <div class="block-handle"><i class="pi pi-bars" /></div>
            <div class="block-body">
              <div class="block-head">
                <span class="block-type"><i :class="blockTypes[block.type]?.icon" /> {{ blockTypes[block.type]?.label }}</span>
                <button class="block-del" @click="form.blocks.splice(i, 1)"><i class="pi pi-trash" /></button>
              </div>

              <div v-if="block.type === 'hero'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="Heading" />
                <input v-model="block.data.subheading" type="text" class="fm-input mb-2" placeholder="Subheading" />
                <div class="fm-row"><input v-model="block.data.cta_text" type="text" class="fm-input flex-1" placeholder="CTA" /><input v-model="block.data.bg_color" type="color" class="color-input" /></div>
              </div>

              <div v-if="block.type === 'features'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" placeholder="Section heading" />
                <div v-for="(item, fi) in block.data.items" :key="fi" class="feat-row">
                  <input v-model="item.icon" type="text" class="fm-input" style="width:40px;" /><input v-model="item.title" type="text" class="fm-input flex-1" placeholder="Title" /><input v-model="item.desc" type="text" class="fm-input flex-1" placeholder="Desc" /><button class="opt-del" @click="block.data.items.splice(fi, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ title: '', desc: '', icon: '✅' })"><i class="pi pi-plus" /> Feature</button>
              </div>

              <div v-if="block.type === 'testimonials'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" />
                <div v-for="(item, ti) in block.data.items" :key="ti" class="feat-row">
                  <input v-model="item.name" type="text" class="fm-input" placeholder="Name" /><input v-model="item.role" type="text" class="fm-input" placeholder="Role" /><input v-model="item.text" type="text" class="fm-input flex-1" placeholder="Quote" /><button class="opt-del" @click="block.data.items.splice(ti, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ name: '', role: '', text: '' })"><i class="pi pi-plus" /> Testimonial</button>
              </div>

              <div v-if="block.type === 'cta'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" /><div class="fm-row"><input v-model="block.data.subheading" type="text" class="fm-input flex-1" /><input v-model="block.data.button_text" type="text" class="fm-input" style="width:120px;" /><input v-model="block.data.bg_color" type="color" class="color-input" /></div>
              </div>

              <div v-if="block.type === 'faq'">
                <input v-model="block.data.heading" type="text" class="fm-input mb-2" />
                <div v-for="(item, qi) in block.data.items" :key="qi" class="feat-row">
                  <input v-model="item.q" type="text" class="fm-input flex-1" placeholder="Q" /><input v-model="item.a" type="text" class="fm-input flex-1" placeholder="A" /><button class="opt-del" @click="block.data.items.splice(qi, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ q: '', a: '' })"><i class="pi pi-plus" /> FAQ</button>
              </div>

              <div v-if="block.type === 'stats'">
                <div v-for="(item, si) in block.data.items" :key="si" class="feat-row">
                  <input v-model="item.number" type="text" class="fm-input" style="width:80px;" /><input v-model="item.label" type="text" class="fm-input flex-1" /><button class="opt-del" @click="block.data.items.splice(si, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="block.data.items.push({ number: '', label: '' })"><i class="pi pi-plus" /> Stat</button>
              </div>

              <div v-if="block.type === 'form'"><input v-model="block.data.heading" type="text" class="fm-input" /><p class="hint">Form linked ở trên</p></div>
              <div v-if="block.type === 'text'"><textarea v-model="block.data.content" rows="3" class="fm-input" /></div>
            </div>
          </div>

          <div class="add-block-palette">
            <p class="abp-title">Thêm block:</p>
            <div class="abp-grid">
              <button v-for="(info, key) in blockTypes" :key="key" class="abp-btn" @click="addBlock(key)"><i :class="info.icon" /><span>{{ info.label }}</span></button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="builder-sidebar">
        <div class="preview-card">
          <h3 class="preview-title">Thống kê</h3>
          <div class="qs-grid">
            <div class="qs-item"><span class="qs-val">{{ page.visits_count }}</span><span class="qs-lbl">Visits</span></div>
            <div class="qs-item"><span class="qs-val">{{ page.conversions_count }}</span><span class="qs-lbl">Converts</span></div>
            <div class="qs-item"><span class="qs-val qs-rate">{{ page.conversion_rate }}%</span><span class="qs-lbl">Rate</span></div>
          </div>
        </div>
        <div class="preview-card">
          <h3 class="preview-title">URL</h3>
          <div class="url-box"><code>{{ page.public_url }}</code></div>
          <button class="btn-copy" @click="copyUrl"><i class="pi pi-copy" /> Copy</button>
        </div>
        <div class="preview-card">
          <h3 class="preview-title">Giao diện</h3>
          <div class="fm-group"><label>Màu chính</label><input v-model="form.style_settings.primary_color" type="color" class="color-input-lg" /></div>
          <div class="fm-group"><label>Font</label>
            <select v-model="form.style_settings.font" class="fm-input"><option value="Inter">Inter</option><option value="Roboto">Roboto</option><option value="Be Vietnam Pro">Be Vietnam Pro</option></select>
          </div>
        </div>
      </div>
    </div>

    <div class="form-footer">
      <button class="btn-del" @click="deletePage"><i class="pi pi-trash" /> Xóa</button>
      <div style="display:flex;gap:0.5rem;">
        <Link href="/landing-pages" class="btn-cancel">Hủy</Link>
        <button class="btn-save" @click="savePage" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> Lưu</button>
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
  props: { page: Object, blockTypes: Object, webForms: Array },
  data() {
    return {
      saving: false,
      form: {
        title: this.page.title, description: this.page.description,
        status: this.page.status, meta_title: this.page.meta_title,
        meta_description: this.page.meta_description, web_form_id: this.page.web_form_id,
        blocks: JSON.parse(JSON.stringify(this.page.blocks || [])),
        style_settings: { ...(this.page.style_settings || { primary_color: '#8b5cf6', font: 'Inter' }) },
      },
    }
  },
  methods: {
    addBlock(type) {
      const defaults = JSON.parse(JSON.stringify(this.blockTypes[type]?.defaults || {}))
      this.form.blocks.push({ type, data: defaults })
    },
    savePage() {
      this.saving = true
      router.put(`/landing-pages/${this.page.id}`, this.form, { onFinish: () => { this.saving = false } })
    },
    deletePage() {
      if (!confirm('Xóa landing page này?')) return
      router.delete(`/landing-pages/${this.page.id}`)
    },
    copyUrl() {
      navigator.clipboard.writeText(this.page.public_url)
      alert('Đã copy URL!')
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
.page-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.page-subtitle { font-size: 0.72rem; color: #94a3b8; margin: 0.1rem 0 0; display: flex; align-items: center; gap: 0.3rem; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.sd-draft { background: #f59e0b; }
.sd-published { background: #10b981; }
.sd-archived { background: #94a3b8; }
.header-actions { display: flex; gap: 0.35rem; }
.btn-preview { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; border-radius: 8px; background: #ede9fe; border: 1.5px solid #8b5cf6; color: #8b5cf6; font-size: 0.72rem; font-weight: 700; text-decoration: none; }
.btn-back { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; text-decoration: none; }
.builder-layout { display: grid; grid-template-columns: 1fr 280px; gap: 0.75rem; align-items: start; }
.section-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 1rem; margin-bottom: 0.65rem; }
.sec-title { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0 0 0.6rem; display: flex; align-items: center; gap: 0.3rem; }
.sec-title i { font-size: 0.72rem; color: #8b5cf6; }
.sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem; }
.sec-head .sec-title { margin: 0; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; box-sizing: border-box; }
.fm-input:focus { border-color: #8b5cf6; }
.fm-row { display: flex; gap: 0.5rem; align-items: flex-end; }
.flex-1 { flex: 1; }
.mb-2 { margin-bottom: 0.35rem; }
.block-row { display: flex; gap: 0.35rem; padding: 0.6rem; background: #fafbfc; border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 0.4rem; }
.block-handle { color: #cbd5e1; font-size: 0.6rem; padding-top: 0.3rem; cursor: grab; }
.block-body { flex: 1; }
.block-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; }
.block-type { font-size: 0.68rem; font-weight: 700; color: #8b5cf6; display: flex; align-items: center; gap: 0.2rem; }
.block-type i { font-size: 0.6rem; }
.block-del { width: 22px; height: 22px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.55rem; display: flex; align-items: center; justify-content: center; }
.block-del:hover { color: #ef4444; }
.feat-row { display: flex; gap: 0.25rem; margin-bottom: 0.2rem; align-items: center; }
.opt-del { width: 20px; height: 20px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.5rem; flex-shrink: 0; }
.opt-add { padding: 0.2rem 0.4rem; border: 1px dashed #e2e8f0; border-radius: 5px; background: transparent; color: #94a3b8; font-size: 0.58rem; cursor: pointer; font-family: inherit; margin-top: 0.2rem; }
.color-input { width: 32px; height: 32px; border: none; cursor: pointer; border-radius: 6px; flex-shrink: 0; }
.hint { font-size: 0.62rem; color: #94a3b8; margin: 0.2rem 0 0; }
.add-block-palette { padding: 0.6rem; border: 1.5px dashed #e2e8f0; border-radius: 10px; margin-top: 0.5rem; }
.abp-title { font-size: 0.65rem; font-weight: 600; color: #94a3b8; margin: 0 0 0.3rem; }
.abp-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.3rem; }
.abp-btn { display: flex; flex-direction: column; align-items: center; padding: 0.4rem; border-radius: 7px; border: 1px solid #e2e8f0; background: white; cursor: pointer; transition: all 0.1s; font-family: inherit; }
.abp-btn:hover { border-color: #8b5cf6; background: #ede9fe; }
.abp-btn i { font-size: 0.78rem; color: #64748b; margin-bottom: 0.1rem; }
.abp-btn span { font-size: 0.5rem; color: #475569; font-weight: 600; }
.preview-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; margin-bottom: 0.65rem; }
.preview-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; }
.qs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.3rem; }
.qs-item { text-align: center; padding: 0.4rem; background: #fafbfc; border-radius: 8px; }
.qs-val { font-size: 1rem; font-weight: 800; color: #0f172a; display: block; }
.qs-rate { color: #10b981; }
.qs-lbl { font-size: 0.5rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.url-box { padding: 0.5rem; background: #1e293b; border-radius: 7px; margin-bottom: 0.35rem; }
.url-box code { font-size: 0.58rem; color: #8b5cf6; word-break: break-all; font-family: 'JetBrains Mono', monospace; }
.btn-copy { display: flex; align-items: center; gap: 0.2rem; padding: 0.3rem 0.6rem; border-radius: 6px; background: #ede9fe; border: 1px solid #8b5cf6; color: #8b5cf6; font-size: 0.6rem; font-weight: 700; cursor: pointer; width: 100%; justify-content: center; font-family: inherit; }
.color-input-lg { width: 100%; height: 32px; border: none; cursor: pointer; border-radius: 6px; }
.form-footer { display: flex; justify-content: space-between; padding: 0.75rem 0; margin-top: 0.5rem; }
.btn-del { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #fca5a5; background: white; color: #ef4444; font-size: 0.72rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-cancel { padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; }
.btn-save { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 1rem; border-radius: 8px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-size: 0.72rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }
@media (max-width: 900px) { .builder-layout { grid-template-columns: 1fr; } }
</style>
