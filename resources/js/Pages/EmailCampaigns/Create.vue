<template>
  <div>
    <Head title="Tạo Campaign" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/email-campaigns" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-megaphone" /></div>
        <div>
          <h1 class="page-title">Tạo Campaign Mới</h1>
          <p class="page-subtitle">Thiết lập chiến dịch email marketing</p>
        </div>
      </div>
    </div>

    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Tên campaign <span class="req">*</span></label>
            <input v-model="form.name" class="form-input" :class="{ err: form.errors.name }" placeholder="VD: Chào mừng khách hàng mới..." />
            <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
          </div>

          <div class="form-group full">
            <label class="form-label">Mô tả</label>
            <textarea v-model="form.description" class="form-textarea" rows="3" placeholder="Mô tả ngắn về chiến dịch..." />
          </div>

          <div class="form-group">
            <label class="form-label">Subject <span class="req">*</span></label>
            <input v-model="form.subject" class="form-input" :class="{ err: form.errors.subject }" placeholder="Tiêu đề email..." />
            <span v-if="form.errors.subject" class="form-error">{{ form.errors.subject }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Template (tuỳ chọn)</label>
            <select v-model="form.email_template_id" class="form-select">
              <option :value="null">— Không chọn —</option>
              <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Danh sách gửi <span class="req">*</span></label>
            <select v-model="form.email_list_id" class="form-select" :class="{ err: form.errors.email_list_id }">
              <option :value="null">— Chọn danh sách —</option>
              <option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }} ({{ l.contacts_count }} contacts)</option>
            </select>
            <span v-if="form.errors.email_list_id" class="form-error">{{ form.errors.email_list_id }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Lên lịch gửi</label>
            <input v-model="form.scheduled_at" type="datetime-local" class="form-input" />
            <span class="form-hint">Bỏ trống = lưu nháp</span>
          </div>

          <div class="form-group full">
            <label class="form-label">Nội dung HTML</label>
            <textarea v-model="form.body_html" class="form-textarea" rows="12" placeholder="Nội dung HTML email..." />
          </div>

          <div class="form-group full">
            <label class="form-label">Nội dung Text (fallback)</label>
            <textarea v-model="form.body_text" class="form-textarea" rows="5" placeholder="Phiên bản text..." />
          </div>
        </div>

        <div class="form-actions">
          <Link href="/email-campaigns"><button type="button" class="btn-cancel">Hủy</button></Link>
          <button type="submit" class="btn-submit" :disabled="form.processing">
            <i v-if="form.processing" class="pi pi-spin pi-spinner" />
            <i v-else class="pi pi-check" /> Tạo Campaign
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
export default {
  components: { Head, Link },
  layout: Layout,
  props: { templates: Array, lists: Array },
  data() {
    return {
      form: useForm({
        name: '', description: '', subject: '', body_html: '', body_text: '',
        email_template_id: null, email_list_id: null, scheduled_at: null,
      }),
    }
  },
  methods: { submit() { this.form.post('/email-campaigns') } },
}
</script>

<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#3b82f6;color:#3b82f6;background:#eff6ff}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{font-size:.78rem;color:#64748b;margin:0}
.form-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.5rem}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.form-group{display:flex;flex-direction:column;gap:.3rem}.form-group.full{grid-column:1/-1}.form-label{font-size:.78rem;font-weight:600;color:#374151}.req{color:#ef4444}.form-input,.form-select,.form-textarea{padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.82rem;color:#334155;transition:all .2s;background:#fafbfc;outline:none;font-family:inherit;width:100%;box-sizing:border-box}.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 3px rgba(59,130,246,.1)}.err{border-color:#ef4444!important}.form-textarea{resize:vertical;min-height:60px}.form-error{font-size:.68rem;color:#ef4444;font-weight:500}.form-hint{font-size:.68rem;color:#94a3b8}
.form-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #f1f5f9}.btn-cancel{padding:.55rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-cancel:hover{background:#f8fafc}.btn-submit{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-submit:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(59,130,246,.3)}.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
</style>
