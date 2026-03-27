<template>
  <div>
    <Head title="Tạo Nội Dung" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/content-items" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-sparkles" /></div>
        <div><h1 class="page-title">Tạo Nội Dung AI</h1><p class="page-subtitle">Sinh nội dung từ template</p></div>
      </div>
    </div>
    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Template <span class="req">*</span></label>
            <select v-model="form.template_id" class="form-select" :class="{ err: form.errors.template_id }">
              <option :value="null">— Chọn template —</option>
              <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }} ({{ t.category }})</option>
            </select>
            <span v-if="form.errors.template_id" class="form-error">{{ form.errors.template_id }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Loại <span class="req">*</span></label>
            <select v-model="form.type" class="form-select">
              <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <div class="form-group full">
            <label class="form-label">Tiêu đề (tuỳ chọn)</label>
            <input v-model="form.title" class="form-input" placeholder="Tiêu đề nội dung..." />
          </div>
        </div>
        <div class="form-actions">
          <Link href="/content-items"><button type="button" class="btn-cancel">Hủy</button></Link>
          <button type="submit" class="btn-submit" :disabled="form.processing"><i v-if="form.processing" class="pi pi-spin pi-spinner" /><i v-else class="pi pi-sparkles" /> Sinh Nội Dung</button>
        </div>
      </form>
    </div>
  </div>
</template>
<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
export default {
  components: { Head, Link }, layout: Layout,
  props: { templates: Array, types: Array, statuses: Array },
  data() { return { form: useForm({ template_id: null, type: this.types?.[0] || 'blog', title: '', variables: {}, options: {} }) } },
  methods: { submit() { this.form.post('/content-items') } },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;margin-bottom:1.25rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#ec4899;color:#ec4899;background:#fdf2f8}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#ec4899,#db2777);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{font-size:.78rem;color:#64748b;margin:0}
.form-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.5rem}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.form-group{display:flex;flex-direction:column;gap:.3rem}.form-group.full{grid-column:1/-1}.form-label{font-size:.78rem;font-weight:600;color:#374151}.req{color:#ef4444}.form-input,.form-select{padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.82rem;color:#334155;transition:all .2s;background:#fafbfc;outline:none;font-family:inherit;width:100%;box-sizing:border-box}.form-input:focus,.form-select:focus{border-color:#ec4899;background:#fff;box-shadow:0 0 0 3px rgba(236,72,153,.1)}.err{border-color:#ef4444!important}.form-error{font-size:.68rem;color:#ef4444;font-weight:500}
.form-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #f1f5f9}.btn-cancel{padding:.55rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-cancel:hover{background:#f8fafc}.btn-submit{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#ec4899,#db2777);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-submit:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(236,72,153,.3)}.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
</style>
