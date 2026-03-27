<template>
  <div>
    <Head title="Chỉnh sửa Email List" />
    <div class="page-header">
      <div class="header-left">
        <Link :href="`/email-lists/${list.id}`" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-list" /></div>
        <div><h1 class="page-title">Chỉnh sửa Danh sách</h1><p class="page-subtitle">{{ list.name }}</p></div>
      </div>
      <button class="btn-danger" @click="confirmDelete"><i class="pi pi-trash" /> Xóa</button>
    </div>
    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Tên danh sách <span class="req">*</span></label>
            <input v-model="form.name" class="form-input" :class="{ err: form.errors.name }" />
            <span v-if="form.errors.name" class="form-error">{{ form.errors.name }}</span>
          </div>
          <div class="form-group full">
            <label class="form-label">Mô tả</label>
            <textarea v-model="form.description" class="form-textarea" rows="3" />
          </div>
          <div class="form-group">
            <label class="form-label">Loại</label>
            <select v-model="form.type" class="form-select">
              <option v-for="t in types" :key="t" :value="t">{{ t === 'manual' ? 'Thủ công' : 'Tự động' }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="toggle-row">
              <input type="checkbox" v-model="form.is_active" class="toggle-input" />
              <span class="toggle-switch" /><span class="toggle-label">Kích hoạt</span>
            </label>
          </div>
        </div>
        <div class="form-actions">
          <Link :href="`/email-lists/${list.id}`"><button type="button" class="btn-cancel">Hủy</button></Link>
          <button type="submit" class="btn-submit" :disabled="form.processing"><i v-if="form.processing" class="pi pi-spin pi-spinner" /><i v-else class="pi pi-check" /> Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</template>
<script>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
export default {
  components: { Head, Link }, layout: Layout,
  props: { list: Object, types: Array },
  data() {
    return { form: useForm({ name: this.list.name, description: this.list.description || '', type: this.list.type, is_active: this.list.is_active ?? true }) }
  },
  methods: {
    submit() { this.form.put(`/email-lists/${this.list.id}`) },
    confirmDelete() { if (confirm('Bạn có chắc muốn xóa danh sách này?')) router.delete(`/email-lists/${this.list.id}`) },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#10b981;color:#10b981;background:#ecfdf5}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{font-size:.78rem;color:#64748b;margin:0}
.btn-danger{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .85rem;border-radius:10px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-danger:hover{background:#fee2e2;border-color:#f87171}
.form-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.5rem}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.form-group{display:flex;flex-direction:column;gap:.3rem}.form-group.full{grid-column:1/-1}.form-label{font-size:.78rem;font-weight:600;color:#374151}.req{color:#ef4444}.form-input,.form-select,.form-textarea{padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.82rem;color:#334155;transition:all .2s;background:#fafbfc;outline:none;font-family:inherit;width:100%;box-sizing:border-box}.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:#10b981;background:#fff;box-shadow:0 0 0 3px rgba(16,185,129,.1)}.err{border-color:#ef4444!important}.form-textarea{resize:vertical}.form-error{font-size:.68rem;color:#ef4444;font-weight:500}
.toggle-row{display:flex;align-items:center;gap:.6rem;cursor:pointer;padding-top:1.2rem}.toggle-input{display:none}.toggle-switch{width:36px;height:20px;border-radius:10px;background:#d1d5db;transition:all .2s;position:relative;flex-shrink:0}.toggle-switch::after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:2px;left:2px;transition:all .2s}.toggle-input:checked+.toggle-switch{background:#10b981}.toggle-input:checked+.toggle-switch::after{transform:translateX(16px)}.toggle-label{font-size:.82rem;font-weight:500;color:#475569}
.form-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #f1f5f9}.btn-cancel{padding:.55rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-cancel:hover{background:#f8fafc}.btn-submit{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-submit:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(16,185,129,.3)}.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
</style>
