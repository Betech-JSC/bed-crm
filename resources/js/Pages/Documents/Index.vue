<template>
  <div>
    <Head title="Biên bản & Biểu mẫu" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-file-word" /></div>
        <div>
          <h1 class="page-title">Biên bản & Biểu mẫu</h1>
          <p class="page-subtitle">Quản lý tài liệu, mẫu biên bản, biểu mẫu nội bộ</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="stat-chips">
          <span class="stat-chip c1"><i class="pi pi-file" /> {{ stats.records }} biên bản</span>
          <span class="stat-chip c2"><i class="pi pi-copy" /> {{ stats.templates }} biểu mẫu</span>
          <span class="stat-chip c3"><i class="pi pi-check-circle" /> {{ stats.published }} xuất bản</span>
        </div>
        <Button label="Thêm mới" icon="pi pi-plus" @click="openDialog()" />
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="pi pi-search" />
        <input v-model="filterForm.search" placeholder="Tìm theo tên, danh mục..." class="search-input" />
      </div>
      <Select v-model="filterForm.type" :options="[{label:'Biên bản',value:'record'},{label:'Biểu mẫu',value:'template'}]" optionLabel="label" optionValue="value" placeholder="Loại" showClear class="filter-select" />
      <Select v-model="filterForm.status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Trạng thái" showClear class="filter-select" />
      <Select v-model="filterForm.category" :options="categories" optionLabel="label" optionValue="value" placeholder="Danh mục" showClear class="filter-select" />
    </div>

    <!-- Document Grid -->
    <div v-if="documents.data.length" class="doc-grid">
      <div v-for="doc in documents.data" :key="doc.id" class="doc-card" @click="openDialog(doc)">
        <div class="doc-icon" :class="doc.type === 'record' ? 'icon-record' : 'icon-template'">
          <i :class="doc.type === 'record' ? 'pi pi-file' : 'pi pi-copy'" />
        </div>
        <div class="doc-info">
          <div class="doc-top">
            <h3>{{ doc.title }}</h3>
            <span class="type-badge" :class="doc.type">{{ doc.type_label }}</span>
            <span class="status-dot" :class="`sd-${doc.status}`" :title="doc.status_label" />
          </div>
          <p v-if="doc.description" class="doc-desc">{{ doc.description }}</p>
          <div class="doc-meta">
            <span v-if="doc.category" class="meta-tag"><i class="pi pi-tag" /> {{ doc.category }}</span>
            <span class="meta-item"><i class="pi pi-user" /> {{ doc.creator_name }}</span>
            <span class="meta-item"><i class="pi pi-clock" /> {{ doc.updated_at }}</span>
            <span v-if="doc.version !== '1.0'" class="meta-item ver"><i class="pi pi-code" /> v{{ doc.version }}</span>
          </div>
        </div>
        <div class="doc-actions" @click.stop>
          <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openDialog(doc)" />
          <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteDoc(doc)" />
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-file-word" /></div>
      <h3>Chưa có biên bản/biểu mẫu</h3>
      <p>Tạo mẫu tài liệu đầu tiên để quản lý.</p>
      <Button label="Thêm mới" icon="pi pi-plus" class="mt-1" @click="openDialog()" />
    </div>

    <!-- ===== CREATE / EDIT DIALOG ===== -->
    <div v-if="dialog" class="dialog-overlay" @click.self="dialog = false" @keydown.esc="dialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon"><i class="pi pi-file-word" /></div>
            <h3>{{ form.id ? 'Chỉnh sửa' : 'Thêm mới' }} biên bản/biểu mẫu</h3>
          </div>
          <button class="dialog-close" @click="dialog = false"><i class="pi pi-times" /></button>
        </div>

        <form @submit.prevent="submitForm" class="dialog-body">
          <div class="form-section">
            <div class="form-group">
              <label>Tiêu đề <span class="req">*</span></label>
              <InputText v-model="form.title" class="w-full" placeholder="VD: Biên bản bàn giao phần mềm" :class="{'p-invalid':form.errors?.title}" />
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label>Loại <span class="req">*</span></label>
                <div class="type-selector">
                  <button type="button" class="type-btn" :class="{active:form.type==='record'}" @click="form.type='record'"><i class="pi pi-file" /> Biên bản</button>
                  <button type="button" class="type-btn" :class="{active:form.type==='template'}" @click="form.type='template'"><i class="pi pi-copy" /> Biểu mẫu</button>
                </div>
              </div>
              <div class="form-group flex-1">
                <label>Danh mục</label>
                <InputText v-model="form.category" class="w-full" placeholder="VD: Nghiệm thu, Bàn giao..." />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label>Trạng thái</label>
                <Select v-model="form.status" :options="statusOpts" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="form-group" style="width:100px">
                <label>Phiên bản</label>
                <InputText v-model="form.version" class="w-full" placeholder="1.0" />
              </div>
            </div>

            <div class="form-group">
              <label>Mô tả ngắn</label>
              <Editor v-model="form.description" editorStyle="height: 80px" class="w-full" />
            </div>

            <div class="form-group">
              <label>Nội dung</label>
              <Editor v-model="form.content" editorStyle="height: 200px" class="w-full" />
            </div>
          </div>

          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="dialog = false" type="button" />
            <Button :label="form.id ? 'Cập nhật' : 'Tạo mới'" icon="pi pi-check" type="submit" :loading="form.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Editor from 'primevue/editor'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: { Head, Button, Select, InputText, Editor },
  layout: Layout,
  props: { documents: Object, filters: Object, stats: Object, categories: Array },
  data() {
    return {
      dialog: false,
      form: this.emptyForm(),
      filterForm: {
        search: this.filters.search, type: this.filters.type,
        status: this.filters.status, category: this.filters.category,
      },
      statusOpts: [
        { label: 'Nháp', value: 'draft' },
        { label: 'Đã xuất bản', value: 'published' },
        { label: 'Lưu trữ', value: 'archived' },
      ],
    }
  },
  mounted() {
    this._escHandler = (e) => { if (e.key === 'Escape') { this.dialog = false } }
    document.addEventListener('keydown', this._escHandler)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this._escHandler)
  },
  watch: {
    filterForm: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/documents', pickBy(this.filterForm), { preserveState: true })
      }, 300),
    },
  },
  methods: {
    emptyForm() {
      return this.$inertia.form({
        id: null, title: '', type: 'template', category: '',
        description: '', content: '', status: 'draft', version: '1.0', tags: [],
      })
    },
    openDialog(doc = null) {
      if (doc) {
        this.form = this.$inertia.form({
          id: doc.id, title: doc.title, type: doc.type, category: doc.category || '',
          description: doc.description || '', content: doc.content || '',
          status: doc.status, version: doc.version || '1.0', tags: doc.tags || [],
        })
      } else {
        this.form = this.emptyForm()
      }
      this.dialog = true
    },
    submitForm() {
      if (this.form.id) {
        this.form.put(`/documents/${this.form.id}`, { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      } else {
        this.form.post('/documents', { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      }
    },
    deleteDoc(doc) {
      if (confirm(`Xóa "${doc.title}"?`)) {
        this.$inertia.delete(`/documents/${doc.id}`, { preserveScroll: true })
      }
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#ec4899,#db2777); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(236,72,153,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }
.header-actions { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap }
.stat-chips { display:flex; gap:.4rem }
.stat-chip { display:flex; align-items:center; gap:.3rem; padding:.3rem .65rem; border-radius:20px; font-size:.65rem; font-weight:600 }
.stat-chip i { font-size:.58rem }
.c1 { background:#fdf2f8; color:#db2777 } .c2 { background:#eef2ff; color:#6366f1 } .c3 { background:#ecfdf5; color:#059669 }

/* ===== Filter Bar ===== */
.filter-bar { display:flex; align-items:center; gap:.75rem; padding:.75rem 1rem; background:white; border:1.5px solid #e2e8f0; border-radius:14px; margin-bottom:1.25rem; flex-wrap:wrap }
.search-box { display:flex; align-items:center; flex:1; min-width:200px; border:1.5px solid #e2e8f0; border-radius:10px; overflow:hidden }
.search-box:focus-within { border-color:#ec4899; box-shadow:0 0 0 3px rgba(236,72,153,.08) }
.search-box i { padding:0 .6rem; color:#94a3b8; font-size:.75rem }
.search-input { flex:1; border:none; outline:none; padding:.5rem .5rem .5rem 0; font-size:.8rem; color:#1e293b; font-family:inherit }
.search-input::placeholder { color:#cbd5e1 }
.filter-select { min-width:120px; font-size:.8rem }

/* ===== Document Grid ===== */
.doc-grid { display:flex; flex-direction:column; gap:.5rem }
.doc-card { display:flex; align-items:center; gap:.85rem; padding:.85rem 1.15rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; cursor:pointer; transition:all .25s }
.doc-card:hover { border-color:#ec4899; box-shadow:0 4px 18px rgba(236,72,153,.06); transform:translateX(2px) }
.doc-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0 }
.icon-record { background:linear-gradient(135deg,#fdf2f8,#fce7f3); color:#db2777 }
.icon-template { background:linear-gradient(135deg,#e0e7ff,#eef2ff); color:#6366f1 }
.doc-info { flex:1; min-width:0 }
.doc-top { display:flex; align-items:center; gap:.4rem }
.doc-top h3 { font-size:.85rem; font-weight:700; color:#1e293b; margin:0 }
.type-badge { font-size:.5rem; font-weight:700; padding:.08rem .3rem; border-radius:4px; text-transform:uppercase }
.type-badge.record { background:#fdf2f8; color:#db2777 }
.type-badge.template { background:#eef2ff; color:#6366f1 }
.status-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0 }
.sd-draft { background:#94a3b8 } .sd-published { background:#059669 } .sd-archived { background:#d97706 }
.doc-desc { font-size:.72rem; color:#64748b; margin:.15rem 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.doc-meta { display:flex; gap:.6rem; margin-top:.15rem; flex-wrap:wrap }
.meta-tag { font-size:.58rem; font-weight:600; padding:.08rem .3rem; border-radius:4px; background:#f1f5f9; color:#64748b; display:flex; align-items:center; gap:.15rem }
.meta-tag i { font-size:.5rem }
.meta-item { font-size:.62rem; color:#94a3b8; display:flex; align-items:center; gap:.15rem }
.meta-item i { font-size:.52rem }
.meta-item.ver { font-family:monospace; font-weight:600 }
.doc-actions { display:flex; gap:.125rem; flex-shrink:0 }

/* ===== Empty State ===== */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#fdf2f8,#fce7f3); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:1.5rem; color:#db2777 }
.empty-state h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .35rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 }
.mt-1 { margin-top:.75rem }

/* ===== Dialog (Popup) ===== */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:680px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.dialog-card * { box-sizing:border-box }
@keyframes slideUp { from { transform:translateY(20px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#ec4899,#db2777); display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; flex-shrink:0 }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.form-section { display:flex; flex-direction:column; gap:0 }
.form-row { display:flex; gap:.75rem; flex-wrap:wrap }
.form-group { margin-bottom:.85rem; min-width:0 }
.flex-1 { flex:1; min-width:120px }
.w-full { width:100% }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.form-group :deep(.p-inputtext), .form-group :deep(.p-textarea), .form-group :deep(.p-select) { width:100% }
.req { color:#ef4444 }
.type-selector { display:flex; gap:.35rem }
.type-btn { display:flex; align-items:center; gap:.3rem; padding:.4rem .65rem; border-radius:8px; border:1.5px solid #e2e8f0; background:white; font-size:.72rem; font-weight:600; color:#64748b; cursor:pointer; font-family:inherit; transition:all .2s }
.type-btn i { font-size:.62rem }
.type-btn:hover { border-color:#ec4899; color:#ec4899 }
.type-btn.active { border-color:#ec4899; background:linear-gradient(135deg,#fdf2f8,#fce7f3); color:#be185d }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0; background:white; border-radius:0 0 18px 18px }

@media (max-width:768px) {
  .page-header { flex-direction:column; align-items:flex-start }
  .filter-bar { flex-direction:column }
  .search-box { min-width:100% }
  .doc-card { flex-wrap:wrap }
  .form-row { flex-direction:column }
  .flex-1 { min-width:100% }
  .dialog-overlay { padding:.75rem }
  .dialog-card { max-height:calc(100vh - 1.5rem) }
}
</style>
