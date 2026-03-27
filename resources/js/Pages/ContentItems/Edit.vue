<template>
  <div>
    <Head title="Chỉnh sửa Nội Dung" />
    <div class="page-header">
      <div class="header-left">
        <Link href="/content-items" class="back-btn"><i class="pi pi-arrow-left" /></Link>
        <div class="header-icon"><i class="pi pi-file-edit" /></div>
        <div><h1 class="page-title">Chỉnh sửa Nội Dung</h1><p class="page-subtitle">{{ contentItem.title || 'Untitled' }}</p></div>
      </div>
      <button class="btn-danger" @click="confirmDelete"><i class="pi pi-trash" /> Xóa</button>
    </div>
    <div class="form-card">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Tiêu đề</label>
            <input v-model="form.title" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Trạng thái</label>
            <select v-model="form.status" class="form-select">
              <option v-for="s in statuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tags</label>
            <input v-model="tagsInput" class="form-input" placeholder="tag1, tag2..." />
          </div>
          <div class="form-group full">
            <label class="form-label">Nội dung <span class="req">*</span></label>
            <textarea v-model="form.content" class="form-textarea" rows="12" :class="{ err: form.errors.content }" />
            <span v-if="form.errors.content" class="form-error">{{ form.errors.content }}</span>
          </div>
        </div>
        <div class="form-actions">
          <Link href="/content-items"><button type="button" class="btn-cancel">Hủy</button></Link>
          <button type="submit" class="btn-submit" :disabled="form.processing"><i v-if="form.processing" class="pi pi-spin pi-spinner" /><i v-else class="pi pi-check" /> Lưu</button>
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
  props: { contentItem: Object, types: Array, statuses: Array },
  data() {
    return {
      tagsInput: (this.contentItem.tags || []).join(', '),
      form: useForm({
        title: this.contentItem.title || '', content: this.contentItem.content || '',
        status: this.contentItem.status || 'draft', metadata: this.contentItem.metadata || {},
        tags: this.contentItem.tags || [],
      }),
    }
  },
  methods: {
    submit() {
      this.form.tags = this.tagsInput.split(',').map(t => t.trim()).filter(Boolean)
      this.form.put(`/content-items/${this.contentItem.id}`)
    },
    confirmDelete() { if (confirm('Xóa nội dung này?')) router.delete(`/content-items/${this.contentItem.id}`) },
    statusLabel(s) { return { draft: 'Nháp', approved: 'Đã duyệt', published: 'Đã đăng', archived: 'Lưu trữ' }[s] || s },
  },
}
</script>
<style scoped>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}.header-left{display:flex;align-items:center;gap:.75rem}.back-btn{width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;transition:all .2s;font-size:.85rem}.back-btn:hover{border-color:#ec4899;color:#ec4899;background:#fdf2f8}.header-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#ec4899,#db2777);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem}.page-title{font-size:1.3rem;font-weight:800;color:#0f172a;margin:0}.page-subtitle{font-size:.78rem;color:#64748b;margin:0}
.btn-danger{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .85rem;border-radius:10px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-danger:hover{background:#fee2e2;border-color:#f87171}
.form-card{background:#fff;border-radius:16px;border:1.5px solid #e2e8f0;padding:1.5rem}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.form-group{display:flex;flex-direction:column;gap:.3rem}.form-group.full{grid-column:1/-1}.form-label{font-size:.78rem;font-weight:600;color:#374151}.req{color:#ef4444}.form-input,.form-select,.form-textarea{padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.82rem;color:#334155;transition:all .2s;background:#fafbfc;outline:none;font-family:inherit;width:100%;box-sizing:border-box}.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:#ec4899;background:#fff;box-shadow:0 0 0 3px rgba(236,72,153,.1)}.err{border-color:#ef4444!important}.form-textarea{resize:vertical;min-height:60px}.form-error{font-size:.68rem;color:#ef4444;font-weight:500}
.form-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #f1f5f9}.btn-cancel{padding:.55rem 1rem;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}.btn-cancel:hover{background:#f8fafc}.btn-submit{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#ec4899,#db2777);color:#fff;font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}.btn-submit:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(236,72,153,.3)}.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
@media(max-width:768px){.form-grid{grid-template-columns:1fr}}
</style>
