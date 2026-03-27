<template>
  <div>
    <Head title="Web Forms — Free Traffic" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-file-edit" style="color:#10b981;" /> Web Forms</h1>
        <p class="page-subtitle">Thu lead từ website — Tạo form, popup, và nhúng vào bất kỳ trang web nào</p>
      </div>
      <Link href="/web-forms/create" class="btn-add"><i class="pi pi-plus" /> Tạo Form mới</Link>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-total"><i class="pi pi-file-edit" /></div><div class="stat-body"><span class="stat-val">{{ stats.total }}</span><span class="stat-lbl">Tổng forms</span></div></div>
      <div class="stat-card"><div class="stat-icon si-active"><i class="pi pi-check-circle" /></div><div class="stat-body"><span class="stat-val">{{ stats.active }}</span><span class="stat-lbl">Đang hoạt động</span></div></div>
      <div class="stat-card"><div class="stat-icon si-subs"><i class="pi pi-inbox" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_submissions }}</span><span class="stat-lbl">Tổng submissions</span></div></div>
      <div class="stat-card"><div class="stat-icon si-unread"><i class="pi pi-envelope" /></div><div class="stat-body"><span class="stat-val">{{ stats.unread }}</span><span class="stat-lbl">Chưa đọc</span></div></div>
      <div class="stat-card"><div class="stat-icon si-today"><i class="pi pi-sun" /></div><div class="stat-body"><span class="stat-val">{{ stats.today_submissions }}</span><span class="stat-lbl">Hôm nay</span></div></div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrap">
        <i class="pi pi-search" />
        <input v-model="search" type="text" placeholder="Tìm form..." @input="doSearch" />
      </div>
      <div class="filter-pills">
        <button class="pill" :class="{ active: !filterStatus }" @click="setFilter(null)">Tất cả</button>
        <button class="pill" :class="{ active: filterStatus === 'active' }" @click="setFilter('active')"><i class="pi pi-check-circle" style="color:#10b981;" /> Active</button>
        <button class="pill" :class="{ active: filterStatus === 'paused' }" @click="setFilter('paused')"><i class="pi pi-pause" style="color:#f59e0b;" /> Tạm dừng</button>
      </div>
    </div>

    <!-- Forms List -->
    <div v-if="forms.data?.length" class="forms-grid">
      <div v-for="form in forms.data" :key="form.id" class="form-card">
        <div class="card-top">
          <div class="card-icon" :class="'ci-' + form.form_type">
            <i :class="form.type_info?.icon || 'pi pi-file-edit'" />
          </div>
          <div class="card-info">
            <Link :href="`/web-forms/${form.id}/edit`" class="card-name">{{ form.name }}</Link>
            <div class="card-meta">
              <span class="type-badge">{{ form.type_info?.label || form.form_type }}</span>
              <span class="status-dot" :class="'st-' + form.status" />
              <span class="meta-text">{{ form.fields_count }} fields</span>
            </div>
          </div>
          <div class="card-status">
            <span class="status-chip" :class="'sc-' + form.status">{{ form.status === 'active' ? 'Active' : form.status === 'paused' ? 'Tạm dừng' : 'Lưu trữ' }}</span>
          </div>
        </div>

        <!-- Stats bar -->
        <div class="card-stats">
          <div class="cs-item">
            <span class="cs-val">{{ form.views_count }}</span>
            <span class="cs-lbl">Views</span>
          </div>
          <div class="cs-item">
            <span class="cs-val">{{ form.submissions_count }}</span>
            <span class="cs-lbl">Submissions</span>
          </div>
          <div class="cs-item">
            <span class="cs-val cs-rate">{{ form.conversion_rate }}%</span>
            <span class="cs-lbl">Conversion</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <Link :href="`/web-forms/${form.id}/edit`"><button class="act-btn"><i class="pi pi-pencil" /> Sửa</button></Link>
          <button class="act-btn" @click="copyEmbed(form)" title="Copy embed code"><i class="pi pi-code" /> Embed</button>
          <button class="act-btn" @click="copyLink(form)" title="Copy link"><i class="pi pi-link" /></button>
          <button class="act-btn act-del" @click="deleteForm(form.id)"><i class="pi pi-trash" /></button>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-file-edit" /></div>
      <h3>Chưa có form nào</h3>
      <p>Tạo form đầu tiên để bắt đầu thu lead từ website</p>
      <Link href="/web-forms/create" class="btn-add"><i class="pi pi-plus" /> Tạo Form mới</Link>
    </div>

    <!-- Pagination -->
    <div v-if="forms.last_page > 1" class="pagination-bar">
      <span class="pg-info">{{ forms.from }}–{{ forms.to }} / {{ forms.total }}</span>
      <div class="pg-btns">
        <button class="pg-btn" :disabled="!forms.prev_page_url" @click="goPage(forms.current_page - 1)"><i class="pi pi-chevron-left" /></button>
        <span class="pg-current">{{ forms.current_page }}/{{ forms.last_page }}</span>
        <button class="pg-btn" :disabled="!forms.next_page_url" @click="goPage(forms.current_page + 1)"><i class="pi pi-chevron-right" /></button>
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
  props: { forms: Object, stats: Object, formTypes: Object, filters: Object },
  data() {
    return {
      search: this.filters?.search || '',
      filterStatus: this.filters?.status || null,
      searchTimeout: null,
    }
  },
  methods: {
    setFilter(status) {
      this.filterStatus = status
      router.get('/web-forms', { search: this.search || undefined, status: status || undefined }, { preserveState: true, replace: true })
    },
    doSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        router.get('/web-forms', { search: this.search || undefined, status: this.filterStatus || undefined }, { preserveState: true, replace: true })
      }, 400)
    },
    copyEmbed(form) {
      const code = `<iframe src="${form.embed_url}" width="100%" height="500" frameborder="0" style="border:none;"></iframe>`
      navigator.clipboard.writeText(code)
      alert('Đã copy embed code!')
    },
    copyLink(form) {
      navigator.clipboard.writeText(form.embed_url)
      alert('Đã copy link form!')
    },
    deleteForm(id) {
      if (!confirm('Xóa form này?')) return
      router.delete(`/web-forms/${id}`)
    },
    goPage(page) {
      router.visit(`/web-forms?page=${page}`, { preserveState: true })
    },
  },
}
</script>

