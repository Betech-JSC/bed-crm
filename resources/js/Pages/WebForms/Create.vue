<template>
  <div>
    <Head title="Tạo Web Form" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-plus" style="color:#10b981;" /> Tạo Web Form</h1>
        <p class="page-subtitle">Thiết kế form thu lead và nhúng vào website</p>
      </div>
      <Link href="/web-forms" class="btn-back"><i class="pi pi-arrow-left" /> Quay lại</Link>
    </div>

    <div class="builder-layout">
      <!-- Left: Form Config -->
      <div class="builder-main">
        <!-- Basic Info -->
        <div class="section-card">
          <h3 class="sec-title"><i class="pi pi-info-circle" /> Thông tin cơ bản</h3>
          <div class="fm-group">
            <label>Tên form <span class="req">*</span></label>
            <input v-model="form.name" type="text" class="fm-input" placeholder="VD: Form liên hệ trang chủ" />
          </div>
          <div class="fm-group">
            <label>Mô tả</label>
            <textarea v-model="form.description" rows="2" class="fm-input" placeholder="Mô tả mục đích form..." />
          </div>
          <div class="fm-group">
            <label>Loại form</label>
            <div class="type-grid">
              <div v-for="(info, key) in formTypes" :key="key" class="type-option" :class="{ active: form.form_type === key }" @click="form.form_type = key">
                <i :class="info.icon" />
                <span class="to-label">{{ info.label }}</span>
                <span class="to-desc">{{ info.desc }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Fields Builder -->
        <div class="section-card">
          <div class="sec-head">
            <h3 class="sec-title"><i class="pi pi-list" /> Trường dữ liệu</h3>
            <button class="add-field-btn" @click="addField"><i class="pi pi-plus" /> Thêm trường</button>
          </div>

          <div v-for="(field, i) in form.fields" :key="i" class="field-row">
            <div class="field-drag"><i class="pi pi-bars" /></div>
            <div class="field-body">
              <div class="field-top">
                <select v-model="field.field_type" class="fld-select">
                  <option v-for="(info, key) in fieldTypes" :key="key" :value="key">{{ info.label }}</option>
                </select>
                <input v-model="field.label" type="text" class="fld-input" placeholder="Label" />
                <input v-model="field.name" type="text" class="fld-input fld-sm" placeholder="Field name" />
                <select v-model="field.crm_mapping" class="fld-select">
                  <option v-for="(label, key) in crmMappings" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="field-bottom">
                <input v-model="field.placeholder" type="text" class="fld-input" placeholder="Placeholder..." />
                <label class="fld-check">
                  <input type="checkbox" v-model="field.is_required" /> Bắt buộc
                </label>
                <select v-model="field.width" class="fld-select fld-xs">
                  <option :value="100">Full</option>
                  <option :value="50">50%</option>
                </select>
              </div>

              <!-- Options for select/radio/checkbox -->
              <div v-if="['select', 'radio', 'checkbox'].includes(field.field_type)" class="field-options">
                <div v-for="(opt, oi) in field.options" :key="oi" class="opt-row">
                  <input v-model="opt.label" type="text" class="fld-input" placeholder="Label" />
                  <input v-model="opt.value" type="text" class="fld-input fld-sm" placeholder="Value" />
                  <button class="opt-del" @click="field.options.splice(oi, 1)"><i class="pi pi-times" /></button>
                </div>
                <button class="opt-add" @click="field.options.push({ label: '', value: '' })"><i class="pi pi-plus" /> Thêm option</button>
              </div>
            </div>
            <button class="field-del" @click="form.fields.splice(i, 1)"><i class="pi pi-trash" /></button>
          </div>

          <div v-if="!form.fields.length" class="no-fields">
            <p>Chưa có trường nào. Click "Thêm trường" để bắt đầu.</p>
          </div>
        </div>

        <!-- Settings -->
        <div class="section-card">
          <h3 class="sec-title"><i class="pi pi-cog" /> Cài đặt</h3>

          <div class="fm-row">
            <div class="fm-group flex-1">
              <label>Sau khi gửi</label>
              <select v-model="form.success_action" class="fm-input">
                <option value="message">Hiện thông báo</option>
                <option value="redirect">Chuyển hướng URL</option>
                <option value="hide">Ẩn form</option>
              </select>
            </div>
            <div v-if="form.success_action === 'message'" class="fm-group flex-1">
              <label>Nội dung thông báo</label>
              <input v-model="form.success_message" type="text" class="fm-input" placeholder="Cảm ơn bạn!" />
            </div>
            <div v-if="form.success_action === 'redirect'" class="fm-group flex-1">
              <label>URL chuyển hướng</label>
              <input v-model="form.redirect_url" type="url" class="fm-input" placeholder="https://..." />
            </div>
          </div>

          <div class="toggle-row">
            <label class="toggle-label">
              <input type="checkbox" v-model="form.auto_create_lead" />
              <span>Tự động tạo Lead trong CRM</span>
            </label>
            <label class="toggle-label">
              <input type="checkbox" v-model="form.email_notify" />
              <span>Gửi email thông báo khi có submission</span>
            </label>
          </div>

          <div v-if="form.email_notify" class="fm-group">
            <label>Email nhận thông báo</label>
            <input v-model="form.notify_emails" type="text" class="fm-input" placeholder="email1@example.com, email2@..." />
          </div>

          <div v-if="form.auto_create_lead" class="fm-row">
            <div class="fm-group flex-1">
              <label>Lead Source</label>
              <input v-model="form.lead_source" type="text" class="fm-input" placeholder="web_form" />
            </div>
            <div class="fm-group flex-1">
              <label>Lead Status</label>
              <select v-model="form.lead_status" class="fm-input">
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Preview -->
      <div class="builder-sidebar">
        <div class="preview-card">
          <h3 class="preview-title">Xem trước</h3>
          <div class="preview-form" :style="previewStyle">
            <h4 class="pf-heading">{{ form.style_settings?.heading || 'Liên hệ với chúng tôi' }}</h4>
            <p class="pf-sub" v-if="form.style_settings?.sub_heading">{{ form.style_settings.sub_heading }}</p>
            <div v-for="field in form.fields" :key="field.name" class="pf-field" :style="{ width: (field.width || 100) + '%' }">
              <label class="pf-label">{{ field.label }} <span v-if="field.is_required" class="pf-req">*</span></label>
              <input v-if="!['textarea','select','checkbox','radio'].includes(field.field_type)"
                :type="field.field_type === 'email' ? 'email' : field.field_type === 'phone' ? 'tel' : 'text'"
                :placeholder="field.placeholder" class="pf-input" disabled />
              <textarea v-else-if="field.field_type === 'textarea'" :placeholder="field.placeholder" class="pf-input" rows="2" disabled />
              <select v-else-if="field.field_type === 'select'" class="pf-input" disabled>
                <option>{{ field.placeholder || 'Chọn...' }}</option>
              </select>
            </div>
            <button class="pf-btn" :style="{ background: form.style_settings?.primary_color || '#10b981' }">
              {{ form.style_settings?.button_text || 'Gửi thông tin' }}
            </button>
          </div>
        </div>

        <!-- Style Settings -->
        <div class="style-card">
          <h3 class="preview-title">Tuỳ chỉnh giao diện</h3>
          <div class="fm-group">
            <label>Tiêu đề form</label>
            <input v-model="form.style_settings.heading" type="text" class="fm-input" />
          </div>
          <div class="fm-group">
            <label>Phụ đề</label>
            <input v-model="form.style_settings.sub_heading" type="text" class="fm-input" />
          </div>
          <div class="fm-row">
            <div class="fm-group flex-1">
              <label>Màu chính</label>
              <div class="color-row">
                <input v-model="form.style_settings.primary_color" type="color" class="color-input" />
                <span>{{ form.style_settings.primary_color }}</span>
              </div>
            </div>
            <div class="fm-group flex-1">
              <label>Nút text</label>
              <input v-model="form.style_settings.button_text" type="text" class="fm-input" />
            </div>
          </div>
          <div class="fm-group">
            <label>Bo góc (px)</label>
            <input v-model.number="form.style_settings.border_radius" type="range" min="0" max="24" class="range-input" />
            <span class="range-val">{{ form.style_settings.border_radius }}px</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
      <Link href="/web-forms" class="btn-cancel">Hủy</Link>
      <button class="btn-save" @click="submit" :disabled="!form.name || !form.fields.length || saving">
        <i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" />
        Tạo Form
      </button>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { formTypes: Object, fieldTypes: Object, crmMappings: Object, defaultFields: Array },
  data() {
    return {
      saving: false,
      form: {
        name: '',
        description: '',
        form_type: 'inline',
        success_action: 'message',
        success_message: 'Cảm ơn bạn! Chúng tôi sẽ liên hệ sớm nhất.',
        redirect_url: '',
        email_notify: true,
        notify_emails: '',
        auto_create_lead: true,
        lead_source: 'web_form',
        lead_status: 'new',
        style_settings: {
          primary_color: '#10b981',
          bg_color: '#ffffff',
          text_color: '#1e293b',
          border_radius: 12,
          button_text: 'Gửi thông tin',
          heading: 'Liên hệ với chúng tôi',
          sub_heading: 'Để lại thông tin, chúng tôi sẽ tư vấn miễn phí cho bạn',
        },
        trigger_settings: null,
        fields: this.defaultFields ? [...this.defaultFields.map(f => ({ ...f, options: f.options || [] }))] : [],
      },
    }
  },
  computed: {
    previewStyle() {
      const s = this.form.style_settings
      return {
        background: s.bg_color || '#ffffff',
        color: s.text_color || '#1e293b',
        borderRadius: (s.border_radius || 12) + 'px',
      }
    },
  },
  methods: {
    addField() {
      this.form.fields.push({
        field_type: 'text', label: '', name: '', placeholder: '',
        is_required: false, crm_mapping: '', options: [], width: 100,
      })
    },
    submit() {
      this.saving = true
      router.post('/web-forms', this.form, {
        onFinish: () => { this.saving = false },
      })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
.page-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.page-subtitle { font-size: 0.75rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-back { display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; text-decoration: none; cursor: pointer; }

.builder-layout { display: grid; grid-template-columns: 1fr 340px; gap: 0.75rem; align-items: start; }

/* Section cards */
.section-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 1rem; margin-bottom: 0.65rem; }
.sec-title { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0 0 0.6rem; display: flex; align-items: center; gap: 0.3rem; }
.sec-title i { font-size: 0.72rem; color: #10b981; }
.sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem; }
.sec-head .sec-title { margin: 0; }

/* Forms */
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.req { color: #ef4444; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; }
.fm-input:focus { border-color: #10b981; }
textarea.fm-input { resize: vertical; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }

/* Type selector */
.type-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.4rem; }
.type-option { display: flex; flex-direction: column; align-items: center; padding: 0.55rem; border-radius: 10px; border: 1.5px solid #e2e8f0; cursor: pointer; text-align: center; transition: all 0.12s; }
.type-option:hover { border-color: #10b981; }
.type-option.active { border-color: #10b981; background: #ecfdf5; }
.type-option i { font-size: 1rem; color: #64748b; margin-bottom: 0.2rem; }
.type-option.active i { color: #10b981; }
.to-label { font-size: 0.7rem; font-weight: 700; color: #1e293b; }
.to-desc { font-size: 0.55rem; color: #94a3b8; }

/* Fields builder */
.add-field-btn { display: flex; align-items: center; gap: 0.2rem; padding: 0.3rem 0.6rem; border-radius: 7px; background: #ecfdf5; border: 1.5px solid #10b981; color: #10b981; font-size: 0.68rem; font-weight: 700; cursor: pointer; font-family: inherit; }
.field-row { display: flex; gap: 0.4rem; padding: 0.55rem; background: #fafbfc; border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 0.35rem; align-items: flex-start; }
.field-drag { color: #cbd5e1; font-size: 0.65rem; padding-top: 0.5rem; cursor: grab; }
.field-body { flex: 1; }
.field-top, .field-bottom { display: flex; gap: 0.3rem; align-items: center; flex-wrap: wrap; }
.field-bottom { margin-top: 0.25rem; }
.fld-input { padding: 0.3rem 0.45rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.68rem; color: #1e293b; outline: none; font-family: inherit; flex: 1; min-width: 80px; }
.fld-input:focus { border-color: #10b981; }
.fld-sm { max-width: 110px; }
.fld-select { padding: 0.3rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.65rem; color: #475569; font-family: inherit; outline: none; background: white; }
.fld-xs { max-width: 70px; }
.fld-check { display: flex; align-items: center; gap: 0.2rem; font-size: 0.62rem; color: #64748b; font-weight: 600; white-space: nowrap; }
.fld-check input { accent-color: #10b981; }
.field-del { width: 26px; height: 26px; border-radius: 6px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; margin-top: 0.35rem; }
.field-del:hover { color: #ef4444; }
.no-fields { text-align: center; padding: 1.5rem; color: #94a3b8; font-size: 0.78rem; }

/* Field options (for select/radio) */
.field-options { margin-top: 0.3rem; padding-top: 0.3rem; border-top: 1px dashed #e2e8f0; }
.opt-row { display: flex; gap: 0.25rem; margin-bottom: 0.2rem; align-items: center; }
.opt-del { width: 20px; height: 20px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.5rem; }
.opt-add { padding: 0.2rem 0.4rem; border: 1px dashed #e2e8f0; border-radius: 5px; background: transparent; color: #94a3b8; font-size: 0.58rem; cursor: pointer; font-family: inherit; }

/* Toggle */
.toggle-row { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 0.5rem; }
.toggle-label { display: flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: #475569; font-weight: 600; cursor: pointer; }
.toggle-label input { accent-color: #10b981; }

/* Preview sidebar */
.preview-card, .style-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; margin-bottom: 0.65rem; }
.preview-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; }
.preview-form { padding: 1rem; border: 1.5px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 0.4rem; }
.pf-heading { font-size: 0.95rem; font-weight: 800; margin: 0 0 0.1rem; width: 100%; }
.pf-sub { font-size: 0.65rem; color: #94a3b8; margin: 0 0 0.4rem; width: 100%; }
.pf-field { box-sizing: border-box; }
.pf-label { display: block; font-size: 0.6rem; font-weight: 600; color: #475569; margin-bottom: 0.1rem; }
.pf-req { color: #ef4444; }
.pf-input { width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.7rem; color: #94a3b8; }
.pf-btn { width: 100%; padding: 0.5rem; border: none; border-radius: 8px; color: white; font-size: 0.78rem; font-weight: 700; cursor: pointer; margin-top: 0.3rem; }

/* Style card */
.color-row { display: flex; align-items: center; gap: 0.35rem; }
.color-input { width: 32px; height: 32px; border: none; cursor: pointer; border-radius: 6px; }
.color-row span { font-size: 0.68rem; color: #64748b; }
.range-input { width: 100%; accent-color: #10b981; }
.range-val { font-size: 0.62rem; color: #94a3b8; }

/* Footer */
.form-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.75rem 0; margin-top: 0.5rem; }
.btn-cancel { padding: 0.45rem 0.85rem; border-radius: 9px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.78rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; }
.btn-save { display: flex; align-items: center; gap: 0.3rem; padding: 0.45rem 1.1rem; border-radius: 9px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 900px) {
  .builder-layout { grid-template-columns: 1fr; }
}
</style>
