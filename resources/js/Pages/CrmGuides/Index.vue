<template>
  <div>
    <Head title="Hướng dẫn sử dụng CRM" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-question-circle" /></div>
        <div>
          <h1 class="page-title">Hướng dẫn sử dụng CRM</h1>
          <p class="page-subtitle">{{ total }} bài hướng dẫn — Trung tâm trợ giúp nội bộ</p>
        </div>
      </div>
      <div class="header-actions">
        <Button label="Thêm bài viết" icon="pi pi-plus" @click="openDialog()" />
      </div>
    </div>

    <!-- Search -->
    <div class="search-bar-wrap">
      <div class="search-bar">
        <i class="pi pi-search" />
        <input v-model="filterForm.search" placeholder="Tìm kiếm bài hướng dẫn..." class="search-input" />
      </div>
      <Select v-if="categories.length" v-model="filterForm.category" :options="categories" optionLabel="label" optionValue="value" placeholder="Danh mục" showClear class="filter-select" />
    </div>

    <!-- Grouped Guide Sections -->
    <div v-if="grouped.length" class="guide-sections">
      <div v-for="group in grouped" :key="group.category" class="guide-section">
        <h2 class="section-title">
          <span class="section-marker" />
          {{ group.category }}
          <span class="section-count">{{ group.items.length }}</span>
        </h2>
        <div class="guide-grid">
          <div v-for="guide in group.items" :key="guide.id" class="guide-card" @click="openReader(guide)">
            <div class="guide-card-icon">
              <i :class="guide.icon || 'pi pi-book'" />
            </div>
            <div class="guide-card-body">
              <div class="guide-card-top">
                <h3>{{ guide.title }}</h3>
                <span v-if="!guide.is_published" class="draft-badge">Nháp</span>
              </div>
              <p v-if="guide.summary" class="guide-summary">{{ guide.summary }}</p>
              <div class="guide-card-meta">
                <span><i class="pi pi-clock" /> {{ guide.updated_at }}</span>
              </div>
            </div>
            <div class="guide-card-actions" @click.stop>
              <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openDialog(guide)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteGuide(guide)" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-question-circle" /></div>
      <h3>Chưa có bài hướng dẫn</h3>
      <p>Tạo bài viết đầu tiên để giúp nhân viên sử dụng CRM hiệu quả hơn.</p>
      <Button label="Thêm bài viết" icon="pi pi-plus" class="mt-1" @click="openDialog()" />
    </div>

    <!-- ===== READER DIALOG ===== -->
    <div v-if="reader" class="dialog-overlay" @click.self="reader = null" @keydown.esc="reader = null">
      <div class="reader-card">
        <div class="reader-header">
          <div class="reader-header-left">
            <div class="reader-icon"><i :class="reader.icon || 'pi pi-book'" /></div>
            <div>
              <h3>{{ reader.title }}</h3>
              <span class="reader-cat">{{ reader.category }}</span>
            </div>
          </div>
          <button class="dialog-close" @click="reader = null"><i class="pi pi-times" /></button>
        </div>
        <div class="reader-body" v-html="formatContent(reader.content)" />
      </div>
    </div>

    <!-- ===== CREATE / EDIT DIALOG ===== -->
    <div v-if="dialog" class="dialog-overlay" @click.self="dialog = false" @keydown.esc="dialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon"><i class="pi pi-question-circle" /></div>
            <h3>{{ form.id ? 'Chỉnh sửa' : 'Thêm' }} bài hướng dẫn</h3>
          </div>
          <button class="dialog-close" @click="dialog = false"><i class="pi pi-times" /></button>
        </div>

        <form @submit.prevent="submitForm" class="dialog-body">
          <div class="form-group">
            <label>Tiêu đề <span class="req">*</span></label>
            <InputText v-model="form.title" class="w-full" placeholder="VD: Cách tạo báo giá mới" :class="{'p-invalid':form.errors?.title}" />
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Danh mục</label>
              <InputText v-model="form.category" class="w-full" placeholder="VD: Bắt đầu, Leads, Báo giá..." />
            </div>
            <div class="form-group" style="width:140px">
              <label>Icon (PrimeIcon)</label>
              <InputText v-model="form.icon" class="w-full" placeholder="pi pi-book" />
            </div>
            <div class="form-group" style="width:80px">
              <label>Thứ tự</label>
              <InputNumber v-model="form.sort_order" class="w-full" />
            </div>
          </div>

          <div class="form-group">
            <label>Tóm tắt</label>
            <Editor v-model="form.summary" editorStyle="height: 60px" class="w-full" />
          </div>

          <div class="form-group">
            <label>Nội dung</label>
            <Editor v-model="form.content" editorStyle="height: 200px" class="w-full" />
          </div>

          <div class="form-group">
            <div class="toggle-row">
              <div><label class="toggle-label">Xuất bản</label><small class="toggle-desc">Hiển thị cho tất cả người dùng</small></div>
              <InputSwitch v-model="form.is_published" />
            </div>
          </div>

          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="dialog = false" type="button" />
            <Button :label="form.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="form.processing" />
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
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import Editor from 'primevue/editor'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: { Head, Button, Select, InputText, InputNumber, InputSwitch, Editor },
  layout: Layout,
  props: { grouped: Array, allGuides: Array, filters: Object, categories: Array, total: Number },
  data() {
    return {
      dialog: false,
      reader: null,
      form: this.emptyForm(),
      filterForm: { search: this.filters.search, category: this.filters.category },
    }
  },
  mounted() {
    this._escHandler = (e) => {
      if (e.key === 'Escape') { this.dialog = false; this.reader = null }
    }
    document.addEventListener('keydown', this._escHandler)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this._escHandler)
  },
  watch: {
    filterForm: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/crm-guides', pickBy(this.filterForm), { preserveState: true })
      }, 300),
    },
  },
  methods: {
    emptyForm() {
      return this.$inertia.form({
        id: null, title: '', category: '', icon: 'pi pi-book',
        summary: '', content: '', is_published: true, sort_order: 0,
      })
    },
    openDialog(guide = null) {
      if (guide) {
        this.form = this.$inertia.form({
          id: guide.id, title: guide.title, category: guide.category || '',
          icon: guide.icon || 'pi pi-book', summary: guide.summary || '',
          content: guide.content || '', is_published: guide.is_published,
          sort_order: guide.sort_order || 0,
        })
      } else {
        this.form = this.emptyForm()
      }
      this.reader = null
      this.dialog = true
    },
    openReader(guide) {
      this.reader = guide
    },
    submitForm() {
      if (this.form.id) {
        this.form.put(`/crm-guides/${this.form.id}`, { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      } else {
        this.form.post('/crm-guides', { preserveScroll: true, onSuccess: () => { this.dialog = false } })
      }
    },
    deleteGuide(guide) {
      if (confirm(`Xóa "${guide.title}"?`)) {
        this.$inertia.delete(`/crm-guides/${guide.id}`, { preserveScroll: true })
      }
    },
    formatContent(text) {
      if (!text) return '<p class="text-muted">Chưa có nội dung.</p>'
      return text.replace(/\n/g, '<br>')
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#0ea5e9,#0284c7); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(14,165,233,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }
.header-actions { display:flex; gap:.5rem }

/* ===== Search ===== */
.search-bar-wrap { display:flex; gap:.75rem; margin-bottom:1.25rem; flex-wrap:wrap }
.search-bar { display:flex; align-items:center; flex:1; min-width:250px; border:1.5px solid #e2e8f0; border-radius:10px; background:white; overflow:hidden }
.search-bar:focus-within { border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.08) }
.search-bar i { padding:0 .65rem; color:#94a3b8; font-size:.78rem }
.search-input { flex:1; border:none; outline:none; padding:.55rem .5rem .55rem 0; font-size:.82rem; color:#1e293b; font-family:inherit }
.search-input::placeholder { color:#cbd5e1 }
.filter-select { min-width:140px; font-size:.8rem }

/* ===== Guide Sections ===== */
.guide-sections { display:flex; flex-direction:column; gap:1.5rem }
.guide-section { margin-bottom: 0 }
.section-title { font-size:.88rem; font-weight:700; color:#1e293b; margin:0 0 .65rem; display:flex; align-items:center; gap:.45rem }
.section-marker { width:4px; height:18px; border-radius:4px; background:linear-gradient(180deg,#0ea5e9,#0284c7) }
.section-count { font-size:.55rem; font-weight:700; padding:.1rem .35rem; border-radius:6px; background:#e0f2fe; color:#0284c7 }
.guide-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:.5rem }

.guide-card { display:flex; align-items:flex-start; gap:.75rem; padding:.85rem 1rem; background:white; border:1.5px solid #f1f5f9; border-radius:14px; cursor:pointer; transition:all .25s }
.guide-card:hover { border-color:#0ea5e9; box-shadow:0 4px 18px rgba(14,165,233,.06); transform:translateY(-1px) }
.guide-card-icon { width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg,#e0f2fe,#bae6fd); color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0 }
.guide-card-body { flex:1; min-width:0 }
.guide-card-top { display:flex; align-items:center; gap:.35rem }
.guide-card-top h3 { font-size:.82rem; font-weight:700; color:#1e293b; margin:0 }
.draft-badge { font-size:.48rem; font-weight:700; padding:.06rem .3rem; border-radius:4px; background:#f1f5f9; color:#94a3b8; text-transform:uppercase }
.guide-summary { font-size:.7rem; color:#64748b; margin:.15rem 0 0; display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical; overflow:hidden }
.guide-card-meta { display:flex; gap:.5rem; margin-top:.2rem }
.guide-card-meta span { font-size:.6rem; color:#94a3b8; display:flex; align-items:center; gap:.15rem }
.guide-card-meta i { font-size:.5rem }
.guide-card-actions { display:flex; gap:.125rem; flex-shrink:0 }

/* ===== Empty State ===== */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#e0f2fe,#bae6fd); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:1.5rem; color:#0284c7 }
.empty-state h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .35rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 }
.mt-1 { margin-top:.75rem }

/* ===== Reader Dialog ===== */
.reader-card { background:white; border-radius:18px; width:700px; max-width:92vw; max-height:88vh; overflow-y:auto; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.reader-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9 }
.reader-header-left { display:flex; align-items:center; gap:.65rem }
.reader-icon { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#0ea5e9,#0284c7); display:flex; align-items:center; justify-content:center; color:white; font-size:.92rem }
.reader-header h3 { font-size:1.05rem; font-weight:700; color:#1e293b; margin:0 }
.reader-cat { font-size:.68rem; color:#64748b }
.reader-body { padding:1.5rem; font-size:.85rem; color:#334155; line-height:1.7 }

/* ===== Create/Edit Dialog ===== */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:680px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.dialog-card * { box-sizing:border-box }
@keyframes slideUp { from { transform:translateY(20px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#0ea5e9,#0284c7); display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; flex-shrink:0 }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.form-row { display:flex; gap:.75rem; flex-wrap:wrap }
.form-group { margin-bottom:.85rem; min-width:0 }
.flex-1 { flex:1; min-width:120px }
.w-full { width:100% }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.form-group :deep(.p-inputtext), .form-group :deep(.p-textarea), .form-group :deep(.p-select), .form-group :deep(.p-inputnumber) { width:100% }
.req { color:#ef4444 }
.toggle-row { display:flex; justify-content:space-between; align-items:center }
.toggle-label { font-size:.82rem; font-weight:600; color:#1e293b }
.toggle-desc { display:block; font-size:.62rem; color:#94a3b8 }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0; background:white; border-radius:0 0 18px 18px }

@media (max-width:768px) {
  .page-header { flex-direction:column; align-items:flex-start }
  .search-bar-wrap { flex-direction:column }
  .search-bar { min-width:100% }
  .guide-grid { grid-template-columns:1fr }
  .form-row { flex-direction:column }
  .flex-1 { min-width:100% }
  .dialog-overlay { padding:.75rem }
  .dialog-card { max-height:calc(100vh - 1.5rem) }
}
</style>
