<template>
  <div>
    <Head title="Tạo Email Template" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <Link href="/email-templates" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-file-edit" /></div>
        <div>
          <h1 class="page-title">Tạo Template Mới</h1>
          <p class="page-subtitle">Thiết lập mẫu email cho chiến dịch</p>
        </div>
      </div>
    </div>

    <!-- Form -->
    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Tên template <span class="required">*</span></label>
            <input v-model="form.name" class="form-input" :class="{ 'has-error': form.errors.name }" placeholder="VD: Welcome Email, Follow-up..." />
            <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Loại <span class="required">*</span></label>
            <select v-model="form.type" class="form-select" :class="{ 'has-error': form.errors.type }">
              <option v-for="t in types" :key="t" :value="t">{{ typeLabel(t) }}</option>
            </select>
            <span v-if="form.errors.type" class="form-error">{{ form.errors.type }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Subject <span class="required">*</span></label>
            <input v-model="form.subject" class="form-input" :class="{ 'has-error': form.errors.subject }" placeholder="Tiêu đề email..." />
            <span v-if="form.errors.subject" class="form-error">{{ form.errors.subject }}</span>
          </div>

          <div class="form-group full">
            <label class="form-label">Nội dung HTML</label>
            <textarea v-model="form.body_html" class="form-textarea" rows="12" placeholder="Nhập nội dung HTML email..." />
            <span class="form-hint">Hỗ trợ biến: {{ '{' + '{name}' + '}' }}, {{ '{' + '{email}' + '}' }}, {{ '{' + '{company}' + '}' }}, {{ '{' + '{unsubscribe_url}' + '}' }}</span>
          </div>

          <div class="form-group full">
            <label class="form-label">Nội dung Text (fallback)</label>
            <textarea v-model="form.body_text" class="form-textarea" rows="6" placeholder="Phiên bản text thuần cho email client không hỗ trợ HTML..." />
          </div>

          <div class="form-group full">
            <label class="toggle-row">
              <input type="checkbox" v-model="form.is_active" class="toggle-input" />
              <span class="toggle-switch" />
              <span class="toggle-label">Kích hoạt template</span>
            </label>
          </div>
        </div>

        <div class="form-actions">
          <Link href="/email-templates"><button type="button" class="btn-cancel">Hủy</button></Link>
          <button type="submit" class="btn-submit" :disabled="form.processing">
            <i v-if="form.processing" class="pi pi-spin pi-spinner" />
            <i v-else class="pi pi-check" />
            Tạo Template
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
  props: { types: Array, variables: Array },
  data() {
    return {
      form: useForm({
        name: '', subject: '', body_html: '', body_text: '', type: 'campaign', is_active: true,
      }),
    }
  },
  methods: {
    typeLabel(t) { return { campaign: 'Campaign', automation: 'Automation', transactional: 'Transactional' }[t] || t },
    submit() { this.form.post('/email-templates') },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.header-left { display: flex; align-items: center; gap: 0.75rem; }
.back-btn { width: 36px; height: 36px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; color: #64748b; text-decoration: none; transition: all 0.2s; font-size: 0.85rem; }
.back-btn:hover { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
.header-icon { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #6366f1, #4f46e5); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; }
.page-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.78rem; color: #64748b; margin: 0; }

.form-card { background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; padding: 1.5rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group.full { grid-column: 1 / -1; }
.form-label { font-size: 0.78rem; font-weight: 600; color: #374151; }
.required { color: #ef4444; }
.form-input, .form-select, .form-textarea { padding: 0.55rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.82rem; color: #334155; transition: all 0.2s; background: #fafbfc; outline: none; font-family: inherit; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #6366f1; background: white; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.form-input.has-error, .form-select.has-error { border-color: #ef4444; }
.form-textarea { resize: vertical; min-height: 80px; }
.form-error { font-size: 0.68rem; color: #ef4444; font-weight: 500; }
.form-hint { font-size: 0.68rem; color: #94a3b8; }

.toggle-row { display: flex; align-items: center; gap: 0.6rem; cursor: pointer; }
.toggle-input { display: none; }
.toggle-switch { width: 36px; height: 20px; border-radius: 10px; background: #d1d5db; transition: all 0.2s; position: relative; flex-shrink: 0; }
.toggle-switch::after { content: ''; position: absolute; width: 16px; height: 16px; border-radius: 50%; background: white; top: 2px; left: 2px; transition: all 0.2s; }
.toggle-input:checked + .toggle-switch { background: #6366f1; }
.toggle-input:checked + .toggle-switch::after { transform: translateX(16px); }
.toggle-label { font-size: 0.82rem; font-weight: 500; color: #475569; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.6rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9; }
.btn-cancel { padding: 0.55rem 1rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: white; color: #475569; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-cancel:hover { background: #f8fafc; }
.btn-submit { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
</style>