<style scoped>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
.page-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
.page-title i { font-size: 1.2rem; }
.page-subtitle { font-size: 0.78rem; color: #94a3b8; margin: 0.1rem 0 0; }
.btn-add { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.55rem 1.1rem; border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 0.78rem; font-weight: 700; border: none; cursor: pointer; text-decoration: none; font-family: inherit; transition: all 0.15s; }
.btn-add:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(16,185,129,0.3); }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.55rem; margin-bottom: 1rem; }
.stat-card { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.8rem; border-radius: 12px; background: white; border: 1.5px solid #f1f5f9; }
.stat-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0; }
.si-total { background: #f1f5f9; color: #64748b; }
.si-active { background: #ecfdf5; color: #10b981; }
.si-subs { background: #eef2ff; color: #6366f1; }
.si-unread { background: #fef3c7; color: #f59e0b; }
.si-today { background: #fef2f2; color: #ef4444; }
.stat-val { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: block; }
.stat-lbl { font-size: 0.6rem; color: #94a3b8; font-weight: 500; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.75rem; flex-wrap: wrap; }
.search-wrap { display: flex; align-items: center; gap: 0.3rem; padding: 0.38rem 0.6rem; border: 1.5px solid #e2e8f0; border-radius: 9px; background: white; flex: 1; max-width: 280px; }
.search-wrap i { color: #94a3b8; font-size: 0.75rem; }
.search-wrap input { border: none; outline: none; font-size: 0.78rem; color: #1e293b; width: 100%; background: transparent; font-family: inherit; }
.filter-pills { display: flex; gap: 0.25rem; }
.pill { padding: 0.28rem 0.6rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.65rem; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; transition: all 0.15s; font-family: inherit; }
.pill:hover { border-color: #10b981; }
.pill.active { background: #10b981; color: white; border-color: #10b981; }

/* Forms grid */
.forms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 0.65rem; }
.form-card { background: white; border-radius: 14px; border: 1.5px solid #f1f5f9; padding: 0.85rem; transition: all 0.15s; }
.form-card:hover { border-color: #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }

.card-top { display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.6rem; }
.card-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
.ci-inline { background: #ecfdf5; color: #10b981; }
.ci-popup { background: #eef2ff; color: #6366f1; }
.ci-slide_in { background: #fef3c7; color: #f59e0b; }
.ci-floating_bar { background: #fce7f3; color: #ec4899; }
.card-info { flex: 1; min-width: 0; }
.card-name { font-size: 0.88rem; font-weight: 700; color: #0f172a; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-name:hover { color: #10b981; }
.card-meta { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.15rem; }
.type-badge { font-size: 0.55rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px; background: #f1f5f9; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; }
.st-active { background: #10b981; }
.st-paused { background: #f59e0b; }
.st-archived { background: #94a3b8; }
.meta-text { font-size: 0.6rem; color: #94a3b8; }
.status-chip { font-size: 0.58rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 6px; }
.sc-active { background: #ecfdf5; color: #10b981; }
.sc-paused { background: #fef3c7; color: #f59e0b; }
.sc-archived { background: #f1f5f9; color: #94a3b8; }

/* Stats bar inside card */
.card-stats { display: flex; gap: 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; padding: 0.45rem 0; margin-bottom: 0.5rem; }
.cs-item { flex: 1; text-align: center; }
.cs-item + .cs-item { border-left: 1px solid #f1f5f9; }
.cs-val { font-size: 0.88rem; font-weight: 800; color: #0f172a; display: block; }
.cs-rate { color: #10b981; }
.cs-lbl { font-size: 0.5rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }

/* Card actions */
.card-actions { display: flex; gap: 0.3rem; }
.act-btn { display: flex; align-items: center; gap: 0.2rem; padding: 0.3rem 0.5rem; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; font-size: 0.62rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.12s; font-family: inherit; }
.act-btn:hover { border-color: #10b981; color: #10b981; }
.act-btn i { font-size: 0.55rem; }
.act-del:hover { border-color: #ef4444; color: #ef4444; }

/* Empty */
.empty-state { text-align: center; padding: 3rem 1rem; }
.empty-icon { width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #ecfdf5, #d1fae5); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; }
.empty-icon i { font-size: 1.3rem; color: #10b981; }
.empty-state h3 { font-size: 1rem; color: #1e293b; margin: 0 0 0.25rem; }
.empty-state p { font-size: 0.78rem; color: #94a3b8; margin: 0 0 1rem; }

/* Pagination */
.pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; }
.pg-info { font-size: 0.72rem; color: #94a3b8; }
.pg-btns { display: flex; align-items: center; gap: 0.3rem; }
.pg-btn { width: 30px; height: 30px; border-radius: 7px; border: 1.5px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; }
.pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pg-current { font-size: 0.72rem; font-weight: 700; color: #10b981; min-width: 50px; text-align: center; }

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .forms-grid { grid-template-columns: 1fr; }
}
</style>
