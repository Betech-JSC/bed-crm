<template>
  <div>
    <Head :title="`Sửa Form: ${webForm.name}`" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-file-edit" style="color:#10b981;" /> {{ webForm.name }}</h1>
        <p class="page-subtitle">
          <span class="status-dot" :class="'st-' + webForm.status" /> {{ webForm.status }}
          · {{ webForm.submissions_count }} submissions · {{ webForm.conversion_rate }}% conversion
        </p>
      </div>
      <div class="header-actions">
        <button class="btn-copy" @click="copyEmbed"><i class="pi pi-code" /> Embed Code</button>
        <Link href="/web-forms" class="btn-back"><i class="pi pi-arrow-left" /> Quay lại</Link>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <button class="tab" :class="{ active: tab === 'editor' }" @click="tab = 'editor'"><i class="pi pi-pencil" /> Form Builder</button>
      <button class="tab" :class="{ active: tab === 'submissions' }" @click="tab = 'submissions'"><i class="pi pi-inbox" /> Submissions <span v-if="submissions.length" class="tab-badge">{{ submissions.length }}</span></button>
      <button class="tab" :class="{ active: tab === 'embed' }" @click="tab = 'embed'"><i class="pi pi-code" /> Embed & Share</button>
    </div>

    <!-- Tab: Editor -->
    <div v-show="tab === 'editor'" class="tab-content">
      <div class="builder-layout">
        <div class="builder-main">
          <!-- Basic Info -->
          <div class="section-card">
            <h3 class="sec-title"><i class="pi pi-info-circle" /> Thông tin</h3>
            <div class="fm-row">
              <div class="fm-group flex-1">
                <label>Tên form</label>
                <input v-model="form.name" type="text" class="fm-input" />
              </div>
              <div class="fm-group" style="width:120px;">
                <label>Trạng thái</label>
                <select v-model="form.status" class="fm-input">
                  <option value="active">Active</option>
                  <option value="paused">Tạm dừng</option>
                  <option value="archived">Lưu trữ</option>
                </select>
              </div>
            </div>
            <div class="fm-group">
              <label>Mô tả</label>
              <textarea v-model="form.description" rows="2" class="fm-input" />
            </div>
            <div class="fm-group">
              <label>Loại form</label>
              <div class="type-grid">
                <div v-for="(info, key) in formTypes" :key="key" class="type-option" :class="{ active: form.form_type === key }" @click="form.form_type = key">
                  <i :class="info.icon" />
                  <span class="to-label">{{ info.label }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Fields -->
          <div class="section-card">
            <div class="sec-head">
              <h3 class="sec-title"><i class="pi pi-list" /> Trường dữ liệu</h3>
              <button class="add-field-btn" @click="addField"><i class="pi pi-plus" /> Thêm</button>
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
                  <input v-model="field.placeholder" type="text" class="fld-input" placeholder="Placeholder" />
                  <label class="fld-check"><input type="checkbox" v-model="field.is_required" /> Bắt buộc</label>
                  <select v-model="field.width" class="fld-select fld-xs">
                    <option :value="100">Full</option>
                    <option :value="50">50%</option>
                  </select>
                </div>
                <div v-if="['select','radio','checkbox'].includes(field.field_type)" class="field-options">
                  <div v-for="(opt, oi) in field.options" :key="oi" class="opt-row">
                    <input v-model="opt.label" type="text" class="fld-input" placeholder="Label" />
                    <input v-model="opt.value" type="text" class="fld-input fld-sm" placeholder="Value" />
                    <button class="opt-del" @click="field.options.splice(oi, 1)"><i class="pi pi-times" /></button>
                  </div>
                  <button class="opt-add" @click="field.options.push({ label: '', value: '' })"><i class="pi pi-plus" /> Option</button>
                </div>
              </div>
              <button class="field-del" @click="form.fields.splice(i, 1)"><i class="pi pi-trash" /></button>
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
                  <option value="redirect">Chuyển hướng</option>
                  <option value="hide">Ẩn form</option>
                </select>
              </div>
              <div v-if="form.success_action === 'message'" class="fm-group flex-1">
                <label>Nội dung</label>
                <input v-model="form.success_message" type="text" class="fm-input" />
              </div>
              <div v-if="form.success_action === 'redirect'" class="fm-group flex-1">
                <label>URL</label>
                <input v-model="form.redirect_url" type="url" class="fm-input" />
              </div>
            </div>
            <div class="toggle-row">
              <label class="toggle-label"><input type="checkbox" v-model="form.auto_create_lead" /> Tự động tạo Lead</label>
              <label class="toggle-label"><input type="checkbox" v-model="form.email_notify" /> Email thông báo</label>
            </div>
            <div v-if="form.email_notify" class="fm-group">
              <label>Email nhận thông báo</label>
              <input v-model="form.notify_emails" type="text" class="fm-input" />
            </div>
          </div>
        </div>

        <!-- Sidebar preview -->
        <div class="builder-sidebar">
          <div class="preview-card">
            <h3 class="preview-title">Xem trước</h3>
            <div class="preview-form">
              <h4 class="pf-heading">{{ form.style_settings?.heading || webForm.name }}</h4>
              <p class="pf-sub" v-if="form.style_settings?.sub_heading">{{ form.style_settings.sub_heading }}</p>
              <div v-for="field in form.fields" :key="field.name" class="pf-field" :style="{ width: (field.width || 100) + '%' }">
                <label class="pf-label">{{ field.label }} <span v-if="field.is_required" class="pf-req">*</span></label>
                <input v-if="!['textarea','select'].includes(field.field_type)" type="text" :placeholder="field.placeholder" class="pf-input" disabled />
                <textarea v-else-if="field.field_type === 'textarea'" :placeholder="field.placeholder" class="pf-input" rows="2" disabled />
                <select v-else class="pf-input" disabled><option>{{ field.placeholder || 'Chọn...' }}</option></select>
              </div>
              <button class="pf-btn">{{ form.style_settings?.button_text || 'Gửi' }}</button>
            </div>
          </div>

          <!-- Quick stats -->
          <div class="preview-card">
            <h3 class="preview-title">Thống kê</h3>
            <div class="qs-grid">
              <div class="qs-item"><span class="qs-val">{{ webForm.views_count }}</span><span class="qs-lbl">Views</span></div>
              <div class="qs-item"><span class="qs-val">{{ webForm.submissions_count }}</span><span class="qs-lbl">Submissions</span></div>
              <div class="qs-item"><span class="qs-val qs-rate">{{ webForm.conversion_rate }}%</span><span class="qs-lbl">Conversion</span></div>
            </div>
          </div>

          <div class="preview-card">
            <h3 class="preview-title">Tuỳ chỉnh giao diện</h3>
            <div class="fm-group"><label>Tiêu đề</label><input v-model="form.style_settings.heading" type="text" class="fm-input" /></div>
            <div class="fm-group"><label>Phụ đề</label><input v-model="form.style_settings.sub_heading" type="text" class="fm-input" /></div>
            <div class="fm-row">
              <div class="fm-group flex-1"><label>Màu chính</label><input v-model="form.style_settings.primary_color" type="color" class="color-input" /></div>
              <div class="fm-group flex-1"><label>Nút</label><input v-model="form.style_settings.button_text" type="text" class="fm-input" /></div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-footer">
        <button class="btn-del" @click="deleteForm"><i class="pi pi-trash" /> Xóa form</button>
        <div style="display:flex;gap:0.5rem;">
          <Link href="/web-forms" class="btn-cancel">Hủy</Link>
          <button class="btn-save" @click="saveForm" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'" /> Lưu thay đổi</button>
        </div>
      </div>
    </div>

    <!-- Tab: Submissions -->
    <div v-show="tab === 'submissions'" class="tab-content">
      <div v-if="submissions.length" class="subs-table-wrap">
        <table class="subs-table">
          <thead>
            <tr>
              <th>#</th>
              <th v-for="field in webForm.fields?.slice(0, 4)" :key="field.name">{{ field.label }}</th>
              <th>UTM</th>
              <th>Thời gian</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sub in submissions" :key="sub.id">
              <td>{{ sub.id }}</td>
              <td v-for="field in webForm.fields?.slice(0, 4)" :key="field.name" class="sub-val">{{ sub.data?.[field.name] || '—' }}</td>
              <td><span v-if="sub.utm_summary" class="utm-tag">{{ sub.utm_summary }}</span><span v-else class="sub-na">—</span></td>
              <td class="sub-time">{{ sub.created_at }}</td>
              <td><span class="sub-status" :class="'ss-' + sub.status">{{ sub.status }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon"><i class="pi pi-inbox" /></div>
        <h3>Chưa có submission</h3>
        <p>Embed form vào website để bắt đầu thu lead</p>
      </div>
    </div>

    <!-- Tab: Embed -->
    <div v-show="tab === 'embed'" class="tab-content">
      <div class="section-card">
        <h3 class="sec-title"><i class="pi pi-code" /> Embed Code</h3>
        <p class="embed-desc">Copy đoạn code dưới đây và dán vào website của bạn:</p>
        <div class="embed-code-block">
          <pre>{{ webForm.embed_code }}</pre>
          <button class="copy-btn" @click="copyEmbed"><i class="pi pi-copy" /> Copy</button>
        </div>
      </div>
      <div class="section-card">
        <h3 class="sec-title"><i class="pi pi-link" /> Direct Link</h3>
        <div class="embed-code-block">
          <pre>{{ webForm.embed_url }}</pre>
          <button class="copy-btn" @click="copyLink"><i class="pi pi-copy" /> Copy</button>
        </div>
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
  props: { webForm: Object, submissions: Array, formTypes: Object, fieldTypes: Object, crmMappings: Object },
  data() {
    return {
      tab: 'editor',
      saving: false,
      form: {
        name: this.webForm.name,
        description: this.webForm.description,
        form_type: this.webForm.form_type,
        status: this.webForm.status,
        success_action: this.webForm.success_action,
        success_message: this.webForm.success_message,
        redirect_url: this.webForm.redirect_url,
        email_notify: this.webForm.email_notify,
        notify_emails: this.webForm.notify_emails,
        auto_create_lead: this.webForm.auto_create_lead,
        lead_source: this.webForm.lead_source,
        lead_status: this.webForm.lead_status,
        style_settings: { ...(this.webForm.style_settings || {}) },
        trigger_settings: this.webForm.trigger_settings,
        fields: (this.webForm.fields || []).map(f => ({ ...f, options: f.options || [] })),
      },
    }
  },
  methods: {
    addField() {
      this.form.fields.push({ field_type: 'text', label: '', name: '', placeholder: '', is_required: false, crm_mapping: '', options: [], width: 100 })
    },
    saveForm() {
      this.saving = true
      router.put(`/web-forms/${this.webForm.id}`, this.form, { onFinish: () => { this.saving = false } })
    },
    deleteForm() {
      if (!confirm('Xóa form này và tất cả submissions?')) return
      router.delete(`/web-forms/${this.webForm.id}`)
    },
    copyEmbed() {
      navigator.clipboard.writeText(this.webForm.embed_code)
      alert('Đã copy embed code!')
    },
    copyLink() {
      navigator.clipboard.writeText(this.webForm.embed_url)
      alert('Đã copy link!')
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
.page-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem; }
.page-subtitle { font-size: 0.72rem; color: #94a3b8; margin: 0.1rem 0 0; display: flex; align-items: center; gap: 0.3rem; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.st-active { background: #10b981; }
.st-paused { background: #f59e0b; }
.st-archived { background: #94a3b8; }
.header-actions { display: flex; gap: 0.35rem; }
.btn-copy { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; border-radius: 8px; background: #ecfdf5; border: 1.5px solid #10b981; color: #10b981; font-size: 0.72rem; font-weight: 700; cursor: pointer; font-family: inherit; }
.btn-back { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; text-decoration: none; }

/* Tabs */
.tabs-bar { display: flex; gap: 0; border-bottom: 1.5px solid #f1f5f9; margin-bottom: 0.75rem; }
.tab { padding: 0.5rem 0.9rem; border: none; background: transparent; font-size: 0.72rem; font-weight: 700; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; display: flex; align-items: center; gap: 0.25rem; font-family: inherit; transition: all 0.12s; }
.tab.active { color: #10b981; border-bottom-color: #10b981; }
.tab:hover { color: #10b981; }
.tab-badge { padding: 0.1rem 0.35rem; border-radius: 9px; background: #ef4444; color: white; font-size: 0.5rem; font-weight: 800; }

/* Builder reuse from Create */
.builder-layout { display: grid; grid-template-columns: 1fr 320px; gap: 0.75rem; align-items: start; }
.section-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 1rem; margin-bottom: 0.65rem; }
.sec-title { font-size: 0.85rem; font-weight: 700; color: #0f172a; margin: 0 0 0.6rem; display: flex; align-items: center; gap: 0.3rem; }
.sec-title i { font-size: 0.72rem; color: #10b981; }
.sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem; }
.sec-head .sec-title { margin: 0; }
.fm-group { margin-bottom: 0.55rem; }
.fm-group label { display: block; font-size: 0.68rem; font-weight: 600; color: #475569; margin-bottom: 0.15rem; }
.fm-input { width: 100%; padding: 0.42rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 0.78rem; color: #1e293b; outline: none; font-family: inherit; }
.fm-input:focus { border-color: #10b981; }
.fm-row { display: flex; gap: 0.5rem; }
.flex-1 { flex: 1; }
.type-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.3rem; }
.type-option { display: flex; flex-direction: column; align-items: center; padding: 0.45rem; border-radius: 8px; border: 1.5px solid #e2e8f0; cursor: pointer; text-align: center; transition: all 0.12s; }
.type-option.active { border-color: #10b981; background: #ecfdf5; }
.type-option i { font-size: 0.85rem; color: #64748b; margin-bottom: 0.1rem; }
.type-option.active i { color: #10b981; }
.to-label { font-size: 0.62rem; font-weight: 700; color: #1e293b; }
.add-field-btn { display: flex; align-items: center; gap: 0.2rem; padding: 0.3rem 0.6rem; border-radius: 7px; background: #ecfdf5; border: 1.5px solid #10b981; color: #10b981; font-size: 0.68rem; font-weight: 700; cursor: pointer; font-family: inherit; }
.field-row { display: flex; gap: 0.35rem; padding: 0.5rem; background: #fafbfc; border-radius: 8px; border: 1px solid #f1f5f9; margin-bottom: 0.3rem; align-items: flex-start; }
.field-drag { color: #cbd5e1; font-size: 0.6rem; padding-top: 0.4rem; cursor: grab; }
.field-body { flex: 1; }
.field-top, .field-bottom { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.field-bottom { margin-top: 0.2rem; }
.fld-input { padding: 0.28rem 0.4rem; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 0.65rem; color: #1e293b; outline: none; font-family: inherit; flex: 1; min-width: 70px; }
.fld-select { padding: 0.28rem; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 0.62rem; color: #475569; font-family: inherit; outline: none; background: white; }
.fld-sm { max-width: 100px; }
.fld-xs { max-width: 65px; }
.fld-check { display: flex; align-items: center; gap: 0.2rem; font-size: 0.58rem; color: #64748b; font-weight: 600; white-space: nowrap; }
.fld-check input { accent-color: #10b981; }
.field-del { width: 24px; height: 24px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.55rem; }
.field-del:hover { color: #ef4444; }
.field-options { margin-top: 0.2rem; padding-top: 0.2rem; border-top: 1px dashed #e2e8f0; }
.opt-row { display: flex; gap: 0.2rem; margin-bottom: 0.15rem; align-items: center; }
.opt-del { width: 18px; height: 18px; border: none; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 0.45rem; }
.opt-add { padding: 0.15rem 0.35rem; border: 1px dashed #e2e8f0; border-radius: 4px; background: transparent; color: #94a3b8; font-size: 0.55rem; cursor: pointer; font-family: inherit; }
.toggle-row { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.4rem; }
.toggle-label { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: #475569; font-weight: 600; cursor: pointer; }
.toggle-label input { accent-color: #10b981; }

/* Preview */
.preview-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; margin-bottom: 0.65rem; }
.preview-title { font-size: 0.78rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem; }
.preview-form { padding: 0.85rem; border: 1.5px solid #e2e8f0; border-radius: 10px; display: flex; flex-wrap: wrap; gap: 0.35rem; }
.pf-heading { font-size: 0.9rem; font-weight: 800; margin: 0 0 0.1rem; width: 100%; }
.pf-sub { font-size: 0.6rem; color: #94a3b8; margin: 0 0 0.25rem; width: 100%; }
.pf-field { box-sizing: border-box; }
.pf-label { display: block; font-size: 0.55rem; font-weight: 600; color: #475569; margin-bottom: 0.05rem; }
.pf-req { color: #ef4444; }
.pf-input { width: 100%; padding: 0.3rem 0.4rem; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 0.65rem; color: #94a3b8; }
.pf-btn { width: 100%; padding: 0.4rem; border: none; border-radius: 7px; background: #10b981; color: white; font-size: 0.72rem; font-weight: 700; cursor: pointer; margin-top: 0.2rem; }
.qs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.3rem; }
.qs-item { text-align: center; padding: 0.4rem; background: #fafbfc; border-radius: 8px; }
.qs-val { font-size: 1rem; font-weight: 800; color: #0f172a; display: block; }
.qs-rate { color: #10b981; }
.qs-lbl { font-size: 0.5rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.color-input { width: 32px; height: 32px; border: none; cursor: pointer; border-radius: 6px; }

/* Submissions table */
.subs-table-wrap { overflow-x: auto; }
.subs-table { width: 100%; border-collapse: collapse; font-size: 0.72rem; }
.subs-table th { padding: 0.5rem 0.65rem; background: #f8fafc; text-align: left; font-weight: 700; color: #475569; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1.5px solid #e2e8f0; }
.subs-table td { padding: 0.5rem 0.65rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.sub-val { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.utm-tag { padding: 0.12rem 0.35rem; border-radius: 4px; background: #eef2ff; color: #6366f1; font-size: 0.58rem; font-weight: 600; }
.sub-na { color: #cbd5e1; }
.sub-time { color: #94a3b8; font-size: 0.65rem; }
.sub-status { padding: 0.12rem 0.35rem; border-radius: 5px; font-size: 0.58rem; font-weight: 700; }
.ss-new { background: #ecfdf5; color: #10b981; }
.ss-contacted { background: #eef2ff; color: #6366f1; }
.ss-converted { background: #fef3c7; color: #f59e0b; }
.ss-spam { background: #fef2f2; color: #ef4444; }

/* Embed section */
.embed-desc { font-size: 0.72rem; color: #64748b; margin: 0 0 0.5rem; }
.embed-code-block { position: relative; background: #1e293b; border-radius: 10px; padding: 0.85rem; overflow-x: auto; }
.embed-code-block pre { margin: 0; font-size: 0.7rem; color: #10b981; white-space: pre-wrap; word-break: break-all; font-family: 'JetBrains Mono', monospace; }
.copy-btn { position: absolute; top: 0.5rem; right: 0.5rem; padding: 0.25rem 0.55rem; border-radius: 6px; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #10b981; font-size: 0.6rem; font-weight: 700; cursor: pointer; }

/* Empty */
.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 48px; height: 48px; border-radius: 14px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem; }
.empty-icon i { font-size: 1.1rem; color: #94a3b8; }
.empty-state h3 { font-size: 0.95rem; color: #1e293b; margin: 0 0 0.2rem; }
.empty-state p { font-size: 0.72rem; color: #94a3b8; margin: 0; }

/* Footer */
.form-footer { display: flex; justify-content: space-between; padding: 0.75rem 0; margin-top: 0.5rem; }
.btn-del { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #fca5a5; background: white; color: #ef4444; font-size: 0.72rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-del:hover { background: #fef2f2; }
.btn-cancel { padding: 0.4rem 0.8rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; color: #64748b; font-size: 0.72rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; }
.btn-save { display: flex; align-items: center; gap: 0.25rem; padding: 0.4rem 1rem; border-radius: 8px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.72rem; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 900px) { .builder-layout { grid-template-columns: 1fr; } }
</style>
